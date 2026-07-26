#!/usr/bin/env python3
"""Synchronise les contacts du portail Wi-Fi NextWi vers l'audience Mailchimp.

Les invites du portail Wi-Fi (dev-admin.mynextwi.com) sont recuperes puis
ajoutes/mis a jour dans l'audience Mailchimp du Riad Mylaya, avec le tag
"Wi-Fi Riad Mylaya", la source et la date de derniere connexion.

Variables d'environnement requises :
    NEXTWI_EMAIL, NEXTWI_PASSWORD, MAILCHIMP_API_KEY

Optionnelles :
    NEXTWI_BASE_URL   (defaut : https://dev-admin.mynextwi.com)
    NEXTWI_LOCATION   (defaut : Riad Mylaya)
    MAILCHIMP_LIST_ID (defaut : 867269a555)
    MAILCHIMP_TAG     (defaut : Wi-Fi Riad Mylaya)
    SYNC_DRY_RUN      (1 pour ne rien envoyer a Mailchimp)
"""

from __future__ import annotations

import argparse
import datetime
import os
import re
import sys

import requests

NEXTWI_BASE_URL = os.environ.get("NEXTWI_BASE_URL", "https://dev-admin.mynextwi.com")
NEXTWI_LOCATION = os.environ.get("NEXTWI_LOCATION", "Riad Mylaya")
MAILCHIMP_LIST_ID = os.environ.get("MAILCHIMP_LIST_ID", "867269a555")
MAILCHIMP_TAG = os.environ.get("MAILCHIMP_TAG", "Wi-Fi Riad Mylaya")

EMAIL_RE = re.compile(r"^[\w.+-]+@[\w-]+\.[\w.-]+$")
DATE_RE = re.compile(r"([A-Z][a-z]{2} \d{2}, \d{4})")


def login(session: requests.Session, email: str, password: str) -> None:
    page = session.get(f"{NEXTWI_BASE_URL}/login", timeout=60)
    page.raise_for_status()
    match = re.search(r'name="?_token"?\s+value="?([^"\s>]+)', page.text) or re.search(
        r'name="?csrf-token"?\s+content="?([^"\s>]+)', page.text)
    if not match:
        raise RuntimeError("Jeton CSRF introuvable sur la page de connexion NextWi")
    response = session.post(
        f"{NEXTWI_BASE_URL}/login",
        data={"_token": match.group(1), "email": email, "password": password},
        timeout=60,
    )
    response.raise_for_status()
    if response.url.endswith("/login"):
        raise RuntimeError("Connexion NextWi refusee (identifiants invalides ?)")


def _last_seen_date(guest: dict) -> str | None:
    """Date de derniere connexion au format ISO (colonne HTML rendue par NextWi)."""
    match = DATE_RE.search(guest.get("last_seen") or "")
    if match:
        return datetime.datetime.strptime(match.group(1), "%b %d, %Y").date().isoformat()
    if guest.get("first_seen"):
        return guest["first_seen"][:10]
    return None


def fetch_guests(session: requests.Session) -> dict[str, dict]:
    """Retourne {email: {"last_seen": ISO, "first_name": str, "last_name": str}}."""
    response = session.get(
        f"{NEXTWI_BASE_URL}/admin/guests",
        params={"draw": 1, "start": 0, "length": 5000},
        headers={"X-Requested-With": "XMLHttpRequest", "Accept": "application/json"},
        timeout=120,
    )
    response.raise_for_status()
    guests: dict[str, dict] = {}
    for guest in response.json().get("data", []):
        email = (guest.get("email") or "").strip().lower()
        if not EMAIL_RE.match(email):
            continue
        if NEXTWI_LOCATION and NEXTWI_LOCATION not in (guest.get("location") or ""):
            continue
        last_seen = _last_seen_date(guest)
        if not last_seen:
            continue
        known = guests.get(email)
        if known and known["last_seen"] >= last_seen:
            continue
        guests[email] = {
            "last_seen": last_seen,
            "first_name": (guest.get("first_name") or "").strip(),
            "last_name": (guest.get("last_name") or "").strip(),
        }
    return guests


def push_to_mailchimp(api_key: str, guests: dict[str, dict]) -> dict:
    datacenter = api_key.rsplit("-", 1)[-1]
    url = f"https://{datacenter}.api.mailchimp.com/3.0/lists/{MAILCHIMP_LIST_ID}"
    members = []
    for email, guest in sorted(guests.items()):
        merge_fields = {"SOURCE": "Wi-Fi NextWi", "DEPART": guest["last_seen"]}
        if guest["first_name"]:
            merge_fields["FNAME"] = guest["first_name"]
        if guest["last_name"]:
            merge_fields["LNAME"] = guest["last_name"]
        members.append({
            "email_address": email,
            "status": "subscribed",
            "email_type": "html",
            "merge_fields": merge_fields,
            "tags": [MAILCHIMP_TAG],
        })
    response = requests.post(
        url,
        auth=("devin", api_key),
        json={"members": members, "update_existing": True, "sync_tags": False},
        timeout=600,
    )
    response.raise_for_status()
    return response.json()


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--dry-run", action="store_true",
                        help="n'envoie rien a Mailchimp, affiche seulement le resultat")
    args = parser.parse_args()
    dry_run = args.dry_run or os.environ.get("SYNC_DRY_RUN") == "1"

    missing = [name for name in ("NEXTWI_EMAIL", "NEXTWI_PASSWORD") if not os.environ.get(name)]
    if not dry_run and not os.environ.get("MAILCHIMP_API_KEY"):
        missing.append("MAILCHIMP_API_KEY")
    if missing:
        print(f"Variables d'environnement manquantes : {', '.join(missing)}", file=sys.stderr)
        return 2

    session = requests.Session()
    session.headers["User-Agent"] = "riadmylaya-wifi-sync"
    login(session, os.environ["NEXTWI_EMAIL"], os.environ["NEXTWI_PASSWORD"])
    guests = fetch_guests(session)
    print(f"{len(guests)} contacts Wi-Fi recuperes depuis NextWi")
    if not guests:
        print("Aucun contact trouve : la page des invites a peut-etre change de structure",
              file=sys.stderr)
        return 1
    if dry_run:
        for email, guest in sorted(guests.items()):
            print(f"  {guest['last_seen']}  {email}")
        return 0

    result = push_to_mailchimp(os.environ["MAILCHIMP_API_KEY"], guests)
    print(f"Mailchimp : {result['total_created']} ajoutes, "
          f"{result['total_updated']} mis a jour, {result['error_count']} en erreur")
    for error in result.get("errors", []):
        print(f"  ! {error['email_address']} : {error['error']}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
