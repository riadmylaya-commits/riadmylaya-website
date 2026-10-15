<?php
/**
 * Riad Bilkis — bandeau cookies (extension CookieAdmin) en trois langues.
 *
 * CookieAdmin ne gère qu'une seule langue : ses textes de bandeau viennent
 * d'un objet JavaScript localisé (cookieadmin_policy) et sa fenêtre de
 * préférences d'un gabarit anglais figé. Les deux sont réécrits ici selon la
 * langue de l'URL, sans modifier le plugin.
 */

if (!defined('ABSPATH')) exit;

// Textes du bandeau et des boutons, par langue.
function riad_bilkis_cookie_policy_texts($lang) {
    $t = array(
        'fr' => array(
            'cookieadmin_notice_title'    => 'Nous respectons votre vie privée',
            'cookieadmin_notice'          => 'Les cookies nous aident à améliorer votre expérience, à fournir un contenu personnalisé et à analyser le trafic. Vous pouvez choisir les cookies à autoriser en cliquant sur <b>Personnaliser</b>. Cliquez sur <b>Tout accepter</b> pour consentir ou sur <b>Tout refuser</b> pour décliner les cookies non essentiels.',
            'cookieadmin_preference_title' => 'Personnalisez vos préférences de cookies',
            'cookieadmin_preference'      => 'Nous utilisons des cookies pour assurer une navigation fluide et activer les fonctions essentielles du site. Vous pouvez consulter les informations détaillées sur chaque catégorie de cookies ci-dessous. <br>Les cookies marqués comme <b>Nécessaires</b> sont stockés dans votre navigateur car ils sont essentiels au fonctionnement de base du site. <b>Ces cookies ne nécessitent pas votre consentement selon le RGPD.</b> <br>Nous utilisons également des cookies tiers pour analyser l\'utilisation du site, mémoriser vos préférences et fournir un contenu et des publicités pertinents. Ceux-ci ne seront activés qu\'avec votre consentement.',
            'reConsent_title'             => 'Modifier les préférences de cookies',
            'cookieadmin_customize_btn'   => 'Personnaliser',
            'cookieadmin_reject_btn'      => 'Tout refuser',
            'cookieadmin_accept_btn'      => 'Tout accepter',
            'cookieadmin_save_btn'        => 'Enregistrer les préférences',
        ),
        'en' => array(
            'cookieadmin_notice_title'    => 'We respect your privacy',
            'cookieadmin_notice'          => 'Cookies help us improve your experience, deliver personalised content and analyse traffic. You can choose which cookies to allow by clicking <b>Customise</b>. Click <b>Accept all</b> to consent, or <b>Reject all</b> to decline non-essential cookies.',
            'cookieadmin_preference_title' => 'Customise your cookie preferences',
            'cookieadmin_preference'      => 'We use cookies to ensure smooth browsing and to enable the essential features of the site. You can review detailed information about each cookie category below. <br>Cookies marked as <b>Necessary</b> are stored in your browser because they are essential to the basic operation of the site. <b>These cookies do not require your consent under the GDPR.</b> <br>We also use third-party cookies to analyse how the site is used, remember your preferences and deliver relevant content and advertising. These are only enabled with your consent.',
            'reConsent_title'             => 'Modify cookie preferences',
            'cookieadmin_customize_btn'   => 'Customise',
            'cookieadmin_reject_btn'      => 'Reject all',
            'cookieadmin_accept_btn'      => 'Accept all',
            'cookieadmin_save_btn'        => 'Save preferences',
        ),
        'es' => array(
            'cookieadmin_notice_title'    => 'Respetamos su privacidad',
            'cookieadmin_notice'          => 'Las cookies nos ayudan a mejorar su experiencia, ofrecer contenidos personalizados y analizar el tráfico. Puede elegir qué cookies autorizar haciendo clic en <b>Personalizar</b>. Haga clic en <b>Aceptar todo</b> para dar su consentimiento o en <b>Rechazar todo</b> para rechazar las cookies no esenciales.',
            'cookieadmin_preference_title' => 'Personalice sus preferencias de cookies',
            'cookieadmin_preference'      => 'Utilizamos cookies para garantizar una navegación fluida y activar las funciones esenciales del sitio. A continuación puede consultar la información detallada de cada categoría de cookies. <br>Las cookies marcadas como <b>Necesarias</b> se almacenan en su navegador porque son esenciales para el funcionamiento básico del sitio. <b>Estas cookies no requieren su consentimiento según el RGPD.</b> <br>También utilizamos cookies de terceros para analizar el uso del sitio, recordar sus preferencias y ofrecer contenidos y anuncios relevantes. Solo se activarán con su consentimiento.',
            'reConsent_title'             => 'Modificar las preferencias de cookies',
            'cookieadmin_customize_btn'   => 'Personalizar',
            'cookieadmin_reject_btn'      => 'Rechazar todo',
            'cookieadmin_accept_btn'      => 'Aceptar todo',
            'cookieadmin_save_btn'        => 'Guardar preferencias',
        ),
    );
    return isset($t[$lang]) ? $t[$lang] : $t['fr'];
}

