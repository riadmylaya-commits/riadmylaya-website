# Guide digital Riad Bilkis

Site statique multilingue pour remplacer le livret d'accueil PDF du Riad Bilkis.

## Solution technique proposée

- **Site statique HTML/CSS/JavaScript** : aucun framework lourd, chargement très rapide, compatible cPanel, Cloudflare Pages, Netlify ou GitHub Pages.
- **Contenu éditable dans un seul fichier** : toutes les traductions et informations du guide sont dans `content/guide.json`.
- **Multilingue intégré** : français, anglais et espagnol, avec détection de la langue du navigateur et bouton de sélection.
- **Mobile-first** : navigation horizontale rapide, cartes responsive, compatible mobile, tablette et ordinateur.
- **Wi-Fi pratique** : bouton pour copier le mot de passe.
- **QR Code prêt** : déployer le site sur `https://guide.riadbilkis.com/`, puis générer un QR Code vers cette URL.
- **Confidentialité** : le site contient le mot de passe Wi-Fi, donc `robots.txt` et la balise `noindex` empêchent l'indexation par les moteurs de recherche.
- **Attention GitHub public** : si ce repo reste public, le mot de passe Wi-Fi sera visible dans l'historique GitHub. Pour garder le mot de passe dans le guide, utiliser un repo privé ou remplacer le mot de passe uniquement au moment du déploiement cPanel.

## Structure

```text
index.html              # Structure de la page
assets/styles.css       # Design responsive
assets/app.js           # Rendu du contenu + langue + copie Wi-Fi
assets/riad-courtyard.svg
content/guide.json      # Contenu éditable FR / EN / ES
manifest.webmanifest    # Installation mobile basique
robots.txt              # Désindexation
sitemap.xml             # À adapter si le domaine change
```

## Mettre à jour le guide sans modifier le code

Modifier uniquement `content/guide.json` :

- `languages.fr.sections` : contenu français
- `languages.en.sections` : contenu anglais
- `languages.es.sections` : contenu espagnol
- `wifi.password` : mot de passe Wi-Fi dans chaque langue
- `contacts` : téléphone, email, liens
- `reviews.actions[0].href` : lien d'avis client final

Après modification, lancer :

```bash
npm run check
```

## Lancer localement

```bash
npm run start
```

Puis ouvrir `http://localhost:4173`.

## Déploiement recommandé

### Option recommandée : sous-domaine `guide.riadbilkis.com`

1. Créer le sous-domaine dans cPanel ou chez le DNS provider.
2. Publier les fichiers statiques du repo dans le dossier du sous-domaine.
3. Générer un QR Code vers `https://guide.riadbilkis.com/`.

### Alternatives

- `welcome.riadbilkis.com` si vous préférez un nom plus chaleureux.
- Cloudflare Pages ou Netlify si vous souhaitez un déploiement automatique à chaque merge GitHub.

Pour cette première version, `guide.riadbilkis.com` est le meilleur choix : clair, court, facile à imprimer sur un QR Code et réutilisable pour les futures sections excursions/activités.
