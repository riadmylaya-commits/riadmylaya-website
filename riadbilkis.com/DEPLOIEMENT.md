# Riad Bilkis — contenu versionné et déploiement

Le site `riadbilkis.com` est un WordPress hébergé sur le même compte cPanel que
`riadmylaya.com` (domaine addon, document root `/home/riaductd/riadbilkis.com`).
Ce dossier versionne uniquement les fichiers ajoutés par nous ; le cœur de
WordPress, le thème Astra et les plugins tiers ne sont pas dans le dépôt.

## Arborescence

| Dépôt | Destination sur le serveur |
|-------|----------------------------|
| `public_html/common-ui.js` | `/home/riaductd/riadbilkis.com/common-ui.js` |
| `public_html/gyg-affiliate.js` | `/home/riaductd/riadbilkis.com/gyg-affiliate.js` |
| `public_html/gyg/` | `/home/riaductd/riadbilkis.com/gyg/` |
| `public_html/.htaccess` | `/home/riaductd/riadbilkis.com/.htaccess` |
| `wp-content/plugins/riad-bilkis-frontend/` | `.../wp-content/plugins/riad-bilkis-frontend/` |
| `wp-content/plugins/riad-bilkis-seo/` | `.../wp-content/plugins/riad-bilkis-seo/` |
| `police/` | `/home/riaductd/police.riadbilkis.com/` (voir `police/INSTRUCTIONS.md`) |

## Pages activités GetYourGuide

Pages statiques servies en URL propres par le bloc `BEGIN Riad Bilkis` du
`.htaccess`, placé **avant** le bloc WordPress :

| URL | Fichier |
|-----|---------|
| `https://riadbilkis.com/activites-groupe` | `gyg/fr.html` |
| `https://riadbilkis.com/en/group-activities` | `gyg/en.html` |
| `https://riadbilkis.com/es/actividades` | `gyg/es.html` |

L'affiliation est configurée dans `gyg-affiliate.js` (`PARTNER_ID`, `CMP`).
Si `PARTNER_ID` est vide, les widgets sont masqués et les liens de secours
s'affichent — aucun lien non rémunéré n'est publié par erreur.

## Barre de réservation mobile et WhatsApp

`common-ui.js` est chargé sur tout le site WordPress par le plugin
`riad-bilkis-frontend`, et directement par les pages statiques. Il :

- affiche une barre collante mobile « Meilleur tarif garanti / sans commission »
  avec un CTA vers le moteur Booking Directly (masquée sur `/reservation/`) ;
- n'ajoute **pas** de second bouton WhatsApp : le plugin Click to Chat en
  fournit déjà un, `common-ui.js` le remonte simplement au-dessus de la barre
  sur mobile ;
- gère FR / EN / ES.

Le code promo est désactivé (`PROMO_CODE: ""`) tant qu'aucun code Bilkis n'a été
confirmé ; le renseigner l'affiche automatiquement dans la barre.

## Mise en ligne

1. cPanel > Gestionnaire de fichiers, ou `scp` vers le document root.
2. Remplacer `.htaccess` (le bloc WordPress et le bloc Really Simple Security
   sont conservés à l'identique dans la version du dépôt).
3. Copier les deux plugins dans `wp-content/plugins/` puis les activer dans
   **Plugins** (`Riad Bilkis Frontend`, `Riad Bilkis SEO` est déjà actif).
4. Purger le cache LiteSpeed (**LiteSpeed Cache > Toolbox > Purge All**).
5. Vérifier :
   - `https://riadbilkis.com/activites-groupe` (+ `/en/group-activities`, `/es/actividades`)
   - barre mobile visible sur `https://riadbilkis.com/` en largeur < 992 px
   - bloc « Réserver en direct » et FAQ JSON-LD sur `https://riadbilkis.com/reservation/`
   - `https://riadbilkis.com/wp-sitemap.xml` contient le sitemap
     `wp-sitemap-riad-bilkis-static-1.xml`
