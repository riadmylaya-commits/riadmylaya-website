<?php
/**
 * Plugin Name: Riad Bilkis Excursions
 * Description: Pages excursions (liste + fiche détaillée) générées depuis la base commune data/excursions.json, en FR/EN/ES, avec formulaire de demande et données structurées.
 * Version: 1.0
 * Author: Devin
 */

if (!defined('ABSPATH')) exit;

// Base commune Mylaya / Bilkis : un seul fichier à modifier pour les textes,
// les photos et les tarifs. Le premier chemin lisible est utilisé.
const RIAD_BILKIS_EXC_DATA_PATHS = array(
    '/home/riaductd/riadbilkis.com/data/excursions.json',
    ABSPATH . 'data/excursions.json',
);
const RIAD_BILKIS_EXC_IMG_BASE = '/img/excursions/';
const RIAD_BILKIS_EXC_BASES = array(
    'fr' => '/excursions',
    'en' => '/en/excursions',
    'es' => '/es/excursiones',
);

function riad_bilkis_exc_data() {
    static $data = null;
    if ($data !== null) return $data;
    $data = array();
    foreach (RIAD_BILKIS_EXC_DATA_PATHS as $path) {
        if (!is_readable($path)) continue;
        $decoded = json_decode(file_get_contents($path), true);
        if (is_array($decoded) && !empty($decoded['excursions'])) {
            $data = $decoded['excursions'];
            break;
        }
    }
    usort($data, function ($a, $b) {
        return ((int) $a['order']) - ((int) $b['order']);
    });
    return $data;
}

function riad_bilkis_exc_find($slug) {
    foreach (riad_bilkis_exc_data() as $exc) {
        if ($exc['slug'] === $slug) return $exc;
    }
    return null;
}

function riad_bilkis_exc_t($field, $lang) {
    if (!is_array($field)) return $field;
    if (isset($field[$lang])) return $field[$lang];
    return isset($field['fr']) ? $field['fr'] : '';
}

// Tarif applicable : « pricing_par_riad.bilkis » s'il existe, sinon le tarif commun.
function riad_bilkis_exc_pricing($exc) {
    if (isset($exc['pricing_par_riad']['bilkis']) && is_array($exc['pricing_par_riad']['bilkis'])) {
        return $exc['pricing_par_riad']['bilkis'] + $exc['pricing'];
    }
    return $exc['pricing'];
}

function riad_bilkis_exc_rate($pricing, $people) {
    $tiers = isset($pricing['tiers']) ? $pricing['tiers'] : array();
    for ($i = min($people, 4); $i >= 1; $i--) {
        if (isset($tiers[(string) $i])) return (int) $tiers[(string) $i];
    }
    return 0;
}

function riad_bilkis_exc_total($pricing, $people) {
    $min = isset($pricing['min_people']) ? (int) $pricing['min_people'] : 1;
    if ($people < $min) return 0;
    if (isset($pricing['type']) && $pricing['type'] === 'flat_then_per_person') {
        $flat_up_to = (int) $pricing['flat_up_to'];
        if ($people <= $flat_up_to) return (int) $pricing['flat_price'];
        if ($people === $flat_up_to + 1) return (int) $pricing['price_4'];
        return (int) $pricing['price_4'] + ($people - $flat_up_to - 1) * (int) $pricing['extra_per_person'];
    }
    return riad_bilkis_exc_rate($pricing, $people) * $people;
}

function riad_bilkis_exc_from_price($pricing) {
    $min = isset($pricing['min_people']) ? (int) $pricing['min_people'] : 1;
    if (isset($pricing['type']) && $pricing['type'] === 'flat_then_per_person') {
        return (int) $pricing['flat_price'];
    }
    return riad_bilkis_exc_rate($pricing, $min);
}

