<?php
/**
 * Plugin Name: Riad Bilkis Frontend
 * Description: Barre de réservation mobile + WhatsApp (common-ui.js), liens vers les pages activités GetYourGuide, informations pratiques et dîner marocain du livret d'accueil, bloc réservation directe et sitemap des pages statiques.
 * Version: 1.1
 * Author: Devin
 */

if (!defined('ABSPATH')) exit;

const RIAD_BILKIS_ACTIVITIES = array(
    'fr' => array('url' => '/activites-groupe',      'label' => 'Activités'),
    'en' => array('url' => '/en/group-activities',   'label' => 'Activities'),
    'es' => array('url' => '/es/actividades',        'label' => 'Actividades'),
);

// Pages statiques reprenant le livret d'accueil (guide.riadbilkis.com).
const RIAD_BILKIS_INFOS = array(
    'fr' => array('url' => '/informations-pratiques',      'label' => 'Infos pratiques'),
    'en' => array('url' => '/en/practical-information',    'label' => 'Practical info'),
    'es' => array('url' => '/es/informacion-practica',     'label' => 'Información práctica'),
);

const RIAD_BILKIS_DINER = array(
    'fr' => array('url' => '/diner-marocain',   'label' => 'Dîner marocain'),
    'en' => array('url' => '/en/moroccan-dinner', 'label' => 'Moroccan dinner'),
    'es' => array('url' => '/es/cena-marroqui',  'label' => 'Cena marroquí'),
);

function riad_bilkis_lang() {
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (isset(RIAD_BILKIS_ACTIVITIES[$lang])) return $lang;
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (preg_match('#^/(en|es)(/|$)#', $uri, $m)) return $m[1];
    return 'fr';
}

function riad_bilkis_activities_url() {
    return RIAD_BILKIS_ACTIVITIES[riad_bilkis_lang()]['url'];
}

// ── Barre de réservation mobile + WhatsApp ───────────────────────────────────
// common-ui.js n'ajoute pas de second bouton WhatsApp quand le plugin
// Click to Chat est actif : il se contente de remonter le bouton existant
// au-dessus de la barre de réservation sur mobile.
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('riad-bilkis-common-ui', '/common-ui.js', array(), '1.0', true);
}, 25);

// ── Entrée « Activités » dans le menu principal ──────────────────────────────
add_filter('wp_nav_menu_items', function ($items, $args) {
    if (!isset($args->theme_location) || !in_array($args->theme_location, array('primary', 'mobile_menu'), true)) {
        return $items;
    }
    $lang = riad_bilkis_lang();
    $added = '';
    $extra = array(
        'activities' => RIAD_BILKIS_ACTIVITIES[$lang],
        'infos'      => RIAD_BILKIS_INFOS[$lang],
        'diner'      => RIAD_BILKIS_DINER[$lang],
    );
    foreach ($extra as $slug => $item) {
        $added .= sprintf(
            '<li class="menu-item menu-item-riad-bilkis-%s"><a href="%s">%s</a></li>',
            esc_attr($slug),
            esc_url($item['url']),
            esc_html($item['label'])
        );
    }
    return $items . $added;
}, 20, 2);

