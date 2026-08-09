# Guide d'accueil digital — Riad Mylaya

Livret d'accueil envoyé aux clients avant leur arrivée (principalement ouvert depuis WhatsApp,
donc pensé mobile d'abord).

**En ligne :** https://guide.riadmylaya.com

## Fonctionnement

Le guide est une page statique pilotée par un fichier de contenu unique :

| Fichier | Rôle |
| --- | --- |
| `index.html` | Coque de la page (hero, switch de langue, conteneurs) |
| `assets/app.js` | Moteur de rendu : lit le JSON et construit les sections, modales et formulaires |
| `assets/styles.css` | Identité visuelle Riad Mylaya (terracotta `#c0754a`, Yanone Kaffeesatz + Quicksand) |
| `content/guide.json` | **Tout le contenu**, dans chaque langue |
| `build_content.py` | Génère `content/guide.json` (source de vérité éditable) |

La langue s'initialise depuis la langue du navigateur, puis reste mémorisée (`localStorage`).

## Modifier le contenu

Éditer `build_content.py`, puis :

```bash
python3 build_content.py     # régénère content/guide.json
```

Pour **ajouter une langue** (ES, IT…) : ajouter le dictionnaire correspondant dans
`build_content.py`, l'exposer dans `{"languages": {...}}`, et ajouter le bouton
`<button type="button" data-lang="es">ES</button>` dans `index.html`.

Les identifiants de section (`id`) doivent rester identiques d'une langue à l'autre :
`build_content.py` le vérifie automatiquement.

## Types de section disponibles

`intro`, `times` (+ `transferForm` optionnel), `dinner` (menus + formulaire de réservation),
`wifi`, `map`, `rules`, `reviews`, et le type par défaut (`items` en cartes + `actions`).

Dans `menuOptions`, une entrée **sans `price`** masque le calcul de total : le guide affiche
alors le message de `summary.placeholder` au lieu d'un prix inventé.

## Formulaires

Les demandes (transfert, dîner, cours de cuisine) partent via `formsubmit.co` vers
`contact@riadmylaya.com`, avec copie à `riadmylaya@gmail.com` (constantes `FORM_ENDPOINT` et
`FORM_CC` en haut de `assets/app.js`).

> ⚠️ `formsubmit.co` exige une **activation unique** : à la première demande envoyée, un e-mail
> de confirmation est reçu et son lien doit être cliqué, sinon les demandes suivantes n'arrivent pas.

Tous les autres appels à l'action ouvrent WhatsApp (`wa.me/212661351989`) avec un message
pré-rempli propre au service concerné.

## Déploiement

Copie du dossier à la racine du sous-domaine (`/guide.riadmylaya.com/` sur le cPanel), via FTPS.
Le `.htaccess` force HTTPS et met en cache les assets (le JSON reste à 10 minutes pour que les
mises à jour de contenu soient visibles rapidement).

Certificat Let's Encrypt installé sur le sous-domaine (renouvellement à prévoir avant expiration).
