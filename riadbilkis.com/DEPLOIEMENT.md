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
| `wp-content/plugins/riad-bilkis-frontend/riad-bilkis-frontend.php` | `.../wp-content/mu-plugins/riad-bilkis-frontend.php` (chargé sans activation) |
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

Le code promo `Bilkis12` (`PROMO_CODE`) est affiché dans la barre et repris dans
le bloc « Réserver en direct » et la FAQ JSON-LD des trois langues.

## Mise en ligne

1. cPanel > Gestionnaire de fichiers, ou `scp` vers le document root.
2. Remplacer `.htaccess` (le bloc WordPress et le bloc Really Simple Security
   sont conservés à l'identique dans la version du dépôt).
3. Copier `riad-bilkis-seo.php` par-dessus le plugin déjà actif, et
   `riad-bilkis-frontend.php` dans `wp-content/mu-plugins/` (les mu-plugins sont
   chargés automatiquement, aucune activation dans l'admin n'est nécessaire).
4. Purger le cache LiteSpeed (**LiteSpeed Cache > Toolbox > Purge All**).
5. Vérifier :
   - `https://riadbilkis.com/activites-groupe` (+ `/en/group-activities`, `/es/actividades`)
   - barre mobile visible sur `https://riadbilkis.com/` en largeur < 992 px
   - bloc « Réserver en direct » et FAQ JSON-LD sur `https://riadbilkis.com/reservation/`
   - `https://riadbilkis.com/wp-sitemap.xml` contient le sitemap
     `wp-sitemap-riadbilkisactivites-1.xml` (le nom du provider ne doit contenir
     que des lettres : la règle de réécriture des sitemaps WordPress refuse les
     tirets et renverrait la page d'accueil)
   - un seul `<link rel="canonical">` par page (le plugin SEO n'en émet plus,
     WordPress s'en charge)

## Fiche de police (`police.riadbilkis.com`)

Sous-domaine cPanel avec document root `/home/riaductd/police.riadbilkis.com`,
base MySQL et utilisateur dédiés, `JWT_SECRET` propre à Bilkis. `api/config.php`
n'est **jamais** versionné avec de vraies valeurs : elles sont renseignées
uniquement sur le serveur. `install.php` est exécuté une fois puis supprimé.

Le sous-domaine nécessite un enregistrement DNS `police` (type A) vers l'IP du
serveur dans la zone Namecheap de `riadbilkis.com` : la zone n'est pas gérée par
cPanel, donc la création du sous-domaine ne suffit pas. L'AutoSSL ne peut émettre
le certificat qu'après propagation.

L'envoi d'e-mails reste désactivé (`GMAIL_APP_PASSWORD` vide) tant qu'un mot de
passe d'application Gmail dédié à `riadbilkis@gmail.com` n'a pas été créé.
