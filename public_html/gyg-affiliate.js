/* ==========================================================================
   Riad Mylaya - Affiliation GetYourGuide
   --------------------------------------------------------------------------
   Un seul endroit a modifier : PARTNER_ID ci-dessous (Portail partenaire
   GetYourGuide > votre nom > Account details, ex. "Q4M7QAO").

   Tant que PARTNER_ID est vide :
     - les widgets GetYourGuide ne sont pas charges ;
     - les blocs [data-gyg-fallback] sont affiches a la place ;
     - les liens restent fonctionnels, simplement sans suivi de commission.
   ========================================================================== */

(function () {
  var PARTNER_ID = "";
  var CMP = "riadmylaya";
  var WIDGET_SCRIPT = "https://widget.getyourguide.com/dist/pa.umd.production.min.js";

  var widgets = document.querySelectorAll("[data-gyg-widget]");
  var fallbacks = document.querySelectorAll("[data-gyg-fallback]");
  var links = document.querySelectorAll("[data-gyg-link]");

  function show(el) { el.removeAttribute("hidden"); }
  function hide(el) { el.setAttribute("hidden", "hidden"); }

  if (!PARTNER_ID) {
    Array.prototype.forEach.call(widgets, hide);
    Array.prototype.forEach.call(fallbacks, show);
    return;
  }

  Array.prototype.forEach.call(links, function (link) {
    var url = new URL(link.href);
    url.searchParams.set("partner_id", PARTNER_ID);
    url.searchParams.set("cmp", CMP);
    link.href = url.toString();
  });

  Array.prototype.forEach.call(widgets, function (widget) {
    widget.setAttribute("data-gyg-partner-id", PARTNER_ID);
    widget.setAttribute("data-gyg-cmp", CMP);
  });

  if (widgets.length) {
    var script = document.createElement("script");
    script.src = WIDGET_SCRIPT;
    script.async = true;
    script.defer = true;
    document.body.appendChild(script);
  }
})();
