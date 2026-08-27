/* Protections contre la copie du contenu de la page.
   Remarque importante : aucune page web ne peut techniquement bloquer une
   capture d'écran ou un enregistrement d'écran. Ce fichier ajoute le maximum
   de protections réalisables : pas de menu contextuel, pas de sélection ni de
   copie, pas de glisser-déposer d'image, pas d'impression, pas d'ouverture
   directe des images, filigrane et masquage de la page quand elle n'est plus
   au premier plan (limite les enregistrements en arrière-plan). */
(function () {
  "use strict";

  const isField = (el) =>
    !!el && (el.tagName === "INPUT" || el.tagName === "TEXTAREA" || el.isContentEditable);

  const block = (event) => {
    if (isField(event.target)) return;
    event.preventDefault();
  };

  document.addEventListener("contextmenu", block);
  document.addEventListener("copy", block);
  document.addEventListener("cut", block);
  document.addEventListener("dragstart", (event) => event.preventDefault());
  document.addEventListener("selectstart", block);

  document.addEventListener("keydown", (event) => {
    const key = (event.key || "").toLowerCase();
    if (event.key === "PrintScreen" || key === "printscreen") {
      event.preventDefault();
      return;
    }
    if (event.ctrlKey || event.metaKey) {
      if (["s", "p", "u"].includes(key) || (!isField(event.target) && ["c", "x", "a"].includes(key))) {
        event.preventDefault();
      }
      if (event.shiftKey && ["i", "j", "c"].includes(key)) event.preventDefault();
    }
    if (event.key === "F12") event.preventDefault();
  });

  /* Agrandissement des pages sans ouvrir l'image dans un nouvel onglet. */
  document.addEventListener("click", (event) => {
    const page = event.target.closest && event.target.closest(".doc-page");
    if (page) page.classList.toggle("zoomed");
  });

  /* Masque le document dès que la page perd le premier plan ou passe en
     arrière-plan (capture d'écran différée, partage d'écran, etc.). */
  const setHidden = (hidden) => {
    document.body.classList.toggle("screen-guard", hidden);
  };
  window.addEventListener("blur", () => setHidden(true));
  window.addEventListener("focus", () => setHidden(false));
  document.addEventListener("visibilitychange", () => setHidden(document.hidden));

  /* Empêche l'impression et l'enregistrement via la boîte d'impression. */
  window.addEventListener("beforeprint", () => setHidden(true));
  window.addEventListener("afterprint", () => setHidden(false));
})();
