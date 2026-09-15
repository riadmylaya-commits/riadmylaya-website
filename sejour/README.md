# Espace client « Préparer mon séjour » — multi-établissements

Un seul moteur, quatre établissements (Riad Mylaya, Riad Bilkis, Dar Le Fennec,
Dardabakech), trois langues. Tout ce qui est commun se modifie **une seule fois** ;
tout ce qui est propre à un établissement tient dans **un fichier JSON**.

```
sejour/
├── etablissements/*.json   ← 1 fichier par établissement (nom, photos, contact, prix, services)
├── i18n/{fr,en,es}.json    ← tous les textes, avec {name} {city} {price}… remplacés au build
├── templates/              ← page, page « merci », registre PHP des établissements
├── assets/                 ← CSS commun, JS de page, photos communes + assets/<id>/hero.jpg
├── hub/                    ← fichiers du sous-domaine commun (envoi.php, .htaccess…)
├── build.py                ← génère tout dans dist/
└── dist/                   ← résultat prêt à déposer sur cPanel (généré, ne pas éditer)
```

Le moteur JavaScript (`transfer-quote.js`, `rm-booking-engine.js`, `rm-antibot.js`) et
le récepteur de formulaires (`rm-envoi.php`) restent ceux du Riad Mylaya dans
`public_html/` : ils lisent désormais `window.RM_BRAND` / `$RM_BRAND` au lieu de
valeurs en dur, donc une correction faite là profite à tous les établissements.

## Générer

```bash
python3 sejour/build.py            # tous les établissements
python3 sejour/build.py riad-bilkis
```

## Où va quoi (cPanel)

| Dossier `dist/`                          | Destination                                              |
|------------------------------------------|----------------------------------------------------------|
| `monsejour-marrakech.com/`                 | hub commun **monsejour-marrakech.com** (alias cPanel → `public_html/sejour-hub/`) |
| `riadbilkis.com/`                        | racine de **riadbilkis.com** (pages seulement)            |
| `riadmylaya.com/`                        | version générée de la page Mylaya (référence / contrôle)  |

L'ancien sous-domaine `sejour.riadmylaya.com` redirige (301) vers le hub.

Le hub héberge : la bibliothèque commune (`/lib`), les photos (`/assets`),
les pages de Dar Le Fennec et Dardabakech (`/dar-le-fennec/…`, `/dardabakech/…`) et le
point d'entrée unique des formulaires `envoi.php` (les pages Bilkis y postent aussi).

Après dépôt : copier `rm-mail-config.sample.php` en `rm-mail-config.php` et y mettre le
mot de passe SMTP (fichier jamais versionné). Ajouter `monsejour-marrakech.com` et
`riadbilkis.com` aux domaines autorisés du widget Cloudflare Turnstile, puis retirer
`"turnstile_sitekey": null` du `site` de l'établissement (tant qu'il est là, le widget
n'est pas chargé et seuls pièges, `_ts` et limite par IP protègent les formulaires).

Sur riadbilkis.com (WordPress), les pages générées vivent dans `/sejour/` et les URL
propres sont servies par des `RewriteRule` dans le bloc « Riad Bilkis » du `.htaccess`
(ex. `^preparer-mon-sejour/?$ /sejour/preparer-mon-sejour.html [L]`).

## Liens

| Établissement  | FR | EN | ES |
|----------------|----|----|----|
| Riad Bilkis    | riadbilkis.com/preparer-mon-sejour | /en/prepare-your-stay | /es/prepara-tu-estancia |
| Dar Le Fennec  | monsejour-marrakech.com/dar-le-fennec/preparer-mon-sejour | …/dar-le-fennec/en/prepare-your-stay | …/dar-le-fennec/es/prepara-tu-estancia |
| Dardabakech    | monsejour-marrakech.com/dardabakech/preparer-mon-sejour | …/dardabakech/en/prepare-your-stay | …/dardabakech/es/prepara-tu-estancia |

Le jour où Dar Le Fennec ou Dardabakech a son propre domaine : changer `site.base_url`
et `site.output_dir` dans son JSON, relancer le build — rien d'autre.

## Ajouter / modifier un établissement

1. Copier `etablissements/dardabakech.json`, changer `id`, `name`, `grammar`, `contact`,
   `images`, `theme`, `stay`, `services` (chaque service a `enabled: true/false` et ses
   tarifs qui écrasent ceux de `public_html/rm-services-data.js`).
   Tant que les horaires (arrivée, bagages, départ, petit-déjeuner) ne sont pas connus :
   `"stay": { "pending": true, ... }` affiche « communiqués dans votre confirmation » à la
   place des heures ; les services sans tarif confirmé restent `enabled: false`.
2. Déposer la photo d'accueil dans `assets/<id>/hero.jpg`.
3. `python3 sejour/build.py`, puis déposer le dossier généré.

Tester en local : `SEJOUR_HUB_URL=http://localhost:8081 python3 sejour/build.py`, puis
`php -S localhost:8081` dans `dist/monsejour-marrakech.com` (avec un routeur qui sert
`x.html` pour `/x`).