function riad_bilkis_exc_texts($lang) {
    $texts = array(
        'fr' => array(
            'label' => 'Excursions au départ de Marrakech',
            'title' => 'Nos Excursions',
            'intro' => 'Explorez les merveilles du Maroc au départ du Riad Bilkis : désert d\'Agafay, montagnes de l\'Atlas, côte atlantique et kasbahs du sud. Chaque excursion est organisée en privé pour vous, avec chauffeur et guide local.',
            'details' => 'Voir les détails',
            'from' => 'À partir de',
            'all' => 'Toutes les excursions',
            'back' => 'Voir toutes les excursions',
            'others' => 'Voir les autres excursions',
            'book_title' => 'Réserver cette excursion',
            'book_text' => 'Indiquez le nombre de personnes et la date : nous confirmons la disponibilité par e-mail.',
            'people' => 'Nombre exact de personnes',
            'date' => 'Date souhaitée',
            'total' => 'Prix total',
            'name' => 'Nom complet',
            'email' => 'E-mail',
            'phone' => 'Téléphone',
            'message' => 'Message / remarques',
            'send' => 'Envoyer la demande',
            'note' => 'Réponse par e-mail sous 24 h. Aucun paiement en ligne à cette étape.',
            'included' => 'Inclus',
            'excluded' => 'Non inclus',
            'inc_title' => 'Inclus / non inclus',
            'price_title' => 'Tarifs',
            'person' => 'personne',
            'persons' => 'personnes',
            'per_person' => '/ pers.',
            'plus' => '4+ personnes',
            'flat_rows' => array('1 à 3 personnes', '4 personnes', 'À partir de 5 personnes'),
            'flat_note' => 'par personne supplémentaire',
            'package' => 'forfait',
            'stay_title' => 'Réserver votre séjour au Riad Bilkis',
            'min_note' => 'Excursion à partir de %d personnes.',
        ),
        'en' => array(
            'label' => 'Excursions from Marrakech',
            'title' => 'Our Excursions',
            'intro' => 'Explore the wonders of Morocco from Riad Bilkis: the Agafay desert, the Atlas mountains, the Atlantic coast and the kasbahs of the south. Every excursion is arranged privately for you, with a driver and a local guide.',
            'details' => 'See details',
            'from' => 'From',
            'all' => 'All excursions',
            'back' => 'See all excursions',
            'others' => 'See the other excursions',
            'book_title' => 'Book this excursion',
            'book_text' => 'Tell us the number of people and the date: we confirm availability by email.',
            'people' => 'Exact number of people',
            'date' => 'Preferred date',
            'total' => 'Total price',
            'name' => 'Full name',
            'email' => 'Email',
            'phone' => 'Phone',
            'message' => 'Message / remarks',
            'send' => 'Send the request',
            'note' => 'Answer by email within 24 h. No online payment at this stage.',
            'included' => 'Included',
            'excluded' => 'Not included',
            'inc_title' => 'Included / not included',
            'price_title' => 'Prices',
            'person' => 'person',
            'persons' => 'people',
            'per_person' => '/ pers.',
            'plus' => '4+ people',
            'flat_rows' => array('1 to 3 people', '4 people', 'From 5 people'),
            'flat_note' => 'per additional person',
            'package' => 'package',
            'stay_title' => 'Book your stay at Riad Bilkis',
            'min_note' => 'Excursion from %d people.',
        ),
        'es' => array(
            'label' => 'Excursiones desde Marrakech',
            'title' => 'Nuestras Excursiones',
            'intro' => 'Explore las maravillas de Marruecos desde el Riad Bilkis: el desierto de Agafay, las montañas del Atlas, la costa atlántica y las kasbahs del sur. Cada excursión se organiza en privado para usted, con chófer y guía local.',
            'details' => 'Ver los detalles',
            'from' => 'Desde',
            'all' => 'Todas las excursiones',
            'back' => 'Ver todas las excursiones',
            'others' => 'Ver las otras excursiones',
            'book_title' => 'Reservar esta excursión',
            'book_text' => 'Indique el número de personas y la fecha: confirmamos la disponibilidad por correo electrónico.',
            'people' => 'Número exacto de personas',
            'date' => 'Fecha deseada',
            'total' => 'Precio total',
            'name' => 'Nombre completo',
            'email' => 'Correo electrónico',
            'phone' => 'Teléfono',
            'message' => 'Mensaje / observaciones',
            'send' => 'Enviar la solicitud',
            'note' => 'Respuesta por correo electrónico en 24 h. Ningún pago en línea en esta etapa.',
            'included' => 'Incluido',
            'excluded' => 'No incluido',
            'inc_title' => 'Incluido / no incluido',
            'price_title' => 'Tarifas',
            'person' => 'persona',
            'persons' => 'personas',
            'per_person' => '/ pers.',
            'plus' => '4+ personas',
            'flat_rows' => array('1 a 3 personas', '4 personas', 'A partir de 5 personas'),
            'flat_note' => 'por persona adicional',
            'package' => 'paquete',
            'stay_title' => 'Reserve su estancia en el Riad Bilkis',
            'min_note' => 'Excursión a partir de %d personas.',
        ),
    );
    return isset($texts[$lang]) ? $texts[$lang] : $texts['fr'];
}

function riad_bilkis_exc_distance($exc, $lang) {
    $km = (int) $exc['distance_km'];
    $suffix = array('fr' => ' km depuis Marrakech', 'en' => ' km from Marrakech', 'es' => ' km desde Marrakech');
    return $km . (isset($suffix[$lang]) ? $suffix[$lang] : $suffix['fr']);
}

function riad_bilkis_exc_url($slug, $lang) {
    $base = isset(RIAD_BILKIS_EXC_BASES[$lang]) ? RIAD_BILKIS_EXC_BASES[$lang] : RIAD_BILKIS_EXC_BASES['fr'];
    return $slug === '' ? $base . '/' : $base . '/' . $slug . '/';
}

// ── Routage : /excursions, /excursions/<slug> et équivalents EN/ES ────────────
function riad_bilkis_exc_route() {
    static $route = false;
    if ($route !== false) return $route;
    $route = null;
    $uri  = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
    $path = '/' . trim($uri, '/');
    foreach (RIAD_BILKIS_EXC_BASES as $lang => $base) {
        if ($path === $base) {
            $route = array('lang' => $lang, 'slug' => '');
            break;
        }
        if (strpos($path, $base . '/') === 0) {
            $slug = trim(substr($path, strlen($base) + 1), '/');
            if (preg_match('/^[a-z0-9-]+$/', $slug) && riad_bilkis_exc_find($slug)) {
                $route = array('lang' => $lang, 'slug' => $slug);
            }
            break;
        }
    }
    return $route;
}

