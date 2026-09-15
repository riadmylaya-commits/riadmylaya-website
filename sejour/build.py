#!/usr/bin/env python3
"""
Générateur de l'espace client « Préparer mon séjour » multi-établissements.

    python3 sejour/build.py            # génère tous les établissements
    python3 sejour/build.py riad-bilkis

Sources (à modifier UNE fois pour tous les établissements) :
  sejour/templates/*.j2          mise en page commune (page + page de remerciement)
  sejour/i18n/{fr,en,es}.json    textes communs, 3 langues
  sejour/assets/sejour.css       feuille de style commune
  public_html/rm-services-data.js tarifs communs (transfert, dîner, cuisine, excursions, spa)
  public_html/transfer-quote.js, rm-booking-engine.js, rm-antibot.js  moteurs JS communs
  public_html/rm-envoi.php       réception des formulaires (e-mail + accusé de réception)

Par établissement (nom, photos, coordonnées, horaires, tarifs spécifiques) :
  sejour/etablissements/<id>.json

Sortie : sejour/dist/<hôte>/… (fichiers prêts à téléverser via cPanel).
"""
import copy
import json
import os
import re
import shutil
import sys
from pathlib import Path
from urllib.parse import quote

from jinja2 import Environment, FileSystemLoader, StrictUndefined

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parent
PUBLIC = REPO / "public_html"
DIST = ROOT / "dist"
HUB_HOST = "monsejour-marrakech.com"
HUB_URL = os.environ.get("SEJOUR_HUB_URL", "https://" + HUB_HOST)  # ex. http://localhost:8081 pour tester
LANGS = ("fr", "en", "es")
VERSION = "1"

SHARED_JS = ["transfer-quote.js", "rm-booking-engine.js", "rm-antibot.js", "gyg-affiliate.js"]


class SafeDict(dict):
    def __missing__(self, key):
        return "{" + key + "}"


def fmt(s, **kw):
    return s.format_map(SafeDict(kw))


def deep_fmt(obj, vars_):
    if isinstance(obj, str):
        return fmt(obj, **vars_)
    if isinstance(obj, dict):
        return {k: deep_fmt(v, vars_) for k, v in obj.items()}
    if isinstance(obj, list):
        return [deep_fmt(v, vars_) for v in obj]
    return obj


def load_services():
    src = (PUBLIC / "rm-services-data.js").read_text(encoding="utf-8")
    return json.loads(src[src.index("{"): src.rindex("}") + 1])


def merge(base, override):
    """Fusion récursive ; une valeur None supprime la clé."""
    out = copy.deepcopy(base)
    for k, v in (override or {}).items():
        if v is None:
            out.pop(k, None)
        elif isinstance(v, dict) and isinstance(out.get(k), dict):
            out[k] = merge(out[k], v)
        else:
            out[k] = copy.deepcopy(v)
    return out


def wa_link(number, text):
    return "https://wa.me/%s?text=%s" % (number, quote(text, safe=""))


def tiers_text(t, tiers):
    parts, prev = [], 0
    for i, tier in enumerate(tiers):
        lo, hi, price = prev + 1, tier["max"], tier["price"]
        if i == 0:
            parts.append(fmt(t["tier_first" if hi > 1 else "tier_one"], price=price, max=hi))
        elif lo == hi:
            parts.append(fmt(t["tier_single"], price=price, max=hi))
        else:
            parts.append(fmt(t["tier_range"], price=price, min=lo, max=hi))
        prev = hi
    return ", ".join(parts)


def hour(h, lang):
    """'22:00' -> '22h00' (fr/es) ou '10 pm' (en)."""
    hh, mm = h.split(":")
    if lang == "en":
        n = int(hh)
        suffix = "am" if n < 12 or n == 24 else "pm"
        n = n % 12 or 12
        return "%d%s %s" % (n, (":" + mm) if mm != "00" else "", suffix)
    return "%dh%s" % (int(hh), mm)


def transfer_lines(t, conf, lang):
    lines = []
    airport, station = conf.get("airport"), conf.get("station")
    if airport:
        lines.append(fmt(t["rates_airport"], tiers=tiers_text(t, airport["tiers"]))
                     + ((" · " + fmt(t["rates_station"], tiers=tiers_text(t, station["tiers"]))) if station else "") + ".")
    a_s = airport and airport.get("surcharge")
    s_s = station and station.get("surcharge")
    if a_s:
        win = a_s.get("arrival") or a_s.get("departure")
        station_part = ""
        if s_s and s_s.get("departure"):
            station_part = fmt(t["surcharge_station"], s_from=hour(s_s["departure"]["from"], lang), s_to=hour(s_s["departure"]["to"], lang))
        lines.append(fmt(t["surcharge"], amount=a_s["amount"], a_from=hour(win["from"], lang), a_to=hour(win["to"], lang), station_part=station_part))
    disc = airport and airport.get("round_trip_discount")
    if disc:
        lines.append(fmt(t["discount"], amount=disc["amount"]))
    return lines