// ── Page réservation : bloc « meilleur tarif garanti » + FAQ ─────────────────
function riad_bilkis_reservation_texts($lang) {
    $texts = array(
        'fr' => array(
            'title'   => 'Réserver en direct, au meilleur tarif',
            'items'   => array(
                'Meilleur tarif garanti : aucune commission d\'intermédiaire sur nos trois chambres (dès 80 € la nuit).',
                'Code promo Bilkis12 : à indiquer lors de votre réservation directe pour bénéficier de notre remise.',
                'Petit-déjeuner marocain servi de 8h00 à 10h30, inclus selon la chambre réservée.',
                'Arrivée à partir de 13h, départ avant 11h — bagages gardés gratuitement en dehors de ces horaires.',
                'Transfert aéroport et excursions organisés sur demande avant votre arrivée.',
            ),
            'help'    => 'Une question sur les disponibilités ou un séjour de plusieurs chambres ?',
            'wa'      => 'Écrire sur WhatsApp',
            'mail'    => 'riadbilkis@gmail.com',
            'wa_text' => 'Bonjour, je souhaite réserver au Riad Bilkis.',
            'faq'     => array(
                array('Comment obtenir le meilleur tarif au Riad Bilkis ?',
                      'En réservant en direct sur riadbilkis.com : aucune commission d\'intermédiaire n\'est appliquée. Les chambres démarrent à 80 € la nuit (Babouche), 90 € (Tarbouche) et 120 € (suite Véro).'),
                array('Existe-t-il un code promo pour le Riad Bilkis ?',
                      'Oui : indiquez le code Bilkis12 lors de votre réservation directe sur riadbilkis.com pour bénéficier de notre remise. Le code n\'est pas disponible sur les plateformes de réservation.'),
                array('Le petit-déjeuner est-il inclus ?',
                      'Le petit-déjeuner marocain fait maison est servi de 8h00 à 10h30 et il est inclus selon la chambre réservée. Le détail est indiqué au moment de la réservation.'),
                array('Quels sont les horaires d\'arrivée et de départ ?',
                      'L\'arrivée se fait à partir de 13h et le départ avant 11h. Nous gardons vos bagages gratuitement avant l\'arrivée ou après le départ.'),
                array('Le riad organise-t-il le transfert depuis l\'aéroport ?',
                      'Oui, sur demande. Contactez-nous par WhatsApp au +212 625 67 54 94 ou par e-mail à riadbilkis@gmail.com en indiquant votre numéro de vol.'),
                array('Y a-t-il un parking près du riad ?',
                      'Le riad se trouve dans la médina, à Bab Doukkala. Un parking surveillé est accessible à proximité pour environ 4 € par 24 heures.'),
            ),
        ),
        'en' => array(
            'title'   => 'Book direct, at the best rate',
            'items'   => array(
                'Best rate guaranteed: no intermediary commission on our three rooms (from €80 per night).',
                'Promo code Bilkis12: enter it when booking direct to get our discount.',
                'Moroccan breakfast served from 8:00 to 10:30, included depending on the room booked.',
                'Check-in from 1 pm, check-out before 11 am — free luggage storage outside these hours.',
                'Airport transfer and excursions arranged on request before you arrive.',
            ),
            'help'    => 'A question about availability or a multi-room stay?',
            'wa'      => 'Message us on WhatsApp',
            'mail'    => 'riadbilkis@gmail.com',
            'wa_text' => 'Hello, I would like to book a stay at Riad Bilkis.',
            'faq'     => array(
                array('How do I get the best rate at Riad Bilkis?',
                      'By booking directly on riadbilkis.com: no intermediary commission is charged. Rooms start at €80 per night (Babouche), €90 (Tarbouche) and €120 (Véro suite).'),
                array('Is there a promo code for Riad Bilkis?',
                      'Yes: enter the code Bilkis12 when booking directly on riadbilkis.com to get our discount. The code is not available on booking platforms.'),
                array('Is breakfast included?',
                      'The homemade Moroccan breakfast is served from 8:00 to 10:30 and is included depending on the room booked. Details are shown during booking.'),
                array('What are the check-in and check-out times?',
                      'Check-in is from 1 pm and check-out before 11 am. We store your luggage free of charge before check-in or after check-out.'),
                array('Does the riad arrange airport transfers?',
                      'Yes, on request. Contact us on WhatsApp at +212 625 67 54 94 or by email at riadbilkis@gmail.com with your flight number.'),
                array('Is there parking near the riad?',
                      'The riad is inside the medina, at Bab Doukkala. A guarded car park is available nearby for around €4 per 24 hours.'),
            ),
        ),
        'es' => array(
            'title'   => 'Reserve directamente, al mejor precio',
            'items'   => array(
                'Mejor precio garantizado: sin comisión de intermediarios en nuestras tres habitaciones (desde 80 € por noche).',
                'Código promocional Bilkis12: indíquelo al reservar directamente para obtener nuestro descuento.',
                'Desayuno marroquí servido de 8:00 a 10:30, incluido según la habitación reservada.',
                'Entrada a partir de las 13h, salida antes de las 11h — consigna de equipaje gratuita fuera de ese horario.',
                'Traslado al aeropuerto y excursiones organizados a petición antes de su llegada.',
            ),
            'help'    => '¿Alguna duda sobre la disponibilidad o una estancia de varias habitaciones?',
            'wa'      => 'Escríbanos por WhatsApp',
            'mail'    => 'riadbilkis@gmail.com',
            'wa_text' => 'Hola, quisiera reservar en el Riad Bilkis.',
            'faq'     => array(
                array('¿Cómo conseguir el mejor precio en el Riad Bilkis?',
                      'Reservando directamente en riadbilkis.com: no se aplica ninguna comisión de intermediarios. Las habitaciones cuestan desde 80 € por noche (Babouche), 90 € (Tarbouche) y 120 € (suite Véro).'),
                array('¿Hay un código promocional para el Riad Bilkis?',
                      'Sí: indique el código Bilkis12 al reservar directamente en riadbilkis.com para obtener nuestro descuento. El código no está disponible en las plataformas de reserva.'),
                array('¿El desayuno está incluido?',
                      'El desayuno marroquí casero se sirve de 8:00 a 10:30 y está incluido según la habitación reservada. El detalle se indica al reservar.'),
                array('¿Cuáles son los horarios de entrada y salida?',
                      'La entrada es a partir de las 13h y la salida antes de las 11h. Guardamos su equipaje gratuitamente antes o después de la estancia.'),
                array('¿El riad organiza el traslado desde el aeropuerto?',
                      'Sí, a petición. Contáctenos por WhatsApp al +212 625 67 54 94 o por correo a riadbilkis@gmail.com indicando su número de vuelo.'),
                array('¿Hay aparcamiento cerca del riad?',
                      'El riad está en la medina, en Bab Doukkala. Hay un aparcamiento vigilado cerca por unos 4 € cada 24 horas.'),
            ),
        ),
    );
    return $texts[$lang];
}

