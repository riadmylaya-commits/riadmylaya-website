/* ==========================================================================
   Riad Mylaya — International phone field
   --------------------------------------------------------------------------
   Upgrades every <input type="tel" data-rm-phone> into a country selector
   (flag + dialling code) followed by the national number. The input keeps its
   name and always carries the full international number, so the WhatsApp
   message, the e-mail and the recap need no change.
   ========================================================================== */

(function () {
  /* Dialling codes, most frequent guest origins first. */
  var COUNTRIES = [
    ["FR", "France", "+33"],
    ["ES", "España", "+34"],
    ["GB", "United Kingdom", "+44"],
    ["BE", "Belgique", "+32"],
    ["CH", "Suisse", "+41"],
    ["DE", "Deutschland", "+49"],
    ["IT", "Italia", "+39"],
    ["NL", "Nederland", "+31"],
    ["PT", "Portugal", "+351"],
    ["MA", "Maroc", "+212"],
    ["US", "United States", "+1"],
    ["CA", "Canada", "+1"],
    ["IE", "Ireland", "+353"],
    ["LU", "Luxembourg", "+352"],
    ["AT", "Österreich", "+43"],
    ["DK", "Danmark", "+45"],
    ["SE", "Sverige", "+46"],
    ["NO", "Norge", "+47"],
    ["FI", "Suomi", "+358"],
    ["IS", "Ísland", "+354"],
    ["PL", "Polska", "+48"],
    ["CZ", "Česko", "+420"],
    ["SK", "Slovensko", "+421"],
    ["HU", "Magyarország", "+36"],
    ["RO", "România", "+40"],
    ["BG", "България", "+359"],
    ["GR", "Ελλάδα", "+30"],
    ["HR", "Hrvatska", "+385"],
    ["SI", "Slovenija", "+386"],
    ["EE", "Eesti", "+372"],
    ["LV", "Latvija", "+371"],
    ["LT", "Lietuva", "+370"],
    ["MT", "Malta", "+356"],
    ["CY", "Κύπρος", "+357"],
    ["RU", "Россия", "+7"],
    ["UA", "Україна", "+380"],
    ["TR", "Türkiye", "+90"],
    ["IL", "Israel", "+972"],
    ["AE", "Emirates", "+971"],
    ["SA", "Saudi Arabia", "+966"],
    ["QA", "Qatar", "+974"],
    ["KW", "Kuwait", "+965"],
    ["BH", "Bahrain", "+973"],
    ["OM", "Oman", "+968"],
    ["JO", "Jordan", "+962"],
    ["LB", "Lebanon", "+961"],
    ["EG", "Egypt", "+20"],
    ["DZ", "Algérie", "+213"],
    ["TN", "Tunisie", "+216"],
    ["LY", "Libya", "+218"],
    ["MR", "Mauritanie", "+222"],
    ["SN", "Sénégal", "+221"],
    ["CI", "Côte d'Ivoire", "+225"],
    ["ML", "Mali", "+223"],
    ["CM", "Cameroun", "+237"],
    ["GA", "Gabon", "+241"],
    ["ZA", "South Africa", "+27"],
    ["BR", "Brasil", "+55"],
    ["AR", "Argentina", "+54"],
    ["CL", "Chile", "+56"],
    ["CO", "Colombia", "+57"],
    ["MX", "México", "+52"],
    ["PE", "Perú", "+51"],
    ["UY", "Uruguay", "+598"],
    ["VE", "Venezuela", "+58"],
    ["EC", "Ecuador", "+593"],
    ["CR", "Costa Rica", "+506"],
    ["PA", "Panamá", "+507"],
    ["DO", "República Dominicana", "+1"],
    ["AU", "Australia", "+61"],
    ["NZ", "New Zealand", "+64"],
    ["JP", "Japan", "+81"],
    ["KR", "Korea", "+82"],
    ["CN", "China", "+86"],
    ["HK", "Hong Kong", "+852"],
    ["SG", "Singapore", "+65"],
    ["MY", "Malaysia", "+60"],
    ["TH", "Thailand", "+66"],
    ["ID", "Indonesia", "+62"],
    ["PH", "Philippines", "+63"],
    ["IN", "India", "+91"],
    ["PK", "Pakistan", "+92"]
  ];

  var LABEL = {
    fr: "Indicatif du pays",
    en: "Country code",
    es: "Prefijo del país"
  };

  var STYLE_ID = "rm-phone-styles";
  var CSS =
    ".rm-phone{display:flex;gap:8px;align-items:stretch;margin-top:6px}" +
    ".rm-phone select.rm-phone__cc{width:auto;flex:0 0 auto;max-width:44%;margin-top:0}" +
    ".rm-phone input{flex:1 1 auto;min-width:0;margin-top:0}";

  function styles() {
    if (document.getElementById(STYLE_ID)) return;
    var el = document.createElement("style");
    el.id = STYLE_ID;
    el.textContent = CSS;
    document.head.appendChild(el);
  }

  /* Regional indicator letters: "FR" -> 🇫🇷 */
  function flag(iso) {
    return String.fromCodePoint(0x1f1e6 + iso.charCodeAt(0) - 65,
      0x1f1e6 + iso.charCodeAt(1) - 65);
  }

  function lang(input) {
    var host = input.closest("[data-lang]");
    var code = host ? host.getAttribute("data-lang") : (document.documentElement.lang || "fr");
    return LABEL[code] ? code : "fr";
  }

  /* Regions where the page language is spoken. A French guest browsing in
     French from an English-language phone must not be offered +1, so the
     browser region is only trusted when it agrees with the page language. */
  var REGIONS = {
    fr: ["FR", "BE", "CH", "CA", "LU", "MA", "DZ", "TN", "SN", "CI", "ML", "CM", "GA", "MR"],
    es: ["ES", "MX", "AR", "CL", "CO", "PE", "UY", "VE", "EC", "CR", "PA", "DO", "US"],
    en: null
  };

  function guess(code) {
    var allowed = REGIONS[code];
    var tags = [].concat(navigator.languages || [], [navigator.language || ""]);
    for (var i = 0; i < tags.length; i++) {
      var region = /-([A-Za-z]{2})\b/.exec(tags[i]);
      if (!region) continue;
      var iso = region[1].toUpperCase();
      if (allowed && allowed.indexOf(iso) < 0) continue;
      for (var j = 0; j < COUNTRIES.length; j++) {
        if (COUNTRIES[j][0] === iso) return iso;
      }
    }
    return { fr: "FR", en: "GB", es: "ES" }[code] || "FR";
  }

  function dialOf(iso) {
    for (var i = 0; i < COUNTRIES.length; i++) {
      if (COUNTRIES[i][0] === iso) return COUNTRIES[i][2];
    }
    return "+33";
  }

  /* Longest dialling code the typed number starts with. */
  function match(value) {
    var best = null;
    for (var i = 0; i < COUNTRIES.length; i++) {
      var dial = COUNTRIES[i][2];
      if (value.indexOf(dial) === 0 && (!best || dial.length > best[2].length)) best = COUNTRIES[i];
    }
    return best;
  }

  function normalize(value) {
    return value.replace(/[^\d+]/g, "");
  }

  function init(input) {
    if (input.dataset.rmPhoneReady) return;
    input.dataset.rmPhoneReady = "1";

    var code = lang(input);
    var select = document.createElement("select");
    select.className = "rm-phone__cc";
    select.setAttribute("aria-label", LABEL[code]);
    COUNTRIES.forEach(function (c) {
      var o = document.createElement("option");
      o.value = c[0];
      /* Dial code before the name: on a narrow screen the closed select
         truncates the end of the option. */
      o.textContent = flag(c[0]) + " " + c[2] + " " + c[1];
      select.appendChild(o);
    });

    var wrap = document.createElement("div");
    wrap.className = "rm-phone";
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(select);
    wrap.appendChild(input);

    var existing = match(normalize(input.value));
    select.value = existing ? existing[0] : guess(code);
    input.inputMode = "tel";
    input.autocomplete = "tel";

    function dial() {
      return dialOf(select.value);
    }

    /* The input always carries the full international number. */
    function setPrefix(next) {
      var current = match(normalize(input.value));
      var rest = current ? normalize(input.value).slice(current[2].length) : normalize(input.value).replace(/^\+/, "");
      input.value = next + (rest ? " " + rest : " ");
    }

    function check() {
      var digits = input.value.replace(/\D/g, "");
      var prefix = dial().replace(/\D/g, "");
      input.setCustomValidity(!input.value || digits.length > prefix.length + 4 ? "" :
        { fr: "Indiquez votre numéro après l'indicatif.",
          en: "Enter your number after the country code.",
          es: "Escriba su número después del prefijo." }[code]);
    }

    setPrefix(existing ? existing[2] : dial());

    select.addEventListener("change", function () {
      setPrefix(dial());
      check();
      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.focus();
    });

    input.addEventListener("input", function () {
      var found = match(normalize(input.value));
      if (found && found[0] !== select.value && found[2] !== dial()) select.value = found[0];
      check();
    });

    input.addEventListener("blur", function () {
      if (input.value && input.value.charAt(0) !== "+") setPrefix(dial());
      check();
    });

    check();
  }

  function boot() {
    styles();
    Array.prototype.forEach.call(document.querySelectorAll('input[type="tel"][data-rm-phone]'), init);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
