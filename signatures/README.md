# Page de signatures électroniques — pétition « أمردول صاغرو »

Transforme le PDF de la pétition (lettre au Gouverneur de Ouarzazate) en document
électronique : chaque personne lit la lettre sur son téléphone ou son ordinateur,
saisit **الاسم / النسب / رقم البطاقة الوطنية (CIN)** et signe au doigt.
Toutes les signatures sont ensuite regroupées dans **un seul PDF final** dont la
mise en page reste identique à l'original.

## Fonctionnement

| Fichier | Rôle |
| --- | --- |
| `index.html` | Page publique : lecture du document, formulaire + pad de signature |
| `admin.html` | Page protégée par mot de passe : liste des signatures, PDF final, export CSV |
| `api/index.php` | API JSON (enregistrement, comptage, liste, suppression) — base **SQLite**, aucune configuration MySQL |
| `assets/document.pdf` | PDF original, jamais modifié : il sert de fond au PDF final |
| `assets/layout.js` | Coordonnées exactes du tableau de la page de signatures (colonnes / 20 lignes) |
| `assets/pdf-build.js` | Construction du PDF final avec `pdf-lib` dans le navigateur |

Points importants :

- Le document original est **conservé tel quel**. Le PDF final = page 1 (lettre)
  + autant de pages de signatures que nécessaire (20 signataires par page,
  numérotées automatiquement « الصفحة رقم : X / Y ») + page de clôture.
- Le nom et le CIN sont dessinés dans un `<canvas>` par le navigateur puis
  insérés comme images : l'arabe reste correctement lié et de droite à gauche
  dans le PDF (ce que ne permet pas l'écriture de texte arabe directe).
- Chaque signataire reçoit immédiatement un lien pour télécharger une copie PDF
  contenant sa propre signature.
- Un même numéro de CIN ne peut signer qu'une seule fois (`UNIQUE_CIN`).
- Les pages sont en `noindex` : elles ne seront pas référencées par Google.

## Déploiement sur l'hébergement Namecheap (cPanel)

1. **Créer un sous-domaine** dans cPanel → *Subdomains*, par ex.
   `tawqi3` + `riadmylaya.com` → dossier `tawqi3.riadmylaya.com/`.
2. **Uploader** le contenu du dossier `signatures/` (et non le dossier lui-même)
   à la racine du sous-domaine, via *File Manager* (upload d'un ZIP puis
   *Extract* est le plus rapide).
3. **Modifier `api/config.php`** :
   - `ADMIN_PASSWORD` : mettre un mot de passe fort (obligatoire).
   - `DB_FILE` : recommandé de le placer hors du dossier public, par ex.
     `/home/UTILISATEUR_CPANEL/signatures_data/signatures.sqlite`.
4. Vérifier que PHP est en version **7.4 ou plus** (cPanel → *MultiPHP Manager*)
   et que l'extension `pdo_sqlite` est active (cPanel → *Select PHP Version*).
5. Ouvrir `https://tawqi3.riadmylaya.com/` : la lettre doit s'afficher et le
   formulaire fonctionner. L'administration est sur `/admin.html`.

Le dossier `api/data/` contient un `.htaccess` qui bloque tout accès web à la
base de données ; ne pas le supprimer.

## Test en local

```bash
cd signatures
php -S 127.0.0.1:8080
# puis ouvrir http://127.0.0.1:8080/
```

## Obtenir le PDF final

1. Ouvrir `admin.html`, saisir le mot de passe.
2. Bouton **« تحميل الوثيقة النهائية (PDF) »** : lettre + toutes les pages de
   signatures remplies + page de clôture.
3. Bouton **« صفحات التوقيعات فقط »** : uniquement les pages de signatures
   (utile pour compléter un dossier déjà déposé).
4. Bouton **« تصدير Excel/CSV »** : liste nominative (noms, CIN, téléphone, date).

## Si un jour la mise en page du PDF original change

Mettre à jour `assets/document.pdf` **et** les coordonnées dans
`assets/layout.js` (`columns`, `firstRowTop`, `rowHeight`, `rowsPerPage`).
Ces valeurs ont été mesurées directement dans le PDF fourni :
colonnes `56.4 / 212.2 / 368.0 / 523.7`, première ligne à `177.6`,
hauteur de ligne `28.355`, 20 lignes par page.
