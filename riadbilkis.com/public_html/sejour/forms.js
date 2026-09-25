/* Riad Bilkis - envoi des demandes de diner et de transfert aeroport.
   Poste le formulaire vers /rb-request.php, qui envoie les e-mails. */
(function () {
  "use strict";

  var ENDPOINT = "/rb-request.php";

  var MESSAGES = {
    fr: {
      ok: "Merci ! Votre demande a bien ete envoyee. Nous vous confirmons par e-mail.",
      err: "L'envoi a echoue. Ecrivez-nous sur WhatsApp ou a riadbilkis@gmail.com.",
      missing: "Merci de completer les champs obligatoires.",
      sending: "Envoi en cours\u2026"
    },
    en: {
      ok: "Thank you! Your request has been sent. We will confirm by email.",
      err: "Sending failed. Please write to us on WhatsApp or at riadbilkis@gmail.com.",
      missing: "Please fill in the required fields.",
      sending: "Sending\u2026"
    },
    es: {
      ok: "\u00a1Gracias! Su solicitud ha sido enviada. Le confirmaremos por correo electronico.",
      err: "El envio ha fallado. Escribanos por WhatsApp o a riadbilkis@gmail.com.",
      missing: "Complete los campos obligatorios, por favor.",
      sending: "Enviando\u2026"
    }
  };

  function texts(form) {
    return MESSAGES[form.getAttribute("data-lang")] || MESSAGES.fr;
  }

  function updateTotal(form) {
    var box = form.querySelector("[data-rb-total]");
    var menu = form.querySelector("[data-rb-menu]");
    var guests = form.querySelector("[data-rb-guests]");
    if (!box || !menu || !guests) return;
    var opt = menu.options[menu.selectedIndex];
    var price = opt ? parseFloat(opt.getAttribute("data-price") || "0") : 0;
    var n = parseInt(guests.value, 10) || 0;
    if (!price || !n) {
      box.textContent = box.getAttribute("data-placeholder") || "";
      return;
    }
    box.textContent = box.getAttribute("data-total-label") + " : " + (price * n) + " \u20ac  (" +
      n + " \u00d7 " + price + " \u20ac " + box.getAttribute("data-per") + ")";
  }

  function serialize(form) {
    var data = {};
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name) return;
      data[el.name] = el.value;
    });
    data.type = form.getAttribute("data-rb-form");
    data.lang = form.getAttribute("data-lang");
    data.page = window.location.pathname;
    return data;
  }

  function setStatus(form, message, kind) {
    var box = form.querySelector("[data-rb-status]");
    if (!box) return;
    box.textContent = message;
    box.className = "rb-form__status" + (kind ? " rb-form__status--" + kind : "");
  }

  function submit(form, event) {
    event.preventDefault();
    var t = texts(form);
    var required = form.querySelectorAll("[required]");
    for (var i = 0; i < required.length; i++) {
      if (!required[i].value) {
        setStatus(form, t.missing, "err");
        required[i].focus();
        return;
      }
    }
    var button = form.querySelector("button[type=submit]");
    if (button) button.disabled = true;
    setStatus(form, t.sending, null);

    var xhr = new XMLHttpRequest();
    xhr.open("POST", ENDPOINT, true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (button) button.disabled = false;
      var ok = false;
      try { ok = xhr.status === 200 && JSON.parse(xhr.responseText).ok === true; } catch (e) { ok = false; }
      if (ok) {
        setStatus(form, t.ok, "ok");
        form.reset();
        updateTotal(form);
      } else {
        setStatus(form, t.err, "err");
      }
    };
    xhr.send(JSON.stringify(serialize(form)));
  }

  function init() {
    var forms = document.querySelectorAll("[data-rb-form]");
    Array.prototype.forEach.call(forms, function (form) {
      form.addEventListener("submit", function (e) { submit(form, e); });
      var menu = form.querySelector("[data-rb-menu]");
      var guests = form.querySelector("[data-rb-guests]");
      if (menu) menu.addEventListener("change", function () { updateTotal(form); });
      if (guests) guests.addEventListener("input", function () { updateTotal(form); });
      updateTotal(form);
    });

    // Les cartes de formules preselectionnent le menu dans le formulaire.
    var picks = document.querySelectorAll("[data-rb-pick]");
    Array.prototype.forEach.call(picks, function (link) {
      link.addEventListener("click", function () {
        var label = link.getAttribute("data-rb-pick");
        var menu = document.querySelector("[data-rb-menu]");
        if (!menu) return;
        for (var i = 0; i < menu.options.length; i++) {
          if (menu.options[i].value === label) {
            menu.selectedIndex = i;
            break;
          }
        }
        var form = menu.form;
        if (form) updateTotal(form);
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
