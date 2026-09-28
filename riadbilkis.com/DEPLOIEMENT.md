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
| `public_html/sejour/` | `/home/riaductd/riadbilkis.com/sejour/` |
| `public_html/rb-request.php` | `/home/riaductd/riadbilkis.com/rb-request.php` |
| `public_html/rb-mail-config.sample.php` | à copier en `/home/riaductd/rb-mail-config.php` (hors racine web, mot de passe SMTP renseigné sur le serveur uniquement) |

PHPMailer est copié dans `/home/riaductd/riadbilkis.com/rb-lib/PHPMailer/`
(`PHPMailer.php`, `SMTP.php`, `Exception.php`), repris de la fiche de police.

## Livret d'accueil repris sur le site

Contenu du guide `guide.riadbilkis.com` (petit-déjeuner, dîner, services,
équipements, arrivée/départ, règlement, FAQ) publié en FR/EN/ES :

| URL | Fichier |
|-----|---------|
| `/informations-pratiques` | `sejour/infos-fr.html` |
| `/en/practical-information` | `sejour/infos-en.html` |
| `/es/informacion-practica` | `sejour/infos-es.html` |
| `/diner-marocain` | `sejour/diner-fr.html` |
| `/en/moroccan-dinner` | `sejour/diner-en.html` |
| `/es/cena-marroqui` | `sejour/diner-es.html` |

Les réservations de chambres restent gérées par Booking Directly. Les demandes
de dîner et de transfert passent par `rb-request.php` (SMTP Gmail
`riadbilkis@gmail.com`) : le formulaire n'affiche une confirmation que si
l'e-mail est réellement parti, sinon l'endpoint répond 502 et invite à
contacter le riad par WhatsApp.

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
