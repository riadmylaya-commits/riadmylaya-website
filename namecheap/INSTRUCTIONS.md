# Guide d'installation — Riad Mylaya sur Namecheap (cPanel)

## Étape 1 : Créer la base de données MySQL

1. Connectez-vous à votre **cPanel** (généralement : `https://votre-domaine.com/cpanel`)
2. Cliquez sur **"MySQL Databases"** (ou "Bases de données MySQL")
3. Créez une nouvelle base de données, par exemple : `riadmylaya`
   - Note : cPanel ajoutera automatiquement un préfixe (ex: `votrenom_riadmylaya`)
4. Créez un nouvel utilisateur MySQL, par exemple : `admin` avec un mot de passe fort
   - Note : le nom complet sera par exemple `votrenom_admin`
5. **Ajoutez l'utilisateur à la base de données** avec "ALL PRIVILEGES"

## Étape 2 : Configurer le fichier config.php

Ouvrez le fichier `api/config.php` et modifiez ces lignes :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votrenom_riadmylaya');  // Le nom complet avec préfixe cPanel
define('DB_USER', 'votrenom_admin');        // Le nom complet avec préfixe cPanel
define('DB_PASS', 'votre_mot_de_passe');    // Le mot de passe que vous avez créé

define('JWT_SECRET', 'une-phrase-longue-aleatoire-au-moins-32-caracteres');

define('SITE_URL', 'https://votre-domaine.com');  // Votre vrai domaine
```

Le mot de passe Gmail est déjà configuré. Ne le changez pas sauf si vous régénérez un nouveau mot de passe d'application.

## Étape 3 : Uploader les fichiers

1. Dans cPanel, ouvrez **"File Manager"** (Gestionnaire de fichiers)
2. Allez dans le dossier `public_html` (ou le dossier de votre domaine/sous-domaine)
3. **Supprimez** tout le contenu existant (ou sauvegardez-le d'abord)
4. **Uploadez** tout le contenu du dossier `namecheap/` directement dans `public_html/`

La structure finale doit être :
```
public_html/
├── .htaccess
├── index.html
├── favicon.svg
├── icons.svg
├── install.php
├── assets/
│   └── (fichiers JS/CSS)
└── api/
    ├── .htaccess
    ├── index.php
    ├── config.php
    ├── db.php
    ├── auth.php
    ├── email.php
    ├── pdf.php
    └── vendor/
        ├── PHPMailer/
        └── fpdf/
```

**Méthode rapide :** Vous pouvez aussi zipper tout le contenu du dossier `namecheap/`, uploader le ZIP via File Manager, puis l'extraire sur place.

## Étape 4 : Installer (créer les tables)

1. Ouvrez dans votre navigateur : `https://votre-domaine.com/install.php`
2. Si vous voyez "Installation réussie" → tout est bon !
3. Si vous voyez une erreur → vérifiez les identifiants dans `api/config.php`
4. **SUPPRIMEZ** le fichier `install.php` après l'installation

## Étape 5 : Tester

1. Visitez `https://votre-domaine.com` — la page d'accueil doit s'afficher
2. Cliquez sur "Espace Staff" et connectez-vous avec :
   - Utilisateur : `admin`
   - Mot de passe : `mylaya2024`
3. Remplissez un formulaire test et vérifiez que :
   - La fiche apparaît dans l'espace staff
   - Vous recevez l'email sur riadmylaya@gmail.com

## Remarques importantes

- **Pas d'écran noir** : L'application se charge instantanément car tout est sur votre serveur
- **Données permanentes** : Tout est stocké dans MySQL sur votre hébergement
- **Emails** : Envoyés via Gmail SMTP (le mot de passe d'application est déjà configuré)
- **Sécurité** : Les mots de passe sont hashés avec bcrypt
- **PHP requis** : Version 7.4+ (la plupart des hébergements Namecheap ont PHP 8.x)

## En cas de problème

- **Erreur 500** : Vérifiez que le module `mod_rewrite` est activé (il l'est par défaut sur Namecheap)
- **Page blanche** : Vérifiez la version PHP (Menu cPanel > "Select PHP Version" → choisir 8.0+)
- **Email ne part pas** : Vérifiez le mot de passe Gmail dans `api/config.php`
- **Erreur BDD** : Vérifiez le nom de base, utilisateur et mot de passe dans `api/config.php`