function riad_bilkis_is_reservation() {
    global $post;
    if (!$post || is_admin()) return false;
    return in_array($post->post_name, array('reservation', 'reservacion', 'booking'), true);
}

add_filter('the_content', function ($content) {
    if (!is_singular() || !is_main_query() || !riad_bilkis_is_reservation()) return $content;

    $t = riad_bilkis_reservation_texts(riad_bilkis_lang());
    $items = '';
    foreach ($t['items'] as $item) {
        $items .= '<li>' . esc_html($item) . '</li>';
    }
    $wa = 'https://wa.me/212625675494?text=' . rawurlencode($t['wa_text']);

    $block = '<section class="rb-direct">'
        . '<h2>' . esc_html($t['title']) . '</h2>'
        . '<ul class="rb-direct__list">' . $items . '</ul>'
        . '<p class="rb-direct__help">' . esc_html($t['help']) . '</p>'
        . '<p class="rb-direct__actions">'
        . '<a class="rb-direct__wa" href="' . esc_url($wa) . '" target="_blank" rel="noopener noreferrer">' . esc_html($t['wa']) . '</a> '
        . '<a class="rb-direct__mail" href="mailto:riadbilkis@gmail.com">' . esc_html($t['mail']) . '</a>'
        . '</p></section>';

    return $content . $block;
}, 20);

