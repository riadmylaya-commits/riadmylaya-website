#!/usr/bin/env bash
# Prépare le dossier `dist/` publié sur Cloudflare Pages.
# On y copie uniquement les fichiers publics : le dossier `api/` (version PHP,
# qui contient le mot de passe d'administration) ne doit JAMAIS être publié,
# car Pages servirait ces fichiers en texte brut.
set -euo pipefail

cd "$(dirname "$0")/.."

rm -rf dist
mkdir -p dist
cp index.html admin.html dist/
cp -r assets fonts vendor dist/

echo "dist/ prêt :"
find dist -maxdepth 1 -mindepth 1 | sort
