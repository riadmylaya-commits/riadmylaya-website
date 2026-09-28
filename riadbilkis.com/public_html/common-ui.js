/* ==========================================================================
   Riad Bilkis - Common UI components
   --------------------------------------------------------------------------
   Charge sur toutes les pages du site (WordPress via le plugin
   riad-bilkis-frontend, pages statiques via <script src>).

     1. Barre mobile collante avec CTA « Réserver » + rappel meilleur tarif
     2. Bouton WhatsApp flottant (uniquement si la page n'en a pas déjà un :
        le plugin Click to Chat en fournit un sur les pages WordPress)

   Multi-langue (FR / EN / ES) - detection depuis l'URL puis <html lang>.
   --------------------------------------------------------------------------
   CONFIGURATION : editer le bloc CONFIG ci-dessous.
   ========================================================================== */

(function () {
  // ========== CONFIGURATION ==========
  var CONFIG = {
    BOOKING_URL: "https://booking-directly.com/widgets/CpCIZwUUpc4p14KAQFEzgGCPRoKW9a2R5UUDUleuJA3xBbFB9ZW7MOaFdMCwX/properties",
    WHATSAPP_NUMBER: "212625675494",     // sans + ni espaces
    PROMO_CODE: "BILKIS12",              // code promo reservation directe, "" = aucun
    STICKY_CTA_ENABLED: true,
    WHATSAPP_ENABLED: true
  };

  // Boutons WhatsApp flottants deja presents : Click to Chat (WordPress) ou
  // un bouton flottant du theme. Les liens WhatsApp dans le contenu ne comptent
  // pas : ils ne remplacent pas un bouton toujours accessible.
  function hasExistingWhatsApp() {
    return !!(document.getElementById("ht-ctc-chat") ||
              document.querySelector(".ht-ctc-chat") ||
              document.getElementById("whatsapp-float") ||
              document.querySelector(".whatsapp-float, .wa-float"));
  }
  // ====================================

  var I18N = {
    fr: {
      stickyBtn: "Réserver en direct",
      stickyHint: "Meilleur tarif garanti",
      stickyNote: "sans commission",
      waLabel: "Contact WhatsApp",
      waMsg: "Bonjour, je souhaite avoir des informations sur le Riad Bilkis."
    },
    en: {
      stickyBtn: "Book direct",
      stickyHint: "Best rate guaranteed",
      stickyNote: "no commission",
      waLabel: "WhatsApp contact",
      waMsg: "Hello, I would like information about Riad Bilkis."
    },
    es: {
      stickyBtn: "Reservar directo",
      stickyHint: "Mejor tarifa garantizada",
      stickyNote: "sin comisión",
      waLabel: "Contacto WhatsApp",
      waMsg: "Hola, me gustaría información sobre el Riad Bilkis."
    }
  };

  function detectLang() {
    var path = window.location.pathname || "";
    if (path.indexOf("/en") === 0) return "en";
    if (path.indexOf("/es") === 0) return "es";
    var htmlLang = (document.documentElement.lang || "").toLowerCase();
    if (htmlLang.indexOf("en") === 0) return "en";
    if (htmlLang.indexOf("es") === 0) return "es";
    return "fr";
  }

  function injectStyles() {
    if (document.getElementById("rb-common-style")) return;
    var css = [
      /* --- Barre mobile collante --- */
      ".rb-sticky-cta{position:fixed;left:0;right:0;bottom:0;z-index:9990;",
      "display:none;background:rgba(255,255,255,.98);",
      "box-shadow:0 -4px 14px rgba(0,0,0,.15);",
      "padding:8px 12px 10px;border-top:1px solid #e6d3c4;",
      "font-family:'Raleway',system-ui,sans-serif;}",
      ".rb-sticky-cta__inner{display:flex;align-items:center;gap:10px;max-width:640px;margin:0 auto;}",
      ".rb-sticky-cta__left{flex:1;min-width:0;line-height:1.2;text-align:left;}",
      ".rb-sticky-cta__hint{display:block;font-size:11px;color:#8a5a3c;text-transform:uppercase;",
      "letter-spacing:.5px;font-weight:700;}",
      ".rb-sticky-cta__note{display:block;font-size:11px;color:#6f6257;}",
      ".rb-sticky-cta__code{display:inline-block;background:#C88B6A;color:#fff;padding:2px 8px;",
      "border-radius:4px;font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;}",
      ".rb-sticky-cta__btn{flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;",
      "background:#C75B39;color:#fff !important;text-decoration:none !important;font-weight:700;",
      "font-size:13px;text-transform:uppercase;letter-spacing:1px;",
      "padding:12px 16px;border-radius:4px;line-height:1.2;min-height:44px;",
      "box-shadow:0 2px 6px rgba(0,0,0,.2);white-space:nowrap;transition:background .2s,transform .1s;}",
      ".rb-sticky-cta__btn:hover{background:#a8492c;color:#fff !important;text-decoration:none !important;}",
      ".rb-sticky-cta__btn:active{transform:scale(.97);}",
      "@media (max-width:991px){.rb-sticky-cta{display:block;}",
      "body.rb-has-sticky{padding-bottom:74px !important;}}",
      /* --- Bouton WhatsApp flottant --- */
      ".rb-wa-btn{position:fixed;right:16px;bottom:16px;z-index:9985;",
      "width:56px;height:56px;border-radius:50%;background:#25d366;",
      "display:flex;align-items:center;justify-content:center;",
      "box-shadow:0 4px 14px rgba(37,211,102,.55);",
      "transition:transform .15s, box-shadow .2s;text-decoration:none !important;}",
      ".rb-wa-btn:hover{transform:scale(1.08);box-shadow:0 6px 20px rgba(37,211,102,.7);}",
      ".rb-wa-btn svg{width:30px;height:30px;fill:#fff;}",
      "@media (max-width:991px){.rb-wa-btn{bottom:84px;right:12px;width:52px;height:52px;}",
      ".rb-wa-btn svg{width:28px;height:28px;}}",
      /* Remonter le bouton Click to Chat sur mobile pour ne pas chevaucher la barre */
      "@media (max-width:991px){#ht-ctc-chat{bottom:84px !important;}",
      "#whatsapp-float{bottom:84px !important;}}"
    ].join("");
    var s = document.createElement("style");
    s.id = "rb-common-style";
    s.appendChild(document.createTextNode(css));
    document.head.appendChild(s);
  }

  function isBookingPage() {
    return /\/(reservation|reservacion|booking)\/?$/.test(window.location.pathname);
  }

  function injectStickyCta(lang) {
    if (!CONFIG.STICKY_CTA_ENABLED) return;
    if (document.getElementById("rb-sticky-cta")) return;
    if (isBookingPage()) return;
    var t = I18N[lang] || I18N.fr;
    var left = CONFIG.PROMO_CODE
      ? '<span class="rb-sticky-cta__hint">' + t.stickyHint + '</span>' +
        '<span class="rb-sticky-cta__code">' + CONFIG.PROMO_CODE + '</span>'
      : '<span class="rb-sticky-cta__hint">' + t.stickyHint + '</span>' +
        '<span class="rb-sticky-cta__note">' + t.stickyNote + '</span>';
    var bar = document.createElement("div");
    bar.id = "rb-sticky-cta";
    bar.className = "rb-sticky-cta";
    bar.innerHTML =
      '<div class="rb-sticky-cta__inner">' +
        '<div class="rb-sticky-cta__left">' + left + '</div>' +
        '<a class="rb-sticky-cta__btn" href="' + CONFIG.BOOKING_URL +
        '" target="_blank" rel="noopener noreferrer">' + t.stickyBtn + '</a>' +
      '</div>';
    document.body.appendChild(bar);
    document.body.classList.add("rb-has-sticky");
  }

  function injectWhatsApp(lang) {
    if (!CONFIG.WHATSAPP_ENABLED) return;
    if (document.getElementById("rb-wa-btn")) return;
    if (hasExistingWhatsApp()) return;
    var t = I18N[lang] || I18N.fr;
    var a = document.createElement("a");
    a.id = "rb-wa-btn";
    a.className = "rb-wa-btn";
    a.href = "https://wa.me/" + CONFIG.WHATSAPP_NUMBER +
             "?text=" + encodeURIComponent(t.waMsg);
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.setAttribute("aria-label", t.waLabel);
    a.innerHTML =
      '<svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">' +
      '<path d="M16.003 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.26.6 4.46 1.74 6.4L3.2 28.8l6.58-1.72a12.76 12.76 0 006.22 1.6h.01c7.06 0 12.8-5.74 12.8-12.8s-5.74-12.68-12.81-12.68zm7.49 18.31c-.32.9-1.86 1.73-2.58 1.84-.7.1-1.6.16-5.12-1.1-3.04-1.1-5-4.04-5.16-4.26-.16-.2-1.18-1.57-1.18-3 0-1.42.75-2.13 1.02-2.42.26-.28.56-.36.75-.36.19 0 .38 0 .55.01.18.01.42-.07.65.5.25.6.86 2.07.94 2.22.08.15.13.33.03.52-.1.18-.15.3-.3.47-.15.18-.32.4-.46.53-.15.15-.31.32-.13.63.18.3.8 1.32 1.72 2.14 1.18 1.05 2.17 1.38 2.48 1.54.3.15.48.13.66-.07.18-.2.76-.88.96-1.18.2-.3.4-.25.67-.15.27.1 1.72.81 2.02.96.3.15.5.22.57.35.08.13.08.74-.24 1.64z"/>' +
      '</svg>';
    document.body.appendChild(a);
  }

  function boot() {
    var lang = detectLang();
    injectStyles();
    injectStickyCta(lang);
    injectWhatsApp(lang);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
