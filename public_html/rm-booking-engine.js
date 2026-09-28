/* ==========================================================================
   Riad Mylaya — Generic booking engine (dinner, cooking class, private
   excursions, hammam & spa)
   --------------------------------------------------------------------------
   Drives every [data-rm-bk="<service>"] block: renders the service-specific
   fields, computes the total live, then fills the prefilled WhatsApp message
   and the hidden recap of the FormSubmit e-mail form.

   All prices, slots and options come from rm-services-data.js, which must be
   loaded first. No price is ever duplicated here.
   ========================================================================== */

(function () {
  var WHATSAPP_NUMBER = "212661351989";

  var I18N = {
    fr: {
      date: "Date souhaitée",
      time: "Heure souhaitée",
      people: "Nombre de personnes",
      formula: "Formule",
      destination: "Destination",
      treatment: "Soin principal",
      extras: "Extras (facultatif)",
      person: "Personne",
      none: "— Aucun —",
      choose: "— Choisir —",
      total: "Total estimé",
      cash: "À régler sur place en espèces.",
      spaCash: "Montant indicatif à régler directement au spa.",
      incomplete: "Choisissez la date pour compléter votre demande.",
      perPerson: "/ personne",
      about: "soit environ",
      minPeople: "Cette excursion se réserve à partir de %d personnes.",
      pickTreatment: "Choisissez au moins un soin pour voir votre total.",
      wa: {
        dinner: "Bonjour, je souhaite réserver le dîner marocain au Riad Mylaya.",
        cooking: "Bonjour, je souhaite réserver le cours de cuisine du Riad Mylaya.",
        excursions: "Bonjour, je souhaite réserver une excursion privée avec le Riad Mylaya.",
        spa: "Bonjour, je souhaite réserver un hammam ou un massage."
      },
      lName: "Nom complet de la réservation",
      lRef: "N° de réservation",
      lPhone: "Téléphone",
      lDetail: "Détail",
      lTotal: "TOTAL",
      pending: "Demande à confirmer par le riad."
    },
    en: {
      date: "Preferred date",
      time: "Preferred time",
      people: "Number of people",
      formula: "Menu",
      destination: "Destination",
      treatment: "Main treatment",
      extras: "Extras (optional)",
      person: "Person",
      none: "— None —",
      choose: "— Choose —",
      total: "Estimated total",
      cash: "Paid on site in cash.",
      spaCash: "Indicative amount, paid directly at the spa.",
      incomplete: "Choose the date to complete your request.",
      perPerson: "/ person",
      about: "about",
      minPeople: "This excursion starts from %d people.",
      pickTreatment: "Choose at least one treatment to see your total.",
      wa: {
        dinner: "Hello, I would like to book the Moroccan dinner at Riad Mylaya.",
        cooking: "Hello, I would like to book the Riad Mylaya cooking class.",
        excursions: "Hello, I would like to book a private excursion with Riad Mylaya.",
        spa: "Hello, I would like to book a hammam or a massage."
      },
      lName: "Full name on the booking",
      lRef: "Booking number",
      lPhone: "Phone",
      lDetail: "Breakdown",
      lTotal: "TOTAL",
      pending: "Request to be confirmed by the riad."
    },
    es: {
      date: "Fecha deseada",
      time: "Hora deseada",
      people: "Número de personas",
      formula: "Menú",
      destination: "Destino",
      treatment: "Tratamiento principal",
      extras: "Extras (opcional)",
      person: "Persona",
      none: "— Ninguno —",
      choose: "— Elegir —",
      total: "Total estimado",
      cash: "A pagar en el riad en efectivo.",
      spaCash: "Importe indicativo a pagar directamente en el spa.",
      incomplete: "Elija la fecha para completar su solicitud.",
      perPerson: "/ persona",
      about: "unos",
      minPeople: "Esta excursión se reserva a partir de %d personas.",
      pickTreatment: "Elija al menos un tratamiento para ver su total.",
      wa: {
        dinner: "Hola, quiero reservar la cena marroquí en el Riad Mylaya.",
        cooking: "Hola, quiero reservar la clase de cocina del Riad Mylaya.",
        excursions: "Hola, quiero reservar una excursión privada con el Riad Mylaya.",
        spa: "Hola, quiero reservar un hammam o un masaje."
      },
      lName: "Nombre completo de la reserva",
      lRef: "N.º de reserva",
      lPhone: "Teléfono",
      lDetail: "Detalle",
      lTotal: "TOTAL",
      pending: "Solicitud pendiente de confirmación por el riad."
    }
  };

  function data() {
    return window.RM_SERVICES || null;
  }

  function eur(n) {
    return n + " €";
  }

  function mad(n) {
    return n.toLocaleString("fr-FR") + " MAD";
  }

  function madToEur(n) {
    var rate = (data().currency && data().currency.mad_per_eur) || 10.8;
    return Math.round(n / rate);
  }

  function esc(s) {
    return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  function localized(value, lang) {
    if (value && typeof value === "object") return value[lang] || value.fr;
    return value;
  }

  function options(list) {
    return list.map(function (o) {
      return '<option value="' + esc(o.value) + '">' + esc(o.label) + "</option>";
    }).join("");
  }

  function label(text, control) {
    return "<label>" + esc(text) + control + "</label>";
  }

  function numbers(from, to) {
    var out = [];
    for (var n = from; n <= to; n++) out.push({ value: n, label: n });
    return out;
  }

  /* ------------------------------------------------------------- services */

  var SERVICES = {
    dinner: {
      fields: function (t, lang) {
        var d = data().dinner;
        return '<div class="rm-tq__grid">' +
          label(t.formula, '<select data-bk="formula">' + options(d.options.map(function (o) {
            return { value: o.id, label: localized(o.name, lang) + " — " + eur(o.price) + " " + t.perPerson };
          })) + "</select>") +
          label(t.people, '<select data-bk="people">' + options(numbers(1, d.max_people)) + "</select>") +
          label(t.date + " *", '<input type="date" data-bk="date" data-bk-required>') +
          label(t.time, '<select data-bk="time">' + options(d.slots.map(function (s) {
            return { value: s, label: s };
          })) + "</select>") +
          "</div>";
      },
      compute: function (get, t, lang) {
        var d = data().dinner;
        var people = parseInt(get("people"), 10) || 1;
        var pick = d.options.filter(function (o) { return o.id === get("formula"); })[0] || d.options[0];
        return {
          lines: [{ label: localized(pick.name, lang) + " × " + people, amount: eur(pick.price * people) }],
          total: eur(pick.price * people),
          recap: [t.formula + " : " + localized(pick.name, lang), t.people + " : " + people,
            t.date + " : " + (get("date") || "…"), t.time + " : " + get("time")]
        };
      }
    },

    cooking: {
      fields: function (t) {
        var c = data().cooking;
        return '<div class="rm-tq__grid">' +
          label(t.people, '<select data-bk="people">' + options(numbers(1, c.max_people)) + "</select>") +
          label(t.date + " *", '<input type="date" data-bk="date" data-bk-required>') +
          label(t.time, '<select data-bk="time">' + options(c.slots.map(function (s) {
            return { value: s, label: s };
          })) + "</select>") +
          "</div>";
      },
      compute: function (get, t) {
        var c = data().cooking;
        var people = parseInt(get("people"), 10) || 1;
        return {
          lines: [{ label: eur(c.price) + " " + t.perPerson + " × " + people, amount: eur(c.price * people) }],
          total: eur(c.price * people),
          recap: [t.people + " : " + people, t.date + " : " + (get("date") || "…"), t.time + " : " + get("time")]
        };
      }
    },

    excursions: {
      fields: function (t, lang) {
        var e = data().excursions;
        return '<div class="rm-tq__grid">' +
          label(t.destination, '<select data-bk="destination">' + options(e.items.map(function (it) {
            return { value: it.id, label: localized(it.name, lang) + " — " + localized(it.duration, lang) };
          })) + "</select>") +
          label(t.people, '<select data-bk="people">' + options(numbers(1, e.max_people)) + "</select>") +
          label(t.date + " *", '<input type="date" data-bk="date" data-bk-required>') +
          "</div>";
      },
      compute: function (get, t, lang) {
        var e = data().excursions;
        var item = e.items.filter(function (it) { return it.id === get("destination"); })[0] || e.items[0];
        var people = parseInt(get("people"), 10) || 1;
        var name = localized(item.name, lang);
        if (people < item.min_people) {
          return { note: t.minPeople.replace("%d", item.min_people), noTotal: true,
            recap: [t.destination + " : " + name, t.people + " : " + people, t.date + " : " + (get("date") || "…")] };
        }
        var total;
        var unit = null;
        if (item.pricing === "essaouira") {
          var r = e.essaouira_rule;
          total = people <= 3 ? r.up_to_3 : (people === 4 ? r.four : r.four + (people - 4) * r.extra_person);
        } else {
          var tiers = item.tiers;
          var top = Math.max.apply(null, Object.keys(tiers).map(Number));
          unit = tiers[String(people)] !== undefined ? tiers[String(people)] : tiers[String(top)];
          total = unit * people;
        }
        return {
          lines: [{ label: name + " · " + people + (unit ? " × " + eur(unit) : ""), amount: eur(total) }],
          total: eur(total),
          recap: [t.destination + " : " + name, t.people + " : " + people, t.date + " : " + (get("date") || "…")]
        };
      }
    },

    spa: {
      fields: function (t, lang) {
        var s = data().spa;
        var slots = [];
        var open = s.opening.split("-");
        var start = open[0].trim().split(":");
        var end = open[1].trim().split(":");
        var minute = parseInt(start[0], 10) * 60 + parseInt(start[1], 10);
        var last = parseInt(end[0], 10) * 60 + parseInt(end[1], 10);
        for (; minute <= last; minute += s.slot_step_minutes) {
          slots.push(("0" + Math.floor(minute / 60)).slice(-2) + ":" + ("0" + (minute % 60)).slice(-2));
        }
        var mains = s.categories.filter(function (c) { return !c.extras; });
        var extras = (s.categories.filter(function (c) { return c.extras; })[0] || { items: [] }).items;

        var people = '<select data-bk="people">' + options(numbers(1, s.max_people)) + "</select>";
        var head = '<div class="rm-tq__grid">' +
          label(t.people, people) +
          label(t.date + " *", '<input type="date" data-bk="date" data-bk-required>') +
          label(t.time, '<select data-bk="time">' + options(slots.map(function (x) {
            return { value: x, label: x };
          })) + "</select>") +
          "</div>";

        var mainOptions = '<option value="">' + esc(t.none) + "</option>" + mains.map(function (c) {
          return '<optgroup label="' + esc(localized(c.name, lang)) + '">' + c.items.map(function (it) {
            return '<option value="' + it.code + '">' + esc(it.name + " · " + it.duration + " · " + mad(it.price_mad)) + "</option>";
          }).join("") + "</optgroup>";
        }).join("");

        var persons = "";
        for (var p = 1; p <= s.max_people; p++) {
          persons += '<div class="rm-bk-person" data-bk-person="' + p + '">' +
            "<h5>" + esc(t.person + " " + p) + "</h5>" +
            label(t.treatment, '<select data-bk-treatment="' + p + '">' + mainOptions + "</select>") +
            '<p class="rm-bk-person__extras">' + esc(t.extras) + "</p>" +
            '<div class="rm-bk-extras">' + extras.map(function (it) {
              return '<label class="rm-bk-extra"><input type="checkbox" data-bk-extra="' + p + '" value="' + it.code +
                '"> ' + esc(it.name + " · " + mad(it.price_mad)) + "</label>";
            }).join("") + "</div></div>";
        }
        return head + '<div class="rm-bk-persons">' + persons + "</div>";
      },
      compute: function (get, t, lang, root) {
        var s = data().spa;
        var byCode = {};
        s.categories.forEach(function (c) {
          c.items.forEach(function (it) { byCode[it.code] = it; });
        });
        var people = parseInt(get("people"), 10) || 1;
        Array.prototype.forEach.call(root.querySelectorAll("[data-bk-person]"), function (block) {
          var on = parseInt(block.getAttribute("data-bk-person"), 10) <= people;
          block.hidden = !on;
          Array.prototype.forEach.call(block.querySelectorAll("select, input"), function (el) { el.disabled = !on; });
        });

        var lines = [];
        var totalMad = 0;
        var recap = [t.people + " : " + people, t.date + " : " + (get("date") || "…"), t.time + " : " + get("time")];
        for (var p = 1; p <= people; p++) {
          var sel = root.querySelector('[data-bk-treatment="' + p + '"]');
          var main = sel && byCode[sel.value];
          var picks = [];
          if (main) {
            lines.push({ label: t.person + " " + p + " · " + main.name, amount: mad(main.price_mad) });
            totalMad += main.price_mad;
            picks.push(main.name);
          }
          Array.prototype.forEach.call(root.querySelectorAll('[data-bk-extra="' + p + '"]'), function (box) {
            if (!box.checked || box.disabled) return;
            var it = byCode[box.value];
            if (!it) return;
            lines.push({ label: "+ " + it.name, amount: mad(it.price_mad), sub: true });
            totalMad += it.price_mad;
            picks.push(it.name);
          });
          recap.push(t.person + " " + p + " : " + (picks.length ? picks.join(" + ") : t.none));
        }
        if (!totalMad) return { note: t.pickTreatment, noTotal: true, recap: recap };
        return {
          lines: lines,
          total: mad(totalMad) + " (" + t.about + " " + eur(madToEur(totalMad)) + ")",
          cash: t.spaCash,
          recap: recap
        };
      }
    }
  };

  /* ---------------------------------------------------------------- engine */

  function init(root) {
    var name = root.getAttribute("data-rm-bk");
    var service = SERVICES[name];
    if (!service) return;
    var lang = root.getAttribute("data-lang") || "fr";
    var t = I18N[lang] || I18N.fr;
    var form = root.querySelector("form");
    var host = root.querySelector("[data-bk-fields]");
    var summary = root.querySelector("[data-bk-summary]");
    var waLink = root.querySelector("[data-bk-wa]");
    var submit = root.querySelector("[data-bk-submit]");
    var recapField = root.querySelector("[data-bk-recap]");
    var totalField = root.querySelector("[data-bk-total-field]");

    host.innerHTML = service.fields(t, lang);

    function get(key) {
      var el = root.querySelector('[data-bk="' + key + '"]');
      return el ? el.value.trim() : "";
    }
    function contact(key) {
      var el = root.querySelector('[data-bk-contact="' + key + '"]');
      return el ? el.value.trim() : "";
    }

    function render() {
      var q = service.compute(get, t, lang, root);
      var complete = !!get("date");

      if (q.noTotal) {
        summary.innerHTML = '<p class="rm-tq__quote">' + esc(q.note) + "</p>";
        if (submit) submit.hidden = true;
      } else {
        var html = '<ul class="rm-tq__lines">';
        q.lines.forEach(function (l) {
          html += '<li' + (l.sub ? ' class="rm-tq__line--sub"' : "") + "><span>" + esc(l.label) +
            "</span><span>" + esc(l.amount) + "</span></li>";
        });
        html += "</ul>";
        html += '<p class="rm-tq__total"><span>' + esc(t.total) + "</span><span>" + esc(q.total) + "</span></p>";
        html += '<p class="rm-tq__cash">' + esc(q.cash || t.cash) + (complete ? "" : " " + esc(t.incomplete)) + "</p>";
        summary.innerHTML = html;
        if (submit) submit.hidden = false;
      }

      var text = [t.wa[name], ""];
      text.push(t.lName + " : " + (contact("name") || "…"));
      if (contact("ref")) text.push(t.lRef + " : " + contact("ref"));
      text.push(t.lPhone + " : " + (contact("phone") || "…"));
      q.recap.forEach(function (line) { text.push(line); });
      if (!q.noTotal) {
        text.push("", t.lDetail + " : " + q.lines.map(function (l) {
          return l.label + " " + l.amount;
        }).join(" · "));
        text.push(t.lTotal + " : " + q.total + " — " + (q.cash || t.cash));
      } else {
        text.push("", q.note);
      }
      text.push(t.pending);

      var body = text.join("\n");
      if (waLink) waLink.href = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(body);
      if (recapField) recapField.value = body;
      if (totalField) totalField.value = q.noTotal ? q.note : q.total;
    }

    form.addEventListener("input", render);
    form.addEventListener("change", render);
    if (waLink) {
      waLink.addEventListener("click", function (e) {
        if (!form.checkValidity()) {
          e.preventDefault();
          form.reportValidity();
        }
      });
    }
    render();
  }

  function boot() {
    if (!data()) {
      console.error("rm-booking-engine.js: rm-services-data.js must be loaded first.");
      return;
    }
    Array.prototype.forEach.call(document.querySelectorAll("[data-rm-bk]"), init);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
