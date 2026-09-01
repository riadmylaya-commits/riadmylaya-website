<?php
/**
 * Plugin Name: Riad Bilkis SEO
 * Description: SEO metadata, title tags, and schema.org for Riad Bilkis Marrakech
 * Version: 3.0
 * Author: Devin
 */

if (!defined('ABSPATH')) exit;

// SEO data per page slug
function riad_bilkis_seo_data() {
    return array(
        '' => array(
            'title' => 'Riad Bilkis Marrakech | Riad de Charme dans la Médina | Réservation Directe',
            'description' => 'Séjournez au Riad Bilkis, maison d\'hôtes de charme au cœur de la Médina de Marrakech. Patio traditionnel, terrasse panoramique, petit-déjeuner marocain. Meilleur tarif garanti en réservation directe.'
        ),
        'chambres' => array(
            'title' => 'Nos Chambres & Suites | Riad Bilkis Marrakech - Hébergement de Charme',
            'description' => 'Découvrez nos 3 chambres de charme au Riad Bilkis Marrakech : Babouche (80€), Tarbouche (90€) et Véro (120€). Décoration traditionnelle marocaine, climatisation, WiFi, salle de bain privée.'
        ),
        'chambre-babouche' => array(
            'title' => 'Chambre Babouche | Riad Bilkis Marrakech - dès 80€/nuit',
            'description' => 'Chambre Babouche au Riad Bilkis : chambre élégante inspirée de l\'artisanat marocain. Zellige, tadelakt, climatisation, WiFi, salle de bain privée. À partir de 80€/nuit petit-déjeuner inclus.'
        ),
        'chambre-tarbouche' => array(
            'title' => 'Chambre Tarbouche | Riad Bilkis Marrakech - dès 90€/nuit',
            'description' => 'Chambre Tarbouche au Riad Bilkis : espace chaleureux aux tons chauds alliant tradition et modernité. Climatisation, WiFi, salle de bain privée. Dès 90€/nuit petit-déjeuner inclus.'
        ),
        'chambre-vero' => array(
            'title' => 'Chambre Véro | Riad Bilkis Marrakech - Bleu et blanc, avec petit salon',
            'description' => 'Chambre Véro au Riad Bilkis Marrakech : chambre double bleu et blanc avec petit salon, au premier étage. Climatisation, WiFi, coffre-fort, produits Les Sens de Marrakech.'
        ),
        'galerie' => array(
            'title' => 'Galerie Photos | Riad Bilkis Marrakech - Visite en Images',
            'description' => 'Galerie photos du Riad Bilkis Marrakech : découvrez nos chambres, le patio, la terrasse panoramique et l\'architecture traditionnelle marocaine en images.'
        ),
        'nos-services' => array(
            'title' => 'Nos Services | Riad Bilkis Marrakech - Petit-déjeuner, Hammam, Transferts',
            'description' => 'Services du Riad Bilkis : petit-déjeuner marocain fait maison, transfert aéroport privé, hammam traditionnel, cours de cuisine, conciergerie personnalisée.'
        ),
        'excursions-activites' => array(
            'title' => 'Excursions et Activités | Riad Bilkis Marrakech - Atlas, Essaouira, Désert',
            'description' => 'Excursions depuis le Riad Bilkis Marrakech : Atlas, Essaouira, désert d\'Agafay, cascades d\'Ouzoud, souks de Marrakech. Organisation sur mesure pour votre séjour.'
        ),
        'contact' => array(
            'title' => 'Contact et Localisation | Riad Bilkis Marrakech - Médina',
            'description' => 'Contactez le Riad Bilkis Marrakech par WhatsApp, email ou téléphone. Situé au cœur de la médina historique, à 5 minutes de la Place Jemaa el-Fna.'
        ),
        'reservation' => array(
            'title' => 'Réservation Directe | Riad Bilkis Marrakech - Meilleur Tarif Garanti',
            'description' => 'Réservez votre séjour au Riad Bilkis Marrakech au meilleur prix garanti. Réservation directe sans commission. 3 chambres de charme dès 80€/nuit.'
        )
    );
}

