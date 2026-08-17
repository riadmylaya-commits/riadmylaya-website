/* Géométrie de la page de signatures du PDF original (assets/document.pdf).
   Les valeurs sont en points PostScript, mesurées depuis le HAUT de la page
   (comme dans le PDF source) ; pdf-build.js les convertit vers le repère
   pdf-lib (origine en bas à gauche). */
window.DOC_LAYOUT = {
  pdfUrl: "assets/document.pdf",
  fontUrl: "fonts/Amiri-Regular.ttf",

  pageWidth: 595.32,
  pageHeight: 841.92,

  /* Index des pages du document original */
  letterPageIndex: 0,
  signaturePageIndex: 1,
  closingPageIndex: 2,

  /* Colonnes du tableau (x0 → x1, de gauche à droite dans le PDF) */
  columns: {
    signature: { x0: 56.4, x1: 212.2 },
    cin: { x0: 212.2, x1: 368.0 },
    fullName: { x0: 368.0, x1: 523.7 },
  },

  /* Lignes de données : la première commence à 177.6 et chaque ligne
     mesure ~28.35 pt jusqu'à 744.7 (20 lignes). */
  firstRowTop: 177.6,
  rowHeight: 28.355,
  rowsPerPage: 20,

  /* Marge intérieure appliquée dans chaque cellule */
  cellPadding: 2.5,

  /* Emplacement du numéro de page « الصفحة رقم : …… / … » à recouvrir */
  pageNumberBox: { x0: 392.5, x1: 441.0, top: 108.5, bottom: 122.0 },
};
