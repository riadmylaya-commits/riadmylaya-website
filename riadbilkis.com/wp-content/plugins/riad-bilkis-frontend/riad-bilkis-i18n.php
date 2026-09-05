<?php
/**
 * Riad Bilkis — navigation et contenus en trois langues (FR / EN / ES).
 *
 * Les pages WordPress n'existent qu'en français : chaque page possède ici une
 * URL anglaise et espagnole, servie par la même page WordPress dont le rendu
 * est traduit (libellés du menu, contenu, liens internes, métadonnées). La
 * langue vient donc de l'URL, ce qui la conserve pendant toute la navigation.
 */

if (!defined('ABSPATH')) exit;

// clé => URL par langue + slug de la page WordPress qui fournit le contenu.
function riad_bilkis_i18n_pages() {
    return array(
        'home'      => array('fr' => '/',                    'en' => '/en/',                     'es' => '/es/',                        'wp' => 'accueil'),
        'rooms'     => array('fr' => '/chambres/',           'en' => '/en/rooms/',               'es' => '/es/habitaciones/',           'wp' => 'chambres'),
        'babouche'  => array('fr' => '/chambre-babouche/',   'en' => '/en/rooms/babouche/',      'es' => '/es/habitaciones/babouche/',  'wp' => 'chambre-babouche'),
        'tarbouche' => array('fr' => '/chambre-tarbouche/',  'en' => '/en/rooms/tarbouche/',     'es' => '/es/habitaciones/tarbouche/', 'wp' => 'chambre-tarbouche'),
        'vero'      => array('fr' => '/chambre-vero/',       'en' => '/en/rooms/vero/',          'es' => '/es/habitaciones/vero/',      'wp' => 'chambre-vero'),
        'gallery'   => array('fr' => '/galerie/',            'en' => '/en/gallery/',             'es' => '/es/galeria/',                'wp' => 'galerie'),
        'services'  => array('fr' => '/nos-services/',       'en' => '/en/services/',            'es' => '/es/servicios/',              'wp' => 'nos-services'),
        'contact'   => array('fr' => '/contact/',            'en' => '/en/contact/',             'es' => '/es/contacto/',               'wp' => 'contact'),
        'booking'   => array('fr' => '/reservation/',        'en' => '/en/booking/',             'es' => '/es/reserva/',                'wp' => 'reservation'),
        // Pages statiques : leurs versions traduites existent déjà.
        'dinner'    => array('fr' => '/diner-marocain',      'en' => '/en/moroccan-dinner',      'es' => '/es/cena-marroqui'),
        'cooking'   => array('fr' => '/cours-de-cuisine',    'en' => '/en/cooking-class',        'es' => '/es/clase-de-cocina'),
        'hammam'    => array('fr' => '/hammam-massage',      'en' => '/en/hammam-massage',       'es' => '/es/hammam-masaje'),
        'infos'     => array('fr' => '/informations-pratiques', 'en' => '/en/practical-information', 'es' => '/es/informacion-practica'),
        'activities' => array('fr' => '/activites-groupe',   'en' => '/en/group-activities',     'es' => '/es/actividades'),
        'excursions' => array('fr' => '/excursions/',        'en' => '/en/excursions/',          'es' => '/es/excursiones/'),
        'blog'      => array('fr' => '/blog',                'en' => '/en/blog',                 'es' => '/es/blog'),
        'marrakech' => array('fr' => '/decouverte-marrakech', 'en' => '/en/discover-marrakech',  'es' => '/es/descubrir-marrakech'),
    );
}

function riad_bilkis_i18n_url($key, $lang) {
    $pages = riad_bilkis_i18n_pages();
    if (!isset($pages[$key])) return '/';
    return isset($pages[$key][$lang]) ? $pages[$key][$lang] : $pages[$key]['fr'];
}

function riad_bilkis_i18n_path() {
    $uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/';
    $uri = '/' . trim($uri, '/');
    return $uri;
}

// URL anglaise ou espagnole d'une page WordPress : renvoie la clé, la langue
// et le slug WordPress à afficher.
function riad_bilkis_i18n_route() {
    static $route = null;
    if ($route !== null) return $route;
    $route = false;
    $path  = riad_bilkis_i18n_path();
    foreach (riad_bilkis_i18n_pages() as $key => $page) {
        if (empty($page['wp'])) continue;
        foreach (array('en', 'es') as $lang) {
            if ('/' . trim($page[$lang], '/') === $path) {
                $route = array('key' => $key, 'lang' => $lang, 'wp' => $page['wp']);
                return $route;
            }
        }
    }
    return $route;
}

// Clé de la page affichée, pour le sélecteur de langue.
function riad_bilkis_i18n_current_key() {
    $route = riad_bilkis_i18n_route();
    if ($route) return $route['key'];
    $path = riad_bilkis_i18n_path();
    foreach (riad_bilkis_i18n_pages() as $key => $page) {
        foreach (array('fr', 'en', 'es') as $lang) {
            if ('/' . trim($page[$lang], '/') === $path) return $key;
        }
    }
    // Fiches d'excursion : /excursions/<slug>, /en/excursions/<slug>…
    foreach (array('fr', 'en', 'es') as $lang) {
        $base = '/' . trim(riad_bilkis_i18n_url('excursions', $lang), '/');
        if (strpos($path, $base . '/') === 0) return 'excursions';
    }
    global $post;
    if ($post && is_page()) {
        foreach (riad_bilkis_i18n_pages() as $key => $page) {
            if (!empty($page['wp']) && $page['wp'] === $post->post_name) return $key;
        }
    }
    return '';
}