def build_context(etab, lang, i18n_raw, services_all):
    g = etab["grammar"][lang]
    hub = HUB_URL
    is_hub = etab["site"]["output_dir"].startswith(HUB_HOST)
    lib = "" if is_hub else hub  # moteurs JS servis par le hub (relatif sur le hub)

    stay = etab["stay"]
    per_lang = lambda v: v[lang] if isinstance(v, dict) else "—"  # noqa: E731  (valeurs à confirmer -> stay.pending)
    vars_ = dict(
        name=etab["name"], city=etab["city"], **g,
        address=etab["contact"]["address"],
        checkin=per_lang(stay.get("checkin")), luggage=per_lang(stay.get("luggage_from")), checkout=per_lang(stay.get("checkout")),
        breakfast=stay.get("breakfast_price") if stay.get("breakfast_price") is not None else "—", tax=per_lang(stay.get("tax")),
    )
    t = deep_fmt(i18n_raw, vars_)

    svc_conf = etab["services"]
    services = copy.deepcopy(services_all)
    if svc_conf.get("transfer", {}).get("override"):
        services["transfer"] = merge(services["transfer"], svc_conf["transfer"]["override"])
    for key in ("dinner", "cooking", "excursions", "spa"):
        if svc_conf.get(key, {}).get("override"):
            services[key] = merge(services[key], svc_conf[key]["override"])

    wa = lambda text: wa_link(etab["contact"]["whatsapp"], text)  # noqa: E731

    base = etab["site"]["base_url"]
    paths = etab["site"]["paths"]
    thanks = etab["site"]["thanks"]
    # Sur le hub, les chemins de page sont préfixés par le dossier de l'établissement.
    prefix = "/" + etab["site"]["output_dir"].split("/", 1)[1] if is_hub else ""
    page_path = prefix + paths[lang]
    urls = {l: base + paths[l] for l in LANGS}

    tr = services["transfer"]
    airport = tr.get("airport")
    station = tr.get("station")
    transfer = dict(
        price=fmt(t["transfer"]["price"], airport=airport["tiers"][0]["price"] if airport else "",
                  station=fmt(t["transfer"]["price_station"], station=station["tiers"][0]["price"]) if station else ""),
        lines=transfer_lines(t["transfer"], tr, lang),
        has_station=bool(station),
        has_discount=bool(airport and airport.get("round_trip_discount")),
        max_people=tr.get("max_people", 8),
    )

    dn = services["dinner"]
    opts = dn["options"]
    dinner = dict(
        min=min(o["price"] for o in opts),
        formulas=fmt(t["dinner"]["formulas"],
                     count=t["dinner"]["count_words"].get(str(len(opts)), str(len(opts))),
                     list=", ".join(fmt(t["dinner"]["formula_item"], label=o["name"][lang], price=o["price"]) for o in opts)),
        service=fmt(t["dinner"]["service"], **{"from": dn["slots"][0], "to": dn["slots"][-1]}),
    )
    ck = services["cooking"]
    cooking = dict(price=ck["price"], max=ck.get("max_people", 6))
    ex = services["excursions"]
    ex_min = min(min(v for v in it["tiers"].values()) for it in ex["items"] if it.get("tiers"))
    excursions = dict(min=ex_min)
    sp = services["spa"]
    spa_prices = [c["price_mad"] for cat in sp["categories"] if cat["id"] != "Extras"
                  for c in cat.get("items", []) if c.get("price_mad")]
    n_treat = sum(len(cat.get("items", [])) for cat in sp["categories"])
    rate = services["currency"]["mad_per_eur"]
    spa_min = min(spa_prices) if spa_prices else 450
    opening = [x.strip() for x in str(sp.get("opening", "10:30 - 18:30")).split("-")]
    spa = dict(mad=spa_min, eur=round(spa_min / rate), count=n_treat,
               open_from=opening[0], open_to=opening[-1])

    brand = {"whatsapp": etab["contact"]["whatsapp"], "name": etab["name"], "phone": etab["contact"]["phone_display"],
             "names": etab["grammar"]}
    if "turnstile_sitekey" in etab["site"]:
        brand["turnstile_sitekey"] = etab["site"]["turnstile_sitekey"] or ""
    brand_js = json.dumps(brand, ensure_ascii=False)

    images = {k: (v.replace("{hub}", hub if not is_hub else "") if isinstance(v, str) else v)
              for k, v in etab["images"].items()}
    hero_abs = images["hero"] if images["hero"].startswith("http") else base.split("/", 3)[0] + "//" + base.split("/", 3)[2] + images["hero"]

    return dict(
        e=etab, lang=lang, t=t, g=g, wa=wa, urls=urls, page_url=urls[lang], page_path=page_path,
        thanks_url=base + thanks[lang], thanks_path=prefix + thanks[lang], images=images, hero_abs=hero_abs,
        lib=lib, hub=hub, is_hub=is_hub, prefix=prefix, version=VERSION,
        transfer=transfer, dinner=dinner, cooking=cooking, excursions=excursions, spa=spa,
        services=svc_conf, brand_js=brand_js, langs=LANGS,
        envoi="/envoi.php" if is_hub else hub + "/envoi.php",
        services_js=prefix + "/services-data.js" if is_hub else hub + "/etablissements/%s/services-data.js" % etab["id"],
        services_data=services,
        excursions_more=(etab["site"].get("excursions_more") or {}).get(lang),
    )


