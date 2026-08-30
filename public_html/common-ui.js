/* ==========================================================================
   Riad Mylaya - Common UI components
   --------------------------------------------------------------------------
   Features injected on every page (loaded via <script src="/common-ui.js">):
     1. Sticky mobile bottom bar with "Book Now" CTA + promo reminder
     2. Floating WhatsApp contact button
     3. Language switcher fix (see fixLanguageSwitcher)
   Multi-language (FR / EN / ES) — detection from URL path + <html lang>.
   --------------------------------------------------------------------------
   CONFIGURATION: edit the CONFIG block below to customize.
   ========================================================================== */

(function () {
  // ========== CONFIGURATION ==========
  var CONFIG = {
    BOOKING_URL: "https://portal.freetobook.com/reservations?w_id=45823&w_tkn=WyeaTPwj6MSYcDxNHIPuXgqvOYtlIS2H086gviFbewghhARIYxSGLtJxULb49",
    WHATSAPP_NUMBER: "212661351989",     // no + no spaces
    PROMO_CODE: "MYRIAD12",
    STICKY_CTA_ENABLED: true,
    // Existing sites already have #whatsapp-float on all pages.
    // We only inject our own WhatsApp button if none is found on the page.
    WHATSAPP_ENABLED: true
  };

  function hasExistingWhatsApp() {
    return !!(document.getElementById("whatsapp-float") ||
              document.querySelector("a[href*='wa.me']"));
  }
  // ====================================

  var I18N = {
    fr: {
      stickyBtn: "Réserver au meilleur prix",
      stickyHint: "−12% avec",
      waLabel: "Contact WhatsApp",
      waMsg: "Bonjour, je souhaite avoir des informations sur le Riad Mylaya."
    },
    en: {
      stickyBtn: "Book at the best price",
      stickyHint: "−12% with",
      waLabel: "WhatsApp contact",
      waMsg: "Hello, I would like information about Riad Mylaya."
    },
    es: {
      stickyBtn: "Reservar al mejor precio",
      stickyHint: "−12% con",
      waLabel: "Contacto WhatsApp",
      waMsg: "Hola, me gustaría información sobre Riad Mylaya."
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
    if (document.getElementById("rm-common-style")) return;
    var css = [
      /* --- Sticky mobile bottom bar --- */
      ".rm-sticky-cta{position:fixed;left:0;right:0;bottom:0;z-index:9990;",
      "display:none;background:rgba(255,255,255,.98);",
      "box-shadow:0 -4px 14px rgba(0,0,0,.15);",
      "padding:8px 12px 10px;border-top:1px solid #e5d4b8;}",
      ".rm-sticky-cta__inner{display:flex;align-items:center;gap:10px;max-width:640px;margin:0 auto;}",
      ".rm-sticky-cta__left{flex:1;min-width:0;line-height:1.15;text-align:left;}",
      ".rm-sticky-cta__hint{display:block;font-size:11px;color:#6b4a1b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;font-weight:600;}",
      ".rm-sticky-cta__code{display:inline-block;background:#c99752;color:#fff;padding:2px 8px;border-radius:4px;font-size:13px;font-weight:700;letter-spacing:1px;}",
      ".rm-sticky-cta__btn{flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;",
      "background:#c99752;color:#fff !important;text-decoration:none !important;font-weight:700;font-size:14px;",
      "padding:12px 16px;border-radius:6px;line-height:1.2;min-height:44px;",
      "box-shadow:0 2px 6px rgba(0,0,0,.2);white-space:nowrap;transition:background .2s,transform .1s;}",
      ".rm-sticky-cta__btn:hover{background:#b07f38;color:#fff !important;text-decoration:none !important;}",
      ".rm-sticky-cta__btn:active{transform:scale(.97);}",
      "@media (max-width:991px){.rm-sticky-cta{display:block;}",
      "body{padding-bottom:74px !important;}}",
      /* --- Floating WhatsApp button --- */
      ".rm-wa-btn{position:fixed;right:16px;bottom:16px;z-index:9985;",
      "width:56px;height:56px;border-radius:50%;background:#25d366;",
      "display:flex;align-items:center;justify-content:center;",
      "box-shadow:0 4px 14px rgba(37,211,102,.55);",
      "transition:transform .15s, box-shadow .2s;text-decoration:none !important;}",
      ".rm-wa-btn:hover{transform:scale(1.08);box-shadow:0 6px 20px rgba(37,211,102,.7);}",
      ".rm-wa-btn svg{width:30px;height:30px;fill:#fff;}",
      "@media (max-width:991px){.rm-wa-btn{bottom:84px;right:12px;width:52px;height:52px;}",
      ".rm-wa-btn svg{width:28px;height:28px;}}",
      /* Shift pre-existing #whatsapp-float up on mobile so it doesn't overlap sticky CTA */
      "@media (max-width:991px){#whatsapp-float{bottom:84px !important;}}",
      /* --- Screen-reader only helper --- */
      ".rm-sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;",
      "clip:rect(0,0,0,0);white-space:nowrap;border:0;}"
    ].join("");
    var s = document.createElement("style");
    s.id = "rm-common-style";
    s.appendChild(document.createTextNode(css));
    document.head.appendChild(s);
  }

  function injectStickyCta(lang) {
    if (!CONFIG.STICKY_CTA_ENABLED) return;
    if (document.getElementById("rm-sticky-cta")) return;
    var t = I18N[lang] || I18N.fr;
    var bar = document.createElement("div");
    bar.id = "rm-sticky-cta";
    bar.className = "rm-sticky-cta";
    bar.innerHTML =
      '<div class="rm-sticky-cta__inner">' +
        '<div class="rm-sticky-cta__left">' +
          '<span class="rm-sticky-cta__hint">' + t.stickyHint + '</span>' +
          '<span class="rm-sticky-cta__code">' + CONFIG.PROMO_CODE + '</span>' +
        '</div>' +
        '<a class="rm-sticky-cta__btn" href="' + CONFIG.BOOKING_URL +
        '" target="_blank" rel="noopener noreferrer">' + t.stickyBtn + '</a>' +
      '</div>';
    document.body.appendChild(bar);
  }

  function injectWhatsApp(lang) {
    if (!CONFIG.WHATSAPP_ENABLED) return;
    if (document.getElementById("rm-wa-btn")) return;
    if (hasExistingWhatsApp()) return;  // avoid duplicates
    var t = I18N[lang] || I18N.fr;
    var a = document.createElement("a");
    a.id = "rm-wa-btn";
    a.className = "rm-wa-btn";
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

  /* The theme opens navbar dropdowns on hover by dispatching a synthetic click
     on the toggle (assets/theme/js/script.js). Such an event is not cancelable,
     so the navbar preventDefault() is ignored and the browser follows the
     toggle's href="#". On French pages <base href="/"> makes that the home
     page, so hovering the language menu navigated away. Dropping the href
     removes the anchor's activation behaviour; data-bs-toggle still opens it. */
  function fixLanguageSwitcher() {
    var toggles = document.querySelectorAll(
      '.navbar-nav .dropdown-toggle[data-bs-toggle="dropdown"][href="#"]'
    );
    Array.prototype.forEach.call(toggles, function (el) {
      el.removeAttribute("href");
      el.setAttribute("role", "button");
      el.setAttribute("tabindex", "0");
      el.style.cursor = "pointer";
      /* Without an href the browser no longer synthesises a click on
         Enter/Space, so the dropdown needs its own key handler. */
      el.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " " || e.key === "Spacebar") {
          e.preventDefault();
          el.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));
        }
      });
    });
  }

  function boot() {
    var lang = detectLang();
    fixLanguageSwitcher();
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