// La page WordPress française fournit le contenu des URL /en/… et /es/…
add_action('parse_request', function ($wp) {
    $route = riad_bilkis_i18n_route();
    if (!$route) return;
    $wp->query_vars = array('pagename' => $route['wp'], 'lang' => 'fr');
    $wp->matched_rule  = '';
    $wp->matched_query = '';
    // Sans cela WordPress et Polylang renverraient vers l'URL française.
    remove_action('template_redirect', 'redirect_canonical');
    add_filter('pll_check_canonical_url', '__return_false', 99);
    add_filter('redirect_canonical', '__return_false', 99);
}, 1);

// Le logo renvoie vers l'accueil de la langue affichée.
add_filter('astra_logo', function ($html) {
    $lang = riad_bilkis_lang();
    if ($lang === 'fr') return $html;
    return str_replace(
        array('href="' . home_url('/') . '"', "href='" . home_url('/') . "'", 'href="' . home_url() . '"'),
        'href="' . esc_url(riad_bilkis_i18n_url('home', $lang)) . '"',
        $html
    );
}, 20);

// L'attribut lang de <html> suit la langue affichée.
add_filter('language_attributes', function ($output) {
    $lang = riad_bilkis_lang();
    if ($lang === 'fr') return $output;
    $codes = array('en' => 'en-GB', 'es' => 'es-ES');
    return preg_replace('/lang="[^"]*"/', 'lang="' . $codes[$lang] . '"', $output, 1);
}, 20);

// ── Traduction du rendu ──────────────────────────────────────────────────────
// Les apostrophes et les « & » arrivent sous plusieurs formes dans le HTML
// rendu par WordPress : chaque chaîne est donc déclinée en variantes.
function riad_bilkis_i18n_variants($fr, $to) {
    $apos = array('’', "'", '&rsquo;', '&#039;', '&#8217;');
    $amps = array('&', '&amp;', '&#038;');
    $out  = array();
    foreach ($amps as $amp) {
        foreach ($apos as $ap) {
            $from = str_replace(array('&', '’'), array($amp, $ap), $fr);
            $out[$from] = str_replace(array('&', '’'), array($amp, $ap), $to);
        }
    }
    return $out;
}

function riad_bilkis_i18n_pairs($lang) {
    static $cache = array();
    if (isset($cache[$lang])) return $cache[$lang];
    $pairs = array();
    foreach (riad_bilkis_i18n_strings() as $fr => $tr) {
        if (empty($tr[$lang])) continue;
        foreach (riad_bilkis_i18n_variants($fr, $tr[$lang]) as $from => $to) {
            $pairs[$from] = $to;
        }
    }
    // Liens internes : chaque page pointe vers sa version dans la langue lue.
    foreach (riad_bilkis_i18n_pages() as $page) {
        if ($page['fr'] === $page[$lang]) continue;
        $fr   = $page['fr'];
        $alt  = substr($fr, -1) === '/' ? rtrim($fr, '/') : $fr . '/';
        $urls = array($fr, $alt, 'https://riadbilkis.com' . $fr, 'https://riadbilkis.com' . $alt);
        foreach ($urls as $url) {
            $pairs['href="' . $url . '"'] = 'href="' . $page[$lang] . '"';
        }
    }
    $cache[$lang] = $pairs;
    return $pairs;
}

function riad_bilkis_i18n_translate($html, $lang = null) {
    if (!is_string($html) || $html === '') return $html;
    if ($lang === null) $lang = riad_bilkis_lang();
    if ($lang === 'fr') return $html;
    return strtr($html, riad_bilkis_i18n_pairs($lang));
}

add_filter('the_content', 'riad_bilkis_i18n_translate', 999);
add_filter('the_title', 'riad_bilkis_i18n_translate', 999);
add_filter('wpcf7_form_elements', 'riad_bilkis_i18n_translate', 999);

