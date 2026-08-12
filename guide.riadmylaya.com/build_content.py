#!/usr/bin/env python3
"""Build content/guide.json (FR + EN) for the Riad Mylaya digital guest guide."""
import json, pathlib, urllib.parse

IMG = "/assets/images/"
WA = "https://wa.me/212661351989?text="
MAPS = "https://maps.app.goo.gl/f6u2Dhybbw8uzf637"
TRIPADVISOR = ("https://www.tripadvisor.fr/Hotel_Review-g293734-d23740759-Reviews-"
               "Riad_Mylaya-Marrakech_Marrakech_Safi.html")
BOOKING = ("https://portal.freetobook.com/reservations?w_id=45823&w_tkn="
           "WyeaTPwj6MSYcDxNHIPuXgqvOYtlIS2H086gviFbewghhARIYxSGLtJxULb49")


def wa(msg):
    return WA + urllib.parse.quote(msg)


# --------------------------------------------------------------------------
# Excursions & services offered by the riad (from riadmylaya.com/excursions
# and /activities) — no invented prices, everything is "on request".
# --------------------------------------------------------------------------
EXCURSIONS = [
    ("Les 3 Vallées de l'Atlas", "The 3 Valleys of the Atlas",
     "Journée dans les vallées berbères de l'Atlas, villages traditionnels et paysages de montagne.",
     "A day through the Berber valleys of the Atlas, traditional villages and mountain landscapes."),
    ("Randonnée d'Imlil dans le Haut Atlas", "Imlil trek in the High Atlas",
     "Randonnée au pied du Toubkal, déjeuner chez l'habitant possible.",
     "Trekking at the foot of Mount Toubkal, with an optional lunch with a local family."),
    ("Désert d'Agafay & Lac Lalla Takerkoust", "Agafay Desert & Lalla Takerkoust Lake",
     "Désert de pierres à 40 min de Marrakech, lac et vues sur l'Atlas.",
     "The stone desert 40 min from Marrakech, the lake and views over the Atlas."),
    ("Coucher de soleil & balade en dromadaire", "Sunset & camel ride",
     "Balade à dos de dromadaire dans l'Agafay au coucher du soleil.",
     "A camel ride across the Agafay desert at sunset."),
    ("Essaouira – Côte Atlantique", "Essaouira – Atlantic coast",
     "Journée à Essaouira, sa médina classée, son port et ses plages.",
     "A day in Essaouira, its UNESCO medina, harbour and beaches."),
    ("Cascades d'Ouzoud", "Ouzoud Waterfalls",
     "Les plus hautes cascades du Maroc, singes en liberté et déjeuner au bord de l'eau.",
     "Morocco's highest waterfalls, wild monkeys and lunch by the water."),
    ("Vallée de l'Ourika", "Ourika Valley",
     "Rivière, jardins et villages berbères à moins d'une heure du riad.",
     "River, gardens and Berber villages less than an hour from the riad."),
    ("Ouarzazate & Aït Ben Haddou", "Ouarzazate & Aït Ben Haddou",
     "Traversée du col du Tichka et visite du ksar classé au patrimoine mondial.",
     "Crossing the Tichka pass and visiting the UNESCO-listed ksar."),
]

SERVICES = [
    ("Hammam & Massage", "Hammam & Massage",
     "Hammam traditionnel à l'extérieur du Riad – sur réservation. Massage à l'huile d'argan également possible.",
     "Traditional hammam outside the Riad – on request. Argan-oil massage also available.",
     "Bonjour, je souhaite réserver un hammam / massage au Riad Mylaya.",
     "Hello, I would like to book a hammam / massage at Riad Mylaya."),
    ("Découverte de Marrakech", "Discover Marrakech",
     "Visite guidée de la médina, des souks et des monuments avec un guide officiel.",
     "Guided tour of the medina, souks and monuments with an official guide.",
     "Bonjour, je souhaite organiser une visite guidée de Marrakech.",
     "Hello, I would like to arrange a guided tour of Marrakech."),
    ("Photographe & Vidéo", "Photographer & Video",
     "Séance photo professionnelle au riad ou dans la médina (couples, familles, demandes en mariage).",
     "Professional photo shoot at the riad or in the medina (couples, families, proposals).",
     "Bonjour, je suis intéressé(e) par une séance photo / vidéo.",
     "Hello, I am interested in a photo / video session."),
    ("Blanchisserie", "Laundry",
     "Service de lavage et repassage, remis sous 24 h.",
     "Washing and ironing service, returned within 24 hours.",
     "Bonjour, je souhaite utiliser le service de blanchisserie.",
     "Hello, I would like to use the laundry service."),
    ("Location de voiture & chauffeur", "Car & driver hire",
     "Voiture avec chauffeur à la journée pour vos déplacements.",
     "Car with driver by the day for your trips.",
     "Bonjour, je souhaite réserver une voiture avec chauffeur.",
     "Hello, I would like to book a car with a driver."),
    ("Occasions spéciales", "Special occasions",
     "Anniversaire, lune de miel, demande en mariage : décoration, fleurs et gâteau sur demande.",
     "Birthday, honeymoon, proposal: decoration, flowers and cake on request.",
     "Bonjour, je souhaite organiser une occasion spéciale au Riad Mylaya.",
     "Hello, I would like to arrange a special occasion at Riad Mylaya."),
]


def excursion_cards(lang):
    cards = []
    for fr_t, en_t, fr_d, en_d in EXCURSIONS:
        title = fr_t if lang == "fr" else en_t
        text = fr_d if lang == "fr" else en_d
        msg = (f"Bonjour, je suis intéressé(e) par l'excursion : {fr_t}."
               if lang == "fr" else
               f"Hello, I am interested in the excursion: {en_t}.")
        label = "Je suis intéressé(e)" if lang == "fr" else "I'm interested"
        cards.append({"title": title, "text": text,
                      "actions": [{"label": label, "href": wa(msg), "external": True}]})
    return cards


