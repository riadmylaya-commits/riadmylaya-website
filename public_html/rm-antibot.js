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
  var SITEKEY = "0x4AAAAAAEydHUtiaotTS4bC";
  var MESSAGES = {
    fr: {
      wait: "Vérification de sécurité en cours…",
      check:
        "Merci de cocher la case de vérification ci-dessus : votre demande" +
        " partira automatiquement ensuite.",
      fail:
        "La vérification de sécurité n'a pas abouti. Merci de réessayer, ou" +
        " écrivez-nous sur WhatsApp au +212 661 351 989."
    },
    en: {
      wait: "Security check in progress…",
      check:
        "Please tick the verification box above: your request will then be sent" +
        " automatically.",
      fail:
        "The security check did not complete. Please try again, or message us" +
        " on WhatsApp at +212 661 351 989."
    },
    es: {
      wait: "Verificación de seguridad en curso…",
      check:
        "Marque la casilla de verificación de arriba: su solicitud se enviará" +
        " automáticamente después.",
      fail:
        "La verificación de seguridad no se ha completado. Inténtelo de nuevo o" +
        " escríbanos por WhatsApp al +212 661 351 989."
    }
  };
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

  var lang = (document.documentElement.lang || "fr").slice(0, 2).toLowerCase();
  var texts = MESSAGES[lang] || MESSAGES.fr;
  var widgets = [];

  forms.forEach(function (form) {
    var note = document.createElement("p");
    note.setAttribute("role", "status");
    note.style.cssText = "margin:10px 0;display:none;font-size:15px;color:#8a2f2f;";
    form.appendChild(note);

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
    widgets.push({ form: form, box: box, note: note, pending: false, tries: 0 });
  });

  function say(entry, text, color) {
    entry.note.textContent = text;
    entry.note.style.color = color;
    entry.note.style.display = text ? "block" : "none";
  }

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
        say(entry, "", "");
        entry.form.submit();
      }
    });
  };

  /* Turnstile en échec : une nouvelle tentative, puis un message expliquant
     comment nous joindre. Envoyer sans jeton serait refusé par le serveur, le
     client ne saurait pas pourquoi. */
  window.rmTurnstileFailed = function () {
    widgets.forEach(function (entry) {
      entry.tries++;
      if (entry.tries < 2 && window.turnstile) {
        window.turnstile.reset(entry.box);
        if (entry.pending) say(entry, texts.wait, "#5a5245");
        return;
      }
      if (!entry.pending) return;
      entry.pending = false;
      say(entry, texts.fail, "#8a2f2f");
    });
  };

  forms.forEach(function (form) {
    form.addEventListener("submit", function (ev) {
      if (token(form)) return;
      ev.preventDefault();
      var entry = entryOf(form);
      if (!entry) return;
      entry.pending = true;
      say(entry, texts.wait, "#5a5245");
      /* Le jeton peut arriver bien après : on n'annule jamais l'attente, on
         guide seulement le client, et l'envoi part dès que le jeton existe. */
      window.setTimeout(function () {
        if (entry.pending && !token(form)) say(entry, texts.check, "#5a5245");
      }, 8000);
    });
  });

  /* Filet de sécurité si le callback de Turnstile ne se déclenche pas. */
  window.setInterval(function () {
    widgets.forEach(function (entry) {
      if (entry.pending && token(entry.form)) {
        entry.pending = false;
        say(entry, "", "");
        entry.form.submit();
      }
    });
  }, 1500);

  var api = document.createElement("script");
  api.src = "https://challenges.cloudflare.com/turnstile/v0/api.js";
  api.async = true;
  api.defer = true;
  document.head.appendChild(api);
})();