// ── Rendu : liste ────────────────────────────────────────────────────────────
function riad_bilkis_exc_card($exc, $lang, $t) {
    $url   = riad_bilkis_exc_url($exc['slug'], $lang);
    $title = riad_bilkis_exc_t($exc['title'], $lang);
    $price = riad_bilkis_exc_from_price(riad_bilkis_exc_pricing($exc));

    return '<article class="rb-exc-card">'
        . '<a class="rb-exc-card__media" href="' . esc_url($url) . '" tabindex="-1" aria-hidden="true">'
        . '<img src="' . esc_url(RIAD_BILKIS_EXC_IMG_BASE . $exc['image']) . '" width="800" height="600"'
        . ' alt="' . esc_attr($title) . '" loading="lazy" decoding="async">'
        . '<span class="rb-exc-badge rb-exc-badge--time">' . esc_html(riad_bilkis_exc_t($exc['duration'], $lang)) . '</span>'
        . '<span class="rb-exc-badge rb-exc-badge--price">' . esc_html($t['from'] . ' ' . $price . ' €') . '</span>'
        . '</a>'
        . '<div class="rb-exc-card__body">'
        . '<h2 class="rb-exc-card__title"><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></h2>'
        . '<p class="rb-exc-card__distance">' . esc_html(riad_bilkis_exc_distance($exc, $lang)) . '</p>'
        . '<p class="rb-exc-card__desc">' . esc_html(riad_bilkis_exc_t($exc['short'], $lang)) . '</p>'
        . '<a class="rb-exc-card__btn" href="' . esc_url($url) . '">' . esc_html($t['details']) . '</a>'
        . '</div></article>';
}

function riad_bilkis_exc_index_html($lang) {
    $t     = riad_bilkis_exc_texts($lang);
    $cards = '';
    foreach (riad_bilkis_exc_data() as $exc) {
        $cards .= riad_bilkis_exc_card($exc, $lang, $t);
    }
    return '<div class="rb-exc">'
        . '<header class="rb-exc__head">'
        . '<span class="rb-section-label">' . esc_html($t['label']) . '</span>'
        . '<h1 class="rb-exc__title">' . esc_html($t['title']) . '</h1>'
        . '<div class="rb-section-line"></div>'
        . '<p class="rb-exc__intro">' . esc_html($t['intro']) . '</p>'
        . '</header>'
        . '<div class="rb-exc__grid">' . $cards . '</div>'
        . '</div>'
        . (function_exists('riad_bilkis_choice_section') ? riad_bilkis_choice_section() : '');
}

// ── Rendu : fiche détaillée ──────────────────────────────────────────────────
function riad_bilkis_exc_price_rows($pricing, $t) {
    $rows = '';
    if (isset($pricing['type']) && $pricing['type'] === 'flat_then_per_person') {
        $labels = $t['flat_rows'];
        $rows .= '<div class="rb-exc-price__row"><span>' . esc_html($labels[0]) . '</span><span>'
            . esc_html((int) $pricing['flat_price'] . ' € (' . $t['package'] . ')') . '</span></div>';
        $rows .= '<div class="rb-exc-price__row"><span>' . esc_html($labels[1]) . '</span><span>'
            . esc_html((int) $pricing['price_4'] . ' €') . '</span></div>';
        $rows .= '<div class="rb-exc-price__row"><span>' . esc_html($labels[2]) . '</span><span>'
            . esc_html((int) $pricing['price_4'] . ' € + ' . (int) $pricing['extra_per_person'] . ' € ' . $t['flat_note'])
            . '</span></div>';
        return $rows;
    }
    $tiers = isset($pricing['tiers']) ? $pricing['tiers'] : array();
    foreach ($tiers as $people => $rate) {
        $n = (int) $people;
        if ($n >= 4) {
            $label = $t['plus'];
            $value = (int) $rate . ' € ' . $t['per_person'];
        } elseif ($n === 1) {
            $label = '1 ' . $t['person'];
            $value = (int) $rate . ' €';
        } else {
            $label = $n . ' ' . $t['persons'];
            $value = (int) $rate . ' € ' . $t['per_person'] . ' = ' . ((int) $rate * $n) . ' €';
        }
        $rows .= '<div class="rb-exc-price__row"><span>' . esc_html($label) . '</span><span>'
            . esc_html($value) . '</span></div>';
    }
    return $rows;
}

function riad_bilkis_exc_body_html($blocks) {
    $html = '';
    foreach ($blocks as $block) {
        if ($block['type'] === 'ul') {
            $items = '';
            foreach ($block['items'] as $item) {
                $items .= '<li>' . esc_html($item) . '</li>';
            }
            $html .= '<ul>' . $items . '</ul>';
        } else {
            $html .= '<p>' . esc_html($block['text']) . '</p>';
        }
    }
    return $html;
}