// ── Sélecteur FR | EN | ES ───────────────────────────────────────────────────
function riad_bilkis_i18n_switcher_html($classes = 'rb-lang') {
    $key   = riad_bilkis_i18n_current_key();
    $lang  = riad_bilkis_lang();
    $names = array('fr' => 'FR', 'en' => 'EN', 'es' => 'ES');
    // Sur une fiche d'excursion, le sélecteur reste sur la même fiche.
    $exc = function_exists('riad_bilkis_exc_route') ? riad_bilkis_exc_route() : false;
    $out   = '<span class="' . esc_attr($classes) . '">';
    foreach ($names as $code => $label) {
        if ($exc && $exc['slug'] !== '') {
            $url = riad_bilkis_exc_url($exc['slug'], $code);
        } else {
            $url = $key ? riad_bilkis_i18n_url($key, $code) : riad_bilkis_i18n_url('home', $code);
        }
        $out .= '<a href="' . esc_url($url) . '" hreflang="' . esc_attr($code) . '" lang="' . esc_attr($code) . '"'
             . ($code === $lang ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
    }
    return $out . '</span>';
}

// ── Métadonnées traduites (titre, description, hreflang) ─────────────────────
function riad_bilkis_i18n_seo($key, $lang) {
    $seo = array(
        'home' => array(
            'en' => array('Riad Bilkis Marrakech | Charming Riad in the Medina | Direct Booking',
                'Stay at Riad Bilkis, a charming guest house in the heart of the Marrakech Medina. Traditional patio, panoramic terrace, Moroccan breakfast. Best price guaranteed when you book direct.'),
            'es' => array('Riad Bilkis Marrakech | Riad con encanto en la Medina | Reserva directa',
                'Alójese en el Riad Bilkis, casa de huéspedes con encanto en el corazón de la Medina de Marrakech. Patio tradicional, terraza panorámica, desayuno marroquí. Mejor precio garantizado reservando directamente.'),
        ),
        'rooms' => array(
            'en' => array('Our Rooms | Riad Bilkis Marrakech — Charming Accommodation',
                'Discover the three charming rooms of Riad Bilkis Marrakech: Babouche, Tarbouche and Véro. Traditional Moroccan decor, air conditioning, Wi-Fi, private bathroom.'),
            'es' => array('Nuestras Habitaciones | Riad Bilkis Marrakech — Alojamiento con encanto',
                'Descubra las tres habitaciones con encanto del Riad Bilkis Marrakech: Babouche, Tarbouche y Véro. Decoración tradicional marroquí, aire acondicionado, Wi-Fi, baño privado.'),
        ),
        'babouche' => array(
            'en' => array('Babouche Room | Riad Bilkis Marrakech', 'Babouche Room at Riad Bilkis: a double room in saffron yellow and white on the ground floor. Air conditioning, Wi-Fi, safe, Les Sens de Marrakech toiletries.'),
            'es' => array('Habitación Babouche | Riad Bilkis Marrakech', 'Habitación Babouche del Riad Bilkis: habitación doble en amarillo azafrán y blanco, en la planta baja. Aire acondicionado, Wi-Fi, caja fuerte, productos Les Sens de Marrakech.'),
        ),
        'tarbouche' => array(
            'en' => array('Tarbouche Room | Riad Bilkis Marrakech', 'Tarbouche Room at Riad Bilkis: a double room in red and white on the first floor, with the colours of Marrakech, the red city. Air conditioning, Wi-Fi, safe.'),
            'es' => array('Habitación Tarbouche | Riad Bilkis Marrakech', 'Habitación Tarbouche del Riad Bilkis: habitación doble en rojo y blanco, en la primera planta, con los colores de Marrakech, la ciudad roja. Aire acondicionado, Wi-Fi, caja fuerte.'),
        ),
        'vero' => array(
            'en' => array('Véro Room | Riad Bilkis Marrakech', 'Véro Room at Riad Bilkis: a soothing double room in blue and white with its own small lounge, on the first floor. Air conditioning, Wi-Fi, safe.'),
            'es' => array('Habitación Véro | Riad Bilkis Marrakech', 'Habitación Véro del Riad Bilkis: habitación doble en azul y blanco, muy relajante, con un pequeño salón, en la primera planta. Aire acondicionado, Wi-Fi, caja fuerte.'),
        ),
        'gallery' => array(
            'en' => array('Photo Gallery | Riad Bilkis Marrakech', 'Photo gallery of Riad Bilkis Marrakech: the rooms, the patio, the panoramic terrace and traditional Moroccan architecture in pictures.'),
            'es' => array('Galería de fotos | Riad Bilkis Marrakech', 'Galería de fotos del Riad Bilkis Marrakech: las habitaciones, el patio, la terraza panorámica y la arquitectura tradicional marroquí en imágenes.'),
        ),
        'services' => array(
            'en' => array('Our Services | Riad Bilkis Marrakech — Breakfast, Hammam, Transfers',
                'Services at Riad Bilkis: homemade Moroccan breakfast, private airport transfer, traditional hammam, cooking class, personalised concierge service.'),
            'es' => array('Nuestros Servicios | Riad Bilkis Marrakech — Desayuno, hammam, traslados',
                'Servicios del Riad Bilkis: desayuno marroquí casero, traslado privado al aeropuerto, hammam tradicional, clase de cocina, conserjería personalizada.'),
        ),
        'contact' => array(
            'en' => array('Contact and Location | Riad Bilkis Marrakech — Medina', 'Contact Riad Bilkis Marrakech by WhatsApp, e-mail or phone. In the heart of the historic medina, five minutes from Jemaa el-Fna square.'),
            'es' => array('Contacto y ubicación | Riad Bilkis Marrakech — Medina', 'Contacte con el Riad Bilkis Marrakech por WhatsApp, correo electrónico o teléfono. En el corazón de la medina histórica, a cinco minutos de la plaza Jemaa el-Fna.'),
        ),
        'booking' => array(
            'en' => array('Direct Booking | Riad Bilkis Marrakech — Best Price Guaranteed', 'Book your stay at Riad Bilkis Marrakech at the guaranteed best price. Direct booking with no commission, three charming rooms.'),
            'es' => array('Reserva directa | Riad Bilkis Marrakech — Mejor precio garantizado', 'Reserve su estancia en el Riad Bilkis Marrakech al mejor precio garantizado. Reserva directa sin comisiones, tres habitaciones con encanto.'),
        ),
    );
    if (!isset($seo[$key][$lang])) return null;
    return array('title' => $seo[$key][$lang][0], 'description' => $seo[$key][$lang][1]);
}

// hreflang sur les pages WordPress traduites (Polylang ne les connaît pas).
add_action('wp_head', function () {
    $key = riad_bilkis_i18n_current_key();
    if (!$key) return;
    $pages = riad_bilkis_i18n_pages();
    if (empty($pages[$key]['wp'])) return;
    remove_action('wp_head', 'rel_canonical');
    $current = riad_bilkis_i18n_url($key, riad_bilkis_lang());
    echo '<link rel="canonical" href="' . esc_url(home_url($current)) . '" />' . "\n";
    foreach (array('fr', 'en', 'es') as $lang) {
        echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="'
            . esc_url(home_url(riad_bilkis_i18n_url($key, $lang))) . '" />' . "\n";
    }
}, 1);

/**
 * Contenus français des pages WordPress et de leurs blocs générés, avec leur
 * traduction anglaise et espagnole. Les clés sont les chaînes telles qu'elles
 * apparaissent dans le rendu (apostrophes typographiques).
 */
function riad_bilkis_i18n_strings() {
    static $strings = null;
    if ($strings !== null) return $strings;
    $rows = array(
        // — Accueil
        array('Bienvenue au', 'Welcome to', 'Bienvenidos al'),
        array('Un havre de paix au cœur de la Médina de Marrakech', 'A haven of peace in the heart of the Marrakech Medina', 'Un remanso de paz en el corazón de la Medina de Marrakech'),
        array('Réserver votre séjour', 'Book your stay', 'Reserve su estancia'),
        array('Découvrir le charme', 'Discover the charm', 'Descubrir el encanto'),
        array('Le charme authentique du Maroc', 'The authentic charm of Morocco', 'El encanto auténtico de Marruecos'),
        array('Niché au cœur de la médina de Marrakech, le Riad Bilkis vous accueille dans un écrin de sérénité où tradition marocaine et confort moderne se marient harmonieusement. Laissez-vous envoûter par le charme de notre patio orné de zellige, la fraîcheur de notre fontaine et la vue panoramique depuis notre terrasse.',
            'Nestled in the heart of the Marrakech medina, Riad Bilkis welcomes you to a haven of serenity where Moroccan tradition and modern comfort blend harmoniously. Let yourself be charmed by our zellige-tiled patio, the coolness of our fountain and the panoramic view from our terrace.',
            'Situado en el corazón de la medina de Marrakech, el Riad Bilkis le acoge en un remanso de serenidad donde la tradición marroquí y el confort moderno se unen en armonía. Déjese seducir por el encanto de nuestro patio de zellige, la frescura de nuestra fuente y la vista panorámica desde nuestra terraza.'),
        array('Nos atouts', 'Our strengths', 'Nuestras ventajas'),
        array('Pourquoi choisir le Riad Bilkis ?', 'Why choose Riad Bilkis?', '¿Por qué elegir el Riad Bilkis?'),
        array('Architecture authentique', 'Authentic architecture', 'Arquitectura auténtica'),
        array('Un riad traditionnel avec patio, fontaine et zellige artisanaux', 'A traditional riad with patio, fountain and handcrafted zellige tiles', 'Un riad tradicional con patio, fuente y zellige artesanal'),
        array('Emplacement idéal', 'Ideal location', 'Ubicación ideal'),
        array('Au cœur de la médina, à quelques pas de la place Jemaa el-Fna', 'In the heart of the medina, a few steps from Jemaa el-Fna square', 'En el corazón de la medina, a pocos pasos de la plaza Jemaa el-Fna'),
        array('Petit-déjeuner marocain', 'Moroccan breakfast', 'Desayuno marroquí'),
        array('Chaque matin, savourez un généreux petit-déjeuner traditionnel inclus', 'Every morning, enjoy a generous traditional breakfast, included', 'Cada mañana, disfrute de un generoso desayuno tradicional incluido'),
        array('Terrasse panoramique', 'Panoramic terrace', 'Terraza panorámica'),
        array('Vue imprenable sur les toits de la médina et l’Atlas', 'Sweeping views over the rooftops of the medina and the Atlas mountains', 'Vistas inigualables a los tejados de la medina y al Atlas'),
        array('Hébergement', 'Accommodation', 'Alojamiento'),
        array('Élégance et artisanat marocain dans une atmosphère intime aux tons chauds', 'Moroccan elegance and craftsmanship in an intimate setting of warm tones', 'Elegancia y artesanía marroquí en un ambiente íntimo de tonos cálidos'),
        array('Espace chaleureux alliant tradition et modernité dans un cadre raffiné', 'A warm space combining tradition and modernity in a refined setting', 'Un espacio acogedor que une tradición y modernidad en un marco refinado'),
        array('Voir toutes nos chambres', 'See all our rooms', 'Ver todas nuestras habitaciones'),
        array('Expériences', 'Experiences', 'Experiencias'),
        array('Transfert Aéroport', 'Airport transfer', 'Traslado al aeropuerto'),
        array('Navette privée depuis l’aéroport de Marrakech-Ménara', 'Private shuttle from Marrakech-Ménara airport', 'Traslado privado desde el aeropuerto de Marrakech-Ménara'),
        array('Découvrez les cascades d’Ouzoud, Essaouira, la vallée de l’Ourika', 'Discover the Ouzoud waterfalls, Essaouira and the Ourika valley', 'Descubra las cascadas de Ouzoud, Essaouira y el valle del Ourika'),
        array('Spa & Hammam', 'Spa & Hammam', 'Spa y hammam'),
        array('Massages traditionnels et soins dans un cadre relaxant', 'Traditional massages and treatments in a relaxing setting', 'Masajes tradicionales y tratamientos en un entorno relajante'),
        array('Cours de Cuisine', 'Cooking class', 'Clase de cocina'),
        array('Apprenez les secrets de la cuisine marocaine authentique', 'Learn the secrets of authentic Moroccan cuisine', 'Aprenda los secretos de la auténtica cocina marroquí'),
        array('Tous nos services', 'All our services', 'Todos nuestros servicios'),
        array('Réservez votre séjour au Riad Bilkis', 'Book your stay at Riad Bilkis', 'Reserve su estancia en el Riad Bilkis'),
        array('Meilleur tarif garanti en réservation directe. Petit-déjeuner inclus.', 'Best rate guaranteed when booking direct. Breakfast included.', 'Mejor tarifa garantizada reservando directamente. Desayuno incluido.'),
        array('Réserver maintenant', 'Book now', 'Reservar ahora'),
        array('Nous contacter', 'Contact us', 'Contáctenos'),
        array('Découvrir', 'Discover', 'Descubrir'),

        // — Chambres (cartes, pages détaillées)
        array('Chambre Babouche', 'Babouche Room', 'Habitación Babouche'),
        array('Chambre Tarbouche', 'Tarbouche Room', 'Habitación Tarbouche'),
        array('Chambre Véro', 'Véro Room', 'Habitación Véro'),
        array('Nos Chambres', 'Our Rooms', 'Nuestras Habitaciones'),
        array('Jaune safran et blanc', 'Saffron yellow and white', 'Amarillo azafrán y blanco'),
        array('Rouge et blanc', 'Red and white', 'Rojo y blanco'),
        array('Bleu et blanc', 'Blue and white', 'Azul y blanco'),
        array('Jaune safran et blanc, artisanat marocain et lumière douce', 'Saffron yellow and white, Moroccan craftsmanship and soft light', 'Amarillo azafrán y blanco, artesanía marroquí y luz suave'),
        array('Rouge et blanc, l’élégance d’un riad traditionnel', 'Red and white, the elegance of a traditional riad', 'Rojo y blanco, la elegancia de un riad tradicional'),
        array('Bleu et blanc, un espace apaisant avec son petit salon', 'Blue and white, a soothing space with its own small lounge', 'Azul y blanco, un espacio relajante con su pequeño salón'),
        array('Cette chambre double avec un grand lit est située au rez-de-chaussée.', 'This double room with a large bed is on the ground floor.', 'Esta habitación doble con cama grande está situada en la planta baja.'),
        array('Dans cette chambre, le jaune safran et le blanc vous feront dormir dans la quiétude.', 'In this room, saffron yellow and white invite peaceful sleep.', 'En esta habitación, el amarillo azafrán y el blanco invitan a dormir con serenidad.'),
        array('Cette chambre double, équipée de deux lits individuels ou d’un grand lit (160 x 200 cm), est située au premier étage.', 'This double room, with two single beds or one large bed (160 x 200 cm), is on the first floor.', 'Esta habitación doble, con dos camas individuales o una cama grande (160 x 200 cm), está situada en la primera planta.'),
        array('Retrouvez dans cette chambre les couleurs de Marrakech, la ville rouge.', 'This room carries the colours of Marrakech, the red city.', 'En esta habitación encontrará los colores de Marrakech, la ciudad roja.'),
        array('Cette chambre double, équipée de deux lits individuels ou d’un grand lit (160 x 200 cm), en face d’un petit salon, est située au premier étage.', 'This double room, with two single beds or one large bed (160 x 200 cm), facing a small lounge, is on the first floor.', 'Esta habitación doble, con dos camas individuales o una cama grande (160 x 200 cm), frente a un pequeño salón, está situada en la primera planta.'),
        array('Le bleu et le blanc font de cette chambre un endroit particulièrement apaisant.', 'Blue and white make this room a particularly soothing place.', 'El azul y el blanco hacen de esta habitación un lugar especialmente relajante.'),
        array('Grand lit double', 'Large double bed', 'Cama doble grande'),
        array('Deux lits individuels ou un grand lit (160 x 200 cm)', 'Two single beds or one large bed (160 x 200 cm)', 'Dos camas individuales o una cama grande (160 x 200 cm)'),
        array('Petit salon en face de la chambre', 'Small lounge facing the room', 'Pequeño salón frente a la habitación'),
        array('Wi-Fi haut débit', 'High-speed Wi-Fi', 'Wi-Fi de alta velocidad'),
        array('Climatisation réversible', 'Reversible air conditioning', 'Aire acondicionado reversible'),
        array('Coffre-fort', 'Safe', 'Caja fuerte'),
        array('Bouteille d’eau', 'Bottle of water', 'Botella de agua'),
        array('Produits de toilette Les Sens de Marrakech', 'Les Sens de Marrakech toiletries', 'Productos de aseo Les Sens de Marrakech'),
        array('Serviettes et linge de maison', 'Towels and household linen', 'Toallas y ropa de casa'),
        array('Salle de bain avec douche et toilettes, murs en tadelakt et sol en carreaux de ciment.', 'Bathroom with shower and toilet, tadelakt walls and cement-tile floor.', 'Baño con ducha y aseo, paredes de tadelakt y suelo de baldosas de cemento.'),
        array('Salle de bain avec toilettes privées à l’extérieur de la chambre, à 2 m à pied : douche, murs en tadelakt et sol en carreaux de ciment.', 'Bathroom with private toilet just outside the room, 2 m away: shower, tadelakt walls and cement-tile floor.', 'Baño con aseo privado justo fuera de la habitación, a 2 m: ducha, paredes de tadelakt y suelo de baldosas de cemento.'),
        array('Équipements et caractéristiques', 'Amenities and features', 'Equipamiento y características'),
        array('Salle de bain', 'Bathroom', 'Baño'),
        array('Description', 'Description', 'Descripción'),
        array('Voir les autres chambres', 'See the other rooms', 'Ver las otras habitaciones'),
        array('Photo à venir', 'Photo coming soon', 'Foto próximamente'),

        // — Galerie
        array('Galerie Photos', 'Photo Gallery', 'Galería de fotos'),
        array('Découvrez le Riad Bilkis en images — architecture traditionnelle, chambres élégantes, terrasse panoramique et jardins intérieurs.', 'Discover Riad Bilkis in pictures — traditional architecture, elegant rooms, panoramic terrace and inner gardens.', 'Descubra el Riad Bilkis en imágenes: arquitectura tradicional, habitaciones elegantes, terraza panorámica y jardines interiores.'),
        array('Le Riad', 'The Riad', 'El Riad'),
        array('Photos du riad à venir — patio central avec fontaine, architecture en zellige et tadelakt, terrasse panoramique avec vue sur la médina et l’Atlas.', 'Photos of the riad coming soon — central patio with fountain, zellige and tadelakt architecture, panoramic terrace overlooking the medina and the Atlas.', 'Fotos del riad próximamente: patio central con fuente, arquitectura de zellige y tadelakt, terraza panorámica con vistas a la medina y al Atlas.'),
        array('Les Chambres', 'The Rooms', 'Las Habitaciones'),
        array('Photos des trois chambres — Babouche, Tarbouche et Véro — avec leurs détails décoratifs uniques et salles de bain.', 'Photos of the three rooms — Babouche, Tarbouche and Véro — with their unique decorative details and bathrooms.', 'Fotos de las tres habitaciones — Babouche, Tarbouche y Véro — con sus detalles decorativos únicos y sus baños.'),
        array('Espaces Communs', 'Shared Spaces', 'Espacios comunes'),
        array('Photos du salon marocain, de la salle à manger, du jardin intérieur et de la terrasse sur le toit.', 'Photos of the Moroccan lounge, the dining room, the inner garden and the rooftop terrace.', 'Fotos del salón marroquí, el comedor, el jardín interior y la terraza en la azotea.'),
        array('Petit-déjeuner & Gastronomie', 'Breakfast & Food', 'Desayuno y gastronomía'),
        array('Photos du petit-déjeuner marocain traditionnel servi chaque matin — crêpes msemen, pain frais, confitures maison, jus d’orange pressé.', 'Photos of the traditional Moroccan breakfast served every morning — msemen pancakes, fresh bread, homemade jams, freshly squeezed orange juice.', 'Fotos del desayuno marroquí tradicional servido cada mañana: crepes msemen, pan recién hecho, mermeladas caseras y zumo de naranja natural.'),
        array('Galerie photo en cours de mise à jour. Contactez-nous pour plus d’images ou consultez nos avis sur TripAdvisor et Google.', 'Photo gallery being updated. Contact us for more images, or read our reviews on TripAdvisor and Google.', 'Galería de fotos en actualización. Contáctenos para más imágenes o consulte nuestras opiniones en TripAdvisor y Google.'),

        // — Nos services
        array('Nos Services', 'Our Services', 'Nuestros Servicios'),
        array('<h3>Excursions</h3>', '<h3>Excursions</h3>', '<h3>Excursiones</h3>'),
        array('Au Riad Bilkis, nous nous engageons à rendre votre séjour inoubliable. Découvrez nos services personnalisés.', 'At Riad Bilkis we are committed to making your stay unforgettable. Discover our personalised services.', 'En el Riad Bilkis nos comprometemos a hacer que su estancia sea inolvidable. Descubra nuestros servicios personalizados.'),
        array('Petit-déjeuner Marocain', 'Moroccan Breakfast', 'Desayuno marroquí'),
        array('Chaque matin, savourez un authentique petit-déjeuner marocain préparé avec amour :', 'Every morning, enjoy an authentic Moroccan breakfast prepared with care:', 'Cada mañana, disfrute de un auténtico desayuno marroquí preparado con esmero:'),
        array('Msemen et baghrir (crêpes marocaines) faits maison', 'Homemade msemen and baghrir (Moroccan pancakes)', 'Msemen y baghrir (crepes marroquíes) caseros'),
        array('Pain frais cuit au four traditionnel', 'Fresh bread baked in a traditional oven', 'Pan recién hecho en horno tradicional'),
        array('Confitures maison (figue, abricot, orange amère)', 'Homemade jams (fig, apricot, bitter orange)', 'Mermeladas caseras (higo, albaricoque, naranja amarga)'),
        array('Miel pur de l’Atlas', 'Pure Atlas honey', 'Miel puro del Atlas'),
        array('Huile d’olive et amlou', 'Olive oil and amlou', 'Aceite de oliva y amlou'),
        array('Jus d’orange fraîchement pressé', 'Freshly squeezed orange juice', 'Zumo de naranja recién exprimido'),
        array('Thé à la menthe et café', 'Mint tea and coffee', 'Té a la menta y café'),
        array('Fruits de saison', 'Seasonal fruit', 'Fruta de temporada'),
        array('Servi sur la terrasse ou dans le patio selon votre préférence. Inclus dans le tarif de la chambre.', 'Served on the terrace or in the patio, as you prefer. Included in the room rate.', 'Servido en la terraza o en el patio, como prefiera. Incluido en el precio de la habitación.'),
        array('Transferts Aéroport', 'Airport Transfers', 'Traslados al aeropuerto'),
        array('Service de transfert privé avec chauffeur depuis/vers l’aéroport de Marrakech-Ménara :', 'Private chauffeur transfer service to and from Marrakech-Ménara airport:', 'Servicio de traslado privado con conductor desde y hacia el aeropuerto de Marrakech-Ménara:'),
        array('Transfert simple :', 'One-way transfer:', 'Traslado de ida:'),
        array('Transfert A/R :', 'Return transfer:', 'Traslado de ida y vuelta:'),
        array('Véhicule climatisé et confortable', 'Comfortable air-conditioned vehicle', 'Vehículo climatizado y cómodo'),
        array('Chauffeur vous attend à la sortie avec panneau nominatif', 'Driver waiting at the exit with a name sign', 'El conductor le espera a la salida con un cartel con su nombre'),
        array('Disponible 24h/24, 7j/7', 'Available 24/7', 'Disponible 24 h, 7 días'),
        array('Bien-être & Hammam', 'Wellness & Hammam', 'Bienestar y hammam'),
        array('Vivez l’expérience du hammam traditionnel marocain :', 'Experience the traditional Moroccan hammam:', 'Viva la experiencia del hammam tradicional marroquí:'),
        array('Gommage au savon noir et gant de kessa', 'Black soap scrub with a kessa glove', 'Exfoliación con jabón negro y guante de kessa'),
        array('Enveloppement au ghassoul', 'Ghassoul clay wrap', 'Envoltura de ghassoul'),
        array('Massage à l’huile d’argan', 'Argan oil massage', 'Masaje con aceite de argán'),
        array('Soin des pieds et des mains au henné', 'Henna hand and foot care', 'Cuidado de manos y pies con henna'),
        array('Sur réservation, dans notre hammam privé ou dans un hammam partenaire.', 'On request, in our private hammam or at a partner hammam.', 'Con reserva previa, en nuestro hammam privado o en un hammam asociado.'),
        array('Dîners sur Réservation', 'Dinner on Request', 'Cenas con reserva'),
        array('Notre cuisinière prépare de délicieux dîners marocains sur demande :', 'Our cook prepares delicious Moroccan dinners on request:', 'Nuestra cocinera prepara deliciosas cenas marroquíes a petición:'),
        array('Tajine traditionnel (poulet, agneau, légumes)', 'Traditional tagine (chicken, lamb, vegetables)', 'Tajine tradicional (pollo, cordero, verduras)'),
        array('Couscous du vendredi', 'Friday couscous', 'Cuscús del viernes'),
        array('Pastilla au poulet et amandes', 'Chicken and almond pastilla', 'Pastela de pollo y almendras'),
        array('Brochettes grillées', 'Grilled skewers', 'Brochetas a la parrilla'),
        array('Menu végétarien disponible', 'Vegetarian menu available', 'Menú vegetariano disponible'),
        array('À partir de 15€ par personne', 'From €15 per person', 'Desde 15 € por persona'),
        array('Réservation 24h à l’avance.', 'Please book 24 hours in advance.', 'Reserva con 24 h de antelación.'),
        array('Autres Services', 'Other Services', 'Otros servicios'),
        array('Conciergerie 24h/24', '24-hour concierge service', 'Conserjería 24 h'),
        array('Wi-Fi gratuit haut débit', 'Free high-speed Wi-Fi', 'Wi-Fi gratuito de alta velocidad'),
        array('Service de blanchisserie', 'Laundry service', 'Servicio de lavandería'),
        array('Réservation de taxis', 'Taxi booking', 'Reserva de taxis'),
        array('Conseils touristiques', 'Sightseeing advice', 'Consejos turísticos'),
        array('Plans de la médina', 'Maps of the medina', 'Planos de la medina'),
        array('Consigne à bagages', 'Luggage storage', 'Consigna de equipaje'),
        array('Thé de bienvenue offert', 'Complimentary welcome tea', 'Té de bienvenida gratuito'),

        // — Contact
        array('Contact et Localisation', 'Contact and Location', 'Contacto y ubicación'),
        array('N’hésitez pas à nous contacter pour toute question ou demande de réservation.', 'Please do not hesitate to contact us with any question or booking request.', 'No dude en contactarnos para cualquier duda o solicitud de reserva.'),
        array('Adresse', 'Address', 'Dirección'),
        array('Téléphone', 'Phone', 'Teléfono'),
        array('Maroc', 'Morocco', 'Marruecos'),
        array('Contactez-nous directement sur WhatsApp pour une réponse rapide :', 'Message us directly on WhatsApp for a quick reply:', 'Escríbanos directamente por WhatsApp para una respuesta rápida:'),
        array('Écrire sur WhatsApp', 'Message us on WhatsApp', 'Escribir por WhatsApp'),
        array('Coordonnées GPS', 'GPS coordinates', 'Coordenadas GPS'),
        array('Ouvrir dans Google Maps', 'Open in Google Maps', 'Abrir en Google Maps'),
        array('Plan d’accès', 'How to find us', 'Cómo llegar'),
        array('Informations pratiques', 'Practical information', 'Información práctica'),
        array('Check-in :', 'Check-in:', 'Entrada:'),
        array('Check-out :', 'Check-out:', 'Salida:'),
        array('à partir de 14h00', 'from 2 p.m.', 'a partir de las 14:00'),
        array('avant 11h00', 'before 11 a.m.', 'antes de las 11:00'),
        array('Réception :', 'Reception:', 'Recepción:'),
        array('24h/24 (contact obligatoire par WhatsApp avant l’arrivée)', '24 hours (please contact us on WhatsApp before arrival)', '24 h (es necesario contactarnos por WhatsApp antes de llegar)'),
        array('Langues parlées :', 'Languages spoken:', 'Idiomas hablados:'),
        array('Français, Anglais, Arabe', 'French, English, Arabic', 'Francés, inglés, árabe'),
        array('Formulaire de Contact', 'Contact Form', 'Formulario de contacto'),
        array('Votre nom complet', 'Your full name', 'Su nombre completo'),
        array('Votre nom', 'Your name', 'Su nombre'),
        array('Votre email', 'Your e-mail', 'Su correo electrónico'),
        array('Sujet', 'Subject', 'Asunto'),
        array('Objet de votre message', 'Subject of your message', 'Asunto de su mensaje'),
        array('Dates de séjour souhaitées', 'Preferred dates of stay', 'Fechas de estancia deseadas'),
        array('votre@email.com', 'your@email.com', 'su@email.com'),
        array('Ex: du 15 au 20 janvier 2027', 'E.g. 15 to 20 January 2027', 'Ej.: del 15 al 20 de enero de 2027'),
        array('Votre message', 'Your message', 'Su mensaje'),
        array('Décrivez votre demande...', 'Describe your request...', 'Describa su solicitud...'),
        array('Envoyer le message', 'Send the message', 'Enviar el mensaje'),
        array('Nous répondons généralement dans les 2 heures pendant les heures ouvrables (8h-22h, heure de Marrakech).', 'We usually reply within two hours during opening hours (8 a.m.–10 p.m., Marrakech time).', 'Normalmente respondemos en dos horas durante el horario de atención (8:00–22:00, hora de Marrakech).'),

        // — Réservation
        array('Réservation Freetobook', 'Freetobook booking', 'Reserva Freetobook'),
        array('Consultez nos disponibilités et réservez directement votre séjour au Riad Bilkis.', 'Check our availability and book your stay at Riad Bilkis directly.', 'Consulte nuestra disponibilidad y reserve directamente su estancia en el Riad Bilkis.'),
        array('Vérifier les disponibilités', 'Check availability', 'Comprobar disponibilidad'),
        array('Arrivée', 'Arrival', 'Llegada'),
        array('Départ', 'Departure', 'Salida'),
        array('Adultes', 'Adults', 'Adultos'),
        array('Enfants', 'Children', 'Niños'),
        array('Les champs obligatoires sont suivis de', 'Required fields are followed by', 'Los campos obligatorios están seguidos de'),
        array('Prix à partir de:', 'Price from:', 'Precio desde:'),
        array('par nuit', 'per night', 'por noche'),
        array('Voir les détails', 'See details', 'Ver los detalles'),
        array('Réserver', 'Book', 'Reservar'),
        array('Détails', 'Details', 'Detalles'),
        array('Informations de réservation', 'Booking information', 'Información de reserva'),
        array('Meilleur tarif garanti', 'Best rate guaranteed', 'Mejor tarifa garantizada'),
        array('En réservant directement sur notre site, vous bénéficiez du tarif le plus bas garanti — pas de commissions intermédiaires.', 'Booking directly on our website guarantees you the lowest rate — with no intermediary commission.', 'Reservando directamente en nuestra web obtiene la tarifa más baja garantizada, sin comisiones de intermediarios.'),
        array('Annulation flexible', 'Flexible cancellation', 'Cancelación flexible'),
        array('Annulation gratuite jusqu’à 48 heures avant votre arrivée. Pas de frais cachés.', 'Free cancellation up to 48 hours before arrival. No hidden fees.', 'Cancelación gratuita hasta 48 horas antes de la llegada. Sin gastos ocultos.'),
        array('Paiement sécurisé', 'Secure payment', 'Pago seguro'),
        array('Paiement par carte bancaire ou virement. Aucun prélèvement avant votre arrivée (selon conditions).', 'Payment by card or bank transfer. No charge before arrival (conditions apply).', 'Pago con tarjeta o transferencia. Sin cargos antes de la llegada (según condiciones).'),
        array('Nos tarifs', 'Our rates', 'Nuestras tarifas'),
        array('Petit-déjeuner inclus', 'Breakfast included', 'Desayuno incluido'),
        array('Tarifs valables toute l’année. Supplément haute saison possible (Noël, Nouvel An, festivals). Contactez-nous pour les longs séjours (réduction à partir de 5 nuits).', 'Rates valid all year round. A high-season supplement may apply (Christmas, New Year, festivals). Contact us for long stays (discount from 5 nights).', 'Tarifas válidas todo el año. Puede aplicarse un suplemento en temporada alta (Navidad, Año Nuevo, festivales). Contáctenos para estancias largas (descuento a partir de 5 noches).'),
        array('Réservation', 'Booking', 'Reserva'),
        array('Réservez directement et bénéficiez du meilleur tarif garanti — sans frais de commission.', 'Book direct and enjoy the guaranteed best rate — with no commission fees.', 'Reserve directamente y disfrute de la mejor tarifa garantizada, sin comisiones.'),
    );
    $strings = array();
    foreach ($rows as $row) {
        $strings[$row[0]] = array('en' => $row[1], 'es' => $row[2]);
    }
    return $strings;
}
