/**
 * Riad Mylaya — protection anti-robots des formulaires.
 *
 * Sur chaque formulaire envoyé à /rm-envoi.php :
 *   - un second champ piège invisible (_url) ;
 *   - l'horodatage d'ouverture de la page (_ts), pour repérer les envois
 *     instantanés des robots ;
 *   - le widget Cloudflare Turnstile, invisible tant que Cloudflare ne juge pas
 *     l'internaute suspect (appearance « interaction-only »).
 *
 * La clé publique ci-dessous n'est pas un secret : la validation réelle du jeton
 * se fait côté serveur dans rm-envoi.php avec la clé secrète. Tant qu'elle est
 * vide, seuls les pièges et la limite par IP protègent les formulaires.
 */
(function () {
  var SITEKEY = "";
  var forms = [].slice.call(
    document.querySelectorAll('form[action*="rm-envoi.php"]')
  );
  if (!forms.length) return;

  var opened = Date.now();

  function hidden(form, name, value) {
    if (form.querySelector('[name="' + name + '"]')) return;
    var input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    form.appendChild(input);
  }

  function trap(form) {
    if (form.querySelector('[name="_url"]')) return;
    var wrap = document.createElement("div");
    wrap.setAttribute("aria-hidden", "true");
    wrap.style.cssText =
      "position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;";
    var input = document.createElement("input");
    input.type = "text";
    input.name = "_url";
    input.tabIndex = -1;
    input.autocomplete = "off";
    wrap.appendChild(input);
    form.appendChild(wrap);
  }

  forms.forEach(function (form) {
    hidden(form, "_ts", String(opened));
    trap(form);
  });

  if (!SITEKEY) return;

  /* ------------------------------------------------ Cloudflare Turnstile */

  var widgets = [];

  forms.forEach(function (form) {
    var box = document.createElement("div");
    box.className = "cf-turnstile";
    box.style.cssText = "margin:10px 0;";
    box.setAttribute("data-sitekey", SITEKEY);
    box.setAttribute("data-appearance", "interaction-only");
    box.setAttribute("data-language", (document.documentElement.lang || "fr").slice(0, 2));
    box.setAttribute("data-callback", "rmTurnstileDone");
    box.setAttribute("data-error-callback", "rmTurnstileFailed");
    box.setAttribute("data-expired-callback", "rmTurnstileFailed");
    form.appendChild(box);
    widgets.push({ form: form, box: box, pending: false });
  });

  function entryOf(el) {
    for (var i = 0; i < widgets.length; i++) {
      if (widgets[i].form.contains(el)) return widgets[i];
    }
    return null;
  }

  function token(form) {
    var field = form.querySelector('[name="cf-turnstile-response"]');
    return field && field.value ? field.value : "";
  }

  /* Appelé par Turnstile dès que le jeton est prêt : si le client avait déjà
     cliqué sur « Envoyer », on termine son envoi sans qu'il ait à recliquer. */
  window.rmTurnstileDone = function () {
    widgets.forEach(function (entry) {
      if (entry.pending && token(entry.form)) {
        entry.pending = false;
        entry.form.submit();
      }
    });
  };

  /* Turnstile injoignable ou jeton expiré : on laisse partir la demande plutôt
     que de bloquer le bouton, le serveur tranche et propose WhatsApp au besoin. */
  window.rmTurnstileFailed = function () {
    widgets.forEach(function (entry) {
      if (entry.pending) {
        entry.pending = false;
        entry.form.submit();
      }
    });
  };

  forms.forEach(function (form) {
    form.addEventListener("submit", function (ev) {
      if (token(form)) return;
      ev.preventDefault();
      var entry = entryOf(form);
      if (!entry) return;
      entry.pending = true;
      /* Filet de sécurité : jamais de bouton mort si Turnstile ne répond pas. */
      window.setTimeout(function () {
        if (entry.pending) {
          entry.pending = false;
          form.submit();
        }
      }, 8000);
    });
  });

  var api = document.createElement("script");
  api.src = "https://challenges.cloudflare.com/turnstile/v0/api.js";
  api.async = true;
  api.defer = true;
  document.head.appendChild(api);
})();