function riad_bilkis_exc_detail_html($exc, $lang) {
    $t       = riad_bilkis_exc_texts($lang);
    $title   = riad_bilkis_exc_t($exc['title'], $lang);
    $pricing = riad_bilkis_exc_pricing($exc);
    $min     = isset($pricing['min_people']) ? (int) $pricing['min_people'] : 1;
    $img     = RIAD_BILKIS_EXC_IMG_BASE . $exc['image'];

    $lists = array('included', 'excluded');
    $cols  = '';
    foreach ($lists as $key) {
        $items = '';
        foreach (riad_bilkis_exc_t($exc[$key], $lang) as $line) {
            $items .= '<li>' . esc_html($line) . '</li>';
        }
        $cols .= '<div class="rb-exc-inc__col rb-exc-inc__col--' . esc_attr($key) . '">'
            . '<h3>' . esc_html($t[$key === 'included' ? 'included' : 'excluded']) . '</h3>'
            . '<ul>' . $items . '</ul></div>';
    }

    $steps = '';
    foreach (riad_bilkis_exc_t($exc['itinerary'], $lang) as $step) {
        $steps .= '<div class="rb-exc-itin__step"><span class="rb-exc-itin__time">' . esc_html($step['time'])
            . '</span><span class="rb-exc-itin__dot"></span><span class="rb-exc-itin__label">'
            . esc_html($step['label']) . '</span></div>';
    }

    // Trois autres excursions, en repartant de la suivante dans la liste.
    $all   = riad_bilkis_exc_data();
    $index = 0;
    foreach ($all as $i => $item) {
        if ($item['slug'] === $exc['slug']) $index = $i;
    }
    $count  = count($all);
    $others = '';
    for ($i = 1; $i <= 3; $i++) {
        $other = $all[($index + $i) % $count];
        if ($other['slug'] === $exc['slug']) continue;
        $others .= '<a class="rb-exc-other" href="' . esc_url(riad_bilkis_exc_url($other['slug'], $lang)) . '">'
            . '<span class="rb-exc-other__img" style="background-image:url(\''
            . esc_url(RIAD_BILKIS_EXC_IMG_BASE . $other['image']) . '\')"></span>'
            . '<span class="rb-exc-other__name">' . esc_html(riad_bilkis_exc_t($other['title'], $lang)) . '</span>'
            . '</a>';
    }

    $form = '<form class="rb-exc-form" data-rb-form="excursion" data-lang="' . esc_attr($lang) . '"'
        . ' data-rb-pricing="' . esc_attr(wp_json_encode($pricing)) . '">'
        . '<input type="hidden" name="excursion" value="' . esc_attr($title) . '">'
        . '<input type="hidden" name="slug" value="' . esc_attr($exc['slug']) . '">'
        . '<input type="hidden" name="total" value="" data-rb-total-field>'
        . '<div class="rb-exc-form__row">'
        . '<label>' . esc_html($t['people']) . '<input type="number" name="people" min="' . $min . '" max="20"'
        . ' value="' . $min . '" required data-rb-people></label>'
        . '<label>' . esc_html($t['date']) . '<input type="date" name="date" required></label>'
        . '</div>'
        . '<div class="rb-exc-form__total"><span>' . esc_html($t['total']) . '</span>'
        . '<strong data-rb-total-display>' . esc_html(riad_bilkis_exc_total($pricing, $min) . ' €') . '</strong></div>'
        . ($min > 1 ? '<p class="rb-exc-form__min">' . esc_html(sprintf($t['min_note'], $min)) . '</p>' : '')
        . '<div class="rb-exc-form__row">'
        . '<label>' . esc_html($t['name']) . '<input type="text" name="name" required></label>'
        . '<label>' . esc_html($t['email']) . '<input type="email" name="email" required></label>'
        . '</div>'
        . '<div class="rb-exc-form__row">'
        . '<label>' . esc_html($t['phone']) . '<input type="tel" name="phone"></label>'
        . '</div>'
        . '<label>' . esc_html($t['message']) . '<textarea name="message" rows="3"></textarea></label>'
        . '<button type="submit" class="rb-official__btn">' . esc_html($t['send']) . '</button>'
        . '<p class="rb-form__status" data-rb-status></p>'
        . '<p class="rb-exc-form__note">' . esc_html($t['note']) . '</p>'
        . '</form>';

    $stay = '';
    if (function_exists('riad_bilkis_official_texts') && function_exists('riad_bilkis_promo_box')) {
        $o = riad_bilkis_official_texts($lang);
        $stay = '<div class="rb-exc-stay"><h2>' . esc_html($t['stay_title']) . '</h2>'
            . '<p class="rb-official__btn-wrap"><a class="rb-official__btn" href="'
            . esc_url(RIAD_BILKIS_OFFICIAL_URL) . '" target="_blank" rel="noopener noreferrer">'
            . esc_html($o['cta']) . '</a></p>' . riad_bilkis_promo_box($o) . '</div>';
    }

    return '<div class="rb-exc-detail">'
        . '<a class="rb-exc-detail__back" href="' . esc_url(riad_bilkis_exc_url('', $lang)) . '">&larr; '
        . esc_html($t['all']) . '</a>'
        . '<figure class="rb-exc-detail__hero">'
        . '<img src="' . esc_url($img) . '" width="800" height="600" alt="' . esc_attr($title) . '" decoding="async">'
        . '<span class="rb-exc-badge rb-exc-badge--time">' . esc_html(riad_bilkis_exc_t($exc['duration'], $lang)) . '</span>'
        . '<span class="rb-exc-badge rb-exc-badge--price">' . esc_html($t['from'] . ' ' . riad_bilkis_exc_from_price($pricing) . ' €') . '</span>'
        . '</figure>'
        . '<h1 class="rb-exc-detail__title">' . esc_html($title) . '</h1>'
        . '<p class="rb-exc-detail__distance">' . esc_html(riad_bilkis_exc_distance($exc, $lang)) . '</p>'
        . '<div class="rb-exc-detail__body">' . riad_bilkis_exc_body_html(riad_bilkis_exc_t($exc['body'], $lang)) . '</div>'
        . '<section class="rb-exc-book" id="rb-exc-book"><h2>' . esc_html($t['book_title']) . '</h2>'
        . '<p class="rb-exc-book__text">' . esc_html($t['book_text']) . '</p>'
        . '<div class="rb-exc-price"><h3>' . esc_html($t['price_title']) . '</h3>'
        . riad_bilkis_exc_price_rows($pricing, $t) . '</div>'
        . $form . '</section>'
        . '<section class="rb-exc-inc"><h2>' . esc_html($t['inc_title']) . '</h2>'
        . '<div class="rb-exc-inc__grid">' . $cols . '</div></section>'
        . '<section class="rb-exc-itin"><h2>' . esc_html(riad_bilkis_exc_t($exc['itinerary_title'], $lang)) . '</h2>'
        . '<div class="rb-exc-itin__list">' . $steps . '</div></section>'
        . $stay
        . '<section class="rb-exc-others"><h2>' . esc_html($t['others']) . '</h2>'
        . '<div class="rb-exc-others__grid">' . $others . '</div></section>'
        . '<p class="rb-exc-detail__back-all"><a href="' . esc_url(riad_bilkis_exc_url('', $lang)) . '">&larr; '
        . esc_html($t['back']) . '</a></p>'
        . '</div>';
}

