# Guide d'installation — Riad Mylaya sur Namecheap (cPanel)
# Sous-domaine : police.riadmylaya.com

## Étape 0 : Créer le sous-domaine dans cPanel

1. Connectez-vous à votre **cPanel** (généralement : `https://riadmylaya.com/cpanel` ou via Namecheap)
2. Cherchez **"Subdomains"** (ou "Sous-domaines") dans cPanel
3. Créez un nouveau sous-domaine :
   - **Sous-domaine :** `police`
   - **Domaine :** `riadmylaya.com`
   - **Dossier racine :** cPanel remplira automatiquement `police.riadmylaya.com` (laissez tel quel)
4. Cliquez **"Create"** / **"Créer"**

> **Important :** Le dossier créé sera par exemple `public_html/police.riadmylaya.com/` ou `police.riadmylaya.com/`.
> Notez bien le chemin — c'est là que vous uploaderez les fichiers à l'étape 3.

## Étape 1 : Créer la base de données MySQL

1. Dans cPanel, cliquez sur **"MySQL Databases"** (ou "Bases de données MySQL")
2. Créez une nouvelle base de données, par exemple : `riadmylaya`
   - Note : cPanel ajoutera automatiquement un préfixe (ex: `votrenom_riadmylaya`)
3. Créez un nouvel utilisateur MySQL, par exemple : `admin` avec un mot de passe fort
   - Note : le nom complet sera par exemple `votrenom_admin`
4. **Ajoutez l'utilisateur à la base de données** avec "ALL PRIVILEGES"

## Étape 2 : Configurer le fichier config.php

Ouvrez le fichier `api/config.php` et modifiez ces lignes :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'votrenom_riadmylaya');  // Le nom complet avec préfixe cPanel
define('DB_USER', 'votrenom_admin');        // Le nom complet avec préfixe cPanel
define('DB_PASS', 'votre_mot_de_passe');    // Le mot de passe que vous avez créé

define('JWT_SECRET', 'une-phrase-longue-aleatoire-au-moins-32-caracteres');
```

> **SITE_URL** est déjà configuré sur `https://police.riadmylaya.com` — pas besoin de le changer.

Le mot de passe Gmail est déjà configuré. Ne le changez pas sauf si vous régénérez un nouveau mot de passe d'application.

## Étape 3 : Uploader les fichiers

1. Dans cPanel, ouvrez **"File Manager"** (Gestionnaire de fichiers)
2. Allez dans le dossier du sous-domaine : **`police.riadmylaya.com/`**
   (Ce dossier a été créé automatiquement à l'étape 0)
3. **Uploadez** le fichier ZIP `riadmylaya-namecheap.zip` dans ce dossier
4. **Clic droit** sur le ZIP → **"Extract"** (Extraire)
5. Les fichiers seront extraits dans un sous-dossier `namecheap/`.
   **Déplacez tout le contenu** de `namecheap/` vers la racine du dossier `police.riadmylaya.com/`

La structure finale doit être :
```
police.riadmylaya.com/
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

> ⚠️ **Important :** Vos fichiers doivent être directement dans `police.riadmylaya.com/`, PAS dans `police.riadmylaya.com/namecheap/`. Le fichier `index.html` doit être à la racine.

## Étape 4 : Installer (créer les tables)

1. Ouvrez dans votre navigateur : `https://police.riadmylaya.com/install.php`
2. Si vous voyez **"Installation réussie"** → tout est bon !
3. Si vous voyez une erreur → vérifiez les identifiants dans `api/config.php`
4. **SUPPRIMEZ** le fichier `install.php` après l'installation (pour la sécurité)

## Étape 5 : Tester

1. Visitez `https://police.riadmylaya.com` — la page d'accueil doit s'afficher **instantanément**
2. Cliquez sur **"Espace Staff"** et connectez-vous avec :
   - Utilisateur : `admin`
   - Mot de passe : `mylaya2024`
3. Remplissez un formulaire test et vérifiez que :
   - La fiche apparaît dans l'espace staff
   - Vous recevez l'email sur riadmylaya@gmail.com

## Résumé des URLs

| Page | URL |
|------|-----|
| Page d'accueil | `https://police.riadmylaya.com` |
| Formulaire | `https://police.riadmylaya.com` → bouton "Remplir la fiche" |
| Espace staff | `https://police.riadmylaya.com/staff` |
| Installation (1 seule fois) | `https://police.riadmylaya.com/install.php` |

## Remarques importantes

- **Pas d'écran noir** : L'application se charge instantanément car tout est sur votre serveur
- **Données permanentes** : Tout est stocké dans MySQL sur votre hébergement
- **Site principal intact** : `www.riadmylaya.com` n'est pas affecté
- **Emails** : Envoyés via Gmail SMTP (le mot de passe d'application est déjà configuré)
- **Sécurité** : Les mots de passe sont hashés avec bcrypt
- **PHP requis** : Version 7.4+ (la plupart des hébergements Namecheap ont PHP 8.x)

## En cas de problème

- **Erreur 500** : Vérifiez que le module `mod_rewrite` est activé (il l'est par défaut sur Namecheap)
- **Page blanche** : Vérifiez la version PHP (Menu cPanel > "Select PHP Version" → choisir 8.0+)
- **Email ne part pas** : Vérifiez le mot de passe Gmail dans `api/config.php`
- **Erreur BDD** : Vérifiez le nom de base, utilisateur et mot de passe dans `api/config.php`
- **Sous-domaine ne fonctionne pas** : Attendez 5-10 minutes après la création (propagation DNS)