// Gabarit anglais de la fenêtre de préférences => texte de la langue affichée.
function riad_bilkis_cookie_modal_texts($lang) {
    $t = array(
        'Necessary Cookies' => array(
            'fr' => 'Cookies nécessaires', 'es' => 'Cookies necesarias'),
        'Functional Cookies' => array(
            'fr' => 'Cookies fonctionnels', 'es' => 'Cookies funcionales'),
        'Analytical Cookies' => array(
            'fr' => 'Cookies analytiques', 'es' => 'Cookies analíticas'),
        'Advertisement Cookies' => array(
            'fr' => 'Cookies publicitaires', 'es' => 'Cookies publicitarias'),
        'Unclassified Cookies' => array(
            'fr' => 'Cookies non classés', 'es' => 'Cookies sin clasificar'),
        'Always Active' => array(
            'fr' => 'Toujours actifs', 'es' => 'Siempre activas'),
        'Cookie Preferences' => array(
            'fr' => 'Préférences de cookies', 'es' => 'Preferencias de cookies'),
        'Necessary cookies enable essential site features like secure log-ins and consent preference adjustments. They do not store personal data.' => array(
            'fr' => 'Les cookies nécessaires assurent les fonctions essentielles du site, comme la connexion sécurisée et l\'enregistrement de vos préférences de consentement. Ils ne stockent aucune donnée personnelle.',
            'es' => 'Las cookies necesarias permiten las funciones esenciales del sitio, como el inicio de sesión seguro y el registro de sus preferencias de consentimiento. No almacenan datos personales.'),
        'Functional cookies support features like content sharing on social media, collecting feedback, and enabling third-party tools.' => array(
            'fr' => 'Les cookies fonctionnels permettent le partage de contenu sur les réseaux sociaux, le recueil de vos avis et l\'utilisation d\'outils tiers.',
            'es' => 'Las cookies funcionales permiten compartir contenidos en redes sociales, recoger sus opiniones y utilizar herramientas de terceros.'),
        'Analytical cookies track visitor interactions, providing insights on metrics like visitor count, bounce rate, and traffic sources.' => array(
            'fr' => 'Les cookies analytiques mesurent la fréquentation du site : nombre de visiteurs, taux de rebond, origine du trafic.',
            'es' => 'Las cookies analíticas miden la actividad del sitio: número de visitantes, tasa de rebote y origen del tráfico.'),
        'Advertisement cookies deliver personalized ads based on your previous visits and analyze the effectiveness of ad campaigns.' => array(
            'fr' => 'Les cookies publicitaires proposent des annonces personnalisées selon vos visites précédentes et mesurent l\'efficacité des campagnes.',
            'es' => 'Las cookies publicitarias muestran anuncios personalizados según sus visitas anteriores y miden la eficacia de las campañas.'),
        'Unclassified cookies are cookies that we are in the process of classifying, together with the providers of individual cookies.' => array(
            'fr' => 'Les cookies non classés sont en cours de classement avec les fournisseurs concernés.',
            'es' => 'Las cookies sin clasificar están en proceso de clasificación con los proveedores correspondientes.'),
        'Reject All' => array(
            'fr' => 'Tout refuser', 'es' => 'Rechazar todo'),
        'Save My Preferences' => array(
            'fr' => 'Enregistrer mes préférences', 'es' => 'Guardar mis preferencias'),
        'Accept All' => array(
            'fr' => 'Tout accepter', 'es' => 'Aceptar todo'),
        'Remark' => array(
            'fr' => 'Détail', 'es' => 'Detalle'),
        '>None<' => array(
            'fr' => '>Aucun<', 'es' => '>Ninguna<'),
    );
    $pairs = array();
    foreach ($t as $en => $tr) {
        if (!empty($tr[$lang])) $pairs[$en] = $tr[$lang];
    }
    return $pairs;
}

function riad_bilkis_cookie_lang() {
    return function_exists('riad_bilkis_lang') ? riad_bilkis_lang() : 'fr';
}

// Bandeau : réécriture de l'objet JavaScript localisé par CookieAdmin.
function riad_bilkis_cookie_localize() {
    if (is_admin() || !function_exists('wp_scripts')) return;
    $texts = riad_bilkis_cookie_policy_texts(riad_bilkis_cookie_lang());
    foreach (wp_scripts()->registered as $handle => $script) {
        if (strpos($handle, 'cookieadmin') === false) continue;
        if (empty($script->extra['data']) || !is_string($script->extra['data'])) continue;
        if (!preg_match('/(var\s+cookieadmin_policy\s*=\s*)(\{.*\})(\s*;?)/s', $script->extra['data'], $m)) continue;
        $cfg = json_decode($m[2], true);
        if (!is_array($cfg)) continue;
        foreach ($texts as $key => $value) {
            if (array_key_exists($key, $cfg)) $cfg[$key] = $value;
        }
        $script->extra['data'] = str_replace($m[0], $m[1] . wp_json_encode($cfg) . ';', $script->extra['data']);
    }
}
add_action('wp_print_scripts', 'riad_bilkis_cookie_localize', 5);
add_action('wp_print_footer_scripts', 'riad_bilkis_cookie_localize', 5);

// Fenêtre de préférences : son gabarit anglais est traduit à la sortie.
function &riad_bilkis_cookie_ob_level() {
    static $level = 0;
    return $level;
}

add_action('wp_footer', function () {
    if (is_admin()) return;
    ob_start(function ($html) {
        if (strpos($html, 'cookieadmin') === false) return $html;
        $pairs = riad_bilkis_cookie_modal_texts(riad_bilkis_cookie_lang());
        return $pairs ? strtr($html, $pairs) : $html;
    });
    $level = &riad_bilkis_cookie_ob_level();
    $level = ob_get_level();
}, -PHP_INT_MAX);

add_action('wp_footer', function () {
    if (is_admin()) return;
    $level = &riad_bilkis_cookie_ob_level();
    if ($level > 0 && ob_get_level() === $level) {
        ob_end_flush();
        $level = 0;
    }
}, PHP_INT_MAX);
