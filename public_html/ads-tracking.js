/* =====================================================================
   Riad Mylaya — Suivi des conversions (GA4 + Google Ads)
   ---------------------------------------------------------------------
   Ce script suit les clics de "réservation" (moteur freetobook), WhatsApp
   et téléphone comme conversions. Il fonctionne déjà avec Google Analytics
   (GA4 : G-WY8GJP57WY, déjà chargé sur le site).

   >>> POUR ACTIVER GOOGLE ADS : renseignez les 2 valeurs ci-dessous <<<
   Vous les obtenez dans Google Ads > Objectifs > Conversions, après avoir
   créé une action de conversion de type "Site web" (ou import GA4).

     googleAdsId     : identifiant du compte, ex. "AW-123456789"
     conversionLabel : libellé de l'action, ex. "AbC-D_efGhIjKl12"

   Tant que ces champs sont vides, seul GA4 enregistre les clics.
   ===================================================================== */
(function () {
  "use strict";

  var RM_ADS = {
    googleAdsId: "",       // ex. "AW-123456789"
    conversionLabel: ""    // ex. "AbC-D_efGhIjKl12"
  };

  function gtagReady() {
    return typeof window.gtag === "function";
  }

  // Charge la config Google Ads si un identifiant est fourni
  if (RM_ADS.googleAdsId && gtagReady()) {
    window.gtag("config", RM_ADS.googleAdsId);
  }

  // Déclenche une conversion pour un type d'action donné
  function track(action, extra) {
    extra = extra || {};
    if (!gtagReady()) return;

    // GA4 — événement standard, exploitable dans les rapports et importable
    // comme conversion (fonctionne dès maintenant)
    var ga4Event = action === "book" ? "begin_checkout" : "generate_lead";
    window.gtag("event", ga4Event, {
      method: action,
      event_category: "reservation",
      event_label: extra.label || action
    });

    // Google Ads — uniquement si configuré
    if (RM_ADS.googleAdsId && RM_ADS.conversionLabel) {
      window.gtag("event", "conversion", {
        send_to: RM_ADS.googleAdsId + "/" + RM_ADS.conversionLabel,
        value: extra.value || 0,
        currency: "EUR"
      });
    }
  }

  function bind() {
    var links = document.querySelectorAll("[data-track]");
    links.forEach(function (el) {
      el.addEventListener("click", function () {
        var action = el.getAttribute("data-track"); // book | whatsapp | call
        var label = el.getAttribute("data-track-label") || action;
        track(action, { label: label });
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bind);
  } else {
    bind();
  }

  // Exposé pour un usage manuel éventuel
  window.rmTrack = track;
})();
