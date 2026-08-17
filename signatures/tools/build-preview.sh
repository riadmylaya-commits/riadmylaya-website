#!/usr/bin/env bash
# Régénère les images de prévisualisation affichées sur index.html à partir de
# assets/document.pdf. À relancer si le PDF original est remplacé.
#
# Dépendances : poppler-utils (pdftoppm) et ImageMagick (convert).
#   sudo apt-get install -y poppler-utils imagemagick
#
# Le rendu arabe de pdf.js dans le navigateur casse les liaisons de lettres de
# ce PDF ; la page publique affiche donc ces images pré-rendues.
set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p assets/preview
rm -f assets/preview/page-*.png

pdftoppm -r 140 -gray -png assets/document.pdf /tmp/signatures-preview
i=1
for file in /tmp/signatures-preview-*.png; do
  convert "$file" -strip -colors 32 -define png:compression-level=9 "assets/preview/page-${i}.png"
  i=$((i + 1))
done
rm -f /tmp/signatures-preview-*.png

ls -la assets/preview