def service_cards(lang):
    cards = []
    for fr_t, en_t, fr_d, en_d, fr_m, en_m in SERVICES:
        title = fr_t if lang == "fr" else en_t
        text = fr_d if lang == "fr" else en_d
        msg = fr_m if lang == "fr" else en_m
        label = "Demander ce service" if lang == "fr" else "Request this service"
        cards.append({"title": title, "text": text,
                      "actions": [{"label": label, "href": wa(msg), "external": True}]})
    return cards


# --------------------------------------------------------------------------
FR = {
    "meta": {"title": "Guide d'accueil - Riad Mylaya"},
    "hero": {
        "eyebrow": "Livret d'accueil digital",
        "title": "Bienvenue au Riad Mylaya",
        "subtitle": "Toutes les informations utiles pour profiter simplement de votre séjour au cœur de la médina de Marrakech.",
        "primaryAction": "Voir le Wi‑Fi",
        "secondaryAction": "Nous contacter",
    },
    "arrivalCard": {
        "label": "Arrivée / départ",
        "times": "Arrivée dès 14h · Départ avant 12h",
        "link": "Voir les détails",
    },
    "footer": {
        "backToTop": "Retour en haut",
        "note": "Guide digital conçu pour les voyageurs du Riad Mylaya · 163 Derb Bounba, Arset Ihiri — Médina, Marrakech",
    },
    "ui": {"copyPassword": "Copier le mot de passe", "passwordCopied": "Mot de passe copié."},
    "sections": [
        {
            "id": "welcome", "shortTitle": "Bienvenue", "type": "intro",
            "eyebrow": "Accueil", "title": "Bonjour et bienvenue",
            "lead": "Nous sommes heureux de vous accueillir au Riad Mylaya.",
            "body": [
                "Nous espérons que vous passerez un séjour mémorable et que vous repartirez avec de merveilleux souvenirs de Marrakech.",
                "Ce guide rassemble tout ce dont vous avez besoin : horaires, services, transferts, excursions et contacts. En cas de besoin, écrivez-nous à tout moment sur WhatsApp.",
            ],
            "blocks": [
                {
                    "eyebrow": "La maison", "title": "Riad Mylaya",
                    "lead": "Une maison d'hôtes traditionnelle, pensée comme un foyer, au cœur de la médina.",
                    "image": IMG + "patio.jpg",
                    "imageAlt": "Patio du Riad Mylaya",
                    "features": {
                        "title": "Les espaces à votre disposition",
                        "list": [
                            "Patio intérieur et salons de détente",
                            "Terrasse avec 3 chaises longues et deux salons pour se reposer",
                            "Chambres authentiques, climatisées et chauffées, équipées d'un coffre-fort",
                            "Petit-déjeuner marocain fait maison",
                        ],
                    },
                },
                {
                    "eyebrow": "Votre équipe", "title": "Une équipe à votre écoute",
                    "lead": "Nous sommes présents tous les jours pour vous aider.",
                    "image": IMG + "salon.jpg",
                    "imageAlt": "Salon traditionnel du Riad Mylaya",
                    "text": "Réservation d'un taxi, conseils sur les souks, réservation d'un dîner ou d'une excursion : n'hésitez jamais à nous solliciter. Un simple message WhatsApp suffit, et nous vous répondons en français, anglais, espagnol ou arabe.",
                },
            ],
        },
        {
            "id": "arrival", "shortTitle": "Arrivée", "type": "times",
            "eyebrow": "Séjour", "title": "Arrivée & départ",
            "lead": "Réservez votre transfert aéroport en quelques clics et profitez de notre service bagages.",
            "times": [
                {"label": "Arrivée", "value": "À partir de 14h", "detail": "Arrivée anticipée possible selon disponibilité — prévenez-nous."},
                {"label": "Départ", "value": "Avant 12h", "detail": "Départ tardif possible sur demande, selon disponibilité."},
                {"label": "Arrivée tardive", "value": "24h/24", "detail": "Nous vous accueillons à toute heure : indiquez-nous votre heure d'arrivée."},
            ],
            "transferForm": {
                "bookButton": "Réserver le transfert aéroport",
                "subtitle": "Organisez votre transfert en quelques clics",
                "features": [
                    {"icon": "✈️", "label": "Arrivée à l'aéroport", "detail": "Notre chauffeur vous attend avec une pancarte à votre nom"},
                    {"icon": "🛫", "label": "Départ vers l'aéroport", "detail": "Nous vous raccompagnons à l'heure qui vous convient"},
                    {"icon": "🧭", "label": "Accompagnement jusqu'au riad", "detail": "Le riad est piéton : on vous guide sur les derniers mètres"},
                    {"icon": "👤", "label": "Chauffeur professionnel", "detail": "Véhicule climatisé, service fiable et ponctuel"},
                ],
                "cta": "Réserver maintenant",
                "badge": "Réponse rapide de l'équipe du riad",
                "title": "Réservation transfert aéroport",
                "transferTypeLabel": "Type de transfert",
                "transferTypeOptions": {"both": "Arrivée et départ", "arrival": "Arrivée uniquement", "departure": "Départ uniquement"},
                "arrivalHeading": "Arrivée",
                "departureHeading": "Départ",
                "fields": {
                    "name": "Nom et prénom", "email": "E-mail",
                    "phone": "Numéro de téléphone (avec indicatif)", "guests": "Nombre de personnes",
                    "arrivalDate": "Date d'arrivée", "arrivalTime": "Heure d'atterrissage",
                    "arrivalFlight": "Numéro de vol",
                    "departureDate": "Date de départ", "departureTime": "Heure souhaitée de départ du riad",
                    "departureFlight": "Numéro de vol de départ (si disponible)",
                },
                "submit": "Envoyer la demande",
                "success": "Merci ! Votre demande de transfert a bien été envoyée. Nous vous confirmerons rapidement.",
                "close": "Fermer",
            },
            "items": [
                {"title": "Service bagages", "text": "Vous arrivez tôt ou repartez tard ? Nous gardons vos bagages gratuitement avant l'enregistrement et après le départ.",
                 "actions": [{"label": "Demander ce service", "href": wa("Bonjour, je souhaite utiliser le service bagages du Riad Mylaya."), "external": True}]},
                {"title": "Arrivée anticipée / départ tardif", "text": "Selon la disponibilité des chambres, nous faisons notre maximum pour vous accueillir plus tôt ou vous laisser partir plus tard.",
                 "actions": [{"label": "Faire la demande", "href": wa("Bonjour, je souhaite demander une arrivée anticipée / un départ tardif."), "external": True}]},
                {"title": "Nous rejoindre à pied", "text": "Le riad se situe dans une ruelle piétonne. Prévenez-nous 15 minutes avant votre arrivée : quelqu'un vient vous chercher au bout de la rue.",
                 "actions": [{"label": "Prévenir de mon arrivée", "href": wa("Bonjour, j'arrive au Riad Mylaya dans environ 15 minutes."), "external": True}]},
            ],
        },
        {
            "id": "breakfast", "shortTitle": "Petit-déj", "type": "times",
            "eyebrow": "Gastronomie", "title": "Petit-déjeuner",
            "lead": "Un petit-déjeuner marocain fait maison, inclus dans votre séjour.",
            "times": [
                {"label": "Horaires", "value": "7h15 – 10h30", "detail": "Servi tous les jours."},
                {"label": "Où", "value": "Patio ou terrasse", "detail": "Vous choisissez l'endroit qui vous plaît le plus."},
                {"label": "Formule", "value": "Inclus", "detail": "Crêpes marocaines, pain maison, confitures, œufs, jus frais, thé, café et autres spécialités selon le jour."},
            ],
            "items": [
                {"title": "Petit-déjeuner plus tôt ou plus tard ?",
                 "text": ("<strong>Vous partez tôt pour une excursion ?</strong><br>"
                          "Avec <strong>notre organisation</strong> : votre petit-déjeuner peut être servi à l'heure souhaitée, "
                          "même très tôt le matin, en fonction de votre heure de départ.<br><br>"
                          "Avec <strong>d'autres organisateurs</strong> : le petit-déjeuner est servi à partir de 7h15. "
                          "Pour un départ plus tôt, <strong>avant 7h15, le petit-déjeuner sera préparé à emporter</strong>.<br><br>"
                          "<strong>Vous préférez faire la grasse matinée ?</strong><br>"
                          "Nous pouvons décaler l'heure de votre petit-déjeuner après 10h30, sur simple demande, "
                          "avec un supplément de 6 € par personne."),
                 "actions": [{"label": "Demander un horaire", "href": wa("Bonjour, je souhaite demander un horaire particulier pour mon petit-déjeuner :"), "external": True}]},
                {"title": "Régime particulier ou allergie", "text": "Végétarien, sans gluten, sans lactose : prévenez-nous la veille et nous adaptons votre petit-déjeuner.",
                 "actions": [{"label": "Signaler un régime", "href": wa("Bonjour, j'ai un régime alimentaire particulier pour le petit-déjeuner :"), "external": True}]},
            ],
        },
        {
            "id": "dinner", "shortTitle": "Dîner", "type": "dinner",
            "eyebrow": "Gastronomie", "title": "Dîner marocain au riad",
            "lead": "Savourez un repas marocain authentique sans quitter le Riad.",
            "image": IMG + "dinner.jpg",
            "imageAlt": "Dîner traditionnel servi au Riad Mylaya",
            "menus": [
                {"icon": "🍲", "label": "Menu complet", "detail": "Entrée + plat + dessert", "price": "25 €"},
                {"icon": "🍴", "label": "Entrée + plat", "detail": "Entrée et plat au choix", "price": "20 €"},
                {"icon": "🍽️", "label": "Plat + dessert", "detail": "Plat principal et dessert", "price": "20 €"},
                {"icon": "☕", "label": "Plat principal", "detail": "Un plat savoureux au choix", "price": "15 €"},
            ],
            "dinnerHeader": {"title": "Réserver le dîner", "subtitle": "Merci de nous prévenir avant 16h le jour même"},
            "bookButton": "Réserver maintenant",
            "perPerson": "/ personne",
            "badges": [
                {"icon": "🍳", "text": "Réservation facile en quelques clics"},
                {"icon": "🌿", "text": "Produits frais et locaux"},
                {"icon": "❤️", "text": "Préparé avec passion"},
            ],
            "form": {
                "title": "Réservation dîner",
                "fields": {"name": "Nom", "email": "E-mail", "date": "Date souhaitée", "time": "Heure du dîner",
                           "guests": "Nombre de personnes", "menu": "Choix de la formule",
                           "message": "Message / allergies / préférences alimentaires"},
                "menuOptions": [
                    {"label": "Menu complet (entrée + plat + dessert)", "price": 25},
                    {"label": "Entrée + plat", "price": 20},
                    {"label": "Plat + dessert", "price": 20},
                    {"label": "Plat principal", "price": 15},
                ],
                "currency": "€", "currencyBefore": False, "perPerson": "/ personne",
                "summary": {"guestsLabel": "Nombre de personnes", "priceLabel": "Prix", "totalLabel": "Total estimé",
                            "placeholder": "Choisissez une formule pour voir le total."},
                "submit": "Envoyer la réservation",
                "success": "Merci ! Votre demande de dîner a bien été envoyée. Nous vous confirmerons rapidement.",
                "close": "Fermer",
            },
        },
        {
            "id": "cooking", "shortTitle": "Cours de cuisine", "type": "dinner",
            "eyebrow": "Expérience", "title": "Cours de cuisine marocaine",
            "lead": "Plongez au cœur de la gastronomie marocaine et vivez une expérience culinaire authentique au Riad Mylaya.",
            "image": IMG + "cooking.jpg",
            "imageAlt": "Cours de cuisine marocaine au Riad Mylaya",
            "body": [
                "Accompagné par notre équipe, vous apprendrez à préparer différentes spécialités marocaines et découvrirez les secrets des épices, les méthodes traditionnelles et les gestes transmis de génération en génération.",
                "À la fin du cours, installez-vous autour de la table et dégustez ensemble le repas que vous aurez préparé dans une ambiance chaleureuse et conviviale.",
            ],
            "listTitle": "Pourquoi choisir cette expérience ?",
            "list": [
                "✨ Expérience authentique dans un riad traditionnel",
                "👥 Petit groupe – maximum 6 personnes",
                "👨‍🍳 Accompagnement par notre équipe",
                "🍽️ Recettes traditionnelles marocaines",
                "🌿 Produits et ingrédients inclus",
                "🍵 Thé à la menthe inclus",
                "🍴 Repas préparé et dégusté sur place",
            ],
            "dinnerHeader": {"title": "Réserver le cours", "subtitle": "À réserver au moins 24h à l'avance"},
            "priceBanner": {"amount": "45 € par personne",
                            "note": "Tout est inclus : ingrédients, accompagnement, thé à la menthe et repas complet."},
            "bookButton": "Réserver maintenant – 45 € / personne",
            "bookButtonIcon": "👨‍🍳",
            "badges": [
                {"icon": "👥", "text": "Petit groupe – maximum 6 personnes"},
                {"icon": "🌿", "text": "Ingrédients et thé à la menthe inclus"},
                {"icon": "🍽️", "text": "Vous dégustez ce que vous cuisinez"},
            ],
            "form": {
                "title": "Réservation cours de cuisine",
                "fields": {"name": "Nom complet", "email": "E-mail", "phone": "WhatsApp / téléphone",
                           "language": "Langue préférée", "date": "Date souhaitée", "time": "Heure de début",
                           "guests": "Nombre de personnes (maximum 6)",
                           "diet": "Allergies / régime alimentaire",
                           "message": "Demandes particulières"},
                "fieldOrder": [
                    {"name": "name", "type": "text", "required": True},
                    {"name": "email", "type": "email", "required": True},
                    {"name": "phone", "type": "tel", "required": True},
                    {"name": "language", "type": "select", "required": True,
                     "options": ["Français", "English", "Español", "Italiano", "العربية"]},
                    {"name": "date", "type": "date", "required": True},
                    {"name": "time", "type": "time", "required": True},
                    {"name": "guests", "type": "number", "required": True, "min": 1, "max": 6},
                    {"name": "diet", "type": "textarea"},
                    {"name": "message", "type": "textarea"},
                ],
                "unitPrice": 45,
                "currency": "€", "currencyBefore": False, "perPerson": "/ personne",
                "summary": {"guestsLabel": "Nombre de personnes", "priceLabel": "Prix", "totalLabel": "Total estimé",
                            "placeholder": "Indiquez le nombre de personnes pour voir le total."},
                "submit": "Envoyer la réservation",
                "success": "Merci ! Votre demande de cours de cuisine a bien été envoyée.",
                "close": "Fermer",
            },
        },
        {
            "id": "transport", "shortTitle": "Transport", "type": "times",
            "eyebrow": "Se déplacer", "title": "Transferts & transport",
            "lead": "Tout ce qu'il faut savoir pour vous déplacer facilement depuis le riad.",
            "times": [
                {"label": "Aéroport Ménara", "value": "20–25 min", "detail": "Transfert privé organisé par le riad, sur réservation."},
                {"label": "Place Jemaa el-Fna", "value": "10 min à pied", "detail": "Le cœur de la médina est tout proche."},
                {"label": "Gare de Marrakech", "value": "15 min", "detail": "En taxi depuis l'entrée de la médina."},
            ],
            "items": [
                {"title": "Transfert aéroport", "text": "Chauffeur privé, véhicule climatisé, accueil avec pancarte à votre nom. Réservez directement depuis la section « Arrivée & départ ».",
                 "actions": [{"label": "Réserver un transfert", "href": wa("Bonjour, je souhaite réserver un transfert aéroport avec le Riad Mylaya."), "external": True}]},
                {"title": "Taxi en ville", "text": "Nous pouvons vous appeler un petit taxi à toute heure. Pensez à négocier le prix avant de monter, ou demandez-nous le tarif habituel.",
                 "actions": [{"label": "Demander un taxi", "href": wa("Bonjour, pouvez-vous m'appeler un taxi depuis le Riad Mylaya ?"), "external": True}]},
                {"title": "Voiture avec chauffeur", "text": "Pour une journée d'excursion ou plusieurs jours, nous organisons une voiture avec chauffeur francophone ou anglophone.",
                 "actions": [{"label": "Je suis intéressé(e)", "href": wa("Bonjour, je souhaite réserver une voiture avec chauffeur."), "external": True}]},
            ],
        },
        {
            "id": "excursions", "shortTitle": "Excursions",
            "eyebrow": "À découvrir", "title": "Excursions & activités",
            "lead": "Nous organisons pour vous les plus belles excursions au départ du riad. Tarifs et horaires communiqués sur demande, selon la saison et le nombre de participants.",
            "image": IMG + "excursions.jpg",
            "imageAlt": "Jardin Majorelle à Marrakech",
            "items": excursion_cards("fr"),
        },
        {
            "id": "services", "shortTitle": "Services",
            "eyebrow": "Au riad", "title": "Services supplémentaires",
            "lead": "Des petits plus pour rendre votre séjour encore plus agréable.",
            "items": service_cards("fr"),
        },
        {
            "id": "wifi", "shortTitle": "Wi‑Fi", "type": "wifi",
            "eyebrow": "Connexion", "title": "Wi‑Fi",
            "lead": "Le Wi‑Fi est gratuit et disponible dans tout le riad.",
            "networkLabel": "Réseau",
            "network": "Riad Mylaya",
            "passwordLabel": "Mot de passe",
            "password": "à demander à la réception",
        },
        {
            "id": "practical", "shortTitle": "Infos pratiques",
            "eyebrow": "Bon à savoir", "title": "Informations pratiques",
            "lead": "Quelques repères utiles pendant votre séjour à Marrakech.",
            "items": [
                {"title": "Électricité", "text": "220V, prises européennes (type C/E). Pensez à un adaptateur si vous venez du Royaume-Uni ou des États-Unis."},
                {"title": "Eau", "text": "Nous vous recommandons de boire de l'eau en bouteille. Une bouteille d'eau est offerte à l'arrivée."},
                {"title": "Climatisation & chauffage", "text": "Chaque chambre est équipée. Merci de fermer les fenêtres lorsque la climatisation fonctionne."},
                {"title": "Monnaie & paiement", "text": "La monnaie est le dirham (MAD). Des distributeurs se trouvent à 5 minutes à pied. Prévoyez du liquide pour les souks."},
                {"title": "Pharmacie & médecin", "text": "Une pharmacie se situe à proximité du riad. En cas de besoin médical, prévenez-nous : nous appelons un médecin qui se déplace au riad."},
                {"title": "Numéros d'urgence", "text": "Police : 19 · Pompiers / ambulance : 15 · Police touristique : +212 524 38 46 01"},
            ],
        },
        {
            "id": "access", "shortTitle": "Accès & parking", "type": "map",
            "eyebrow": "Nous trouver", "title": "Parking & accès au riad",
            "lead": "Le riad se trouve dans une ruelle piétonne de la médina : les voitures ne peuvent pas arriver jusqu'à la porte.",
            "address": "Riad Mylaya — 163 Derb Bounba, Arset Ihiri, Médina, 40000 Marrakech, Maroc",
            "mapText": "🚗 Déposez-vous à l'entrée du derb, puis 2 minutes à pied. Prévenez-nous 15 min avant : nous venons vous chercher avec vos bagages.\n\n🅿️ Parking gardé payant à environ 5 minutes à pied (24h/24). Nous vous indiquons l'emplacement exact sur demande.",
            "actions": [
                {"label": "Ouvrir dans Google Maps", "href": MAPS, "external": True},
                {"label": "Besoin d'aide pour arriver", "href": wa("Bonjour, j'ai besoin d'aide pour trouver le Riad Mylaya."), "external": True},
            ],
        },
        {
            "id": "rules", "shortTitle": "Règlement", "type": "rules",
            "eyebrow": "Vivre ensemble", "title": "Règlement intérieur",
            "lead": "Quelques règles simples pour le confort de tous.",
            "items": [
                {"icon": "🔇", "color": "amber", "title": "Calme après 22h", "text": "Le riad est une maison ouverte : merci de baisser le ton sur le patio et la terrasse en soirée."},
                {"icon": "🚭", "color": "red", "title": "Non-fumeur", "text": "Il est interdit de fumer dans les chambres et les espaces intérieurs. La terrasse est à votre disposition."},
                {"icon": "👟", "color": "gray", "title": "Chaussures", "text": "Merci de retirer vos chaussures dans les salons tapissés, comme le veut la tradition marocaine."},
                {"icon": "🔑", "color": "gray", "title": "Clés & départ", "text": "Merci de remettre vos clés à l'équipe avant votre départ et de libérer la chambre avant 12h."},
                {"icon": "👶", "color": "amber", "title": "Enfants", "text": "Les escaliers et la terrasse ne sont pas sécurisés : les enfants doivent rester accompagnés."},
                {"icon": "💎", "color": "green", "title": "Objets de valeur", "text": "Un coffre est disponible sur demande. Le riad ne peut être tenu responsable des objets laissés dans les chambres."},
            ],
        },
        {
            "id": "contacts", "shortTitle": "Contact",
            "eyebrow": "Nous joindre", "title": "Contact & WhatsApp",
            "lead": "Une question, une envie, un imprévu ? Nous répondons rapidement, 7j/7.",
            "items": [
                {"title": "WhatsApp (le plus rapide)", "text": "+212 661 351 989",
                 "actions": [{"label": "Écrire sur WhatsApp", "href": wa("Bonjour, je séjourne au Riad Mylaya et j'ai une question."), "external": True}]},
                {"title": "Téléphone", "text": "+212 808 644 081 · +212 661 351 989",
                 "actions": [{"label": "Appeler le riad", "href": "tel:+212661351989", "external": False}]},
                {"title": "E-mail", "text": "contact@riadmylaya.com",
                 "actions": [{"label": "Envoyer un e-mail", "href": "mailto:contact@riadmylaya.com", "external": False}]},
                {"title": "Prolonger votre séjour", "text": "Envie de rester une nuit de plus ? Vérifiez nos disponibilités en direct, au meilleur prix.",
                 "actions": [{"label": "Voir les disponibilités", "href": BOOKING, "external": True}]},
            ],
        },
        {
            "id": "reviews", "shortTitle": "Avis", "type": "reviews",
            "eyebrow": "Merci", "title": "Votre avis compte",
            "lead": "",
            "heading": "Vous avez passé un bon séjour ?",
            "text": "Votre avis nous aide énormément et permet à d'autres voyageurs de nous découvrir. Cela ne prend qu'une minute — merci du fond du cœur !",
            "actions": [
                {"label": "Laisser un avis sur Google", "href": MAPS, "external": True},
                {"label": "Laisser un avis sur TripAdvisor", "href": TRIPADVISOR, "external": True},
            ],
        },
    ],
}