function riad_bilkis_get_current_slug() {
    // Les pages excursions fournissent elles-mêmes leur titre et leurs métadonnées.
    if (function_exists('riad_bilkis_exc_route') && riad_bilkis_exc_route()) return '__excursions__';
    if (is_front_page() || is_home()) return '';
    global $post;
    return $post ? $post->post_name : '';
}

// Custom title
function riad_bilkis_custom_title($title) {
    if (is_admin()) return $title;
    $seo_data = riad_bilkis_seo_data();
    $slug = riad_bilkis_get_current_slug();
    if (isset($seo_data[$slug])) {
        return $seo_data[$slug]['title'];
    }
    return $title;
}
add_filter('pre_get_document_title', 'riad_bilkis_custom_title', 999);
add_filter('wp_title', 'riad_bilkis_custom_title', 999);

// Meta description + Schema.org
function riad_bilkis_head_meta() {
    if (is_admin()) return;
    $seo_data = riad_bilkis_seo_data();
    $slug = riad_bilkis_get_current_slug();
    
    // Meta description
    if (isset($seo_data[$slug]) && !empty($seo_data[$slug]['description'])) {
        echo '<meta name="description" content="' . esc_attr($seo_data[$slug]['description']) . '" />' . "\n";
    }
    
    // Open Graph tags
    if (isset($seo_data[$slug])) {
        $title = $seo_data[$slug]['title'];
        $desc = $seo_data[$slug]['description'];
        $url = home_url($_SERVER['REQUEST_URI']);
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($desc) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
        echo '<meta property="og:site_name" content="Riad Bilkis Marrakech" />' . "\n";
        echo '<meta property="og:locale" content="fr_FR" />' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($desc) . '" />' . "\n";
    }
    
    // Schema.org Hotel JSON-LD (on all pages)
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Hotel',
        'name' => 'Riad Bilkis',
        'description' => 'Riad de charme au cœur de la médina de Marrakech. 3 chambres élégantes, terrasse panoramique, petit-déjeuner marocain traditionnel.',
        'url' => 'https://riadbilkis.com',
        'telephone' => '+212625675494',
        'email' => 'riadbilkis@gmail.com',
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => '117 Derb Jdid, Bab Doukkala, Médina',
            'addressLocality' => 'Marrakech',
            'addressRegion' => 'Marrakech-Safi',
            'postalCode' => '40000',
            'addressCountry' => 'MA'
        ),
        'geo' => array(
            '@type' => 'GeoCoordinates',
            'latitude' => '31.6295',
            'longitude' => '-7.9811'
        ),
        'image' => 'https://images.unsplash.com/photo-1539437829697-1b4ed5aebd19?w=1600&q=85',
        'priceRange' => '€€',
        'starRating' => array(
            '@type' => 'Rating',
            'ratingValue' => '4'
        ),
        'checkinTime' => '13:00',
        'checkoutTime' => '11:00',
        'numberOfRooms' => 3,
        'amenityFeature' => array(
            array('@type' => 'LocationFeatureSpecification', 'name' => 'WiFi gratuit', 'value' => true),
            array('@type' => 'LocationFeatureSpecification', 'name' => 'Petit-déjeuner inclus', 'value' => true),
            array('@type' => 'LocationFeatureSpecification', 'name' => 'Climatisation', 'value' => true),
            array('@type' => 'LocationFeatureSpecification', 'name' => 'Terrasse panoramique', 'value' => true),
            array('@type' => 'LocationFeatureSpecification', 'name' => 'Transfert aéroport', 'value' => true)
        ),
        'availableLanguage' => array('French', 'English', 'Spanish'),
        'sameAs' => array('https://guide.riadbilkis.com/'),
        'currenciesAccepted' => 'EUR, MAD',
        'paymentAccepted' => 'Cash, Credit Card',
        'makesOffer' => array(
            array(
                '@type' => 'Offer',
                'name' => 'Chambre Babouche',
                'description' => 'Chambre élégante inspirée de l\'artisanat marocain',
                'price' => '80',
                'priceCurrency' => 'EUR',
                'url' => 'https://riadbilkis.com/chambre-babouche/'
            ),
            array(
                '@type' => 'Offer',
                'name' => 'Chambre Tarbouche',
                'description' => 'Espace chaleureux alliant tradition et modernité',
                'price' => '90',
                'priceCurrency' => 'EUR',
                'url' => 'https://riadbilkis.com/chambre-tarbouche/'
            ),
            array(
                '@type' => 'Offer',
                'name' => 'Chambre Véro',
                'description' => 'Chambre double bleu et blanc avec petit salon',
                'price' => '120',
                'priceCurrency' => 'EUR',
                'url' => 'https://riadbilkis.com/chambre-vero/'
            )
        )
    );
    
    // Add Room-specific schema on room pages
    $room_schemas = array(
        'chambre-babouche' => array(
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            'name' => 'Chambre Babouche',
            'description' => 'Chambre élégante inspirée de l\'artisanat marocain traditionnel',
            'url' => 'https://riadbilkis.com/chambre-babouche/',
            'occupancy' => array('@type' => 'QuantitativeValue', 'value' => 2),
            'bed' => array('@type' => 'BedDetails', 'typeOfBed' => 'Double', 'numberOfBeds' => 1),
            'amenityFeature' => array(
                array('@type' => 'LocationFeatureSpecification', 'name' => 'WiFi'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Climatisation'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Salle de bain privée')
            ),
            'offers' => array(
                '@type' => 'Offer',
                'price' => '80',
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock'
            )
        ),
        'chambre-tarbouche' => array(
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            'name' => 'Chambre Tarbouche',
            'description' => 'Espace chaleureux aux tons chauds alliant tradition et modernité',
            'url' => 'https://riadbilkis.com/chambre-tarbouche/',
            'occupancy' => array('@type' => 'QuantitativeValue', 'value' => 2),
            'bed' => array('@type' => 'BedDetails', 'typeOfBed' => 'Double', 'numberOfBeds' => 1),
            'amenityFeature' => array(
                array('@type' => 'LocationFeatureSpecification', 'name' => 'WiFi'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Climatisation'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Salle de bain privée')
            ),
            'offers' => array(
                '@type' => 'Offer',
                'price' => '90',
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock'
            )
        ),
        'chambre-vero' => array(
            '@context' => 'https://schema.org',
            '@type' => 'HotelRoom',
            'name' => 'Chambre Véro',
            'description' => 'Chambre double bleu et blanc avec petit salon, au premier étage',
            'url' => 'https://riadbilkis.com/chambre-vero/',
            'occupancy' => array('@type' => 'QuantitativeValue', 'value' => 2),
            'bed' => array('@type' => 'BedDetails', 'typeOfBed' => 'King', 'numberOfBeds' => 1),
            'amenityFeature' => array(
                array('@type' => 'LocationFeatureSpecification', 'name' => 'WiFi'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Climatisation'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Salle de bain privée'),
                array('@type' => 'LocationFeatureSpecification', 'name' => 'Vue panoramique')
            ),
            'offers' => array(
                '@type' => 'Offer',
                'price' => '120',
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock'
            )
        )
    );
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
    
    if (isset($room_schemas[$slug])) {
        echo '<script type="application/ld+json">' . wp_json_encode($room_schemas[$slug], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    // BreadcrumbList schema
    if (!empty($slug)) {
        $breadcrumb = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Accueil',
                    'item' => 'https://riadbilkis.com'
                ),
                array(
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => get_the_title(),
                    'item' => get_permalink()
                )
            )
        );
        echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
}
add_action('wp_head', 'riad_bilkis_head_meta', 1);

