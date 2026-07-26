# riadmylaya-website
Website and SEO blog for Riad Mylaya Marrakech

## Synchronisation Wi-Fi NextWi → Mailchimp

`scripts/sync_wifi_to_mailchimp.py` récupère les invités du portail Wi-Fi NextWi
(location « Riad Mylaya ») et les ajoute/met à jour dans l'audience Mailchimp du
riad, avec le tag `Wi-Fi Riad Mylaya`, la source de collecte et la date de
dernière connexion.

```bash
pip install -r scripts/requirements.txt
export NEXTWI_EMAIL=... NEXTWI_PASSWORD=... MAILCHIMP_API_KEY=...
python3 scripts/sync_wifi_to_mailchimp.py --dry-run   # aperçu, aucun envoi
python3 scripts/sync_wifi_to_mailchimp.py             # synchronisation réelle
```

Les identifiants ne sont jamais stockés dans le dépôt : ils sont passés par
variables d'environnement (secrets).