EN = {
    "meta": {"title": "Welcome Guide - Riad Mylaya"},
    "hero": {
        "eyebrow": "Digital welcome book",
        "title": "Welcome to Riad Mylaya",
        "subtitle": "Everything you need to enjoy your stay in the heart of the Marrakech medina.",
        "primaryAction": "See the Wi‑Fi",
        "secondaryAction": "Contact us",
    },
    "arrivalCard": {
        "label": "Check-in / check-out",
        "times": "Check-in from 2pm · Check-out before 12pm",
        "link": "See the details",
    },
    "footer": {
        "backToTop": "Back to top",
        "note": "Digital guide for the guests of Riad Mylaya · 163 Derb Bounba, Arset Ihiri — Medina, Marrakech",
    },
    "ui": {"copyPassword": "Copy the password", "passwordCopied": "Password copied."},
    "sections": [
        {
            "id": "welcome", "shortTitle": "Welcome", "type": "intro",
            "eyebrow": "Welcome", "title": "Hello and welcome",
            "lead": "We are delighted to welcome you to Riad Mylaya.",
            "body": [
                "We hope you will have a memorable stay and leave with wonderful memories of Marrakech.",
                "This guide gathers everything you need: opening times, services, transfers, excursions and contacts. If anything is missing, just message us on WhatsApp.",
            ],
            "blocks": [
                {
                    "eyebrow": "The house", "title": "Riad Mylaya",
                    "lead": "A traditional guest house, designed to feel like a home, in the heart of the medina.",
                    "image": IMG + "patio.jpg",
                    "imageAlt": "Patio of Riad Mylaya",
                    "features": {
                        "title": "Spaces available to you",
                        "list": [
                            "Inner patio and relaxation lounges",
                            "Terrace with 3 sun loungers and two seating areas to relax",
                            "Authentic rooms with air conditioning, heating and a safe",
                            "Homemade Moroccan breakfast",
                        ],
                    },
                },
                {
                    "eyebrow": "Your hosts", "title": "A team at your service",
                    "lead": "We are here every day to help you.",
                    "image": IMG + "salon.jpg",
                    "imageAlt": "Traditional lounge at Riad Mylaya",
                    "text": "Booking a taxi, advice about the souks, reserving a dinner or an excursion: never hesitate to ask. A simple WhatsApp message is enough, and we reply in English, French, Spanish or Arabic.",
                },
            ],
        },
        {
            "id": "arrival", "shortTitle": "Arrival", "type": "times",
            "eyebrow": "Your stay", "title": "Check-in & check-out",
            "lead": "Book your airport transfer in a few clicks and use our luggage service.",
            "times": [
                {"label": "Check-in", "value": "From 2:00 pm", "detail": "Early check-in possible subject to availability — please let us know."},
                {"label": "Check-out", "value": "Before 12:00 pm", "detail": "Late check-out possible on request, subject to availability."},
                {"label": "Late arrival", "value": "24/7", "detail": "We welcome you at any hour: just tell us your arrival time."},
            ],
            "transferForm": {
                "bookButton": "Book your airport transfer",
                "subtitle": "Arrange your transfer in a few clicks",
                "features": [
                    {"icon": "✈️", "label": "Airport pick-up", "detail": "Our driver waits for you with a sign with your name"},
                    {"icon": "🛫", "label": "Departure to the airport", "detail": "We take you back at the time that suits you"},
                    {"icon": "🧭", "label": "Walk to the riad", "detail": "The riad is car-free: we guide you on the last few metres"},
                    {"icon": "👤", "label": "Professional driver", "detail": "Air-conditioned vehicle, reliable and punctual"},
                ],
                "cta": "Book now",
                "badge": "Fast reply from the riad team",
                "title": "Airport transfer request",
                "transferTypeLabel": "Type of transfer",
                "transferTypeOptions": {"both": "Arrival and departure", "arrival": "Arrival only", "departure": "Departure only"},
                "arrivalHeading": "Arrival",
                "departureHeading": "Departure",
                "fields": {
                    "name": "Full name", "email": "E-mail",
                    "phone": "Phone number (with country code)", "guests": "Number of people",
                    "arrivalDate": "Arrival date", "arrivalTime": "Landing time",
                    "arrivalFlight": "Flight number",
                    "departureDate": "Departure date", "departureTime": "Preferred pick-up time at the riad",
                    "departureFlight": "Departure flight number (if known)",
                },
                "submit": "Send the request",
                "success": "Thank you! Your transfer request has been sent. We will confirm shortly.",
                "close": "Close",
            },
            "items": [
                {"title": "Luggage service", "text": "Arriving early or leaving late? We store your luggage free of charge before check-in and after check-out.",
                 "actions": [{"label": "Request this service", "href": wa("Hello, I would like to use the luggage service at Riad Mylaya."), "external": True}]},
                {"title": "Early check-in / late check-out", "text": "Depending on room availability, we do our best to welcome you earlier or let you leave later.",
                 "actions": [{"label": "Make a request", "href": wa("Hello, I would like to request an early check-in / late check-out."), "external": True}]},
                {"title": "Walking to the riad", "text": "The riad is in a pedestrian lane. Let us know 15 minutes before you arrive and someone will meet you at the end of the street.",
                 "actions": [{"label": "Tell us I'm arriving", "href": wa("Hello, I will arrive at Riad Mylaya in about 15 minutes."), "external": True}]},
            ],
        },
        {
            "id": "breakfast", "shortTitle": "Breakfast", "type": "times",
            "eyebrow": "Food", "title": "Breakfast",
            "lead": "A homemade Moroccan breakfast, included in your stay.",
            "times": [
                {"label": "Times", "value": "7:15 – 10:30 am", "detail": "Served every day."},
                {"label": "Where", "value": "Patio or terrace", "detail": "Choose whichever spot you prefer."},
                {"label": "Included", "value": "Yes", "detail": "Moroccan pancakes, homemade bread, jams, eggs, fresh juice, tea, coffee and other specialities depending on the day."},
            ],
            "items": [
                {"title": "Earlier or later breakfast?",
                 "text": ("<strong>Leaving early for an excursion?</strong><br>"
                          "With <strong>our own tours</strong>: your breakfast can be served at the time you wish, "
                          "even very early in the morning, depending on your departure time.<br><br>"
                          "With <strong>other tour operators</strong>: breakfast is served from 7:15 am. "
                          "For an earlier departure, <strong>before 7:15 am, breakfast will be prepared as a takeaway</strong>.<br><br>"
                          "<strong>Would you rather sleep in?</strong><br>"
                          "We can serve your breakfast after 10:30 am on request, with a supplement of €6 per person."),
                 "actions": [{"label": "Request a time", "href": wa("Hello, I would like to request a specific breakfast time:"), "external": True}]},
                {"title": "Special diet or allergy", "text": "Vegetarian, gluten-free, lactose-free: let us know the day before and we will adapt your breakfast.",
                 "actions": [{"label": "Tell us about a diet", "href": wa("Hello, I have a special dietary requirement for breakfast:"), "external": True}]},
            ],
        },
        {
            "id": "dinner", "shortTitle": "Dinner", "type": "dinner",
            "eyebrow": "Food", "title": "Moroccan dinner at the riad",
            "lead": "Enjoy an authentic Moroccan meal without leaving the Riad.",
            "image": IMG + "dinner.jpg",
            "imageAlt": "Traditional dinner served at Riad Mylaya",
            "menus": [
                {"icon": "🍲", "label": "Full menu", "detail": "Starter + main course + dessert", "price": "€25"},
                {"icon": "🍴", "label": "Starter + main", "detail": "Starter and main course of your choice", "price": "€20"},
                {"icon": "🍽️", "label": "Main + dessert", "detail": "Main course and dessert", "price": "€20"},
                {"icon": "☕", "label": "Main course", "detail": "One tasty dish of your choice", "price": "€15"},
            ],
            "dinnerHeader": {"title": "Book dinner", "subtitle": "Please let us know before 4pm on the day"},
            "bookButton": "Book now",
            "perPerson": "/ person",
            "badges": [
                {"icon": "🍳", "text": "Easy booking in a few clicks"},
                {"icon": "🌿", "text": "Fresh, local produce"},
                {"icon": "❤️", "text": "Prepared with passion"},
            ],
            "form": {
                "title": "Dinner reservation",
                "fields": {"name": "Name", "email": "E-mail", "date": "Preferred date", "time": "Dinner time",
                           "guests": "Number of people", "menu": "Choice of menu",
                           "message": "Message / allergies / dietary preferences"},
                "menuOptions": [
                    {"label": "Full menu (starter + main + dessert)", "price": 25},
                    {"label": "Starter + main", "price": 20},
                    {"label": "Main + dessert", "price": 20},
                    {"label": "Main course", "price": 15},
                ],
                "currency": "€", "currencyBefore": True, "perPerson": "/ person",
                "summary": {"guestsLabel": "Number of people", "priceLabel": "Price", "totalLabel": "Estimated total",
                            "placeholder": "Choose a menu to see the total."},
                "submit": "Send the reservation",
                "success": "Thank you! Your dinner request has been sent. We will confirm shortly.",
                "close": "Close",
            },
        },
        {
            "id": "cooking", "shortTitle": "Cooking class", "type": "dinner",
            "eyebrow": "Experience", "title": "Moroccan cooking class",
            "lead": "Dive into the heart of Moroccan cuisine and enjoy an authentic culinary experience at Riad Mylaya.",
            "image": IMG + "cooking.jpg",
            "imageAlt": "Moroccan cooking class at Riad Mylaya",
            "body": [
                "Guided by our team, you will learn to prepare several Moroccan specialities and discover the secrets of the spices, the traditional methods and the gestures passed down from generation to generation.",
                "At the end of the class, sit down together around the table and enjoy the meal you have prepared, in a warm and friendly atmosphere.",
            ],
            "listTitle": "Why choose this experience?",
            "list": [
                "✨ An authentic experience in a traditional riad",
                "👥 Small group – maximum 6 people",
                "👨‍🍳 Guided by our team",
                "🍽️ Traditional Moroccan recipes",
                "🌿 Produce and ingredients included",
                "🍵 Mint tea included",
                "🍴 Meal cooked and enjoyed on site",
            ],
            "dinnerHeader": {"title": "Book the class", "subtitle": "To be booked at least 24h in advance"},
            "priceBanner": {"amount": "€45 per person",
                            "note": "Everything is included: ingredients, guidance, mint tea and the full meal."},
            "bookButton": "Book now – €45 / person",
            "bookButtonIcon": "👨‍🍳",
            "badges": [
                {"icon": "👥", "text": "Small group – maximum 6 people"},
                {"icon": "🌿", "text": "Ingredients and mint tea included"},
                {"icon": "🍽️", "text": "You eat what you cook"},
            ],
            "form": {
                "title": "Cooking class reservation",
                "fields": {"name": "Full name", "email": "E-mail", "phone": "WhatsApp / phone",
                           "language": "Preferred language", "date": "Preferred date", "time": "Start time",
                           "guests": "Number of people (maximum 6)",
                           "diet": "Allergies / dietary requirements",
                           "message": "Special requests"},
                "fieldOrder": [
                    {"name": "name", "type": "text", "required": True},
                    {"name": "email", "type": "email", "required": True},
                    {"name": "phone", "type": "tel", "required": True},
                    {"name": "language", "type": "select", "required": True,
                     "options": ["English", "Français", "Español", "Italiano", "العربية"]},
                    {"name": "date", "type": "date", "required": True},
                    {"name": "time", "type": "time", "required": True},
                    {"name": "guests", "type": "number", "required": True, "min": 1, "max": 6},
                    {"name": "diet", "type": "textarea"},
                    {"name": "message", "type": "textarea"},
                ],
                "unitPrice": 45,
                "currency": "€", "currencyBefore": True, "perPerson": "/ person",
                "summary": {"guestsLabel": "Number of people", "priceLabel": "Price", "totalLabel": "Estimated total",
                            "placeholder": "Enter the number of people to see the total."},
                "submit": "Send the reservation",
                "success": "Thank you! Your cooking class request has been sent.",
                "close": "Close",
            },
        },
        {
            "id": "transport", "shortTitle": "Transport", "type": "times",
            "eyebrow": "Getting around", "title": "Transfers & transport",
            "lead": "Everything you need to move around easily from the riad.",
            "times": [
                {"label": "Menara Airport", "value": "20–25 min", "detail": "Private transfer arranged by the riad, on request."},
                {"label": "Jemaa el-Fna square", "value": "10 min walk", "detail": "The heart of the medina is very close."},
                {"label": "Marrakech station", "value": "15 min", "detail": "By taxi from the edge of the medina."},
            ],
            "items": [
                {"title": "Airport transfer", "text": "Private driver, air-conditioned vehicle, welcome with a name sign. Book it directly from the “Check-in & check-out” section.",
                 "actions": [{"label": "Book a transfer", "href": wa("Hello, I would like to book an airport transfer with Riad Mylaya."), "external": True}]},
                {"title": "Taxi in town", "text": "We can call you a petit taxi at any time. Agree the fare before getting in, or ask us the usual price.",
                 "actions": [{"label": "Ask for a taxi", "href": wa("Hello, could you call me a taxi from Riad Mylaya?"), "external": True}]},
                {"title": "Car with driver", "text": "For a day trip or several days, we arrange a car with an English- or French-speaking driver.",
                 "actions": [{"label": "I'm interested", "href": wa("Hello, I would like to book a car with a driver."), "external": True}]},
            ],
        },
        {
            "id": "excursions", "shortTitle": "Excursions",
            "eyebrow": "Discover", "title": "Excursions & activities",
            "lead": "We arrange the finest excursions departing from the riad. Prices and times are given on request, depending on the season and number of guests.",
            "image": IMG + "excursions.jpg",
            "imageAlt": "Majorelle Garden in Marrakech",
            "items": excursion_cards("en"),
        },
        {
            "id": "services", "shortTitle": "Services",
            "eyebrow": "At the riad", "title": "Additional services",
            "lead": "Little extras to make your stay even more enjoyable.",
            "items": service_cards("en"),
        },
        {
            "id": "wifi", "shortTitle": "Wi‑Fi", "type": "wifi",
            "eyebrow": "Connection", "title": "Wi‑Fi",
            "lead": "Wi‑Fi is free and available throughout the riad.",
            "networkLabel": "Network",
            "network": "Riad Mylaya",
            "passwordLabel": "Password",
            "password": "please ask at reception",
        },
        {
            "id": "practical", "shortTitle": "Practical info",
            "eyebrow": "Good to know", "title": "Practical information",
            "lead": "A few useful pointers during your stay in Marrakech.",
            "items": [
                {"title": "Electricity", "text": "220V, European sockets (type C/E). Bring an adapter if you come from the UK or the US."},
                {"title": "Water", "text": "We recommend drinking bottled water. A bottle of water is offered on arrival."},
                {"title": "Air conditioning & heating", "text": "Every room is equipped. Please close the windows while the air conditioning is running."},
                {"title": "Currency & payment", "text": "The currency is the dirham (MAD). Cash machines are a 5-minute walk away. Bring cash for the souks."},
                {"title": "Pharmacy & doctor", "text": "There is a pharmacy near the riad. If you need medical help, tell us: we can call a doctor who comes to the riad."},
                {"title": "Emergency numbers", "text": "Police: 19 · Fire / ambulance: 15 · Tourist police: +212 524 38 46 01"},
            ],
        },
        {
            "id": "access", "shortTitle": "Access & parking", "type": "map",
            "eyebrow": "Finding us", "title": "Parking & access to the riad",
            "lead": "The riad is in a pedestrian lane of the medina: cars cannot reach the door.",
            "address": "Riad Mylaya — 163 Derb Bounba, Arset Ihiri, Medina, 40000 Marrakech, Morocco",
            "mapText": "🚗 Get dropped at the entrance of the derb, then it is a 2-minute walk. Tell us 15 min before and we will come and meet you with your luggage.\n\n🅿️ Guarded paid car park about 5 minutes' walk away (24/7). We will show you the exact spot on request.",
            "actions": [
                {"label": "Open in Google Maps", "href": MAPS, "external": True},
                {"label": "I need help getting here", "href": wa("Hello, I need help finding Riad Mylaya."), "external": True},
            ],
        },
        {
            "id": "rules", "shortTitle": "House rules", "type": "rules",
            "eyebrow": "Living together", "title": "House rules",
            "lead": "A few simple rules for everyone's comfort.",
            "items": [
                {"icon": "🔇", "color": "amber", "title": "Quiet after 10pm", "text": "The riad is an open house: please keep your voice down on the patio and terrace in the evening."},
                {"icon": "🚭", "color": "red", "title": "No smoking", "text": "Smoking is not allowed in the rooms or indoor areas. The terrace is available to you."},
                {"icon": "👟", "color": "gray", "title": "Shoes", "text": "Please take off your shoes in the carpeted lounges, as Moroccan tradition requires."},
                {"icon": "🔑", "color": "gray", "title": "Keys & departure", "text": "Please hand your keys back to the team before leaving and vacate the room before 12pm."},
                {"icon": "👶", "color": "amber", "title": "Children", "text": "The stairs and terrace are not childproof: children must be accompanied at all times."},
                {"icon": "💎", "color": "green", "title": "Valuables", "text": "A safe is available on request. The riad cannot be held responsible for items left in the rooms."},
            ],
        },
        {
            "id": "contacts", "shortTitle": "Contact",
            "eyebrow": "Reach us", "title": "Contact & WhatsApp",
            "lead": "A question, a wish, something unexpected? We reply quickly, 7 days a week.",
            "items": [
                {"title": "WhatsApp (fastest)", "text": "+212 661 351 989",
                 "actions": [{"label": "Message us on WhatsApp", "href": wa("Hello, I am staying at Riad Mylaya and I have a question."), "external": True}]},
                {"title": "Phone", "text": "+212 808 644 081 · +212 661 351 989",
                 "actions": [{"label": "Call the riad", "href": "tel:+212661351989", "external": False}]},
                {"title": "E-mail", "text": "contact@riadmylaya.com",
                 "actions": [{"label": "Send an e-mail", "href": "mailto:contact@riadmylaya.com", "external": False}]},
                {"title": "Extend your stay", "text": "Fancy one more night? Check our live availability at the best price.",
                 "actions": [{"label": "Check availability", "href": BOOKING, "external": True}]},
            ],
        },
        {
            "id": "reviews", "shortTitle": "Reviews", "type": "reviews",
            "eyebrow": "Thank you", "title": "Your review matters",
            "lead": "",
            "heading": "Did you enjoy your stay?",
            "text": "Your review helps us enormously and lets other travellers discover us. It only takes a minute — thank you so much!",
            "actions": [
                {"label": "Leave a review on Google", "href": MAPS, "external": True},
                {"label": "Leave a review on TripAdvisor", "href": TRIPADVISOR, "external": True},
            ],
        },
    ],
}

out = pathlib.Path("content/guide.json")
out.parent.mkdir(parents=True, exist_ok=True)
out.write_text(json.dumps({"languages": {"fr": FR, "en": EN}}, ensure_ascii=False, indent=2), encoding="utf-8")

# sanity: both languages must expose the same sections in the same order
fr_ids = [s["id"] for s in FR["sections"]]
en_ids = [s["id"] for s in EN["sections"]]
assert fr_ids == en_ids, (fr_ids, en_ids)
print(f"OK · {out} · {out.stat().st_size} bytes · {len(fr_ids)} sections: {', '.join(fr_ids)}")
