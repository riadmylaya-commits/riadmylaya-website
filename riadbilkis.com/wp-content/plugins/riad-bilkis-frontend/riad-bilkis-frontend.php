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

// ── Menu principal : 7 rubriques sur une ligne, avec sous-menus ──────────────
function riad_bilkis_menu_tree($lang) {
    $menus = array(
        'fr' => array(
            array('Accueil',   '/'),
            array('Chambres',  '/chambres/'),
            array('Galerie',   '/galerie/'),
            array('Services',  '/nos-services/', array(
                array('Dîner traditionnel', '/diner-marocain'),
                array('Cours de cuisine',   '/cours-de-cuisine'),
                array('Hammam & Massage',   '/hammam-massage'),
                array('Infos pratiques',    '/informations-pratiques'),
            )),
            array('Activités', '/activites-groupe', array(
                array('Activités en groupe et en privé', '/activites-groupe'),
            )),
            array('Blog',      '/blog', array(
                array('Découverte de Marrakech', '/decouverte-marrakech'),
            )),
            array('Contact',   '/contact/'),
        ),
        'en' => array(
            array('Home',       '/en/'),
            array('Rooms',      '/chambres/'),
            array('Gallery',    '/galerie/'),
            array('Services',   '/nos-services/', array(
                array('Traditional dinner', '/en/moroccan-dinner'),
                array('Cooking class',      '/en/cooking-class'),
                array('Hammam & Massage',   '/en/hammam-massage'),
                array('Practical info',     '/en/practical-information'),
            )),
            array('Activities', '/en/group-activities', array(
                array('Group and private activities', '/en/group-activities'),
            )),
            array('Blog',       '/en/blog', array(
                array('Discover Marrakech', '/en/discover-marrakech'),
            )),
            array('Contact',    '/contact/'),
        ),
        'es' => array(
            array('Inicio',       '/es/'),
            array('Habitaciones', '/chambres/'),
            array('Galería',      '/galerie/'),
            array('Servicios',    '/nos-services/', array(
                array('Cena tradicional',  '/es/cena-marroqui'),
                array('Clase de cocina',   '/es/clase-de-cocina'),
                array('Hammam y masaje',   '/es/hammam-masaje'),
                array('Información práctica', '/es/informacion-practica'),
            )),
            array('Actividades',  '/es/actividades', array(
                array('Actividades en grupo y privadas', '/es/actividades'),
            )),
            array('Blog',         '/es/blog', array(
                array('Descubrir Marrakech', '/es/descubrir-marrakech'),
            )),
            array('Contacto',     '/contact/'),
        ),
    );
    return isset($menus[$lang]) ? $menus[$lang] : $menus['fr'];
}

// Le menu du thème est entièrement remplacé : les rubriques retirées
// (Excursions, Réservation, Infos pratiques, Dîner marocain) sont désormais
// regroupées dans Services, Activités et Blog.
function riad_bilkis_menu_items_html() {
    $path = isset($_SERVER['REQUEST_URI']) ? rtrim(strtok($_SERVER['REQUEST_URI'], '?'), '/') : '';
    $out  = '';
    foreach (riad_bilkis_menu_tree(riad_bilkis_lang()) as $entry) {
        $children = isset($entry[2]) ? $entry[2] : array();
        $current  = rtrim($entry[1], '/') === $path;
        $classes  = 'menu-item rb-menu-item';
        if ($children) $classes .= ' menu-item-has-children rb-has-children';
        // Services sert uniquement de parent de navigation : il ouvre son sous-menu.
        if ($children && rtrim($entry[1], '/') === '/nos-services') $classes .= ' rb-nolink';
        if ($current)  $classes .= ' current-menu-item';
        $sub = '';
        if ($children) {
            $sub .= '<button type="button" class="rb-submenu-toggle" aria-expanded="false" aria-label="'
                 . esc_attr($entry[0]) . '"><span aria-hidden="true"></span></button>';
            $sub .= '<ul class="sub-menu rb-submenu">';
            foreach ($children as $child) {
                $sub .= '<li class="menu-item"><a class="menu-link" href="' . esc_url($child[1]) . '">'
                     . esc_html($child[0]) . '</a></li>';
            }
            $sub .= '</ul>';
        }
        $out .= '<li class="' . esc_attr($classes) . '"><a class="menu-link" href="' . esc_url($entry[1]) . '">'
             . esc_html($entry[0]) . '</a>' . $sub . '</li>';
    }
    return $out;
}

// Polylang suffixe les emplacements par langue (primary___en, primary___es).
function riad_bilkis_is_main_menu($location) {
    $base = strtok((string) $location, '_');
    return in_array($base, array('primary', 'mobile'), true);
}

add_filter('wp_nav_menu_items', function ($items, $args) {
    if (!isset($args->theme_location) || !riad_bilkis_is_main_menu($args->theme_location)) {
        return $items;
    }
    return riad_bilkis_menu_items_html();
}, 20, 2);

// Les traductions Polylang (/en/, /es/) n'ont pas de menu assigné : sans cela
// le thème n'afficherait aucune navigation sur ces pages.
function riad_bilkis_menu_fallback($args) {
    $args  = (array) $args;
    $id    = isset($args['menu_id']) ? $args['menu_id'] : '';
    $class = isset($args['menu_class']) ? $args['menu_class'] : 'main-header-menu';
    $html  = '<ul' . ($id ? ' id="' . esc_attr($id) . '"' : '') . ' class="' . esc_attr($class) . '">'
           . riad_bilkis_menu_items_html() . '</ul>';
    if (isset($args['echo']) && $args['echo']) {
        echo $html;
        return null;
    }
    return $html;
}

function riad_bilkis_first_filled_menu() {
    static $id = null;
    if ($id !== null) return $id;
    $id = 0;
    foreach ((array) wp_get_nav_menus() as $menu) {
        if ((int) $menu->count > 0) {
            $id = (int) $menu->term_id;
            break;
        }
    }
    return $id;
}

// Sans menu assigné pour la langue courante, le thème n'affiche pas de
// navigation du tout sur /en/ et /es/ : on rattache l'emplacement à un menu
// existant, ses entrées étant ensuite remplacées par la version traduite.
add_filter('theme_mod_nav_menu_locations', function ($locations) {
    if (is_admin() || !is_array($locations)) {
        return $locations;
    }
    $fallback = riad_bilkis_first_filled_menu();
    if (!$fallback) {
        return $locations;
    }
    foreach (array('primary', 'mobile_menu') as $location) {
        if (empty($locations[$location])) {
            $locations[$location] = $fallback;
        }
    }
    return $locations;
}, 100);

