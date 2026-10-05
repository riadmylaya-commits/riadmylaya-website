# Fiche de police Riad Bilkis — installation sur cPanel
# Sous-domaine : police.riadbilkis.com

Même application que `police.riadmylaya.com`, avec l'identité, l'adresse, le
téléphone et l'e-mail du Riad Bilkis. La base de données, le compte staff et le
mot de passe SMTP sont **distincts** de ceux de Mylaya.

## Étape 0 : créer le sous-domaine

1. cPanel > **Domaines** / **Sous-domaines**
2. Sous-domaine : `police` — Domaine : `riadbilkis.com`
3. Dossier racine : `police.riadbilkis.com` (valeur proposée par cPanel)

## Étape 1 : créer la base MySQL

1. cPanel > **Bases de données MySQL**
2. Base : `riadbilkis` (cPanel ajoute le préfixe du compte, ex. `riaductd_riadbilkis`)
3. Utilisateur : `bilkis` avec un mot de passe fort (ex. `riaductd_bilkis`)
4. Ajouter l'utilisateur à la base avec **ALL PRIVILEGES**

## Étape 2 : renseigner `api/config.php`

Toutes les valeurs sensibles sont des placeholders dans ce dépôt et doivent être
saisies **sur le serveur uniquement** :

```php
define('DB_NAME', 'riaductd_riadbilkis');
define('DB_USER', 'riaductd_bilkis');
define('DB_PASS', '…');                 // mot de passe MySQL créé à l'étape 1
define('JWT_SECRET', '…');              // chaîne aléatoire de 32 caractères minimum
define('GMAIL_APP_PASSWORD', '…');      // mot de passe d'application Gmail de riadbilkis@gmail.com
define('DEFAULT_ADMIN_PASS', '…');      // mot de passe du compte staff « admin »
```

`SITE_URL` est déjà réglé sur `https://police.riadbilkis.com`.

Le mot de passe d'application Gmail se crée sur
<https://myaccount.google.com/apppasswords> avec le compte `riadbilkis@gmail.com`
(la validation en deux étapes doit être active).

## Étape 3 : uploader les fichiers

Copier le contenu de `riadbilkis.com/police/` (hors `INSTRUCTIONS.md`) à la
racine de `police.riadbilkis.com/` :

```
police.riadbilkis.com/
├── .htaccess
├── index.html
├── favicon.svg
├── icons.svg
├── install.php
├── assets/
├── uploads/          (droits 755, contient son propre .htaccess)
└── api/
    ├── .htaccess
    ├── index.php  config.php  db.php  auth.php  email.php  pdf.php
    └── vendor/PHPMailer  vendor/fpdf
```

## Étape 4 : créer les tables

1. Ouvrir `https://police.riadbilkis.com/install.php`
2. En cas d'erreur, vérifier les identifiants de `api/config.php`
3. **Supprimer `install.php`** dès que l'installation est réussie

## Étape 5 : tester

1. `https://police.riadbilkis.com` doit s'afficher immédiatement (Riad Bilkis,
   117 Derb Jdid, +212 6 25 67 54 94)
2. `https://police.riadbilkis.com/staff` — connexion avec `admin` et le mot de
   passe défini dans `DEFAULT_ADMIN_PASS`
3. Envoyer une fiche de test et vérifier la réception sur `riadbilkis@gmail.com`

## URLs

| Page | URL |
|------|-----|
| Accueil / QR code | `https://police.riadbilkis.com` |
| Formulaire | bouton « Remplir la fiche » |
| Espace staff | `https://police.riadbilkis.com/staff` |
| Installation (une seule fois) | `https://police.riadbilkis.com/install.php` |

## Reconstruire le front-end

Le dossier `assets/` et `index.html` sont un build Vite de l'application
`src/` de la branche `devin/1779325520-guest-registration-platform`, avec les
libellés Bilkis (`src/i18n/translations.ts`, `index.html`,
`src/pages/StaffPage.tsx`, `src/pages/QRCodePage.tsx`).

## Dépannage

- **Erreur 500** : vérifier que `mod_rewrite` est actif
- **Page blanche** : cPanel > « Select PHP Version » → PHP 8.0+
- **E-mail non envoyé** : vérifier `GMAIL_APP_PASSWORD`
- **Erreur BDD** : vérifier base / utilisateur / mot de passe
- **Sous-domaine inaccessible** : attendre 5-10 minutes après la création
