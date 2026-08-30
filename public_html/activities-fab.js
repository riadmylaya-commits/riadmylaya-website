/* ==========================================================================
   Riad Mylaya - Floating Activities Button (FAB)
   --------------------------------------------------------------------------
   Injects a floating button on every page that expands to show
   links to all activities/services.
   Multi-language (FR / EN / ES).
   ========================================================================== */

(function () {
  var I18N = {
    fr: {
      label: "Activit\u00e9s",
      items: [
        { title: "D\u00eener traditionnel", icon: "\ud83c\udf7d\ufe0f", href: "/page19" },
        { title: "D\u00e9couverte Marrakech", icon: "\ud83d\udd4c", href: "/page14" },
        { title: "Hammam & Massage", icon: "\ud83d\udec1", href: "/hammam-spa" },
        { title: "Cours de cuisine", icon: "\ud83d\udc68\u200d\ud83c\udf73", href: "/page16" },
        { title: "Excursions", icon: "\u26f0\ufe0f", href: "/excursions" },
        { title: "Autres plaisirs", icon: "\u2728", href: "/page18" },
        { title: "Photographe & Vid\u00e9o", icon: "\ud83d\udcf8", href: "/photographe" },
        { title: "Activit\u00e9s en priv\u00e9 ou en groupe", icon: "\ud83d\udc65", href: "/activites-groupe" }
      ]
    },
    en: {
      label: "Activities",
      items: [
        { title: "Traditional dinner", icon: "\ud83c\udf7d\ufe0f", href: "/en/page9" },
        { title: "Discover Marrakech", icon: "\ud83d\udd4c", href: "/en/page10" },
        { title: "Hammam & Massage", icon: "\ud83d\udec1", href: "/en/hammam-spa" },
        { title: "Cooking class", icon: "\ud83d\udc68\u200d\ud83c\udf73", href: "/en/page13" },
        { title: "Excursions", icon: "\u26f0\ufe0f", href: "/en/page14" },
        { title: "Other activities", icon: "\u2728", href: "/en/page15" },
        { title: "Photographer & Video", icon: "\ud83d\udcf8", href: "/en/photographe" },
        { title: "Private or group activities", icon: "\ud83d\udc65", href: "/en/group-activities" }
      ]
    },
    es: {
      label: "Actividades",
      items: [
        { title: "Cena tradicional", icon: "\ud83c\udf7d\ufe0f", href: "/es/page9" },
        { title: "Descubrir Marrakech", icon: "\ud83d\udd4c", href: "/es/page10" },
        { title: "Hammam y Masaje", icon: "\ud83d\udec1", href: "/es/hammam-spa" },
        { title: "Clase de cocina", icon: "\ud83d\udc68\u200d\ud83c\udf73", href: "/es/page13" },
        { title: "Excursiones", icon: "\u26f0\ufe0f", href: "/es/page14" },
        { title: "Otras actividades", icon: "\u2728", href: "/es/page15" },
        { title: "Fotograf\u00eda y V\u00eddeo", icon: "\ud83d\udcf8", href: "/es/photographe" },
        { title: "Actividades en privado o en grupo", icon: "\ud83d\udc65", href: "/es/actividades-en-grupo" }
      ]
    }
  };

  function detectLang() {
    var path = window.location.pathname || "";
    if (path.indexOf("/en/") === 0 || (path.indexOf("/en") === 0 && path.length <= 4)) return "en";
    if (path.indexOf("/es/") === 0 || (path.indexOf("/es") === 0 && path.length <= 4)) return "es";
    var htmlLang = (document.documentElement.lang || "").toLowerCase();
    if (htmlLang.indexOf("en") === 0) return "en";
    if (htmlLang.indexOf("es") === 0) return "es";
    return "fr";
  }

  function injectStyles() {
    if (document.getElementById("rm-fab-style")) return;
    var css = [
      ".rm-fab-wrap{position:fixed;bottom:90px;left:20px;z-index:9998;font-family:'Quicksand','Yanone Kaffeesatz',Arial,sans-serif;}",
      ".rm-fab-btn{width:56px;height:56px;border-radius:50%;border:none;cursor:pointer;",
      "background:linear-gradient(135deg,#c99752 0%,#8a6a3b 100%);color:#fff;font-size:24px;",
      "box-shadow:0 4px 16px rgba(201,151,82,.45);display:flex;align-items:center;justify-content:center;",
      "transition:transform .25s ease,box-shadow .25s ease;outline:none;position:relative;z-index:2;}",
      ".rm-fab-btn:hover{transform:scale(1.08);box-shadow:0 6px 22px rgba(201,151,82,.6);}",
      ".rm-fab-btn.open{transform:rotate(45deg);background:linear-gradient(135deg,#6b4a1b 0%,#4a3518 100%);}",
      ".rm-fab-label{position:absolute;left:66px;top:50%;transform:translateY(-50%);",
      "background:#fff;color:#6b4a1b;font-size:13px;font-weight:700;padding:6px 14px;",
      "border-radius:20px;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,.12);",
      "pointer-events:none;opacity:1;transition:opacity .3s;}",
      ".rm-fab-wrap.open .rm-fab-label{opacity:0;pointer-events:none;}",
      ".rm-fab-menu{position:absolute;bottom:66px;left:0;display:flex;flex-direction:column;gap:10px;",
      "opacity:0;transform:translateY(10px) scale(0.95);pointer-events:none;",
      "transition:opacity .25s ease,transform .25s ease;}",
      ".rm-fab-wrap.open .rm-fab-menu{opacity:1;transform:translateY(0) scale(1);pointer-events:auto;}",
      ".rm-fab-item{display:flex;align-items:center;gap:10px;text-decoration:none !important;",
      "background:#fff;padding:10px 18px 10px 14px;border-radius:30px;",
      "box-shadow:0 3px 12px rgba(0,0,0,.1);transition:transform .2s,box-shadow .2s;min-width:200px;}",
      ".rm-fab-item:hover{transform:translateX(4px);box-shadow:0 5px 18px rgba(0,0,0,.15);text-decoration:none !important;}",
      ".rm-fab-item__icon{font-size:22px;width:32px;text-align:center;flex-shrink:0;}",
      ".rm-fab-item__text{font-size:14px;font-weight:600;color:#232323;white-space:nowrap;}",
      ".rm-fab-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:9997;}",
      ".rm-fab-wrap.open+.rm-fab-overlay{display:block;}",
      "@media(max-width:576px){",
      "  .rm-fab-wrap{bottom:75px;left:14px;}",
      "  .rm-fab-btn{width:50px;height:50px;font-size:22px;}",
      "  .rm-fab-label{left:58px;font-size:12px;padding:5px 12px;}",
      "  .rm-fab-menu{bottom:60px;}",
      "  .rm-fab-item{padding:8px 14px 8px 12px;min-width:180px;}",
      "  .rm-fab-item__text{font-size:13px;}",
      "}"
    ].join("\n");
    var style = document.createElement("style");
    style.id = "rm-fab-style";
    style.textContent = css;
    document.head.appendChild(style);
  }

  function build() {
    var lang = detectLang();
    var t = I18N[lang] || I18N.fr;

    injectStyles();

    // Wrapper
    var wrap = document.createElement("div");
    wrap.className = "rm-fab-wrap";
    wrap.id = "rm-activities-fab";

    // Menu items
    var menu = document.createElement("div");
    menu.className = "rm-fab-menu";
    for (var i = 0; i < t.items.length; i++) {
      var it = t.items[i];
      var a = document.createElement("a");
      a.className = "rm-fab-item";
      a.href = it.href;
      a.innerHTML = '<span class="rm-fab-item__icon">' + it.icon + '</span>'
                   + '<span class="rm-fab-item__text">' + it.title + '</span>';
      menu.appendChild(a);
    }
    wrap.appendChild(menu);

    // Main button
    var btn = document.createElement("button");
    btn.className = "rm-fab-btn";
    btn.setAttribute("aria-label", t.label);
    btn.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
    wrap.appendChild(btn);

    // Label
    var label = document.createElement("span");
    label.className = "rm-fab-label";
    label.textContent = t.label;
    wrap.appendChild(label);

    // Overlay
    var overlay = document.createElement("div");
    overlay.className = "rm-fab-overlay";

    document.body.appendChild(wrap);
    document.body.appendChild(overlay);

    // Toggle
    function toggle() {
      var isOpen = wrap.classList.contains("open");
      if (isOpen) {
        wrap.classList.remove("open");
        btn.classList.remove("open");
      } else {
        wrap.classList.add("open");
        btn.classList.add("open");
      }
    }

    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      toggle();
    });
    overlay.addEventListener("click", function () {
      wrap.classList.remove("open");
      btn.classList.remove("open");
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        wrap.classList.remove("open");
        btn.classList.remove("open");
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", build);
  } else {
    build();
  }
})();