// ── SEO : titre, description, canonique, hreflang, JSON-LD ───────────────────
function riad_bilkis_exc_seo($route) {
    $lang = $route['lang'];
    $t    = riad_bilkis_exc_texts($lang);
    if ($route['slug'] === '') {
        $titles = array(
            'fr' => 'Excursions depuis Marrakech | Riad Bilkis - Atlas, Agafay, Essaouira',
            'en' => 'Excursions from Marrakech | Riad Bilkis - Atlas, Agafay, Essaouira',
            'es' => 'Excursiones desde Marrakech | Riad Bilkis - Atlas, Agafay, Essaouira',
        );
        $first = riad_bilkis_exc_data();
        return array(
            'title' => $titles[$lang],
            'description' => $t['intro'],
            'image' => home_url(RIAD_BILKIS_EXC_IMG_BASE . $first[0]['image']),
        );
    }
    $exc   = riad_bilkis_exc_find($route['slug']);
    $title = riad_bilkis_exc_t($exc['title'], $lang);
    $price = riad_bilkis_exc_from_price(riad_bilkis_exc_pricing($exc));
    $suffix = array(
        'fr' => ' | Excursion depuis Marrakech - Riad Bilkis, à partir de ' . $price . ' €',
        'en' => ' | Excursion from Marrakech - Riad Bilkis, from ' . $price . ' €',
        'es' => ' | Excursión desde Marrakech - Riad Bilkis, desde ' . $price . ' €',
    );
    return array(
        'title' => $title . $suffix[$lang],
        'description' => riad_bilkis_exc_t($exc['short'], $lang) . ' ' . riad_bilkis_exc_t($exc['duration'], $lang)
            . ' · ' . riad_bilkis_exc_distance($exc, $lang) . ' · ' . $t['from'] . ' ' . $price . ' €.',
        'image' => home_url(RIAD_BILKIS_EXC_IMG_BASE . $exc['image']),
    );
}