// Remove WordPress default meta generator tag
remove_action('wp_head', 'wp_generator');

// Canonical : WordPress emet deja rel_canonical, sans les parametres d'URL.

// Enqueue Google Fonts and custom luxury CSS
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('rb-google-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Raleway:wght@300;400;500;600&display=swap', array(), null);
    wp_add_inline_style('astra-theme-css', riad_bilkis_luxury_css());
}, 20);

function riad_bilkis_luxury_css() {
    return '
/* ===== RIAD BILKIS LUXURY DESIGN ===== */

/* Typography */
body { font-family: "Raleway", sans-serif; color: #3D3229; }
h1, h2, h3, h4, h5, h6 { font-family: "Cormorant Garamond", Georgia, serif; }

/* Header - Transparent & Elegant */
.ast-primary-header-bar { background: rgba(255,255,255,0.95) !important; backdrop-filter: blur(10px); box-shadow: 0 1px 20px rgba(0,0,0,0.08); border-bottom: 1px solid rgba(199,91,57,0.1); }
.ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link { font-family: "Raleway", sans-serif !important; font-weight: 500; font-size: 14px !important; letter-spacing: 1.5px; text-transform: uppercase; color: #3D3229 !important; padding: 10px 18px !important; transition: color 0.3s ease; }
.ast-builder-menu-1 .main-header-menu > .menu-item > .menu-link:hover,
.ast-builder-menu-1 .main-header-menu > .menu-item.current-menu-item > .menu-link { color: #C75B39 !important; }
.site-title a, .site-title { font-family: "Cormorant Garamond", serif !important; font-weight: 600; font-size: 28px !important; color: #C75B39 !important; letter-spacing: 2px; }
.site-description { font-family: "Raleway", sans-serif !important; font-size: 11px !important; letter-spacing: 3px; text-transform: uppercase; color: #8B7355 !important; }

/* Hero Section */
.rb-hero { position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: url("https://images.unsplash.com/photo-1539437829697-1b4ed5aebd19?w=1600&q=85") center center / cover no-repeat; margin-top: -80px; }
.rb-hero-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.5) 100%); }
.rb-hero-content { position: relative; z-index: 2; text-align: center; color: #fff; padding: 20px; }
.rb-hero-subtitle-top { font-family: "Raleway", sans-serif; font-size: 16px; letter-spacing: 6px; text-transform: uppercase; opacity: 0.9; display: block; margin-bottom: 15px; font-weight: 300; }
.rb-hero-title { font-family: "Cormorant Garamond", serif; font-size: 80px; font-weight: 300; letter-spacing: 8px; margin: 0 0 20px; line-height: 1.1; color: #fff !important; }
.rb-hero-line { width: 80px; height: 1px; background: #D4A574; margin: 25px auto; }
.rb-hero-subtitle { font-family: "Raleway", sans-serif; font-size: 20px; font-weight: 300; letter-spacing: 3px; margin-bottom: 40px; opacity: 0.9; color: #fff; }
.rb-hero-btn { display: inline-block; padding: 16px 45px; border: 1px solid #D4A574; color: #fff; text-decoration: none; font-family: "Raleway", sans-serif; font-size: 13px; letter-spacing: 3px; text-transform: uppercase; transition: all 0.4s ease; background: rgba(212,165,116,0.2); }
.rb-hero-btn:hover { background: #D4A574; color: #fff; }

/* Sections */
.rb-section { padding: 100px 20px; }
.rb-container { max-width: 1100px; margin: 0 auto; }
.rb-section-label { display: block; text-align: center; font-family: "Raleway", sans-serif; font-size: 12px; letter-spacing: 5px; text-transform: uppercase; color: #C75B39; margin-bottom: 15px; font-weight: 500; }
.rb-section-title { text-align: center; font-family: "Cormorant Garamond", serif; font-size: 42px; font-weight: 400; color: #3D3229; margin: 0 0 20px; letter-spacing: 2px; }
.rb-section-line { width: 60px; height: 1px; background: #C75B39; margin: 0 auto 40px; }
.rb-section-text { text-align: center; font-size: 17px; line-height: 1.9; color: #6B5B4E; max-width: 750px; margin: 0 auto; font-weight: 300; }

/* Intro */
.rb-intro { background: #FAF8F5; }

/* Features / Why */
.rb-why { background: #fff; }
.rb-features { display: grid; grid-template-columns: repeat(4, 1fr); gap: 40px; margin-top: 50px; }
.rb-feature { text-align: center; padding: 30px 20px; }
.rb-feature-icon { font-size: 36px; margin-bottom: 20px; }
.rb-feature h3 { font-family: "Cormorant Garamond", serif; font-size: 22px; color: #3D3229; margin-bottom: 12px; font-weight: 500; }
.rb-feature p { font-size: 15px; color: #8B7355; line-height: 1.7; font-weight: 300; }

/* Rooms */
.rb-rooms { background: #F5F0E8; }
.rb-rooms-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 50px; }
.rb-room-card { background: #fff; overflow: hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; }
.rb-room-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
.rb-room-img { height: 280px; background-size: cover; background-position: center; }
.rb-room-info { padding: 30px; }
.rb-room-info h3 { font-family: "Cormorant Garamond", serif; font-size: 26px; color: #3D3229; margin-bottom: 10px; font-weight: 500; }
.rb-room-info p { font-size: 15px; color: #8B7355; line-height: 1.7; margin-bottom: 15px; font-weight: 300; }
.rb-room-price { font-size: 15px; color: #C75B39; margin-bottom: 15px; }
.rb-room-price strong { font-size: 22px; font-weight: 600; }
.rb-room-link { color: #C75B39; text-decoration: none; font-size: 14px; letter-spacing: 1px; text-transform: uppercase; font-weight: 500; transition: color 0.3s; }
.rb-room-link:hover { color: #3D3229; }

/* Services */
.rb-services-preview { background: #fff; }
.rb-services-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-top: 50px; }
.rb-service-item { padding: 40px; border: 1px solid #E8E0D5; transition: border-color 0.3s, box-shadow 0.3s; }
.rb-service-item:hover { border-color: #C75B39; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
.rb-service-item h3 { font-family: "Cormorant Garamond", serif; font-size: 24px; color: #3D3229; margin-bottom: 10px; font-weight: 500; }
.rb-service-item p { font-size: 15px; color: #8B7355; line-height: 1.7; font-weight: 300; margin: 0; }

/* CTA */
.rb-cta { background: linear-gradient(135deg, #3D3229, #6B4E3D); padding: 100px 20px; text-align: center; }
.rb-cta-title { font-family: "Cormorant Garamond", serif; font-size: 40px; color: #fff; margin-bottom: 15px; font-weight: 400; letter-spacing: 2px; }
.rb-cta-text { font-size: 17px; color: rgba(255,255,255,0.8); margin-bottom: 40px; font-weight: 300; }
.rb-cta-buttons { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
.rb-btn-outline { display: inline-block; padding: 14px 40px; border: 1px solid #C75B39; color: #C75B39; text-decoration: none; font-family: "Raleway", sans-serif; font-size: 13px; letter-spacing: 2px; text-transform: uppercase; transition: all 0.3s ease; font-weight: 500; }
.rb-btn-outline:hover { background: #C75B39; color: #fff; }
.rb-btn-white { border-color: rgba(255,255,255,0.6); color: #fff; }
.rb-btn-white:hover { background: rgba(255,255,255,0.15); border-color: #fff; color: #fff; }

/* Footer */
.ast-footer-overlay { background: #2C2218; }
.site-footer { background: #2C2218 !important; color: rgba(255,255,255,0.7); }
.site-footer a { color: #D4A574 !important; }

/* Global touches */
.entry-content { font-size: 16px; line-height: 1.8; }
.ast-page-builder-template .entry-content { padding: 0; }

/* Remove default page title on homepage */
.page-id-42 .entry-title,
.page-id-42 .ast-archive-description,
.page-id-42 .page-header { display: none !important; }

/* Full width content area for homepage */
.page-id-42 .site-content > .ast-container,
.elementor-template-full-width .site-content > .ast-container { max-width: 100% !important; padding: 0 !important; }
.page-id-42 .entry-content { padding: 0 !important; margin: 0 !important; }
.page-id-42 .site-main { padding: 0 !important; }
.page-id-42 .ast-separate-container .ast-article-single { padding: 0 !important; margin: 0 !important; }
.page-id-42 .ast-separate-container { background: transparent !important; }

/* Smooth scrolling */
html { scroll-behavior: smooth; }

/* Mobile */
@media (max-width: 768px) {
  .rb-hero-title { font-size: 42px; letter-spacing: 4px; }
  .rb-hero-subtitle { font-size: 16px; letter-spacing: 2px; }
  .rb-hero-subtitle-top { font-size: 13px; }
  .rb-features { grid-template-columns: repeat(2, 1fr); gap: 25px; }
  .rb-rooms-grid { grid-template-columns: 1fr; }
  .rb-services-grid { grid-template-columns: 1fr; }
  .rb-section { padding: 70px 20px; }
  .rb-section-title { font-size: 32px; }
  .rb-cta-title { font-size: 30px; }
}
@media (max-width: 480px) {
  .rb-hero { min-height: 90vh; }
  .rb-hero-title { font-size: 34px; }
  .rb-features { grid-template-columns: 1fr; }
  .rb-hero-btn { padding: 14px 35px; font-size: 12px; }
}
';
}

// Force Menu Principal (ID 13) for primary nav location
add_filter('wp_nav_menu_args', function($args) {
    if (isset($args['theme_location']) && in_array($args['theme_location'], array('primary', 'mobile_menu'))) {
        $args['menu'] = 13;
        $args['fallback_cb'] = false;
    }
    return $args;
}, 999);

// REST API for theme configuration
add_action('rest_api_init', function() {
    register_rest_route('riadbilkis/v1', '/theme-mods', array(
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(get_theme_mods(), 200);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    register_rest_route('riadbilkis/v1', '/set-theme-mod', array(
        'methods' => 'POST',
        'callback' => function($request) {
            $key = $request->get_param('key');
            $value = $request->get_param('value');
            if ($key) {
                $decoded = json_decode($value, true);
                if ($decoded !== null) { $value = $decoded; }
                set_theme_mod($key, $value);
                return new WP_REST_Response(array('success' => true, 'key' => $key, 'value' => get_theme_mod($key)), 200);
            }
            return new WP_REST_Response(array('error' => 'Missing key'), 400);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    register_rest_route('riadbilkis/v1', '/set-option', array(
        'methods' => 'POST',
        'callback' => function($request) {
            $key = $request->get_param('key');
            $value = $request->get_param('value');
            if ($key) {
                $decoded = json_decode($value, true);
                if ($decoded !== null) { $value = $decoded; }
                update_option($key, $value);
                return new WP_REST_Response(array('success' => true, 'key' => $key, 'value' => get_option($key)), 200);
            }
            return new WP_REST_Response(array('error' => 'Missing key'), 400);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    register_rest_route('riadbilkis/v1', '/get-option', array(
        'methods' => 'GET',
        'callback' => function($request) {
            $key = $request->get_param('key');
            if ($key) {
                return new WP_REST_Response(array('key' => $key, 'value' => get_option($key)), 200);
            }
            return new WP_REST_Response(array('error' => 'Missing key'), 400);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    register_rest_route('riadbilkis/v1', '/activate-plugin', array(
        'methods' => 'POST',
        'callback' => function($request) {
            $plugin = $request->get_param('plugin');
            if ($plugin) {
                $result = activate_plugin($plugin);
                if (is_wp_error($result)) {
                    return new WP_REST_Response(array('error' => $result->get_error_message()), 500);
                }
                return new WP_REST_Response(array('success' => true, 'plugin' => $plugin), 200);
            }
            return new WP_REST_Response(array('error' => 'Missing plugin'), 400);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
});
