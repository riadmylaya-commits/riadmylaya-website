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
| `assets/preview/page-*.png` | Images pré-rendues du PDF, affichées sur la page publique |
| `tools/build-preview.sh` | Régénère ces images si le PDF original change |
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
- La lettre est affichée sous forme d'**images pré-rendues** et non via `pdf.js` :
  le moteur de rendu de `pdf.js` casse les liaisons des lettres arabes de ce PDF
  (`Warning: TT: undefined function: 21`), ce qui rendait le texte illisible sur
  la page publique. Le PDF original reste téléchargeable tel quel.

## Anonymat vis-à-vis du site hôte (exigence du projet)

La page doit apparaître comme une **plateforme indépendante et neutre** : aucun
visiteur ne doit pouvoir la relier au site principal. Ce qui est déjà garanti par
le code :

- aucune mention du site hôte dans `index.html`, `admin.html`, le CSS ou le PDF
  final (métadonnées `Title/Author/Creator/Producer` volontairement neutres) ;
- `Referrer-Policy: no-referrer` et `X-Powered-By` supprimé (`.htaccess`) ;
- `noindex, nofollow` : la page n'apparaîtra pas dans Google ;
- aucun lien depuis le site principal vers cette page (et inversement).

Ce qui dépend du **choix de l'hébergement** (à décider) :

| Option | URL vue par les signataires | Lien possible avec le site hôte |
| --- | --- | --- |
| Sous-domaine du site existant | contient forcément le domaine hôte | ❌ visible dans l'URL |
| **Domaine neutre dédié** (ex. `tawqi3-taourirt.com`), même hébergement cPanel en *Addon Domain* | totalement neutre | ⚠️ même adresse IP : un *reverse IP lookup* peut rapprocher les deux sites ; enregistrer le domaine avec *WHOIS privacy* |
| **Hébergement séparé et gratuit** (Cloudflare Pages + Workers/D1, ou un hébergeur PHP gratuit) avec domaine neutre ou URL en `*.pages.dev` | totalement neutre | ✅ aucune IP, aucun compte ni WHOIS en commun — option la plus sûre |

Les étapes ci-dessous décrivent l'installation sur un **cPanel** (Addon Domain
neutre ou sous-domaine) ; le dossier `signatures/` fonctionne à l'identique
derrière n'importe quel hébergement PHP.

## Déploiement sur Cloudflare Pages (option retenue : hébergement séparé, URL neutre)

Le même dossier fonctionne sur Cloudflare Pages, avec les signatures stockées
dans **Cloudflare D1** (SQLite managé) au lieu du fichier SQLite local. Aucune
IP, aucun compte et aucun WHOIS en commun avec le site principal ; l'URL est de
la forme `https://<projet>.pages.dev`.

| Fichier | Rôle |
| --- | --- |
| `functions/api/[[route]].js` | Équivalent de `api/index.php` (Pages Function + D1). Répond à `/api/index.php?action=...`, donc le front-end est identique. |
| `wrangler.toml` | Nom du projet, binding D1, dossier publié (`dist`) |
| `tools/build-cloudflare.sh` | Construit `dist/` **sans** le dossier `api/` (sinon Cloudflare servirait `api/config.php` en texte brut) |

```bash
cd signatures
npx wrangler login                                  # ou CLOUDFLARE_API_TOKEN
npx wrangler d1 create signatures                   # copier le database_id dans wrangler.toml
npx wrangler pages project create tawqi3            # nom = début de l'URL *.pages.dev
bash tools/build-cloudflare.sh
npx wrangler pages deploy dist
npx wrangler pages secret put ADMIN_PASSWORD --project-name tawqi3
```

Dans le tableau de bord Pages → *Settings* → *Bindings*, ajouter le binding D1
`DB` → base `signatures` (production **et** preview). La table est créée
automatiquement au premier appel de l'API.

Test en local avec la même pile que la production :

```bash
bash tools/build-cloudflare.sh
npx wrangler pages dev dist --d1 DB --binding ADMIN_PASSWORD=testpass
```

## Déploiement sur un hébergement cPanel (variante PHP)

1. **Créer le domaine** dans cPanel → *Addon Domains* (domaine neutre dédié,
   recommandé) ou *Subdomains*, et noter le dossier créé.
2. **Uploader** le contenu du dossier `signatures/` (et non le dossier lui-même)
   à la racine de ce domaine, via *File Manager* (upload d'un ZIP puis
   *Extract* est le plus rapide).
3. **Modifier `api/config.php`** :
   - `ADMIN_PASSWORD` : mettre un mot de passe fort (obligatoire).
   - `DB_FILE` : recommandé de le placer hors du dossier public, par ex.
     `/home/UTILISATEUR_CPANEL/signatures_data/signatures.sqlite`.
4. Vérifier que PHP est en version **7.4 ou plus** (cPanel → *MultiPHP Manager*)
   et que l'extension `pdo_sqlite` est active (cPanel → *Select PHP Version*).
5. Ouvrir l'adresse du site : la lettre doit s'afficher et le formulaire
   fonctionner. L'administration est sur `/admin.html`.

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

Mettre à jour `assets/document.pdf`, relancer `tools/build-preview.sh` **et**
vérifier les coordonnées dans `assets/layout.js` (`columns`, `firstRowTop`, `rowHeight`, `rowsPerPage`).
Ces valeurs ont été mesurées directement dans le PDF fourni :
colonnes `56.4 / 212.2 / 368.0 / 523.7`, première ligne à `177.6`,
hauteur de ligne `28.355`, 20 lignes par page.
