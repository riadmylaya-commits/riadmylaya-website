# Déploiement et configuration de l'hébergement

Le site est un site statique. Le contenu de `public_html/` est téléversé dans le
`public_html` de l'hébergement (LiteSpeed / cPanel, Namecheap). GitHub ne
déploie rien automatiquement.

## Fichier `.htaccess`

`public_html/.htaccess` est la copie de référence du fichier utilisé en
production. Il n'est pas déployé automatiquement : après une restauration de
sauvegarde ou un changement d'hébergement, il faut le téléverser à la racine de
`public_html` comme les autres fichiers.

Il contient, entre autres :

- la redirection HTTP → HTTPS ;
- les redirections 301 des anciennes URLs `page11` vers `/contact`, `/en/contact`
  et `/es/contact` ;
- les URLs sans extension `.html` ;
- la page d'erreur 404, le cache navigateur, la compression GZIP, la variante WebP ;
- les en-têtes de sécurité, dont la Content-Security-Policy.

### Configuration requise par le widget GetYourGuide

La Content-Security-Policy autorise explicitement le domaine du widget
d'affiliation. Sans ces trois autorisations, le widget ne s'affiche pas et la
section « activités » de la page reste vide, **sans message d'erreur visible** :

| directive     | valeur à conserver                                              |
| ------------- | --------------------------------------------------------------- |
| `script-src`  | `https://widget.getyourguide.com`                               |
| `frame-src`   | `https://widget.getyourguide.com`                               |
| `connect-src` | `https://widget.getyourguide.com` et `https://api.getyourguide.com` |

L'identifiant d'affiliation (`PGMWEHF`) et la campagne (`riadmylaya`) sont dans
`public_html/gyg-affiliate.js`, et le lien de la bannière générale
(`https://gyg.me/pieHSnog`, campagne `brand-short-url`) est dans les pages
d'activités.

### Vérifier que le réglage est en place

```bash
curl -sI https://riadmylaya.com/activites-groupe | grep -i content-security-policy
```

La ligne renvoyée doit contenir `widget.getyourguide.com` trois fois. Sinon,
téléverser `public_html/.htaccess` de ce dépôt à la racine du `public_html` de
l'hébergement, puis recharger `https://riadmylaya.com/activites-groupe` : la
section « Les activités les plus appréciées à Marrakech » doit afficher de vraies
activités.

## Fichier `common-ui.js`

`public_html/common-ui.js` est chargé par **toutes** les pages du site, y compris
celles qui ne sont pas dans ce dépôt. Il injecte la barre de réservation mobile
et le bouton WhatsApp, et corrige le sélecteur de langue (voir
`fixLanguageSwitcher`). C'est donc le bon endroit pour tout correctif qui doit
s'appliquer à l'ensemble du site.

## Pages localisées

`en/activities.html` et `es/activities.html` n'ont pas de balise `<base href="/">`,
contrairement à la version française. Toute image ou ressource ajoutée à ces
pages doit donc utiliser un chemin absolu (`/assets/...`), sinon elle est
recherchée dans `/en/` ou `/es/` et renvoie 404.