add_filter('wp_nav_menu_args', function ($args) {
    $location = isset($args['theme_location']) ? $args['theme_location'] : '';
    if (!riad_bilkis_is_main_menu($location)) {
        return $args;
    }
    $args['fallback_cb'] = 'riad_bilkis_menu_fallback';

    // WordPress abandonne le rendu quand le menu de la langue courante est vide :
    // on lui fournit alors un menu non vide, dont les entrées sont remplacées
    // ensuite par riad_bilkis_menu_items_html().
    $locations = get_nav_menu_locations();
    $assigned  = isset($locations[$location]) ? wp_get_nav_menu_object($locations[$location]) : false;
    if ($assigned && !is_wp_error($assigned) && (int) $assigned->count > 0) {
        return $args;
    }
    foreach (wp_get_nav_menus() as $menu) {
        if ((int) $menu->count > 0) {
            $args['menu'] = $menu->term_id;
            break;
        }
    }
    return $args;
}, 20);

// Style sobre du menu + ouverture des sous-menus (survol desktop, clic mobile).
add_action('wp_footer', function () {
    ?>
<style id="rb-menu-css">
.main-header-menu>.rb-menu-item>.menu-link,.ast-mobile-popup-drawer .rb-menu-item>.menu-link{
 font-family:"Raleway","Helvetica Neue",Arial,sans-serif;font-size:13px;letter-spacing:2.2px;
 text-transform:uppercase;font-weight:500}
.main-header-menu>.rb-menu-item>.menu-link{padding-left:18px;padding-right:18px;position:relative}
.main-header-menu>.rb-menu-item>.menu-link:after{content:"";position:absolute;left:18px;right:18px;bottom:14px;
 height:1px;background:#C99752;transform:scaleX(0);transform-origin:center;transition:transform .35s ease}
.main-header-menu>.rb-menu-item:hover>.menu-link:after,
.main-header-menu>.rb-menu-item.current-menu-item>.menu-link:after{transform:scaleX(1)}
.rb-submenu-toggle{display:none}
@media(min-width:922px){
 .main-header-menu{flex-wrap:nowrap!important}
 .main-header-menu>.rb-menu-item{white-space:nowrap}
 .main-header-menu>.rb-has-children{position:relative}
 .main-header-menu>.rb-has-children>.menu-link{padding-right:36px}
 .main-header-menu>.rb-has-children>.menu-link:before{content:"";position:absolute;right:17px;top:50%;
  width:6px;height:6px;margin-top:-5px;border-right:1px solid currentColor;
  border-bottom:1px solid currentColor;transform:rotate(45deg);opacity:.45;
  transition:transform .3s ease,opacity .3s ease}
 .main-header-menu>.rb-has-children:hover>.menu-link:before{opacity:.8;
  transform:rotate(45deg) translate(2px,2px)}
 .main-header-menu>.rb-has-children>.menu-link:after{right:36px}
 .main-header-menu>.rb-has-children>.rb-submenu{position:absolute;top:100%;left:50%;transform:translate(-50%,10px);
  min-width:250px;background:#fff;border:none;border-top:2px solid #C99752;border-radius:0 0 3px 3px;
  box-shadow:0 14px 34px rgba(0,0,0,.13);padding:10px 0;opacity:0;visibility:hidden;pointer-events:none;
  transition:opacity .28s ease,transform .28s ease;display:block;z-index:9999}
 .main-header-menu>.rb-has-children:hover>.rb-submenu,
 .main-header-menu>.rb-has-children:focus-within>.rb-submenu{opacity:1;visibility:visible;pointer-events:auto;
  transform:translate(-50%,0)}
 .main-header-menu .rb-submenu>li>.menu-link{display:block;padding:11px 26px;font-family:"Raleway",Arial,sans-serif;
  font-size:12.5px;letter-spacing:1.4px;text-transform:uppercase;color:#3F2935;white-space:nowrap;
  transition:color .25s,padding-left .25s}
 .main-header-menu .rb-submenu>li>.menu-link:hover{color:#821F0C;padding-left:31px}
}
/* Menu mobile : lignes pleine largeur, grandes zones tactiles (>= 56 px). */
@media(max-width:921px){
 .ast-builder-menu-mobile .main-header-menu{background:#fff;padding:6px 0 10px}
 .main-header-menu>.rb-menu-item{border-bottom:1px solid #EFE7DC}
 .main-header-menu>.rb-menu-item:last-child{border-bottom:none}
 .main-header-menu>.rb-menu-item>.menu-link,.ast-mobile-popup-drawer .rb-menu-item>.menu-link{font-size:15px;
  letter-spacing:1.6px}
 .main-header-menu>.rb-menu-item>.menu-link{display:flex!important;align-items:center;width:100%;
  min-height:58px;padding:0 22px!important;color:#2C2318}
 .main-header-menu>.rb-menu-item>.menu-link:after{display:none}
 .main-header-menu>.rb-menu-item>.menu-link:active,
 .main-header-menu>.rb-menu-item>.menu-link:focus{background:#FBF7F2}
 .main-header-menu>.rb-menu-item.current-menu-item>.menu-link{color:#C75B39;
  box-shadow:inset 3px 0 0 var(--rb-menu-accent,#C99752)}
 .rb-has-children{display:flex!important;flex-direction:row!important;flex-wrap:wrap!important;align-items:stretch}
 .rb-has-children>.menu-link{flex:1 1 auto;width:auto!important;max-width:none;height:auto!important}
 .rb-has-children>.rb-submenu{flex:0 0 100%}
 .rb-submenu-toggle{display:flex;align-items:center;justify-content:center;flex:0 0 64px;align-self:stretch;
  background:transparent!important;border:none!important;border-left:1px solid #EFE7DC!important;
  box-shadow:none!important;padding:0;cursor:pointer;min-height:58px;line-height:1}
 .rb-submenu-toggle span{display:block;width:11px;height:11px;margin:0 auto -4px;border-right:2px solid #8B7355;
  border-bottom:2px solid #8B7355;transform:rotate(45deg);transition:transform .3s}
 .rb-submenu-toggle[aria-expanded="true"] span{margin:4px auto 0;transform:rotate(-135deg);border-color:#C75B39}
 .rb-has-children>.rb-submenu{display:none!important;padding:0;opacity:1;visibility:visible;
  position:static;width:auto;height:auto;box-shadow:none;border:none;background:#FBF7F2}
 .rb-has-children.rb-open>.rb-submenu{display:block!important}
 .rb-submenu>li{border-top:1px solid #EFE7DC}
 .rb-submenu>li>.menu-link{display:flex!important;align-items:center;min-height:52px;width:100%;
  padding:0 22px 0 38px!important;font-size:14.5px;letter-spacing:.6px;text-transform:none;color:#4A3D31}
 .rb-submenu>li>.menu-link:active,.rb-submenu>li>.menu-link:focus{background:#F3EADD}
 /* Ouverture / fermeture : cibles tactiles confortables. */
 .ast-mobile-header-wrap .menu-toggle.main-header-menu-toggle{min-width:52px;min-height:52px;
  display:flex;align-items:center;justify-content:center;border-radius:4px}
 .ast-mobile-header-wrap .menu-toggle .ast-mobile-svg{width:28px!important;height:28px!important;
  fill:#C75B39!important}
 .ast-mobile-header-wrap .menu-toggle.toggled{background:#FBF7F2!important;border:1px solid #E8E0D5}
 .ast-mobile-header-wrap .menu-toggle.toggled .ast-mobile-svg{width:30px!important;height:30px!important}
}
</style>
<script id="rb-menu-js">
(function(){
  function isMobile(){return window.matchMedia("(max-width:921px)").matches}
  function toggle(li){
    var open=li.classList.toggle("rb-open"),btn=li.querySelector(".rb-submenu-toggle");
    if(btn)btn.setAttribute("aria-expanded",open?"true":"false");
  }
  document.addEventListener("click",function(e){
    if(!e.target.closest)return;
    var btn=e.target.closest(".rb-submenu-toggle");
    if(btn){e.preventDefault();toggle(btn.parentNode);return;}
    // Sur mobile, une rubrique à sous-menu ouvre son sous-menu au lieu de naviguer.
    var link=e.target.closest(".rb-has-children>.menu-link");
    if(!link)return;
    if(!isMobile()&&!link.parentNode.classList.contains("rb-nolink"))return;
    e.preventDefault();
    toggle(link.parentNode);
  });
})();
</script>
    <?php
}, 99);

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

function riad_bilkis_official_block($variant = '') {
    $t = riad_bilkis_official_texts(riad_bilkis_lang());
    $lines = '';
    foreach ($t['lines'] as $line) {
        $lines .= esc_html($line) . '<br>';
    }
    $class = 'rb-official' . ($variant === 'hero' ? ' rb-official--hero' : '');
    return '<section class="' . $class . '" id="rb-official">'
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

// ── Section « Nos Chambres » : label, suppression des tarifs, CTA réservation ─
function riad_bilkis_rooms_texts($lang) {
    $texts = array(
        'fr' => array('label' => 'Riad en exclusivité', 'cta' => 'Réserver au meilleur prix'),
        'en' => array('label' => 'Exclusive riad',      'cta' => 'Book at the best price'),
        'es' => array('label' => 'Riad en exclusiva',   'cta' => 'Reservar al mejor precio'),
    );
    return isset($texts[$lang]) ? $texts[$lang] : $texts['fr'];
}

function riad_bilkis_tune_rooms($content) {
    if (strpos($content, 'rb-rooms') === false) return $content;
    $t = riad_bilkis_rooms_texts(riad_bilkis_lang());

    $content = preg_replace(
        '#(<section class="rb-section rb-rooms">.*?<span class="rb-section-label">)[^<]*#s',
        '${1}' . esc_html($t['label']),
        $content
    );
    $content = preg_replace('#\s*<div class="rb-room-price">.*?</div>#s', '', $content);
    $content = preg_replace(
        '#<a href="/chambres/" class="rb-btn-outline">[^<]*</a>#',
        '<a href="' . esc_url(RIAD_BILKIS_OFFICIAL_URL) . '" class="rb-btn-outline" target="_blank" rel="noopener noreferrer">' . esc_html($t['cta']) . '</a>',
        $content
    );
    // Chaque carte reprend la première photo de la chambre correspondante.
    $cards = preg_replace_callback(
        '#<div class="rb-room-card">.*?</div>\s*</div>#s',
        function ($m) {
            if (!preg_match('#href="/([a-z-]+)/"#', $m[0], $link)) return $m[0];
            $slug = $link[1];
            if (empty(RIAD_BILKIS_ROOM_PHOTOS[$slug][0])) return $m[0];
            return preg_replace(
                '#(<div class="rb-room-img" style="background-image:url\(\')[^\']*#',
                '${1}' . esc_url(RIAD_BILKIS_ROOM_PHOTOS[$slug][0]),
                $m[0],
                1
            );
        },
        $content
    );
    if ($cards !== null) $content = $cards;

    $choice = str_replace('$', '\\$', riad_bilkis_choice_section());
    $with_choice = preg_replace(
        '#(<section class="rb-section rb-rooms">.*?</section>)#s',
        '${1}' . $choice,
        $content,
        1
    );

    return $with_choice === null ? $content : $with_choice;
}

add_filter('the_content', function ($content) {
    if (!riad_bilkis_is_home_page() || !in_the_loop() || !is_main_query()) return $content;
    $content = riad_bilkis_tune_rooms($content);

    // Le bloc s'affiche en surimpression de la photo du hero (fond transparent).
    if (strpos($content, '<section class="rb-hero">') !== false) {
        $overlaid = preg_replace(
            '#(<section class="rb-hero">.*?)(\s*</div>\s*</section>)#s',
            '${1}' . str_replace('$', '\\$', riad_bilkis_official_block('hero')) . '${2}',
            $content,
            1
        );
        if ($overlaid !== null) return $overlaid;
    }

    $block = riad_bilkis_official_block();
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
    if (!riad_bilkis_is_home_page() && !riad_bilkis_room_slug() && !riad_bilkis_is_rooms_page()) return;
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
/* Variante surimpression sur la photo du hero : aucun fond opaque. */
.rb-hero{min-height:100vh;height:auto;padding:110px 0 70px}
.rb-official--hero{background:transparent;padding:26px 20px 0}
/* Le CTA du hero ferait doublon avec « Réserver au meilleur prix ». */
.rb-hero-content .rb-hero-btn{display:none}
.rb-hero-content .rb-hero-subtitle{margin-bottom:0}
.rb-official--hero .rb-official__title{color:#fff;font-size:38px;letter-spacing:.5px;
 text-shadow:0 2px 14px rgba(0,0,0,.55);margin-bottom:14px}
.rb-official--hero .rb-official__text{color:#fff;font-size:19px;text-shadow:0 1px 10px rgba(0,0,0,.6);
 margin-bottom:22px}
.rb-official--hero .rb-promo-box{background:rgba(20,14,10,.42);backdrop-filter:blur(3px);
 -webkit-backdrop-filter:blur(3px);border-color:#D4A574;box-shadow:0 4px 18px rgba(0,0,0,.28)}
.rb-official--hero .rb-promo-label{color:#F0D9B5}
.rb-official--hero .rb-promo-hint{color:#F3E7D6}
@media(max-width:768px){
.rb-hero{padding:96px 0 52px}
.rb-official--hero{padding:24px 16px 0}
.rb-official--hero .rb-official__title{font-size:26px}
.rb-official--hero .rb-official__text{font-size:16px;margin-bottom:18px}
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
  var buttons=document.querySelectorAll(".rb-promo-value");
  if(!buttons.length)return;
  function toast(msg){
    var t=document.createElement("div");
    t.className="rb-promo-toast";
    t.textContent=msg;
    document.body.appendChild(t);
    void t.offsetWidth;
    t.classList.add("is-visible");
    setTimeout(function(){t.classList.remove("is-visible");setTimeout(function(){if(t.parentNode)t.parentNode.removeChild(t);},400);},1600);
  }
  Array.prototype.forEach.call(buttons,function(btn){
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
  });
})();
');
});

// ── Chambres : pages individuelles et section de choix sur l'accueil ─────────
// Les photos sont volontairement regroupées ici : il suffit d'ajouter les URL
// des vraies photos du riad (3 par chambre, la première sert aussi de vignette
// dans « Voir les autres chambres »). Tant qu'une liste est vide, la page
// affiche des emplacements neutres plutôt que des images d'illustration.
const RIAD_BILKIS_ROOM_PHOTOS = array(
    'chambre-babouche' => array(
        'https://riadbilkis.com/wp-content/uploads/chambres/babouche-01.jpg',
        'https://riadbilkis.com/wp-content/uploads/chambres/babouche-02.jpg',
        'https://riadbilkis.com/wp-content/uploads/chambres/babouche-03.jpg',
    ),
    'chambre-tarbouche' => array(
        'https://riadbilkis.com/wp-content/uploads/chambres/tarbouche-01.jpg',
        'https://riadbilkis.com/wp-content/uploads/chambres/tarbouche-02.jpg',
        'https://riadbilkis.com/wp-content/uploads/chambres/tarbouche-03.jpg',
    ),
    'chambre-vero' => array(
        'https://riadbilkis.com/wp-content/uploads/chambres/vero-01.jpg',
        'https://riadbilkis.com/wp-content/uploads/chambres/vero-02.jpg',
        'https://riadbilkis.com/wp-content/uploads/chambres/vero-03.jpg',
    ),
);
const RIAD_BILKIS_ROOM_PHOTO_SLOTS = 3;

function riad_bilkis_rooms() {
    return array(
        'chambre-babouche' => array(
            'name'    => 'Chambre Babouche',
            'tagline' => 'Jaune safran et blanc, artisanat marocain et lumière douce',
            'accent'  => '#D9A02B',
            'colors'  => 'Jaune safran et blanc',
            'intro'   => array(
                'Cette chambre double avec un grand lit est située au rez-de-chaussée.',
                'Dans cette chambre, le jaune safran et le blanc vous feront dormir dans la quiétude.',
            ),
            'equip'   => array(
                array('Grand lit double', 'Wi-Fi haut débit', 'Climatisation réversible', 'Coffre-fort'),
                array('Bouteille d\'eau', 'Produits de toilette Les Sens de Marrakech', 'Serviettes et linge de maison'),
            ),
            'bath'    => 'Salle de bain avec douche et toilettes, murs en tadelakt et sol en carreaux de ciment.',
        ),
        'chambre-tarbouche' => array(
            'name'    => 'Chambre Tarbouche',
            'tagline' => 'Rouge et blanc, l\'élégance d\'un riad traditionnel',
            'accent'  => '#B2402F',
            'colors'  => 'Rouge et blanc',
            'intro'   => array(
                'Cette chambre double, équipée de deux lits individuels ou d\'un grand lit (160 x 200 cm), est située au premier étage.',
                'Retrouvez dans cette chambre les couleurs de Marrakech, la ville rouge.',
            ),
            'equip'   => array(
                array('Deux lits individuels ou un grand lit (160 x 200 cm)', 'Wi-Fi haut débit', 'Climatisation réversible', 'Coffre-fort'),
                array('Bouteille d\'eau', 'Produits de toilette Les Sens de Marrakech', 'Serviettes et linge de maison'),
            ),
            'bath'    => 'Salle de bain avec douche et toilettes, murs en tadelakt et sol en carreaux de ciment.',
        ),
        'chambre-vero' => array(
            'name'    => 'Chambre Véro',
            'tagline' => 'Bleu et blanc, un espace apaisant avec son petit salon',
            'accent'  => '#3E6E96',
            'colors'  => 'Bleu et blanc',
            'intro'   => array(
                'Cette chambre double, équipée de deux lits individuels ou d\'un grand lit (160 x 200 cm), en face d\'un petit salon, est située au premier étage.',
                'Le bleu et le blanc font de cette chambre un endroit particulièrement apaisant.',
            ),
            'equip'   => array(
                array('Deux lits individuels ou un grand lit (160 x 200 cm)', 'Petit salon en face de la chambre', 'Wi-Fi haut débit', 'Climatisation réversible', 'Coffre-fort'),
                array('Bouteille d\'eau', 'Produits de toilette Les Sens de Marrakech', 'Serviettes et linge de maison'),
            ),
            'bath'    => 'Salle de bain avec toilettes privées à l\'extérieur de la chambre, à 2 m à pied : douche, murs en tadelakt et sol en carreaux de ciment.',
        ),
    );
}

function riad_bilkis_room_slug() {
    global $post;
    if (!$post || !is_page()) return '';
    $rooms = riad_bilkis_rooms();
    return isset($rooms[$post->post_name]) ? $post->post_name : '';
}

function riad_bilkis_rename_vero($text) {
    if (!is_string($text) || strpos($text, 'Véro') === false) return $text;
    return str_replace(
        array('Suite Véro', 'Notre suite premium offrant un espace généreux et une décoration raffinée'),
        array('Chambre Véro', 'Bleu et blanc, un espace apaisant avec son petit salon'),
        $text
    );
}
add_filter('the_content', 'riad_bilkis_rename_vero', 99);
add_filter('the_title', 'riad_bilkis_rename_vero', 99);
add_filter('wp_nav_menu_items', 'riad_bilkis_rename_vero', 99);

function riad_bilkis_promo_box($t) {
    return '<div class="rb-promo-box" role="note" aria-label="' . esc_attr($t['promo'] . ' ' . RIAD_BILKIS_PROMO) . '">'
        . '<span class="rb-promo-label">' . esc_html($t['promo']) . '</span>'
        . '<button type="button" class="rb-promo-value" data-copied="' . esc_attr($t['copied']) . '">' . esc_html(RIAD_BILKIS_PROMO) . '</button>'
        . '<span class="rb-promo-hint">' . esc_html($t['hint']) . '</span>'
        . '</div>';
}

function riad_bilkis_room_page_html($slug) {
    $rooms = riad_bilkis_rooms();
    $room  = $rooms[$slug];
    $t     = riad_bilkis_official_texts(riad_bilkis_lang());

    $photos = '';
    $urls   = RIAD_BILKIS_ROOM_PHOTOS[$slug];
    $slots  = max(count($urls), RIAD_BILKIS_ROOM_PHOTO_SLOTS);
    for ($i = 0; $i < $slots; $i++) {
        if (isset($urls[$i])) {
            $photos .= '<figure class="rb-room-photo"><img src="' . esc_url($urls[$i]) . '" loading="lazy"'
                . ' alt="' . esc_attr($room['name'] . ' — photo ' . ($i + 1)) . '"></figure>';
        } else {
            $photos .= '<figure class="rb-room-photo rb-room-photo--empty"><span>Photo à venir</span></figure>';
        }
    }

    $equip = '';
    foreach ($room['equip'] as $column) {
        $items = '';
        foreach ($column as $line) {
            $items .= '<li>' . esc_html($line) . '</li>';
        }
        $equip .= '<ul>' . $items . '</ul>';
    }

    $others = '';
    foreach ($rooms as $other_slug => $other) {
        if ($other_slug === $slug) continue;
        $thumb = isset(RIAD_BILKIS_ROOM_PHOTOS[$other_slug][0])
            ? ' style="background-image:url(\'' . esc_url(RIAD_BILKIS_ROOM_PHOTOS[$other_slug][0]) . '\')"'
            : '';
        $others .= '<a class="rb-room-other" href="' . esc_url('/' . $other_slug . '/') . '">'
            . '<span class="rb-room-other__img"' . $thumb . '></span>'
            . '<span class="rb-room-other__name">' . esc_html($other['name']) . '</span>'
            . '</a>';
    }

    return '<div class="rb-room-page" style="--rb-room-accent:' . esc_attr($room['accent']) . '">'
        . '<header class="rb-room-head">'
        . '<span class="rb-room-eyebrow">' . esc_html($room['colors']) . '</span>'
        . '<h1 class="rb-room-title">' . esc_html($room['name']) . '</h1>'
        . '<p class="rb-room-tagline">' . esc_html($room['tagline']) . '</p>'
        . '<span class="rb-room-rule"></span>'
        . '</header>'
        . '<div class="rb-room-body"><h2>Description</h2><p>' . implode('</p><p>', array_map('esc_html', $room['intro'])) . '</p></div>'
        . '<div class="rb-room-body"><h2>Équipements et caractéristiques</h2>'
        . '<div class="rb-room-equip">' . $equip . '</div></div>'
        . '<div class="rb-room-body"><h2>Salle de bain</h2><p>' . esc_html($room['bath']) . '</p></div>'
        . '<div class="rb-room-gallery">' . $photos . '</div>'
        . '<div class="rb-room-book">'
        . '<p class="rb-official__btn-wrap"><a class="rb-official__btn" href="' . esc_url(RIAD_BILKIS_OFFICIAL_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html($t['cta']) . '</a></p>'
        . riad_bilkis_promo_box($t)
        . '</div>'
        . '<div class="rb-room-others"><h2>Voir les autres chambres</h2>'
        . '<div class="rb-room-others__grid">' . $others . '</div></div>'
        . '</div>';
}

add_filter('the_content', function ($content) {
    if (!is_singular() || !is_main_query() || !in_the_loop()) return $content;
    $slug = riad_bilkis_room_slug();
    if (!$slug) return $content;
    return riad_bilkis_room_page_html($slug);
}, 23);

// -- Page « Chambres » : trois cartes identiques, sans les longues descriptions
function riad_bilkis_is_rooms_page() {
    global $post;
    return (bool) ($post && is_page() && $post->post_name === 'chambres');
}

function riad_bilkis_rooms_index_texts($lang) {
    $texts = array(
        'fr' => array(
            'label' => 'Riad en exclusivité', 'title' => 'Nos Chambres',
            'intro' => 'Trois chambres décorées dans le plus pur style marocain, chacune avec ses couleurs et son atmosphère.',
            'cta'   => 'Découvrir la chambre',
        ),
        'en' => array(
            'label' => 'Exclusive riad', 'title' => 'Our Rooms',
            'intro' => 'Three rooms decorated in the purest Moroccan style, each with its own colours and atmosphere.',
            'cta'   => 'Discover the room',
        ),
        'es' => array(
            'label' => 'Riad en exclusiva', 'title' => 'Nuestras Habitaciones',
            'intro' => 'Tres habitaciones decoradas en el más puro estilo marroquí, cada una con sus colores y su ambiente.',
            'cta'   => 'Descubrir la habitación',
        ),
    );
    return isset($texts[$lang]) ? $texts[$lang] : $texts['fr'];
}

function riad_bilkis_rooms_index_html() {
    $t     = riad_bilkis_rooms_index_texts(riad_bilkis_lang());
    $cards = '';
    foreach (riad_bilkis_rooms() as $slug => $room) {
        $photo = isset(RIAD_BILKIS_ROOM_PHOTOS[$slug][0]) ? RIAD_BILKIS_ROOM_PHOTOS[$slug][0] : '';
        $url   = esc_url('/' . $slug . '/');
        $thumb = $photo ? ' style="background-image:url(\'' . esc_url($photo) . '\')"' : '';
        $cards .= '<article class="rb-rooms-card" style="--rb-room-accent:' . esc_attr($room['accent']) . '">'
            . '<a class="rb-rooms-card__link" href="' . $url . '" aria-label="' . esc_attr($room['name']) . '">'
            . '<span class="rb-rooms-card__img"' . $thumb . '></span></a>'
            . '<div class="rb-rooms-card__body">'
            . '<span class="rb-rooms-card__colors">' . esc_html($room['colors']) . '</span>'
            . '<h2 class="rb-rooms-card__name"><a href="' . $url . '">' . esc_html($room['name']) . '</a></h2>'
            . '<a class="rb-rooms-card__btn" href="' . $url . '">' . esc_html($t['cta']) . '</a>'
            . '</div></article>';
    }

    return '<div class="rb-rooms-index">'
        . '<header class="rb-rooms-index__head">'
        . '<span class="rb-section-label">' . esc_html($t['label']) . '</span>'
        . '<h1 class="rb-rooms-index__title">' . esc_html($t['title']) . '</h1>'
        . '<div class="rb-section-line"></div>'
        . '<p class="rb-rooms-index__intro">' . esc_html($t['intro']) . '</p>'
        . '</header>'
        . '<div class="rb-rooms-index__grid">' . $cards . '</div>'
        . '</div>'
        . riad_bilkis_choice_section();
}

add_filter('the_content', function ($content) {
    if (!is_singular() || !is_main_query() || !in_the_loop()) return $content;
    if (!riad_bilkis_is_rooms_page()) return $content;
    return riad_bilkis_rooms_index_html();
}, 23);

// Section « demander des informations OU réserver en ligne », sous les chambres.
function riad_bilkis_choice_texts($lang) {
    $texts = array(
        'fr' => array(
            'label' => 'Votre séjour', 'title' => 'Une question ou une réservation ?',
            'form_title' => 'Demander des informations',
            'form_text'  => 'Disponibilités, arrivée tardive, transfert : écrivez-nous, nous répondons rapidement.',
            'name' => 'Nom complet', 'email' => 'E-mail', 'phone' => 'Téléphone',
            'message' => 'Message', 'send' => 'Envoyer la demande', 'or' => 'ou',
            'book_title' => 'Réserver directement en ligne',
            'book_text'  => 'Meilleur prix garanti, sans commission d\'intermédiaire.',
        ),
        'en' => array(
            'label' => 'Your stay', 'title' => 'A question or a booking?',
            'form_title' => 'Request information',
            'form_text'  => 'Availability, late arrival, transfer: write to us, we answer quickly.',
            'name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone',
            'message' => 'Message', 'send' => 'Send the request', 'or' => 'or',
            'book_title' => 'Book online right away',
            'book_text'  => 'Best price guaranteed, with no intermediary commission.',
        ),
        'es' => array(
            'label' => 'Su estancia', 'title' => '¿Una duda o una reserva?',
            'form_title' => 'Solicitar información',
            'form_text'  => 'Disponibilidad, llegada tardía, traslado: escríbanos, respondemos rápido.',
            'name' => 'Nombre completo', 'email' => 'Correo electrónico', 'phone' => 'Teléfono',
            'message' => 'Mensaje', 'send' => 'Enviar la solicitud', 'or' => 'o',
            'book_title' => 'Reservar directamente en línea',
            'book_text'  => 'Mejor precio garantizado, sin comisión de intermediarios.',
        ),
    );
    return isset($texts[$lang]) ? $texts[$lang] : $texts['fr'];
}

function riad_bilkis_choice_section() {
    $lang = riad_bilkis_lang();
    $c    = riad_bilkis_choice_texts($lang);
    $t    = riad_bilkis_official_texts($lang);

    $form = '<form class="rb-choice__form" data-rb-form="info" data-lang="' . esc_attr($lang) . '">'
        . '<label>' . esc_html($c['name']) . '<input type="text" name="name" required></label>'
        . '<label>' . esc_html($c['email']) . '<input type="email" name="email" required></label>'
        . '<label>' . esc_html($c['phone']) . '<input type="tel" name="phone"></label>'
        . '<label>' . esc_html($c['message']) . '<textarea name="message" rows="4" required></textarea></label>'
        . '<button type="submit" class="rb-official__btn">' . esc_html($c['send']) . '</button>'
        . '<p class="rb-form__status" data-rb-status></p>'
        . '</form>';

    return '<section class="rb-section rb-choice" id="rb-choice"><div class="rb-container">'
        . '<span class="rb-section-label">' . esc_html($c['label']) . '</span>'
        . '<h2 class="rb-section-title">' . esc_html($c['title']) . '</h2>'
        . '<div class="rb-section-line"></div>'
        . '<div class="rb-choice__grid">'
        . '<div class="rb-choice__card"><h3>' . esc_html($c['form_title']) . '</h3>'
        . '<p>' . esc_html($c['form_text']) . '</p>' . $form . '</div>'
        . '<div class="rb-choice__or"><span>' . esc_html($c['or']) . '</span></div>'
        . '<div class="rb-choice__card rb-choice__card--book"><h3>' . esc_html($c['book_title']) . '</h3>'
        . '<p>' . esc_html($c['book_text']) . '</p>'
        . '<p class="rb-official__btn-wrap"><a class="rb-official__btn" href="' . esc_url(RIAD_BILKIS_OFFICIAL_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html($t['cta']) . '</a></p>'
        . riad_bilkis_promo_box($t)
        . '</div></div></div></section>';
}

add_action('wp_enqueue_scripts', function () {
    if (!riad_bilkis_is_home_page() && !riad_bilkis_room_slug() && !riad_bilkis_is_rooms_page()) return;
    wp_enqueue_script('riad-bilkis-forms', '/sejour/forms.js', array(), '1.0', true);
    wp_enqueue_style(
        'riad-bilkis-fonts',
        'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    wp_register_style('riad-bilkis-rooms', false);
    wp_enqueue_style('riad-bilkis-rooms');
    wp_add_inline_style('riad-bilkis-rooms', '
/* Section « question ou réservation » : serif pour les titres, sans-serif moderne pour le reste. */
.rb-choice{background:#fff;--rb-choice-serif:"Cormorant Garamond",Georgia,serif;
 --rb-choice-sans:"Montserrat","Helvetica Neue",Arial,sans-serif;--rb-choice-accent:#A8341C}
.rb-choice .rb-section-label{font-family:var(--rb-choice-sans);font-size:12.5px;font-weight:600;
 letter-spacing:3.4px;text-transform:uppercase;color:#B08A57}
.rb-choice .rb-section-title{font-family:var(--rb-choice-serif);font-size:47px;line-height:1.12;font-weight:700;
 letter-spacing:.5px;color:#2C2318}
.rb-choice__grid{display:grid;grid-template-columns:1fr 60px 1fr;align-items:stretch;gap:0;margin-top:46px}
.rb-choice__card{background:#FBF7F2;border:1px solid #E8E0D5;padding:34px 30px;text-align:left;
 font-family:var(--rb-choice-sans)}
.rb-choice__card--book{text-align:center;display:flex;flex-direction:column;justify-content:center;
 background:#FFFBF4;border-color:#E3CFA9}
.rb-choice__card h3{font-family:var(--rb-choice-serif);font-size:31px;line-height:1.2;color:#2C2318;
 margin:0 0 12px;font-weight:600;letter-spacing:.3px}
.rb-choice__card--book h3{font-size:37px;font-weight:700;color:var(--rb-choice-accent);margin-bottom:8px}
.rb-choice__card--book h3:after{content:"";display:block;width:64px;height:2px;background:#C99752;
 margin:16px auto 0}
.rb-choice__card p{font-family:var(--rb-choice-sans);font-size:15.5px;color:#6E5B45;line-height:1.7;
 font-weight:400;margin:0 0 18px}
.rb-choice__card--book p{font-size:16.5px;color:#5A4A38;margin:18px 0 24px}
.rb-choice__or{display:flex;align-items:center;justify-content:center;position:relative}
.rb-choice__or span{font-family:var(--rb-choice-sans);font-size:12px;font-weight:600;letter-spacing:2px;
 text-transform:uppercase;color:#8B7355;background:#fff;padding:8px 0;z-index:1}
.rb-choice__or:before{content:"";position:absolute;top:0;bottom:0;left:50%;width:1px;background:#E8E0D5}
.rb-choice__form label{display:block;font-family:var(--rb-choice-sans);font-size:12px;font-weight:600;
 letter-spacing:1.6px;text-transform:uppercase;color:#7A6650;margin-bottom:16px}
.rb-choice__form input,.rb-choice__form textarea{display:block;width:100%;margin-top:7px;padding:13px 14px;
 border:1px solid #E0D6C8;border-radius:3px;background:#fff;font-size:16px;color:#2C2318;font-weight:400;
 font-family:var(--rb-choice-sans);text-transform:none;letter-spacing:0}
.rb-choice__form input:focus,.rb-choice__form textarea:focus{outline:none;border-color:#C99752}
.rb-choice__form button{margin-top:6px}
.rb-choice .rb-official__btn,.rb-choice .rb-official__btn:visited{font-family:var(--rb-choice-sans);
 font-size:16.5px;font-weight:600;letter-spacing:.9px;padding:17px 38px}
.rb-choice__card--book .rb-official__btn,.rb-choice__card--book .rb-official__btn:visited{background:#C0452A;
 box-shadow:0 4px 16px rgba(168,52,28,.28)}
.rb-choice__card--book .rb-official__btn:hover,.rb-choice__card--book .rb-official__btn:focus{background:#A8341C}
.rb-choice .rb-choice__form .rb-official__btn{background:transparent;color:#8A6A3B;border:1px solid #C99752;
 box-shadow:none;font-size:15px;letter-spacing:1.4px;text-transform:uppercase;padding:15px 30px}
.rb-choice .rb-choice__form .rb-official__btn:hover,
.rb-choice .rb-choice__form .rb-official__btn:focus{background:#C99752;color:#fff}
.rb-choice .rb-promo-box{margin-top:26px;padding:16px 26px;border-width:2px}
.rb-choice .rb-promo-label{font-family:var(--rb-choice-sans);font-size:12.5px;font-weight:600;letter-spacing:2px;
 color:#6b4a1b}
.rb-choice .rb-promo-value{font-family:var(--rb-choice-sans);font-size:25px;font-weight:700;letter-spacing:3px;
 padding:9px 22px}
.rb-choice .rb-promo-hint{font-family:var(--rb-choice-sans);font-size:13px;color:#6E5B45;font-style:normal}
.rb-form__status{min-height:20px;margin:12px 0 0;font-size:14px;color:#8B7355}
.rb-form__status--ok{color:#2f7a4f}
.rb-form__status--err{color:#b3392a}
/* Page d\'une chambre : la couleur d\'accent distingue chaque chambre. */
.rb-room-page{max-width:1060px;margin:0 auto;padding:8px 0 20px;color:#33291F}
.rb-room-head{text-align:center;margin-bottom:40px}
.rb-room-eyebrow{display:block;font-family:"Raleway",Arial,sans-serif;font-size:12px;letter-spacing:3px;
 text-transform:uppercase;font-weight:600;color:var(--rb-room-accent);margin-bottom:14px}
.rb-room-title{font-family:"Cormorant Garamond",Georgia,serif;font-size:50px;line-height:1.12;font-weight:600;
 letter-spacing:1.5px;text-transform:uppercase;color:#2C2318;margin:0 0 12px}
.rb-room-tagline{font-size:17.5px;color:#7A6650;font-weight:400;margin:0}
.rb-room-rule{display:block;width:60px;height:1px;background:var(--rb-room-accent);margin:26px auto 0}
.rb-room-body{margin-bottom:34px}
.rb-room-body h2{font-family:"Cormorant Garamond",Georgia,serif;font-size:30px;font-weight:600;color:#2C2318;
 letter-spacing:.3px;margin:0 0 16px;padding-left:16px;border-left:4px solid var(--rb-room-accent)}
.rb-room-body p{font-size:16.5px;line-height:1.8;color:#4A3D31;margin:0 0 14px;font-weight:400}
.rb-room-equip{display:grid;grid-template-columns:repeat(2,1fr);gap:0 30px}
.rb-room-equip ul{list-style:none;margin:0;padding:0}
.rb-room-equip li{position:relative;padding:0 0 10px 20px;font-size:16px;color:#4A3D31;font-weight:400}
.rb-room-equip li:before{content:"";position:absolute;left:2px;top:8px;width:6px;height:6px;border-radius:50%;
 background:var(--rb-room-accent)}
.rb-room-gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:10px 0 40px}
.rb-room-photo{margin:0;overflow:hidden}
.rb-room-photo img{display:block;width:100%;height:260px;object-fit:cover;transition:transform .5s ease}
.rb-room-photo:hover img{transform:scale(1.04)}
.rb-room-photo--empty{display:flex;align-items:center;justify-content:center;height:260px;background:#F6F1EA;
 border:1px dashed #DDD2C4}
.rb-room-photo--empty span{font-family:"Cormorant Garamond",Georgia,serif;font-size:16px;letter-spacing:1px;
 color:#A79684}
.rb-room-book{text-align:center;background:#FBF7F2;border:1px solid #E8E0D5;padding:32px 20px;margin-bottom:46px}
.rb-room-book .rb-official__btn{background:var(--rb-room-accent);letter-spacing:1.6px;text-transform:uppercase;
 padding:15px 34px}
.rb-room-book .rb-official__btn:hover,.rb-room-book .rb-official__btn:focus{background:#3D3229}
.rb-room-others h2{font-family:"Cormorant Garamond",Georgia,serif;font-size:32px;font-weight:600;letter-spacing:.5px;
 text-align:center;color:#2C2318;margin:0 0 26px}
.rb-room-others__grid{display:grid;grid-template-columns:repeat(2,1fr);gap:26px}
.rb-room-other{display:block;text-decoration:none;background:#fff;border:1px solid #E8E0D5;overflow:hidden;
 transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease}
.rb-room-other:hover{transform:translateY(-4px);box-shadow:0 14px 34px rgba(0,0,0,.1);
 border-color:var(--rb-room-accent)}
.rb-room-other__img{display:block;height:230px;background-size:cover;background-position:center;background-color:#F6F1EA}
.rb-room-other__name{display:block;padding:20px;text-align:center;font-family:"Cormorant Garamond",Georgia,serif;
 font-size:24px;font-weight:600;color:#2C2318}
/* Page « Chambres » : trois cartes identiques sur une ligne. */
.rb-rooms-index{max-width:1140px;margin:0 auto;padding:8px 0 10px}
.rb-rooms-index__head{text-align:center;margin-bottom:44px}
.rb-rooms-index__title{font-family:"Cormorant Garamond",Georgia,serif;font-size:46px;font-weight:600;
 letter-spacing:1.5px;color:#2C2318;margin:0 0 8px}
.rb-rooms-index__intro{max-width:640px;margin:18px auto 0;font-size:16.5px;line-height:1.8;color:#4A3D31}
.rb-rooms-index__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:30px}
.rb-rooms-card{display:flex;flex-direction:column;background:#fff;border:1px solid #E8E0D5;overflow:hidden;
 transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease}
.rb-rooms-card:hover{transform:translateY(-5px);box-shadow:0 16px 38px rgba(0,0,0,.1);
 border-color:var(--rb-room-accent)}
.rb-rooms-card__link{display:block;overflow:hidden}
.rb-rooms-card__img{display:block;height:280px;background-size:cover;background-position:center;
 background-color:#F6F1EA;transition:transform .6s ease}
.rb-rooms-card:hover .rb-rooms-card__img{transform:scale(1.05)}
.rb-rooms-card__body{flex:1;display:flex;flex-direction:column;align-items:center;text-align:center;
 padding:26px 22px 30px}
.rb-rooms-card__colors{font-family:"Raleway",Arial,sans-serif;font-size:11.5px;letter-spacing:2.6px;
 text-transform:uppercase;font-weight:600;color:var(--rb-room-accent)}
.rb-rooms-card__name{font-family:"Cormorant Garamond",Georgia,serif;font-size:27px;font-weight:600;
 letter-spacing:.5px;margin:10px 0 20px}
.rb-rooms-card__name a{color:#2C2318;text-decoration:none}
.rb-rooms-card__btn,.rb-rooms-card__btn:visited{margin-top:auto;display:inline-block;padding:12px 26px;
 border:1px solid var(--rb-room-accent);color:var(--rb-room-accent);background:transparent;text-decoration:none;
 font-family:"Raleway",Arial,sans-serif;font-size:12.5px;font-weight:600;letter-spacing:2px;
 text-transform:uppercase;transition:background .3s ease,color .3s ease}
.rb-rooms-card__btn:hover,.rb-rooms-card__btn:focus{background:var(--rb-room-accent);color:#fff}
@media(max-width:768px){
.rb-rooms-index__title{font-size:31px;letter-spacing:.8px}
.rb-rooms-index__grid{grid-template-columns:1fr;gap:22px}
.rb-rooms-card__img{height:240px}
.rb-choice .rb-section-title{font-size:32px}
.rb-choice__card{padding:26px 20px}
.rb-choice__card h3{font-size:26px}
.rb-choice__card--book h3{font-size:30px}
.rb-choice__card--book p{font-size:15.5px}
.rb-choice .rb-official__btn{font-size:15.5px;padding:16px 26px}
.rb-choice .rb-promo-value{font-size:22px;letter-spacing:2px}
.rb-choice__grid{grid-template-columns:1fr}
.rb-choice__or{padding:18px 0}
.rb-choice__or:before{top:50%;bottom:auto;left:0;right:0;width:auto;height:1px}
.rb-room-title{font-size:31px;letter-spacing:.8px}
.rb-room-body h2{font-size:26px}
.rb-room-equip{grid-template-columns:1fr}
.rb-room-gallery{grid-template-columns:1fr;gap:12px}
.rb-room-photo img{height:230px}
.rb-room-others__grid{grid-template-columns:1fr}
}
');
}, 26);

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
