/* ==========================================================================
   Riad Mylaya — Transfer quote calculator
   --------------------------------------------------------------------------
   Drives the [data-rm-tq] booking form: live price breakdown, prefilled
   WhatsApp message and email fallback (FormSubmit).

   Every rate, surcharge window and discount comes from rm-services-data.js,
   which must be loaded first. Nothing is duplicated here.
   ========================================================================== */

(function () {
  /* Brand: window.RM_BRAND lets another establishment reuse this engine
     (whatsapp number + establishment name declined per language). */
  var BRAND = window.RM_BRAND || {};
  var WHATSAPP_NUMBER = BRAND.whatsapp || "212661351989";
  var NAMES = BRAND.names || {
    fr: { with: "avec le Riad Mylaya", at: "au Riad Mylaya", of: "du Riad Mylaya" },
    en: { with: "with Riad Mylaya", at: "at Riad Mylaya", of: "Riad Mylaya" },
    es: { with: "con el Riad Mylaya", at: "en el Riad Mylaya", of: "del Riad Mylaya" }
  };
  function brand(lang, s) {
    var n = NAMES[lang] || NAMES.fr;
    return s.replace("{with}", n.with).replace("{at}", n.at).replace("{of}", n.of);
  }

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
      waIntro: "Bonjour, je souhaite réserver un transfert {with}.",
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
      waIntro: "Hello, I would like to book a transfer {with}.",
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
      waIntro: "Hola, quiero reservar un traslado {with}.",
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

      var lines = [brand(lang, t.waIntro), ""];
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

  /* Two-formula mode (data-tq-mode="formulas"): the transfer is free depending
     on how the stay is paid; only the optional departure of the "online"
     formula is charged (airport tiers + night surcharge + luggage cart). */
  var FI18N = {
    fr: {
      formula: "Formule", cash: "Paiement du séjour en espèces", online: "Paiement du séjour en ligne",
      offered: "OFFERT", transfer: "Transfert", cart: "Charrette à bagages",
      night: "supplément nuit (22h–7h)", cartTbc: "tarif communiqué par le riad",
      total: "Total à payer sur place", free: "Rien à payer : votre transfert et la charrette sont offerts 🎁",
      noDep: "Départ non demandé — vous pourrez toujours le réserver plus tard.",
      incomplete: "Complétez la date et l'heure du vol de départ pour voir votre total.",
      cashNote: "Le départ se règle sur place en espèces.",
      waIntro: "Bonjour, je souhaite réserver mon transfert aéroport {with}.",
      lName: "Nom complet de la réservation", lRef: "N° de réservation", lPhone: "Téléphone",
      lPeople: "Nombre de personnes", lFlight: "vol", lAirline: "compagnie", lDetail: "Détail", lTotal: "TOTAL",
      pending: "Demande à confirmer par le riad."
    },
    en: {
      formula: "Option", cash: "Stay paid in cash", online: "Stay paid online",
      offered: "FREE", transfer: "Transfer", cart: "Luggage cart",
      night: "night surcharge (10 pm–7 am)", cartTbc: "price given by the riad",
      total: "Total to pay on site", free: "Nothing to pay: your transfer and luggage cart are free 🎁",
      noDep: "Departure not requested — you can still book it later.",
      incomplete: "Fill in the departure flight date and time to see your total.",
      cashNote: "The departure is paid on site in cash.",
      waIntro: "Hello, I would like to book my airport transfer {with}.",
      lName: "Full name on the booking", lRef: "Booking no.", lPhone: "Phone",
      lPeople: "Number of people", lFlight: "flight", lAirline: "airline", lDetail: "Details", lTotal: "TOTAL",
      pending: "Request to be confirmed by the riad."
    },
    es: {
      formula: "Fórmula", cash: "Estancia pagada en efectivo", online: "Estancia pagada en línea",
      offered: "GRATIS", transfer: "Traslado", cart: "Carrito de equipaje",
      night: "suplemento nocturno (22h–7h)", cartTbc: "precio comunicado por el riad",
      total: "Total a pagar en el riad", free: "Nada que pagar: su traslado y el carrito son gratis 🎁",
      noDep: "Salida no solicitada — podrá reservarla más adelante.",
      incomplete: "Complete la fecha y la hora del vuelo de salida para ver su total.",
      cashNote: "La salida se paga en el riad en efectivo.",
      waIntro: "Hola, deseo reservar mi traslado al aeropuerto {with}.",
      lName: "Nombre completo de la reserva", lRef: "N.º de reserva", lPhone: "Teléfono",
      lPeople: "Número de personas", lFlight: "vuelo", lAirline: "compañía", lDetail: "Detalle", lTotal: "TOTAL",
      pending: "Solicitud pendiente de confirmación por el riad."
    }
  };

  function initFormulas(root) {
    var lang = root.getAttribute("data-lang") || "fr";
    var t = FI18N[lang] || FI18N.fr;
    var base = I18N[lang] || I18N.fr;
    var conf = (window.RM_SERVICES && window.RM_SERVICES.transfer) || {};
    var cartPrice = conf.formulas && typeof conf.formulas.cart_price === "number" ? conf.formulas.cart_price : null;
    var form = root.querySelector("form");
    var summary = root.querySelector("[data-tq-summary]");
    var waLink = root.querySelector("[data-tq-wa]");
    var recap = root.querySelector("[data-tq-recap]");
    var totalField = root.querySelector("[data-tq-total-field]");
    var depFields = root.querySelector("[data-tq-block='departure_fields']");

    function val(name) {
      var el = root.querySelector('[data-tq="' + name + '"]');
      return el ? el.value.trim() : "";
    }
    function checked(name) {
      var el = root.querySelector('[data-tq="' + name + '"]');
      return !!(el && el.checked && !el.disabled);
    }
    function formula() {
      var el = root.querySelector('[data-tq="formula"]:checked');
      return el && el.value === "online" ? "online" : "cash";
    }
    function setBlock(block, on) {
      if (!block) return;
      block.hidden = !on;
      Array.prototype.forEach.call(block.querySelectorAll("input, select"), function (el) {
        if (el.hasAttribute("data-tq-required")) {
          el.disabled = !on;
          if (on) el.setAttribute("required", "required");
          else el.removeAttribute("required");
        }
      });
    }
    function showFor(f) {
      Array.prototype.forEach.call(root.querySelectorAll("[data-tq-show]"), function (el) {
        el.hidden = el.getAttribute("data-tq-show") !== f;
      });
    }
    function dateTime(date, time) {
      return date && time ? date + " · " + time : (date || time || "");
    }
    function money(n) { return n + " €"; }

    function compute() {
      var f = formula();
      showFor(f);
      var wantsDep = f === "cash" || checked("want_dep");
      setBlock(depFields, wantsDep);

      var lines = [];
      var total = 0;
      var complete = true;
      var cartTbc = false;
      var aLabel = base.arrival + (dateTime(val("arr_date"), val("arr_time")) ? " (" + dateTime(val("arr_date"), val("arr_time")) + ")" : "");
      lines.push({ label: t.transfer + " — " + aLabel, free: true });
      lines.push({ label: t.cart + " — " + base.arrival, free: true });

      if (wantsDep) {
        var dLabel = base.departure + (dateTime(val("dep_date"), val("dep_time")) ? " (" + dateTime(val("dep_date"), val("dep_time")) + ")" : "");
        if (f === "cash") {
          lines.push({ label: t.transfer + " — " + dLabel, free: true });
          lines.push({ label: t.cart + " — " + base.departure, free: true });
        } else {
          var people = parseInt(val("dep_people"), 10) || 1;
          if (!val("dep_date") || !val("dep_time")) complete = false;
          if (checked("pay_dep")) {
            var rate = baseRate("airport", people);
            var sur = surcharge("airport", val("dep_time"), true);
            lines.push({ label: t.transfer + " — " + dLabel + " · " + people + " " + (lang === "fr" ? "pers." : lang === "es" ? "pers." : "people"), amount: rate === null ? 0 : rate });
            if (sur) lines.push({ label: t.night, amount: sur, sub: true });
            total += (rate || 0) + sur;
          }
          if (checked("pay_cart")) {
            if (cartPrice === null) { lines.push({ label: t.cart + " — " + base.departure, tbc: true }); cartTbc = true; }
            else { lines.push({ label: t.cart + " — " + base.departure, amount: cartPrice }); total += cartPrice; }
          }
        }
      }
      return { formula: f, wantsDep: wantsDep, lines: lines, total: total, complete: complete, cartTbc: cartTbc };
    }

    function amountText(l) {
      if (l.free) return t.offered;
      if (l.tbc) return t.cartTbc;
      return money(l.amount);
    }

    function render() {
      var q = compute();
      var html = '<ul class="rm-tq__lines">';
      q.lines.forEach(function (l) {
        html += '<li' + (l.sub ? ' class="rm-tq__line--sub"' : "") + "><span>" + l.label + "</span><span>" + amountText(l) + "</span></li>";
      });
      html += "</ul>";
      var paid = q.formula === "online" && q.wantsDep;
      if (paid) {
        html += '<p class="rm-tq__total"><span>' + t.total + "</span><span>" + money(q.total) + (q.cartTbc ? " + " + t.cart.toLowerCase() : "") + "</span></p>";
        html += '<p class="rm-tq__cash">' + t.cashNote + (q.complete ? "" : " " + t.incomplete) + "</p>";
      } else {
        html += '<p class="rm-tq__total rm-tq__total--free"><span>' + t.total + "</span><span>" + money(0) + "</span></p>";
        html += '<p class="rm-tq__cash">' + t.free + (q.formula === "online" ? " " + t.noDep : "") + "</p>";
      }
      summary.innerHTML = html;

      var lines = [brand(lang, t.waIntro), ""];
      lines.push(t.formula + " : " + (q.formula === "cash" ? t.cash : t.online));
      lines.push(t.lName + " : " + (val("name") || "…"));
      if (val("ref")) lines.push(t.lRef + " : " + val("ref"));
      lines.push(t.lPhone + " : " + (val("phone") || "…"));
      lines.push(base.arrival + " : " + (dateTime(val("arr_date"), val("arr_time")) || "…") +
        (val("arr_num") ? " — " + t.lFlight + " " + val("arr_num") : "") +
        (val("arr_airline") ? " (" + val("arr_airline") + ")" : "") + " — " + t.lPeople + " : " + (val("people") || "…"));
      if (q.wantsDep) {
        lines.push(base.departure + " : " + (dateTime(val("dep_date"), val("dep_time")) || "…") +
          (val("dep_num") ? " — " + t.lFlight + " " + val("dep_num") : "") +
          (val("dep_airline") ? " (" + val("dep_airline") + ")" : "") + " — " + t.lPeople + " : " + (val("dep_people") || "…"));
      } else {
        lines.push(base.departure + " : " + t.noDep);
      }
      lines.push("", t.lDetail + " : " + q.lines.map(function (l) { return l.label + " " + amountText(l); }).join(" · "));
      lines.push(t.lTotal + " : " + money(q.total) + (q.cartTbc ? " + " + t.cart.toLowerCase() + " (" + t.cartTbc + ")" : ""));
      lines.push(t.pending);

      var text = lines.join("\n");
      if (waLink) waLink.href = "https://wa.me/" + WHATSAPP_NUMBER + "?text=" + encodeURIComponent(text);
      if (recap) recap.value = text;
      if (totalField) totalField.value = money(q.total) + (q.cartTbc ? " + " + t.cart.toLowerCase() : "");
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
    Array.prototype.forEach.call(document.querySelectorAll("[data-rm-tq]"), function (root) {
      (root.getAttribute("data-tq-mode") === "formulas" ? initFormulas : init)(root);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
