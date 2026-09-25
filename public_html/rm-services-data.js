/* Riad Mylaya — SINGLE SOURCE OF TRUTH for every service price and option.
 *
 * Edit prices ONLY here. This file feeds the guest area
 * (/preparer-mon-sejour + en/es), the public transfer pages and the spa booking
 * form (spa-booking.js loads it on demand, since /hammam-spa does not include
 * it). The build script gen_stay.py parses the JSON below to print the rate
 * tables and teasers, so the object must stay valid JSON (no comments, no
 * trailing commas, no JS expressions).
 *
 * Amounts are euros unless "currency" says otherwise. Times are 24h "HH:MM".
 */
window.RM_SERVICES = {
  "currency": {
    "eur": "€",
    "mad": "MAD",
    "mad_per_eur": 10.8
  },

  "transfer": {
    "unit": "per_vehicle",
    "max_people": 8,
    "airport": {
      "tiers": [
        { "max": 4, "price": 20 },
        { "max": 6, "price": 25 },
        { "max": 7, "price": 30 },
        { "max": 8, "price": 35 }
      ],
      "surcharge": { "amount": 5, "arrival": { "from": "22:00", "to": "07:00" }, "departure": { "from": "22:00", "to": "07:00" } },
      "round_trip_discount": { "amount": 5, "leg": "departure" }
    },
    "station": {
      "tiers": [
        { "max": 2, "price": 12 },
        { "max": 4, "price": 17 },
        { "max": 6, "price": 20 }
      ],
      "surcharge": { "amount": 5, "departure": { "from": "03:00", "to": "07:45" } },
      "round_trip_discount": null
    }
  },

  "dinner": {
    "unit": "per_person",
    "max_people": 12,
    "slots": ["18:00", "18:30", "19:00", "19:30", "20:00", "20:30", "21:00", "21:30", "22:00", "22:30"],
    "options": [
      { "id": "full", "price": 25, "name": { "fr": "Menu complet (entrée + plat + dessert)", "en": "Full menu (starter + main + dessert)", "es": "Menú completo (entrante + plato + postre)" } },
      { "id": "starter_main", "price": 20, "name": { "fr": "Entrée + plat", "en": "Starter + main", "es": "Entrante + plato" } },
      { "id": "main_dessert", "price": 20, "name": { "fr": "Plat + dessert", "en": "Main + dessert", "es": "Plato + postre" } },
      { "id": "main", "price": 15, "name": { "fr": "Plat principal", "en": "Main course", "es": "Plato principal" } }
    ]
  },

  "cooking": {
    "unit": "per_person",
    "price": 45,
    "max_people": 6,
    "slots": ["10:30", "11:00", "11:30", "12:00", "12:30", "13:00", "13:30", "14:00", "14:30"]
  },

  "excursions": {
    "unit": "total",
    "max_people": 12,
    "items": [
      {
        "id": "3-vallees", "slug": "excursion-3-vallees", "pricing": "tiers", "min_people": 1,
        "tiers": { "1": 120, "2": 75, "3": 60, "4": 50 }, "km": 145,
        "name": { "fr": "Les 3 Vallées de l'Atlas", "en": "The 3 Atlas Valleys", "es": "Los 3 Valles del Atlas" },
        "duration": { "fr": "8 heures", "en": "8 hours", "es": "8 horas" }
      },
      {
        "id": "imlil", "slug": "excursion-imlil", "pricing": "tiers", "min_people": 1,
        "tiers": { "1": 130, "2": 75, "3": 60, "4": 50 }, "km": 80,
        "name": { "fr": "Randonnée d'Imlil dans le Haut Atlas", "en": "Imlil hike in the High Atlas", "es": "Senderismo en Imlil en el Alto Atlas" },
        "duration": { "fr": "8 heures", "en": "8 hours", "es": "8 horas" }
      },
      {
        "id": "agafay", "slug": "excursion-agafay", "pricing": "tiers", "min_people": 1,
        "tiers": { "1": 120, "2": 75, "3": 60, "4": 50 }, "km": 75,
        "name": { "fr": "Désert d'Agafay, lac Lalla Takerkoust & Atlas", "en": "Agafay desert, Lalla Takerkoust lake & Atlas", "es": "Desierto de Agafay, lago Lalla Takerkoust y Atlas" },
        "duration": { "fr": "8 heures", "en": "8 hours", "es": "8 horas" }
      },
      {
        "id": "dromadaire-agafay", "slug": "excursion-dromadaire-agafay", "pricing": "tiers", "min_people": 1,
        "tiers": { "1": 100, "2": 60, "3": 55, "4": 50 }, "km": 40,
        "name": { "fr": "Coucher de soleil & balade en dromadaire à Agafay", "en": "Sunset & camel ride in Agafay", "es": "Puesta de sol y paseo en dromedario en Agafay" },
        "duration": { "fr": "4-5 heures", "en": "4-5 hours", "es": "4-5 horas" }
      },
      {
        "id": "essaouira", "slug": "excursion-essaouira", "pricing": "essaouira", "min_people": 1,
        "tiers": {}, "km": 170,
        "name": { "fr": "Essaouira — côte atlantique", "en": "Essaouira — Atlantic coast", "es": "Essaouira — costa atlántica" },
        "duration": { "fr": "10h30", "en": "10h30", "es": "10h30" }
      },
      {
        "id": "ouzoud", "slug": "excursion-ouzoud", "pricing": "tiers", "min_people": 1,
        "tiers": { "1": 150, "2": 85, "3": 70, "4": 60 }, "km": 180,
        "name": { "fr": "Cascades d'Ouzoud", "en": "Ouzoud waterfalls", "es": "Cascadas de Ouzoud" },
        "duration": { "fr": "9h30", "en": "9h30", "es": "9h30" }
      },
      {
        "id": "ourika", "slug": "excursion-ourika", "pricing": "tiers", "min_people": 1,
        "tiers": { "1": 100, "2": 65, "3": 55, "4": 45 }, "km": 70,
        "name": { "fr": "Vallée de l'Ourika", "en": "Ourika valley", "es": "Valle de Ourika" },
        "duration": { "fr": "7 heures", "en": "7 hours", "es": "7 horas" }
      },
      {
        "id": "ouarzazate", "slug": "excursion-ouarzazate", "pricing": "tiers", "min_people": 2,
        "tiers": { "2": 110, "3": 95, "4": 80 }, "km": 200,
        "name": { "fr": "Ouarzazate & Aït Ben Haddou", "en": "Ouarzazate & Aït Ben Haddou", "es": "Ouarzazate y Aït Ben Haddou" },
        "duration": { "fr": "12 heures", "en": "12 hours", "es": "12 horas" }
      }
    ],
    "essaouira_rule": { "up_to_3": 120, "four": 140, "extra_person": 24 }
  },

  "spa": {
    "unit": "per_person",
    "currency": "MAD",
    "opening": "10:30 - 18:30",
    "max_people": 8,
    "slot_step_minutes": 30,
    "categories": [
      {
        "id": "Hammam + Massage",
        "name": { "fr": "Rituels Hammam + Massage", "en": "Hammam + Massage Rituals", "es": "Rituales Hammam + Masaje" },
        "items": [
          { "code": "HMS1", "name": "Mythic massage 1h30", "duration": "2h05", "price_mad": 1030, "oil": "Huile douce" },
          { "code": "HMS2", "name": "Mythic massage premium", "duration": "2h05", "price_mad": 1130, "oil": "Baume" },
          { "code": "HMA1", "name": "Ayurvédique", "duration": "1h50", "price_mad": 930, "oil": "Huile chaude" },
          { "code": "HMA2", "name": "Ayurvédique premium", "duration": "1h50", "price_mad": 1030, "oil": "Baume chaud" },
          { "code": "HMR1", "name": "Relaxant 1h", "duration": "1h35", "price_mad": 770, "oil": "Huile douce" },
          { "code": "HMR2", "name": "Relaxant premium", "duration": "1h35", "price_mad": 870, "oil": "Baume" },
          { "code": "HMZ1", "name": "Relaxant 1h15", "duration": "1h50", "price_mad": 880, "oil": "Huile douce" },
          { "code": "HMZ2", "name": "Relaxant premium 1h15", "duration": "1h50", "price_mad": 980, "oil": "Baume" }
        ]
      },
      {
        "id": "Massage",
        "name": { "fr": "Massages", "en": "Massages", "es": "Masajes" },
        "items": [
          { "code": "MS1", "name": "Mythic 1h30", "duration": "1h30", "price_mad": 750, "oil": "Huile douce" },
          { "code": "MS2", "name": "Mythic premium", "duration": "1h30", "price_mad": 850, "oil": "Baume" },
          { "code": "MA1", "name": "Ayurvédique", "duration": "1h15", "price_mad": 650, "oil": "Huile chaude" },
          { "code": "MA2", "name": "Ayurvédique premium", "duration": "1h15", "price_mad": 750, "oil": "Baume" },
          { "code": "MR1", "name": "Relaxant 1h", "duration": "1h", "price_mad": 470, "oil": "Huile douce" },
          { "code": "MR2", "name": "Relaxant premium", "duration": "1h", "price_mad": 570, "oil": "Baume" }
        ]
      },
      {
        "id": "Visage",
        "name": { "fr": "Soins du visage", "en": "Facial Treatments", "es": "Tratamientos faciales" },
        "items": [
          { "code": "V1", "name": "Soin anti-âge", "duration": "50min", "price_mad": 550 },
          { "code": "V2", "name": "Soin acide hyaluronique", "duration": "50min", "price_mad": 680 }
        ]
      },
      {
        "id": "Hammam",
        "name": { "fr": "Hammam traditionnel", "en": "Traditional Hammam", "es": "Hammam tradicional" },
        "items": [
          { "code": "H1", "name": "Hammam beldi", "duration": "40min", "price_mad": 450, "oil": "Huile douce" },
          { "code": "H2", "name": "Hammam premium", "duration": "40min", "price_mad": 550, "oil": "Baume" }
        ]
      },
      {
        "id": "Extras",
        "name": { "fr": "Extras & suppléments", "en": "Extras & Add-ons", "es": "Extras y complementos" },
        "extras": true,
        "items": [
          { "code": "ADD1", "name": "Rhassoul", "duration": "15min", "price_mad": 100 },
          { "code": "ADD2", "name": "Bain oriental", "duration": "15min", "price_mad": 120 },
          { "code": "ADD3", "name": "Massage 15min", "duration": "15min", "price_mad": 125 },
          { "code": "ADD4", "name": "Massage premium 15min", "duration": "15min", "price_mad": 150 },
          { "code": "ADD5", "name": "Massage 30min", "duration": "30min", "price_mad": 240 },
          { "code": "ADD6", "name": "Massage premium 30min", "duration": "30min", "price_mad": 290 }
        ]
      }
    ]
  }
};
