/* Comportements de la page « Préparer mon séjour » (sommaire, ancres, widget GetYourGuide). Commun à tous les établissements. */
(function () {
  /* Mobile: open the WhatsApp app directly (whatsapp://send) instead of the wa.me landing page.
     Links keep their https://wa.me href (desktop, no-JS, app missing → fallback after a short delay). */
  var mobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
  if (mobile) {
    document.addEventListener("click", function (ev) {
      var a = ev.target.closest && ev.target.closest('a[href^="https://wa.me/"]');
      if (!a || ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey) return;
      var m = a.href.match(/^https:\/\/wa\.me\/(\d+)(?:\?text=(.*))?$/);
      if (!m) return;
      ev.preventDefault();
      var fallback = a.href;
      var deep = "whatsapp://send?phone=" + m[1] + (m[2] ? "&text=" + m[2] : "");
      var t = setTimeout(function () { window.location.href = fallback; }, 1500);
      var cancel = function () { if (document.hidden) { clearTimeout(t); document.removeEventListener("visibilitychange", cancel); } };
      document.addEventListener("visibilitychange", cancel);
      window.addEventListener("pagehide", function () { clearTimeout(t); }, { once: true });
      window.location.href = deep;
    });
  }

  /* Load the GetYourGuide affiliate script only when the group block is opened. */
  var group = document.getElementById("groupe");
  if (group) {
    var loaded = false;
    group.addEventListener("toggle", function () {
      if (!group.open || loaded) return;
      loaded = true;
      var s = document.createElement("script");
      var me = document.querySelector('script[src*="sejour-page.js"]');
      s.src = (me ? me.src.replace(/sejour-page\.js.*$/, "") : "/lib/") + "gyg-affiliate.js";
      document.body.appendChild(s);
    });
  }

  /* The summary is sticky under the sticky header, whose height varies with the viewport. */
  var header = document.querySelector(".rm-cs-head");
  var toc = document.querySelector(".rm-ps-toc");
  function dockHeight() {
    var h = header ? header.getBoundingClientRect().height : 0;
    document.documentElement.style.setProperty("--rm-head-h", h + "px");
    return h + (toc ? toc.getBoundingClientRect().height : 0);
  }
  dockHeight();
  window.addEventListener("resize", dockHeight);
  window.addEventListener("load", dockHeight);

  /* Summary links: open the targeted block and scroll to it without leaving the page. */
  function reveal(id, smooth) {
    var target = document.getElementById(id);
    if (!target) return false;
    if (target.tagName === "DETAILS") target.open = true;
    /* Explicit offset: scroll-margin-top is ignored on the <details> blocks. */
    var offset = dockHeight() + 14;
    var top = target.getBoundingClientRect().top + window.pageYOffset - offset;
    window.scrollTo({ top: top < 0 ? 0 : top, behavior: smooth ? "smooth" : "auto" });
    return true;
  }
  document.addEventListener("click", function (ev) {
    var link = ev.target.closest ? ev.target.closest('a[href*="#"]') : null;
    if (!link) return;
    var href = link.getAttribute("href") || "";
    var hash = href.slice(href.indexOf("#") + 1);
    if (!hash || href.indexOf("#") < 0) return;
    var path = href.split("#")[0];
    if (path && path !== location.pathname && path + ".html" !== location.pathname) return;
    if (reveal(hash, true)) {
      ev.preventDefault();
      history.replaceState(null, "", location.pathname + "#" + hash);
    }
  });
  window.addEventListener("hashchange", function () {
    if (location.hash.length > 1) reveal(decodeURIComponent(location.hash.slice(1)), false);
  });
  /* Highlight the service the guest is currently reading. */
  var links = [].slice.call(document.querySelectorAll(".rm-ps-toc__list a, .rm-ps-sheet__list a"));
  if (links.length) {
    var pending = false;
    var spy = function () {
      pending = false;
      var limit = dockHeight() + 24;
      var current = null;
      links.forEach(function (link) {
        var href = link.getAttribute("href") || "";
        var el = document.getElementById(href.slice(href.indexOf("#") + 1));
        if (el && el.getBoundingClientRect().top <= limit) current = link;
      });
      links.forEach(function (link) {
        if (link === current) link.setAttribute("aria-current", "true");
        else link.removeAttribute("aria-current");
      });
    };
    window.addEventListener("scroll", function () {
      if (pending) return;
      pending = true;
      window.requestAnimationFrame(spy);
    }, { passive: true });
    spy();
  }

  /* Mobile "Services" bottom sheet. */
  var sheet = document.getElementById("rm-ps-sheet");
  var sheetBtn = document.querySelector(".rm-ps-toc__btn");
  if (sheet && sheetBtn) {
    var setSheet = function (open) {
      sheet.hidden = !open;
      sheetBtn.setAttribute("aria-expanded", open ? "true" : "false");
      document.body.classList.toggle("rm-sheet-open", open);
    };
    sheetBtn.addEventListener("click", function () { setSheet(sheet.hidden); });
    sheet.addEventListener("click", function (ev) {
      if (ev.target.closest && ev.target.closest("[data-sheet-close]")) setSheet(false);
    });
    document.addEventListener("keydown", function (ev) { if (ev.key === "Escape" && !sheet.hidden) setSheet(false); });
  }

  if (location.hash.length > 1) {
    var hash = decodeURIComponent(location.hash.slice(1));
    reveal(hash, false);
    /* Re-scroll once the opened block and lazy images have settled. */
    window.addEventListener("load", function () {
      setTimeout(function () { reveal(hash, false); }, 60);
    });
  }
})();
