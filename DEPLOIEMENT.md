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

## Protection anti-robots des formulaires

Trois barrières, toutes appliquées dans `public_html/rm-envoi.php` avant le
moindre envoi d'e-mail :

1. **Champs pièges** `_honey` (dans le HTML) et `_url` (ajouté par
   `public_html/rm-antibot.js`). Remplis = demande ignorée silencieusement.
2. **Horodatage `_ts`** : un formulaire renvoyé en moins de
   `min_fill_seconds` secondes après l'ouverture de la page vient d'un robot.
3. **Limite par adresse IP** : `rate_per_hour` (6) et `rate_per_day` (15). Les
   compteurs sont de simples fichiers dans le dossier `rm-rate`, créé
   automatiquement **au-dessus** de `public_html` (donc inaccessible en HTTP) ;
   `rate_dir` permet d'en imposer un autre.

### Cloudflare Turnstile

- Clé **publique** : constante `SITEKEY` en haut de `public_html/rm-antibot.js`.
  Ce n'est pas un secret, elle peut être versionnée.
- Clé **secrète** : `turnstile_secret` dans `rm-mail-config.php`
  (jamais versionné). Tant qu'elle est vide, la vérification est inactive et
  seules les barrières 1 à 3 s'appliquent.
- Le widget est rendu en mode `interaction-only` : invisible pour un vrai
  client, il ne demande un geste que si Cloudflare juge la session suspecte.
- La CSP de `.htaccess` autorise `https://challenges.cloudflare.com` dans
  `script-src`, `connect-src` et `frame-src` : sans cela le widget est bloqué.
- Créer les clés : Cloudflare → *Turnstile* → *Add widget*, domaine
  `riadmylaya.com`, type *Managed*.

Un visiteur bloqué reçoit une page expliquée dans sa langue (403 ou 429) avec
les liens WhatsApp et e-mail : personne ne reste sans solution de contact.

## En-têtes de cache des pages HTML

Les pages étaient servies en `no-store`, ce qui interdit à Chrome Android de
garder la page en mémoire : après éviction d'un onglet, le retour affichait un
écran blanc le temps de tout re-télécharger. `.htaccess` utilise maintenant
`no-cache, must-revalidate` + `FileETag MTime Size` : la page est toujours
revalidée (aucun tarif obsolète) mais peut être réaffichée instantanément.

Les scripts sont mis en cache un mois ; leurs URL portent donc un
`?v=<horodatage>` qu'il faut incrémenter à chaque modification d'un `.js`,
sinon un téléphone peut garder l'ancienne version jusqu'à 30 jours.
