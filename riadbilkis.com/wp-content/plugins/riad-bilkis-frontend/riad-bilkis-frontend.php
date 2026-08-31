<?php
/**
 * Plugin Name: Riad Bilkis Frontend
 * Description: Barre de réservation mobile + WhatsApp (common-ui.js), liens vers les pages activités GetYourGuide, bloc réservation directe et sitemap des pages statiques.
 * Version: 1.0
 * Author: Devin
 */

if (!defined('ABSPATH')) exit;

const RIAD_BILKIS_ACTIVITIES = array(
    'fr' => array('url' => '/activites-groupe',      'label' => 'Activités'),
    'en' => array('url' => '/en/group-activities',   'label' => 'Activities'),
    'es' => array('url' => '/es/actividades',        'label' => 'Actividades'),
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
    $item = RIAD_BILKIS_ACTIVITIES[$lang];
    return $items . sprintf(
        '<li class="menu-item menu-item-riad-bilkis-activities"><a href="%s">%s</a></li>',
        esc_url($item['url']),
        esc_html($item['label'])
    );
}, 20, 2);

// ── Page réservation : bloc « meilleur tarif garanti » + FAQ ─────────────────
function riad_bilkis_reservation_texts($lang) {
    $texts = array(
        'fr' => array(
            'title'   => 'Réserver en direct, au meilleur tarif',
            'items'   => array(
                'Meilleur tarif garanti : aucune commission d\'intermédiaire sur nos trois chambres (dès 80 € la nuit).',
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

// ── Pages statiques dans wp-sitemap.xml ──────────────────────────────────────
add_action('init', function () {
    if (!class_exists('WP_Sitemaps_Provider')) return;

    class Riad_Bilkis_Static_Sitemap_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name = 'riad-bilkis-static';
            $this->object_type = 'page';
        }
        public function get_url_list($page_num, $object_subtype = '') {
            $urls = array();
            foreach (RIAD_BILKIS_ACTIVITIES as $item) {
                $urls[] = array('loc' => home_url($item['url']));
            }
            return $urls;
        }
        public function get_max_num_pages($object_subtype = '') {
            return 1;
        }
    }

    wp_register_sitemap_provider('riad-bilkis-static', new Riad_Bilkis_Static_Sitemap_Provider());
});