function riad_bilkis_exc_head() {
    $route = riad_bilkis_exc_route();
    if (!$route) return;
    $seo  = riad_bilkis_exc_seo($route);
    $lang = $route['lang'];
    $url  = home_url(riad_bilkis_exc_url($route['slug'], $lang));

    echo '<meta name="description" content="' . esc_attr($seo['description']) . '" />' . "\n";
    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
    foreach (array('fr' => 'fr', 'en' => 'en', 'es' => 'es') as $code => $slug_lang) {
        echo '<link rel="alternate" hreflang="' . esc_attr($code) . '" href="'
            . esc_url(home_url(riad_bilkis_exc_url($route['slug'], $slug_lang))) . '" />' . "\n";
    }
    echo '<meta property="og:type" content="website" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($seo['title']) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($seo['description']) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="Riad Bilkis Marrakech" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url($seo['image']) . '" />' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";

    if ($route['slug'] === '') {
        $items = array();
        $position = 1;
        foreach (riad_bilkis_exc_data() as $exc) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'url' => home_url(riad_bilkis_exc_url($exc['slug'], $lang)),
                'name' => riad_bilkis_exc_t($exc['title'], $lang),
            );
        }
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => riad_bilkis_exc_texts($lang)['title'],
            'itemListElement' => $items,
        );
    } else {
        $exc     = riad_bilkis_exc_find($route['slug']);
        $pricing = riad_bilkis_exc_pricing($exc);
        $schema  = array(
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => riad_bilkis_exc_t($exc['title'], $lang),
            'description' => riad_bilkis_exc_t($exc['short'], $lang),
            'url' => $url,
            'image' => home_url(RIAD_BILKIS_EXC_IMG_BASE . $exc['image']),
            'touristType' => 'Leisure',
            'provider' => array(
                '@type' => 'LodgingBusiness',
                'name' => 'Riad Bilkis',
                'url' => home_url('/'),
                'telephone' => '+212625675494',
            ),
            'offers' => array(
                '@type' => 'Offer',
                'price' => riad_bilkis_exc_from_price($pricing),
                'priceCurrency' => 'EUR',
                'url' => $url,
                'availability' => 'https://schema.org/InStock',
            ),
        );
    }
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}

