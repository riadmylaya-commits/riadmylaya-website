/* Bouton WhatsApp de la page de remerciement : reprend la demande que le client
   vient d'envoyer par e-mail depuis « Préparer mon séjour », pour qu'il n'ait
   rien à ressaisir. Le texte passe par sessionStorage (même onglet). */
(function () {
  "use strict";

  var KEY = "rmWaRecap";
  var MAX_AGE = 2 * 60 * 60 * 1000;
  var I18N = {
    fr: {
      intro: "Bonjour, je viens d'envoyer ce message depuis le site du Riad Mylaya.",
      email: "E-mail",
      sent: "(Demande également envoyée par e-mail depuis le site.)"
    },
    en: {
      intro: "Hello, I have just sent this message from the Riad Mylaya website.",
      email: "Email",
      sent: "(Request also sent by email from the website.)"
    },
    es: {
      intro: "Hola, acabo de enviar este mensaje desde la web del Riad Mylaya.",
      email: "Correo electrónico",
      sent: "(Solicitud también enviada por correo electrónico desde la web.)"
    }
  };

  function lang() {
    var l = (document.documentElement.lang || "fr").slice(0, 2).toLowerCase();
    return I18N[l] ? l : "fr";
  }

  function value(el) {
    return el && el.value ? el.value.trim() : "";
  }

  /* Le récapitulatif commence par : intro, ligne vide, nom, [n° de réservation], téléphone. */
  function fromRecap(form, recap, t) {
    var lines = recap.split("\n");
    var mail = value(form.querySelector('[data-tq="mail"], [data-bk-contact="mail"]'));
    if (mail) {
      var hasRef = !!value(form.querySelector('[data-tq="ref"], [data-bk-contact="ref"]'));
      lines.splice(Math.min(lines.length, hasRef ? 5 : 4), 0, t.email + " : " + mail);
    }
    return lines;
  }

  function fromFields(form, t) {
    var lines = [t.intro, ""];
    Array.prototype.forEach.call(form.querySelectorAll("input, select, textarea"), function (el) {
      if (!el.name || el.name.charAt(0) === "_" || el.name === "cf-turnstile-response") return;
      if (el.type === "hidden" || el.type === "submit" || el.type === "button") return;
      if ((el.type === "checkbox" || el.type === "radio") && !el.checked) return;
      var v = value(el);
      if (v) lines.push(el.name + " : " + v);
    });
    return lines.length > 2 ? lines : null;
  }

  function remember(ev) {
    var form = ev.target;
    if (!form || form.tagName !== "FORM") return;
    if ((form.getAttribute("action") || "").indexOf("rm-envoi.php") === -1) return;
    var t = I18N[lang()];
    var recap = value(form.querySelector("[data-tq-recap], [data-bk-recap]"));
    var lines = recap ? fromRecap(form, recap, t) : fromFields(form, t);
    if (!lines) return;
    lines.push("", t.sent);
    try {
      sessionStorage.setItem(KEY, JSON.stringify({ text: lines.join("\n"), at: Date.now() }));
    } catch (e) { /* stockage indisponible : le bouton garde son message par défaut */ }
  }

  function prefill() {
    var link = document.querySelector('.rm-thankyou a[href^="https://wa.me/"]');
    if (!link) return;
    var saved;
    try { saved = JSON.parse(sessionStorage.getItem(KEY) || "null"); } catch (e) { return; }
    if (!saved || !saved.text || !(Date.now() - saved.at < MAX_AGE)) return;
    link.href = link.href.split("?")[0] + "?text=" + encodeURIComponent(saved.text);
  }

  document.addEventListener("submit", remember, true);
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", prefill);
  } else {
    prefill();
  }
})();
