/* ==========================================================================
   Riad Mylaya — Transfer quote calculator
   --------------------------------------------------------------------------
   Drives the [data-rm-tq] booking form: live price breakdown, prefilled
   WhatsApp message and email fallback (FormSubmit).

   Every rate, surcharge window and discount comes from rm-services-data.js,
   which must be loaded first. Nothing is duplicated here.
   ========================================================================== */

(function () {
  var WHATSAPP_NUMBER = "212661351989";

  function rates(kind) {
    var data = window.RM_SERVICES && window.RM_SERVICES.transfer;
    return (data && data[kind]) || null;
  }

  var I18N = {
    fr: {
      arrival: "Arrivée",
      departure: "Départ",
      night: "supplément nuit (22h–7h)",
      early: "supplément départ tôt (3h–7h45)",
      discount: "remise aller-retour",
      total: "Total à payer sur place",
      cash: "À régler sur place en espèces, le jour du transfert.",
      quote: "Pour 7 personnes ou plus, merci de nous contacter afin que nous organisions le véhicule adapté.",
      incomplete: "Complétez les dates et les heures pour voir votre total.",
      waIntro: "Bonjour, je souhaite réserver un transfert avec le Riad Mylaya.",
      lName: "Nom complet de la réservation",
      lRef: "N° de réservation",
      lPhone: "Téléphone",
      lTrip: "Trajet",
      lPeople: "Nombre de personnes",
      lFlight: "n° de vol / train",
      airport: "Aéroport Marrakech-Menara",
      station: "Gare de Marrakech",
      lDetail: "Détail",
      lTotal: "TOTAL",
      pending: "Demande à confirmer par le riad."
    },
    en: {
      arrival: "Arrival",
      departure: "Departure",
      night: "night surcharge (10 pm–7 am)",
      early: "early departure surcharge (3–7:45 am)",
      discount: "return discount",
      total: "Total to pay on site",
      cash: "Paid on site in cash, on the day of the transfer.",
      quote: "For 7 people or more, please contact us so that we can arrange a suitable vehicle.",
      incomplete: "Fill in the dates and times to see your total.",
      waIntro: "Hello, I would like to book a transfer with Riad Mylaya.",
      lName: "Full name on the booking",
      lRef: "Booking number",
      lPhone: "Phone",
      lTrip: "Transfer",
      lPeople: "Number of people",
      lFlight: "flight / train no.",
      airport: "Marrakech-Menara airport",
      station: "Marrakech train station",
      lDetail: "Breakdown",
      lTotal: "TOTAL",
      pending: "Request to be confirmed by the riad."
    },
    es: {
      arrival: "Llegada",
      departure: "Salida",
      night: "suplemento nocturno (22h–7h)",
      early: "suplemento salida temprana (3h–7h45)",
      discount: "descuento ida y vuelta",
      total: "Total a pagar en el riad",
      cash: "A pagar en el riad en efectivo, el día del traslado.",
      quote: "Para 7 personas o más, contáctenos para que organicemos el vehículo adecuado.",
      incomplete: "Complete las fechas y las horas para ver su total.",
      waIntro: "Hola, quiero reservar un traslado con el Riad Mylaya.",
      lName: "Nombre completo de la reserva",
      lRef: "N.º de reserva",
      lPhone: "Teléfono",
      lTrip: "Traslado",
      lPeople: "Número de personas",
      lFlight: "n.º de vuelo / tren",
      airport: "Aeropuerto Marrakech-Menara",
      station: "Estación de Marrakech",
      lDetail: "Detalle",
      lTotal: "TOTAL",
      pending: "Solicitud pendiente de confirmación por el riad."
    }
  };

  function money(n) {
    return n + " €";
  }

  function minutes(time) {
    var m = /^(\d{1,2}):(\d{2})/.exec(time || "");
    return m ? parseInt(m[1], 10) * 60 + parseInt(m[2], 10) : null;
  }

  function baseRate(kind, people) {
    var conf = rates(kind);
    var grid = (conf && conf.tiers) || [];
    for (var i = 0; i < grid.length; i++) {
      if (people <= grid[i].max) return grid[i].price;
    }
    return null;
  }

  /* A window whose end is before its start spans midnight (22:00 -> 07:00). */
  function inWindow(t, win) {
    var from = minutes(win.from);
    var to = minutes(win.to);
    if (from === null || to === null) return false;
    return to < from ? (t >= from || t < to) : (t >= from && t <= to);
  }

  function surcharge(kind, time, isDeparture) {
    var t = minutes(time);
    var conf = rates(kind);
    var rule = conf && conf.surcharge;
    if (t === null || !rule) return 0;
    var win = rule[isDeparture ? "departure" : "arrival"];
    return win && inWindow(t, win) ? rule.amount : 0;
  }

  /* Round trip: the discount applies to a single leg (the departure). */
  function roundTripDiscount(kind, trips) {
    var conf = rates(kind);
    var rule = conf && conf.round_trip_discount;
    if (!rule || trips !== "both") return { amount: 0, leg: null };
    return { amount: rule.amount, leg: rule.leg || "departure" };
  }

  function init(root) {
    var lang = root.getAttribute("data-lang") || "fr";
    var t = I18N[lang] || I18N.fr;
    var form = root.querySelector("form");
    var summary = root.querySelector("[data-tq-summary]");
    var waLink = root.querySelector("[data-tq-wa]");
    var submit = root.querySelector("[data-tq-submit]");
    var recap = root.querySelector("[data-tq-recap]");
    var totalField = root.querySelector("[data-tq-total-field]");

    function field(name) {
      return root.querySelector('[data-tq="' + name + '"]');
    }
    function val(name) {
      var el = field(name);
      return el ? el.value.trim() : "";
    }

    var arrivalBlock = root.querySelector("[data-tq-block='arrival']");
    var departureBlock = root.querySelector("[data-tq-block='departure']");

    function setBlock(block, on) {
      if (!block) return;
      block.hidden = !on;
      Array.prototype.forEach.call(block.querySelectorAll("input, select"), function (el) {
        el.disabled = !on;
        if (el.hasAttribute("data-tq-required")) {
          if (on) el.setAttribute("required", "required");
          else el.removeAttribute("required");
        }
      });
    }

    function dateTime(date, time) {
      return date && time ? date + " · " + time : (date || time || "");
    }

    function compute() {
      var kind = val("type") === "station" ? "station" : "airport";
      var people = parseInt(val("people"), 10) || 1;
      var trips = val("trips") || "both";
      var wantsArrival = trips !== "departure";
      var wantsDeparture = trips !== "arrival";

      setBlock(arrivalBlock, wantsArrival);
      setBlock(departureBlock, wantsDeparture);

      var rate = baseRate(kind, people);
      var lines = [];
      var total = 0;
      var complete = true;

      if (rate === null) {
        return { quoteOnly: true, lines: [], total: 0, kind: kind, people: people, trips: trips };
      }

      var discount = roundTripDiscount(kind, trips);

      if (wantsArrival) {
        var aTime = val("arr_time");
        if (!val("arr_date") || !aTime) complete = false;
        var aSur = surcharge(kind, aTime, false);
        var aDisc = discount.leg === "arrival" ? discount.amount : 0;
        lines.push({ label: t.arrival + (dateTime(val("arr_date"), aTime) ? " (" + dateTime(val("arr_date"), aTime) + ")" : ""), amount: rate });
        if (aSur) lines.push({ label: t.night, amount: aSur, sub: true });
        if (aDisc) lines.push({ label: t.discount, amount: -aDisc, sub: true });
        total += rate + aSur - aDisc;
      }
      if (wantsDeparture) {
        var dTime = val("dep_time");
        if (!val("dep_date") || !dTime) complete = false;
        var dSur = surcharge(kind, dTime, true);
        var dDisc = discount.leg === "departure" ? discount.amount : 0;
        lines.push({ label: t.departure + (dateTime(val("dep_date"), dTime) ? " (" + dateTime(val("dep_date"), dTime) + ")" : ""), amount: rate });
        if (dSur) lines.push({ label: kind === "airport" ? t.night : t.early, amount: dSur, sub: true });
        if (dDisc) lines.push({ label: t.discount, amount: -dDisc, sub: true });
        total += rate + dSur - dDisc;
      }

      return { quoteOnly: false, lines: lines, total: total, complete: complete, kind: kind, people: people, trips: trips };
    }

    function render() {
      var q = compute();
      var kindLabel = q.kind === "station" ? t.station : t.airport;

      if (q.quoteOnly) {
        summary.innerHTML = '<p class="rm-tq__quote">' + t.quote + "</p>";
        if (submit) submit.hidden = true;
      } else {
        var html = '<ul class="rm-tq__lines">';
        q.lines.forEach(function (l) {
          html += '<li' + (l.sub ? ' class="rm-tq__line--sub"' : "") + "><span>" + l.label + "</span><span>" +
            (l.amount < 0 ? "− " + money(-l.amount) : money(l.amount)) + "</span></li>";
        });
        html += "</ul>";
        html += '<p class="rm-tq__total"><span>' + t.total + "</span><span>" + money(q.total) + "</span></p>";
        html += '<p class="rm-tq__cash">' + t.cash + (q.complete ? "" : " " + t.incomplete) + "</p>";
        summary.innerHTML = html;
        if (submit) submit.hidden = false;
      }

      var lines = [t.waIntro, ""];
      lines.push(t.lName + " : " + (val("name") || "…"));
      if (val("ref")) lines.push(t.lRef + " : " + val("ref"));
      lines.push(t.lPhone + " : " + (val("phone") || "…"));
      lines.push(t.lTrip + " : " + kindLabel);
      lines.push(t.lPeople + " : " + q.people);
      if (q.trips !== "departure") {
        lines.push(t.arrival + " : " + (dateTime(val("arr_date"), val("arr_time")) || "…") +
          (val("arr_num") ? " — " + t.lFlight + " " + val("arr_num") : ""));
      }
      if (q.trips !== "arrival") {
        lines.push(t.departure + " : " + (dateTime(val("dep_date"), val("dep_time")) || "…") +
          (val("dep_num") ? " — " + t.lFlight + " " + val("dep_num") : ""));
      }
      if (q.quoteOnly) {
        lines.push("", t.quote);
      } else {
        lines.push("", t.lDetail + " : " + q.lines.map(function (l) {
          return l.label + " " + (l.amount < 0 ? "− " + money(-l.amount) : money(l.amount));
        }).join(" · "));
        lines.push(t.lTotal + " : " + money(q.total) + " — " + t.cash);
      }
      lines.push(t.pending);

      var text = lines.join("\n");
      if (waLink) waLink.href = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(text);
      if (recap) recap.value = text;
      if (totalField) totalField.value = q.quoteOnly ? t.quote : money(q.total);
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
    if (!rates("airport")) {
      console.error("transfer-quote.js: rm-services-data.js must be loaded first.");
      return;
    }
    Array.prototype.forEach.call(document.querySelectorAll("[data-rm-tq]"), init);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