// ── Styles et scripts ────────────────────────────────────────────────────────
function riad_bilkis_exc_assets() {
    wp_enqueue_script('riad-bilkis-forms', '/sejour/forms.js', array(), '1.0', true);
    wp_enqueue_style(
        'riad-bilkis-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    wp_register_style('riad-bilkis-exc', false);
    wp_enqueue_style('riad-bilkis-exc');
    wp_add_inline_style('riad-bilkis-exc', '
.rb-exc,.rb-exc-detail{--rb-serif:"Cormorant Garamond",Georgia,serif;--rb-sans:"Montserrat","Helvetica Neue",Arial,sans-serif;
 --rb-safran:#C99752;--rb-terra:#C0452A;--rb-brown:#2C2318;max-width:1140px;margin:0 auto;padding:8px 0 10px;
 font-family:var(--rb-sans);color:#4A3D31}
.rb-exc__head{text-align:center;margin-bottom:44px}
.rb-exc .rb-section-label{display:block;font-size:12.5px;font-weight:600;letter-spacing:3.4px;text-transform:uppercase;
 color:#B08A57;margin-bottom:12px}
.rb-exc__title{font-family:var(--rb-serif);font-size:47px;font-weight:700;letter-spacing:1px;color:var(--rb-brown);margin:0}
.rb-exc .rb-section-line{width:64px;height:2px;background:var(--rb-safran);margin:18px auto 0}
.rb-exc__intro{max-width:720px;margin:20px auto 0;font-size:16.5px;line-height:1.8}
.rb-exc__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:30px}
.rb-exc-card{display:flex;flex-direction:column;background:#fff;border:1px solid #E8E0D5;overflow:hidden;
 transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease}
.rb-exc-card:hover{transform:translateY(-5px);box-shadow:0 16px 38px rgba(0,0,0,.1);border-color:var(--rb-safran)}
.rb-exc-card__media{position:relative;display:block;overflow:hidden}
.rb-exc-card__media img{display:block;width:100%;height:220px;object-fit:cover;transition:transform .6s ease}
.rb-exc-card:hover .rb-exc-card__media img{transform:scale(1.05)}
.rb-exc-badge{position:absolute;top:14px;font-family:var(--rb-sans);font-size:12px;font-weight:600;letter-spacing:.6px;
 padding:6px 12px;border-radius:2px;color:#fff;background:rgba(44,35,24,.82)}
.rb-exc-badge--time{left:14px}
.rb-exc-badge--price{right:14px;background:var(--rb-terra)}
.rb-exc-card__body{flex:1;display:flex;flex-direction:column;padding:22px 22px 26px}
.rb-exc-card__title{font-family:var(--rb-serif);font-size:26px;font-weight:600;line-height:1.25;margin:0 0 8px}
.rb-exc-card__title a{color:var(--rb-brown);text-decoration:none}
.rb-exc-card__distance{font-size:12.5px;letter-spacing:1.4px;text-transform:uppercase;color:#B08A57;font-weight:600;margin:0 0 12px}
.rb-exc-card__desc{font-size:15.5px;line-height:1.7;margin:0 0 20px}
.rb-exc-card__btn,.rb-exc-card__btn:visited{margin-top:auto;align-self:flex-start;display:inline-block;padding:12px 26px;
 border:1px solid var(--rb-safran);color:#8A6A3B;text-decoration:none;font-size:12.5px;font-weight:600;letter-spacing:2px;
 text-transform:uppercase;transition:background .3s ease,color .3s ease}
.rb-exc-card__btn:hover,.rb-exc-card__btn:focus{background:var(--rb-safran);color:#fff}
/* Fiche d\'une excursion */
.rb-exc-detail{max-width:860px}
.rb-exc-detail__back,.rb-exc-detail__back-all a{font-size:13px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;
 color:#8A6A3B;text-decoration:none}
.rb-exc-detail__hero{position:relative;margin:16px 0 26px;overflow:hidden}
.rb-exc-detail__hero img{display:block;width:100%;height:380px;object-fit:cover}
.rb-exc-detail__title{font-family:var(--rb-serif);font-size:44px;font-weight:700;line-height:1.14;letter-spacing:.6px;
 color:var(--rb-brown);margin:0 0 10px}
.rb-exc-detail__distance{font-size:12.5px;letter-spacing:2px;text-transform:uppercase;color:#B08A57;font-weight:600;margin:0 0 26px}
.rb-exc-detail__body p{font-size:16.5px;line-height:1.85;margin:0 0 14px}
.rb-exc-detail__body ul{margin:0 0 18px;padding-left:22px}
.rb-exc-detail__body li{font-size:16px;line-height:1.8;margin-bottom:6px}
.rb-exc-detail h2{font-family:var(--rb-serif);font-size:32px;font-weight:600;letter-spacing:.3px;color:var(--rb-brown);margin:0 0 14px}
.rb-exc-book{background:#FBF7F2;border:1px solid #E8E0D5;padding:30px;margin:36px 0}
.rb-exc-book__text{font-size:15.5px;line-height:1.7;margin:0 0 20px}
.rb-exc-price{background:#fff;border:1px solid #E8E0D5;padding:18px 20px;margin-bottom:24px}
.rb-exc-price h3{font-family:var(--rb-serif);font-size:23px;font-weight:600;color:var(--rb-brown);margin:0 0 10px}
.rb-exc-price__row{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid #F0E8DC;font-size:15px}
.rb-exc-price__row:last-child{border-bottom:none}
.rb-exc-price__row span:last-child{font-weight:600;color:var(--rb-terra)}
.rb-exc-form label{display:block;font-size:12px;font-weight:600;letter-spacing:1.6px;text-transform:uppercase;color:#7A6650;
 margin-bottom:16px}
.rb-exc-form input,.rb-exc-form textarea{display:block;width:100%;margin-top:7px;padding:13px 14px;border:1px solid #E0D6C8;
 border-radius:3px;background:#fff;font-size:16px;color:var(--rb-brown);font-family:var(--rb-sans);text-transform:none;letter-spacing:0}
.rb-exc-form input:focus,.rb-exc-form textarea:focus{outline:none;border-color:var(--rb-safran)}
.rb-exc-form__row{display:grid;grid-template-columns:1fr 1fr;gap:0 22px}
.rb-exc-form__row label:only-child{max-width:50%}
.rb-exc-form__total{display:flex;justify-content:space-between;align-items:center;background:#fff;border:1px solid #E3CFA9;
 padding:14px 18px;margin:2px 0 18px;font-size:14px;letter-spacing:1.4px;text-transform:uppercase;font-weight:600;color:#7A6650}
.rb-exc-form__total strong{font-family:var(--rb-serif);font-size:30px;letter-spacing:0;text-transform:none;color:var(--rb-terra)}
.rb-exc-form__min{font-size:13.5px;color:#8B7355;margin:-8px 0 18px}
.rb-exc-form__note{font-size:13px;color:#8B7355;margin:12px 0 0}
.rb-exc-form .rb-official__btn{margin-top:4px}
.rb-exc-inc__grid{display:grid;grid-template-columns:1fr 1fr;gap:26px;margin-bottom:12px}
.rb-exc-inc__col{background:#fff;border:1px solid #E8E0D5;padding:22px 24px}
.rb-exc-inc__col h3{font-family:var(--rb-serif);font-size:24px;font-weight:600;margin:0 0 12px}
.rb-exc-inc__col--included h3{color:#2f7a4f}
.rb-exc-inc__col--excluded h3{color:#B2402F}
.rb-exc-inc__col ul{list-style:none;margin:0;padding:0}
.rb-exc-inc__col li{position:relative;padding:0 0 10px 22px;font-size:15.5px;line-height:1.6}
.rb-exc-inc__col--included li:before{content:"\\2713";position:absolute;left:0;top:0;color:#2f7a4f;font-weight:700}
.rb-exc-inc__col--excluded li:before{content:"\\00d7";position:absolute;left:2px;top:-1px;color:#B2402F;font-weight:700;font-size:17px}
.rb-exc-inc,.rb-exc-itin,.rb-exc-others{margin:38px 0}
.rb-exc-itin__step{display:flex;align-items:flex-start;gap:14px;position:relative;padding-bottom:16px}
.rb-exc-itin__time{min-width:58px;font-size:13.5px;font-weight:700;color:#8A6A3B;padding-top:1px}
.rb-exc-itin__dot{width:12px;height:12px;min-width:12px;border-radius:50%;background:var(--rb-safran);margin-top:4px;position:relative}
.rb-exc-itin__step:not(:last-child) .rb-exc-itin__dot:after{content:"";position:absolute;left:50%;top:12px;
 transform:translateX(-50%);width:2px;height:calc(100% + 20px);background:#E3CFA9}
.rb-exc-itin__label{font-size:15.5px;line-height:1.5}
.rb-exc-stay{background:#FFFBF4;border:1px solid #E3CFA9;padding:30px 24px;text-align:center;margin:38px 0}
.rb-exc-stay h2{font-size:31px;color:var(--rb-terra);margin-bottom:18px}
.rb-exc-stay .rb-official__btn{background:#C0452A;box-shadow:0 4px 16px rgba(168,52,28,.28)}
.rb-exc-stay .rb-official__btn:hover,.rb-exc-stay .rb-official__btn:focus{background:#A8341C}
.rb-exc-others__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.rb-exc-other{display:block;text-decoration:none;background:#fff;border:1px solid #E8E0D5;overflow:hidden;
 transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease}
.rb-exc-other:hover{transform:translateY(-4px);box-shadow:0 14px 34px rgba(0,0,0,.1);border-color:var(--rb-safran)}
.rb-exc-other__img{display:block;height:130px;background-size:cover;background-position:center;background-color:#F6F1EA}
.rb-exc-other__name{display:block;padding:14px 16px;text-align:center;font-family:var(--rb-serif);font-size:19px;
 font-weight:600;line-height:1.3;color:var(--rb-brown)}
.rb-exc-detail__back-all{text-align:center;margin:30px 0 0}
@media(max-width:921px){
.rb-exc__grid{grid-template-columns:1fr;gap:22px}
.rb-exc__title{font-size:32px}
.rb-exc-detail__title{font-size:30px}
.rb-exc-detail__hero img{height:230px}
.rb-exc-detail h2{font-size:26px}
.rb-exc-book{padding:22px 18px}
.rb-exc-form__row{grid-template-columns:1fr}
.rb-exc-form__row label:only-child{max-width:none}
.rb-exc-form__total strong{font-size:25px}
.rb-exc-inc__grid{grid-template-columns:1fr;gap:18px}
.rb-exc-others__grid{grid-template-columns:1fr}
.rb-exc-other__img{height:170px}
.rb-exc-stay h2{font-size:26px}
}
');

    wp_add_inline_script('riad-bilkis-forms', '
(function(){
  var form=document.querySelector("[data-rb-pricing]");
  if(!form)return;
  var p;try{p=JSON.parse(form.getAttribute("data-rb-pricing"))}catch(e){return}
  var people=form.querySelector("[data-rb-people]"),
      display=form.querySelector("[data-rb-total-display]"),
      field=form.querySelector("[data-rb-total-field]");
  function rate(n){var t=p.tiers||{};for(var i=Math.min(n,4);i>=1;i--){if(t[i]!==undefined)return t[i]}return 0}
  function total(n){
    var min=p.min_people||1;
    if(!n||n<min)return 0;
    if(p.type==="flat_then_per_person"){
      var up=p.flat_up_to;
      if(n<=up)return p.flat_price;
      if(n===up+1)return p.price_4;
      return p.price_4+(n-up-1)*p.extra_per_person;
    }
    return rate(n)*n;
  }
  function update(){
    var n=parseInt(people.value,10)||0,sum=total(n);
    if(display)display.textContent=sum+" \u20ac";
    if(field)field.value=sum+" EUR ("+n+")";
  }
  people.addEventListener("input",update);
  people.addEventListener("change",update);
  form.addEventListener("reset",function(){setTimeout(update,0)});
  update();
})();
');
}

// ── Sortie de la page ────────────────────────────────────────────────────────
add_action('template_redirect', function () {
    $route = riad_bilkis_exc_route();
    if (!$route) return;

    global $wp_query;
    if ($wp_query) {
        $wp_query->is_404 = false;
    }
    status_header(200);

    $seo = riad_bilkis_exc_seo($route);
    add_filter('pre_get_document_title', function () use ($seo) {
        return $seo['title'];
    }, 1000);
    add_action('wp_head', 'riad_bilkis_exc_head', 2);
    riad_bilkis_exc_assets();

    $html = $route['slug'] === ''
        ? riad_bilkis_exc_index_html($route['lang'])
        : riad_bilkis_exc_detail_html(riad_bilkis_exc_find($route['slug']), $route['lang']);

    get_header();
    echo '<div id="primary" class="content-area primary"><main id="main" class="site-main">'
        . '<article class="ast-article-single"><div class="entry-content clear">'
        . $html
        . '</div></article></main></div>';
    get_footer();
    exit;
}, 0);

// ── Pages excursions dans wp-sitemap.xml ─────────────────────────────────────
add_action('init', function () {
    if (!class_exists('WP_Sitemaps_Provider')) return;

    class Riad_Bilkis_Excursions_Sitemap_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name = 'riadbilkisexcursions';
            $this->object_type = 'page';
        }
        public function get_url_list($page_num, $object_subtype = '') {
            $urls = array();
            foreach (array_keys(RIAD_BILKIS_EXC_BASES) as $lang) {
                $urls[] = array('loc' => home_url(riad_bilkis_exc_url('', $lang)));
                foreach (riad_bilkis_exc_data() as $exc) {
                    $urls[] = array('loc' => home_url(riad_bilkis_exc_url($exc['slug'], $lang)));
                }
            }
            return $urls;
        }
        public function get_max_num_pages($object_subtype = '') {
            return 1;
        }
    }

    wp_register_sitemap_provider('riadbilkisexcursions', new Riad_Bilkis_Excursions_Sitemap_Provider());
});