add_action('wp_head', function () {
    if (!riad_bilkis_is_reservation()) return;
    $t = riad_bilkis_reservation_texts(riad_bilkis_lang());
    $entities = array();
    foreach ($t['faq'] as $qa) {
        $entities[] = array(
            '@type' => 'Question',
            'name' => $qa[0],
            'acceptedAnswer' => array('@type' => 'Answer', 'text' => $qa[1]),
        );
    }
    $faq = array('@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $entities);
    echo '<script type="application/ld+json">' . wp_json_encode($faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 3);

add_action('wp_enqueue_scripts', function () {
    if (!riad_bilkis_is_reservation()) return;
    wp_register_style('riad-bilkis-direct', false);
    wp_enqueue_style('riad-bilkis-direct');
    wp_add_inline_style('riad-bilkis-direct', '
.rb-direct{margin:48px 0 8px;padding:32px;background:#FBF7F2;border:1px solid #E6D3C4}
.rb-direct h2{font-family:"Cormorant Garamond",Georgia,serif;font-size:30px;color:#3D3229;margin:0 0 18px}
.rb-direct__list{margin:0 0 20px;padding-left:20px;color:#5B4E43;line-height:1.75}
.rb-direct__list li{margin-bottom:8px}
.rb-direct__help{color:#6B5B4E;margin-bottom:14px}
.rb-direct__actions a{display:inline-block;margin:0 10px 10px 0;padding:12px 22px;text-decoration:none;font-size:14px;letter-spacing:1px;text-transform:uppercase}
.rb-direct__wa{background:#25D366;color:#fff!important}
.rb-direct__mail{border:1px solid #C75B39;color:#C75B39!important}
@media(max-width:600px){.rb-direct{padding:24px 18px}.rb-direct h2{font-size:25px}}
');
}, 26);

// ── Livret d'accueil repris sur la page services ─────────────────────────────
function riad_bilkis_stay_texts($lang) {
    $texts = array(
        'fr' => array(
            'title'   => 'Votre séjour au Riad Bilkis, dans le détail',
            'intro'   => 'Les informations de notre livret d\'accueil, reprises ici avant votre arrivée.',
            'groups'  => array(
                'Petit-déjeuner' => array(
                    'Servi de 8h00 à 10h30 sur la terrasse ou dans le patio.',
                    'Avant 7h30, nous préparons un petit-déjeuner à emporter.',
                    'Après 10h30, il est proposé au tarif de 6 € par personne.',
                ),
                'Dîner marocain de Mme Sinan' => array(
                    'Menu complet (entrée, plat, dessert) : 25 € par personne.',
                    'Entrée + plat ou plat + dessert : 20 € par personne.',
                    'Plat principal seul : 15 € par personne. Version végétarienne sur demande.',
                ),
                'Services et confort' => array(
                    'Eau et thé à la menthe offerts à l\'arrivée, avec pâtisseries marocaines.',
                    'Terrasse, jacuzzi, patio, climatisation et chauffage, Wi-Fi gratuit.',
                    'Ménage et petit-déjeuner assurés par Mme Khadija, de 7h30 à 15h.',
                    'Blanchisserie : 5 € par machine, de 9h à 15h. Parking à proximité : 4 € par 24 h.',
                ),
                'Arrivée et départ' => array(
                    'Arrivée à partir de 13h, départ avant 11h.',
                    'Transfert privé depuis l\'aéroport Marrakech-Ménara : 15 € l\'aller, 25 € l\'aller-retour.',
                    'Serviettes et linge de lit changés tous les deux à trois jours selon la durée du séjour.',
                ),
            ),
            'cta_infos' => 'Toutes les informations pratiques',
            'cta_diner' => 'Réserver un dîner marocain',
        ),
        'en' => array(
            'title'   => 'Your stay at Riad Bilkis, in detail',
            'intro'   => 'The information from our welcome guide, available here before you arrive.',
            'groups'  => array(
                'Breakfast' => array(
                    'Served from 8:00 am to 10:30 am on the terrace or in the patio.',
                    'Before 7:30 am we prepare a takeaway breakfast.',
                    'After 10:30 am, breakfast is available for €6 per person.',
                ),
                'Mrs. Sinan\'s Moroccan dinner' => array(
                    'Full menu (starter, main, dessert): €25 per person.',
                    'Starter + main or main + dessert: €20 per person.',
                    'Main course only: €15 per person. Vegetarian version on request.',
                ),
                'Services and comfort' => array(
                    'Complimentary water and mint tea on arrival, with Moroccan pastries.',
                    'Terrace, jacuzzi, patio, air conditioning and heating, free Wi-Fi.',
                    'Breakfast and housekeeping by Mrs. Khadija, from 7:30 am to 3 pm.',
                    'Laundry: €5 per load, from 9 am to 3 pm. Parking nearby: €4 per 24 hours.',
                ),
                'Arrival and departure' => array(
                    'Check-in from 1 pm, check-out before 11 am.',
                    'Private transfer from Marrakech-Menara airport: €15 one way, €25 return.',
                    'Towels and bed linen changed every two to three days depending on the stay.',
                ),
            ),
            'cta_infos' => 'All practical information',
            'cta_diner' => 'Book a Moroccan dinner',
        ),
        'es' => array(
            'title'   => 'Su estancia en el Riad Bilkis, en detalle',
            'intro'   => 'La información de nuestra guía de bienvenida, disponible aquí antes de su llegada.',
            'groups'  => array(
                'Desayuno' => array(
                    'Se sirve de 8:00 a 10:30 en la terraza o en el patio.',
                    'Antes de las 7:30 preparamos un desayuno para llevar.',
                    'Después de las 10:30 se ofrece por 6 € por persona.',
                ),
                'La cena marroquí de la Sra. Sinan' => array(
                    'Menú completo (entrada, plato y postre): 25 € por persona.',
                    'Entrada + plato o plato + postre: 20 € por persona.',
                    'Solo plato principal: 15 € por persona. Versión vegetariana a petición.',
                ),
                'Servicios y confort' => array(
                    'Agua y té de menta de cortesía a la llegada, con pasteles marroquíes.',
                    'Terraza, jacuzzi, patio, aire acondicionado y calefacción, Wi-Fi gratuito.',
                    'Desayuno y limpieza a cargo de la Sra. Khadija, de 7:30 a 15:00.',
                    'Lavandería: 5 € por lavado, de 9:00 a 15:00. Aparcamiento cercano: 4 € cada 24 h.',
                ),
                'Llegada y salida' => array(
                    'Llegada a partir de las 13:00, salida antes de las 11:00.',
                    'Traslado privado desde el aeropuerto Marrakech-Menara: 15 € por trayecto, 25 € ida y vuelta.',
                    'Toallas y ropa de cama cambiadas cada dos o tres días según la estancia.',
                ),
            ),
            'cta_infos' => 'Toda la información práctica',
            'cta_diner' => 'Reservar una cena marroquí',
        ),
    );
    return $texts[$lang];
}

add_filter('the_content', function ($content) {
    global $post;
    if (!is_singular() || !is_main_query() || !$post) return $content;
    if (!in_array($post->post_name, array('nos-services', 'services', 'servicios', 'our-services'), true)) return $content;

    $lang = riad_bilkis_lang();
    $t = riad_bilkis_stay_texts($lang);

    $groups = '';
    foreach ($t['groups'] as $heading => $lines) {
        $items = '';
        foreach ($lines as $line) {
            $items .= '<li>' . esc_html($line) . '</li>';
        }
        $groups .= '<div class="rb-stay__group"><h3>' . esc_html($heading) . '</h3><ul>' . $items . '</ul></div>';
    }

    return $content . '<section class="rb-direct rb-stay">'
        . '<h2>' . esc_html($t['title']) . '</h2>'
        . '<p>' . esc_html($t['intro']) . '</p>'
        . '<div class="rb-stay__grid">' . $groups . '</div>'
        . '<p class="rb-direct__actions">'
        . '<a class="rb-direct__mail" href="' . esc_url(RIAD_BILKIS_INFOS[$lang]['url']) . '">' . esc_html($t['cta_infos']) . '</a> '
        . '<a class="rb-direct__mail" href="' . esc_url(RIAD_BILKIS_DINER[$lang]['url']) . '">' . esc_html($t['cta_diner']) . '</a>'
        . '</p></section>';
}, 22);

add_action('wp_enqueue_scripts', function () {
    global $post;
    if (!$post || !in_array($post->post_name, array('nos-services', 'services', 'servicios', 'our-services'), true)) return;
    wp_register_style('riad-bilkis-stay', false);
    wp_enqueue_style('riad-bilkis-stay');
    wp_add_inline_style('riad-bilkis-stay', '
.rb-stay{margin:48px 0 8px;padding:32px;background:#FBF7F2;border:1px solid #E6D3C4}
.rb-stay h2{font-family:"Cormorant Garamond",Georgia,serif;font-size:30px;color:#3D3229;margin:0 0 12px}
.rb-stay__grid{display:grid;grid-template-columns:repeat(2,1fr);gap:22px;margin:22px 0}
.rb-stay__group{background:#fff;border:1px solid #E6D3C4;padding:20px}
.rb-stay__group h3{font-family:"Cormorant Garamond",Georgia,serif;font-size:23px;color:#8a5a3c;margin:0 0 10px}
.rb-stay__group ul{margin:0;padding-left:20px;color:#5B4E43;line-height:1.7}
@media(max-width:768px){.rb-stay__grid{grid-template-columns:1fr}.rb-stay{padding:24px 18px}}
');
}, 27);

// ── Lien vers les activités depuis la page excursions ────────────────────────
add_filter('the_content', function ($content) {
    global $post;
    if (!is_singular() || !is_main_query() || !$post) return $content;
    if (!in_array($post->post_name, array('excursions-activites', 'excursions-activities', 'excursiones'), true)) return $content;

    $lang = riad_bilkis_lang();
    $labels = array(
        'fr' => array('Réserver une excursion en ligne', 'Comparez les excursions en groupe et en privé au départ de Marrakech, avec annulation gratuite jusqu\'à 24 h.', 'Voir les activités disponibles'),
        'en' => array('Book an excursion online', 'Compare group and private excursions departing from Marrakech, with free cancellation up to 24 hours before.', 'See available activities'),
        'es' => array('Reserve una excursión en línea', 'Compare excursiones en grupo y privadas desde Marrakech, con cancelación gratuita hasta 24 h antes.', 'Ver las actividades disponibles'),
    );
    list($title, $text, $cta) = $labels[$lang];

    return $content . '<section class="rb-direct">'
        . '<h2>' . esc_html($title) . '</h2>'
        . '<p>' . esc_html($text) . '</p>'
        . '<p class="rb-direct__actions"><a class="rb-direct__mail" href="' . esc_url(riad_bilkis_activities_url()) . '">' . esc_html($cta) . '</a></p>'
        . '</section>';
}, 21);

// ── Bloc « Site officiel » (titre + meilleur prix + CTA + code promo) ────────
// Reprise du bloc de riadmylaya.com, adapté à Bilkis (code Bilkis12).

const RIAD_BILKIS_OFFICIAL_URL = 'https://booking-directly.com/widgets/CpCIZwUUpc4p14KAQFEzgGCPRoKW9a2R5UUDUleuJA3xBbFB9ZW7MOaFdMCwX/properties';
const RIAD_BILKIS_PROMO = 'BILKIS12';

function riad_bilkis_official_texts($lang) {
    $texts = array(
        'fr' => array(
            'title' => 'Site officiel du Riad Bilkis',
            'lines' => array('Meilleur prix garanti', 'Réduction sur les réservations directes'),
            'cta'   => 'Réserver au meilleur prix',
            'promo' => 'Code promo',
            'hint'  => 'à saisir lors de votre réservation',
            'copied' => 'Copié !',
        ),
        'en' => array(
            'title' => 'Official website of Riad Bilkis',
            'lines' => array('Best price guaranteed', 'Discount on direct bookings'),
            'cta'   => 'Book at the best price',
            'promo' => 'Promo code',
            'hint'  => 'use this code when booking',
            'copied' => 'Copied!',
        ),
        'es' => array(
            'title' => 'Sitio oficial del Riad Bilkis',
            'lines' => array('Mejor precio garantizado', 'Descuento en las reservas directas'),
            'cta'   => 'Reservar al mejor precio',
            'promo' => 'Código promocional',
            'hint'  => 'úsalo al reservar',
            'copied' => '¡Copiado!',
        ),
    );
    return isset($texts[$lang]) ? $texts[$lang] : $texts['fr'];
}

function riad_bilkis_official_block() {
    $t = riad_bilkis_official_texts(riad_bilkis_lang());
    $lines = '';
    foreach ($t['lines'] as $line) {
        $lines .= esc_html($line) . '<br>';
    }
    return '<section class="rb-official" id="rb-official">'
        . '<div class="rb-official__inner">'
        . '<h2 class="rb-official__title">' . esc_html($t['title']) . '</h2>'
        . '<p class="rb-official__text">' . $lines . '</p>'
        . '<p class="rb-official__btn-wrap"><a class="rb-official__btn" href="' . esc_url(RIAD_BILKIS_OFFICIAL_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html($t['cta']) . '</a></p>'
        . '<div class="rb-promo-box" role="note" aria-label="' . esc_attr($t['promo'] . ' ' . RIAD_BILKIS_PROMO) . '">'
        . '<span class="rb-promo-label">' . esc_html($t['promo']) . '</span>'
        . '<button type="button" class="rb-promo-value" data-copied="' . esc_attr($t['copied']) . '">' . esc_html(RIAD_BILKIS_PROMO) . '</button>'
        . '<span class="rb-promo-hint">' . esc_html($t['hint']) . '</span>'
        . '</div>'
        . '</div>'
        . '</section>';
}

// Les URL /en/ et /es/ affichent la même page que la racine sans être
// « front page » au sens WordPress : on compare donc l'ID de la page affichée.
function riad_bilkis_is_home_page() {
    if (is_front_page()) return true;
    $front = (int) get_option('page_on_front');
    if ($front && is_page() && get_queried_object_id() === $front) return true;
    // /en/ et /es/ : points d'entrée des versions traduites (aucune page d'accueil
    // traduite n'existe, Polylang y sert l'index).
    $path = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
    return is_home() && in_array(rtrim($path, '/'), array('/en', '/es'), true);
}

add_filter('the_content', function ($content) {
    if (!riad_bilkis_is_home_page() || !in_the_loop() || !is_main_query()) return $content;
    $block = riad_bilkis_official_block();
    // Le bloc se place juste sous le hero, comme sur riadmylaya.com.
    $pos = strpos($content, '<section class="rb-section');
    if ($pos !== false) {
        return substr($content, 0, $pos) . $block . substr($content, $pos);
    }
    return $block . $content;
}, 19);

add_action('astra_primary_content_top', function () {
    if (!riad_bilkis_is_home_page() || !is_home()) return;
    echo riad_bilkis_official_block();
});

add_action('wp_enqueue_scripts', function () {
    if (!riad_bilkis_is_home_page()) return;
    wp_register_style('riad-bilkis-official', false);
    wp_enqueue_style('riad-bilkis-official');
    wp_add_inline_style('riad-bilkis-official', '
.rb-official{background:#fff;padding:56px 20px 64px;text-align:center;
 --rb-official-font:"Jost","Helvetica Neue",Arial,sans-serif}
.rb-official *{font-family:var(--rb-official-font)}
.rb-official__inner{max-width:900px;margin:0 auto}
.rb-official__title{font-size:44px;line-height:1.15;font-weight:700;color:#821F0C;margin:0 0 18px;
 letter-spacing:0}
.rb-official__title:after,.rb-official__title:before{display:none}
.rb-official__text{font-size:22px;line-height:1.5;color:#3F2935;margin:0 0 26px}
.rb-official__btn-wrap{margin:0}
.rb-official__btn,.rb-official__btn:visited{display:inline-block;background:#FE8A8A;color:#fff;
 font-size:15px;font-weight:600;letter-spacing:.4px;text-decoration:none;padding:14px 30px;border-radius:4px;
 box-shadow:0 2px 8px rgba(0,0,0,.12);transition:background .2s,transform .1s}
.rb-official__btn:hover,.rb-official__btn:focus{background:#F97070;color:#fff}
.rb-official__btn:active{transform:scale(.98)}
.rb-promo-box{display:inline-block;margin:22px auto 0;padding:12px 22px;background:#FFF9ED;
 border:2px dashed #C99752;border-radius:10px;color:#2a2a2a;line-height:1.35;text-align:center;
 box-shadow:0 3px 10px rgba(0,0,0,.12);max-width:100%;animation:rbPromoPulse 2.6s ease-in-out infinite}
.rb-promo-label{display:block;font-size:14px;font-weight:500;letter-spacing:.3px;color:#6b4a1b;
 margin-bottom:6px;text-transform:uppercase}
.rb-promo-value{display:inline-block;padding:6px 16px;background:#C99752;color:#fff;border:none;outline:none;
 border-radius:6px;font-size:20px;font-weight:700;letter-spacing:2px;cursor:pointer;user-select:all;
 text-transform:none;font-variant:normal;transition:background .2s,transform .1s}
.rb-promo-value:hover{background:#b07f38}
.rb-promo-value:active{transform:scale(.97)}
.rb-promo-hint{display:block;font-size:12.5px;color:#555;margin-top:6px;font-style:italic}
.rb-promo-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#2a2a2a;color:#fff;
 padding:10px 18px;border-radius:6px;font-size:14px;opacity:0;transition:opacity .25s;z-index:9999;
 pointer-events:none}
.rb-promo-toast.is-visible{opacity:.95}
@keyframes rbPromoPulse{0%,100%{box-shadow:0 3px 10px rgba(0,0,0,.12)}50%{box-shadow:0 3px 22px rgba(201,151,82,.55)}}
@media(max-width:768px){
.rb-official{padding:40px 18px 46px}
.rb-official__title{font-size:31px}
.rb-official__text{font-size:18px}
.rb-official__btn{padding:13px 24px;font-size:14px}
.rb-promo-box{padding:10px 14px;margin-top:16px}
.rb-promo-value{font-size:17px;letter-spacing:1.5px;padding:5px 12px}
.rb-promo-label{font-size:12px}
.rb-promo-hint{font-size:11.5px}
}
');
    wp_register_script('riad-bilkis-official', '', array(), null, true);
    wp_enqueue_script('riad-bilkis-official');
    wp_add_inline_script('riad-bilkis-official', '
(function(){
  var block=document.getElementById("rb-official");
  if(!block)return;
  var btn=block.querySelector(".rb-promo-value");
  if(!btn)return;
  function toast(msg){
    var t=document.createElement("div");
    t.className="rb-promo-toast";
    t.textContent=msg;
    document.body.appendChild(t);
    void t.offsetWidth;
    t.classList.add("is-visible");
    setTimeout(function(){t.classList.remove("is-visible");setTimeout(function(){if(t.parentNode)t.parentNode.removeChild(t);},400);},1600);
  }
  btn.addEventListener("click",function(){
    var code=btn.textContent.trim(),done=btn.getAttribute("data-copied")||"";
    if(navigator.clipboard&&navigator.clipboard.writeText){
      navigator.clipboard.writeText(code).then(function(){toast(done);});
      return;
    }
    var ta=document.createElement("textarea");
    ta.value=code;ta.style.position="fixed";ta.style.opacity="0";
    document.body.appendChild(ta);ta.select();
    try{document.execCommand("copy");toast(done);}catch(e){}
    document.body.removeChild(ta);
  });
})();
');
});

// ── Pages statiques dans wp-sitemap.xml ──────────────────────────────────────
add_action('init', function () {
    if (!class_exists('WP_Sitemaps_Provider')) return;

    class Riad_Bilkis_Static_Sitemap_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name = 'riadbilkisactivites';
            $this->object_type = 'page';
        }
        public function get_url_list($page_num, $object_subtype = '') {
            $urls = array();
            $sets = array(RIAD_BILKIS_ACTIVITIES, RIAD_BILKIS_INFOS, RIAD_BILKIS_DINER);
            foreach ($sets as $set) {
                foreach ($set as $item) {
                    $urls[] = array('loc' => home_url($item['url']));
                }
            }
            return $urls;
        }
        public function get_max_num_pages($object_subtype = '') {
            return 1;
        }
    }

    wp_register_sitemap_provider('riadbilkisactivites', new Riad_Bilkis_Static_Sitemap_Provider());
});