def write(path, content):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")


def build_etab(etab, env, i18n, services_all):
    out = DIST / etab["site"]["output_dir"]
    page_tpl = env.get_template("page.html.j2")
    thanks_tpl = env.get_template("merci.html.j2")
    is_hub = etab["site"]["output_dir"].startswith(HUB_HOST)
    merged = None
    for lang in LANGS:
        ctx = build_context(etab, lang, i18n[lang], services_all)
        merged = ctx["services_data"]
        rel = etab["site"]["paths"][lang].lstrip("/") + ".html"
        write(out / rel, page_tpl.render(**ctx))
        write(out / (etab["site"]["thanks"][lang].lstrip("/") + ".html"), thanks_tpl.render(**ctx))
    data_js = "/* Généré par sejour/build.py — ne pas modifier à la main. */\nwindow.RM_SERVICES = " + \
        json.dumps(merged, ensure_ascii=False, indent=1) + ";\n"
    if is_hub:
        write(out / "services-data.js", data_js)
    else:
        write(DIST / HUB_HOST / "etablissements" / etab["id"] / "services-data.js", data_js)
    return out


def build_hub(etabs, env):
    hub = DIST / HUB_HOST
    (hub / "lib").mkdir(parents=True, exist_ok=True)
    for js in SHARED_JS:
        shutil.copy(PUBLIC / js, hub / "lib" / js)
    shutil.copy(ROOT / "assets" / "sejour.css", hub / "lib" / "sejour.css")
    shutil.copy(ROOT / "assets" / "sejour-page.js", hub / "lib" / "sejour-page.js")
    for d in (ROOT / "assets").iterdir():
        if d.is_dir():
            shutil.copytree(d, hub / "assets" / d.name, dirs_exist_ok=True)
    shutil.copy(PUBLIC / "rm-envoi.php", hub / "rm-envoi.php")
    for f in ("envoi.php", ".htaccess", "rm-mail-config.sample.php", "index.html"):
        shutil.copy(ROOT / "hub" / f, hub / f)
    write(hub / "etablissements.php", env.get_template("etablissements.php.j2").render(etabs=etabs, langs=LANGS, hub=HUB_URL))


def main(argv):
    env = Environment(loader=FileSystemLoader(str(ROOT / "templates")), undefined=StrictUndefined,
                      autoescape=False, trim_blocks=True, lstrip_blocks=True)
    env.filters["urlq"] = lambda s: quote(s, safe="")
    i18n = {l: json.loads((ROOT / "i18n" / (l + ".json")).read_text(encoding="utf-8")) for l in LANGS}
    services_all = load_services()
    files = sorted((ROOT / "etablissements").glob("*.json"))
    etabs = [json.loads(f.read_text(encoding="utf-8")) for f in files]
    if argv:
        etabs = [e for e in etabs if e["id"] in argv]
    for e in etabs:
        for lang in LANGS:
            assert re.match(r"^/[a-z0-9/-]+$", e["site"]["paths"][lang]), e["id"]
        out = build_etab(e, env, i18n, services_all)
        print("OK", e["id"], "->", out.relative_to(REPO))
    build_hub([json.loads(f.read_text(encoding="utf-8")) for f in files], env)
    print("OK hub ->", (DIST / HUB_HOST).relative_to(REPO))


if __name__ == "__main__":
    main(sys.argv[1:])
