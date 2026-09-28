<?php
/**
 * Riad Bilkis — articles du blog en trois langues (FR / EN / ES).
 *
 * Les articles n'existent qu'en français dans WordPress : chaque article a ici
 * une URL anglaise et espagnole servie par le même article, dont le titre et le
 * corps sont remplacés par leur version traduite. La langue vient de l'URL,
 * comme pour les pages (riad-bilkis-i18n.php).
 */

if (!defined('ABSPATH')) exit;

function riad_bilkis_blog_articles() {
    static $articles = null;
    if ($articles !== null) return $articles;

    $a = array();

    $a['blog-10-choses'] = array(
        'fr'   => '/10-choses-a-faire-marrakech/',
        'en'   => '/en/blog/10-things-to-do-in-marrakech/',
        'es'   => '/es/blog/10-cosas-que-hacer-en-marrakech/',
        'post' => '10-choses-a-faire-marrakech',
        'title' => array(
            'en' => '10 things you must do in Marrakech',
            'es' => '10 cosas que hay que hacer en Marrakech',
        ),
        'seo' => array(
            'en' => array('10 things to do in Marrakech | Riad Bilkis blog',
                'Souks, Jemaa el-Fna, Majorelle garden, Bahia palace, hammam, tagine, Ourika valley: our selection of the ten experiences not to miss in Marrakech.'),
            'es' => array('10 cosas que hacer en Marrakech | Blog del Riad Bilkis',
                'Souks, Jemaa el-Fna, jardín Majorelle, palacio de la Bahía, hammam, tayín, valle del Ourika: nuestra selección de las diez experiencias imprescindibles en Marrakech.'),
        ),
    );

    $a['blog-10-choses']['body']['en'] = <<<'HTML'
<p class="wp-block-paragraph">Marrakech, the ochre city, is full of treasures. Whether you love culture, food or adventure, here is our selection of the ten experiences not to be missed for a successful stay in Marrakech.</p>

<h2 class="wp-block-heading">1. Get lost in the souks</h2>

<p class="wp-block-paragraph">The maze of the Marrakech souks is a unique experience for the senses. Colourful spices, wrought-iron lanterns, Berber rugs, leather goods… Let the atmosphere carry you along, and do not hesitate to bargain — it is a tradition!</p>

<h2 class="wp-block-heading">2. Take in Jemaa el-Fna square</h2>

<p class="wp-block-paragraph">Listed as UNESCO intangible heritage, Jemaa el-Fna square is the beating heart of Marrakech. In the evening it turns into a huge open-air restaurant, with musicians, storytellers and acrobats.</p>

<h2 class="wp-block-heading">3. Visit the Majorelle garden</h2>

<p class="wp-block-paragraph">Created by Jacques Majorelle and restored by Yves Saint Laurent, this garden is an oasis of calm with its exotic plants, its iconic blue and its Berber museum.</p>

<h2 class="wp-block-heading">4. Discover the Bahia palace</h2>

<p class="wp-block-paragraph">A masterpiece of nineteenth-century Moroccan architecture, the Bahia palace dazzles with its zellige tiles, stucco and hand-painted cedar ceilings.</p>

<h2 class="wp-block-heading">5. Treat yourself to a traditional hammam</h2>

<p class="wp-block-paragraph">The hammam is an essential ritual in Morocco. Black-soap scrub, ghassoul clay mask, argan-oil massage… A deeply relaxing experience. At Riad Bilkis we offer a private hammam for our guests.</p>

<h2 class="wp-block-heading">6. Enjoy an authentic tagine</h2>

<p class="wp-block-paragraph">The tagine is Morocco's emblematic dish. Chicken with olives and preserved lemon, lamb with prunes, vegetables… Every version is a delight. The best way to taste it? In a family home or in a riad!</p>

<h2 class="wp-block-heading">7. Explore the Ourika valley</h2>

<p class="wp-block-paragraph">Only 45 minutes from Marrakech, the Ourika valley offers spectacular scenery: waterfalls, Berber villages clinging to the mountainside and lunch with a local family.</p>

<h2 class="wp-block-heading">8. Drink mint tea on a rooftop</h2>

<p class="wp-block-paragraph">Nothing is more Moroccan than sipping mint tea while looking out over the medina from a rooftop. At Riad Bilkis, our terrace offers a panoramic view of the rooftops and the Atlas mountains.</p>

<h2 class="wp-block-heading">9. Ride a camel in the Palmeraie</h2>

<p class="wp-block-paragraph">The Marrakech Palmeraie, with its thousands of palm trees, is the perfect setting for a camel ride at sunset. A magical and very photogenic experience!</p>

<h2 class="wp-block-heading">10. Sleep in a riad</h2>

<p class="wp-block-paragraph">Staying in a riad is the ultimate Marrakech experience. These traditional houses, with their patio, fountain and handcrafted decoration, offer an intimate and authentic setting. At <a href="/en/">Riad Bilkis</a> we welcome you in a jewel box of zellige and tadelakt in the heart of the medina.</p>

<hr class="wp-block-separator has-alpha-channel-opacity"/>

<p class="wp-block-paragraph"><strong>Planning a trip to Marrakech?</strong> <a href="/en/booking/">Book your stay at Riad Bilkis</a> and enjoy our ideal location to discover all these wonders.</p>
HTML;

    $a['blog-10-choses']['body']['es'] = <<<'HTML'
<p class="wp-block-paragraph">Marrakech, la ciudad ocre, está llena de tesoros por descubrir. Si le gusta la cultura, la gastronomía o la aventura, esta es nuestra selección de las diez experiencias imprescindibles para disfrutar de su estancia en Marrakech.</p>

<h2 class="wp-block-heading">1. Perderse en los souks</h2>

<p class="wp-block-paragraph">El laberinto de los souks de Marrakech es una experiencia sensorial única. Especias de colores, lámparas de hierro forjado, alfombras berberiscas, marroquinería… Déjese llevar por el ambiente y no dude en negociar: ¡es una tradición!</p>

<h2 class="wp-block-heading">2. Admirar la plaza Jemaa el-Fna</h2>

<p class="wp-block-paragraph">Declarada patrimonio inmaterial por la Unesco, la plaza Jemaa el-Fna es el corazón de Marrakech. Al anochecer se convierte en un inmenso restaurante al aire libre, con músicos, cuentacuentos y acróbatas.</p>

<h2 class="wp-block-heading">3. Visitar el jardín Majorelle</h2>

<p class="wp-block-paragraph">Este jardín creado por Jacques Majorelle y restaurado por Yves Saint Laurent es un oasis de calma, con sus plantas exóticas, su azul icónico y su museo berberisco.</p>

<h2 class="wp-block-heading">4. Descubrir el palacio de la Bahía</h2>

<p class="wp-block-paragraph">Obra maestra de la arquitectura marroquí del siglo XIX, el palacio de la Bahía deslumbra por sus zellijes, sus estucos y sus techos de madera de cedro pintados a mano.</p>

<h2 class="wp-block-heading">5. Disfrutar de un hammam tradicional</h2>

<p class="wp-block-paragraph">El hammam es un ritual imprescindible en Marruecos. Exfoliación con jabón negro, mascarilla de gassoul, masaje con aceite de argán… Una experiencia de bienestar profundo. En el Riad Bilkis ofrecemos un hammam privado para nuestros huéspedes.</p>

<h2 class="wp-block-heading">6. Saborear un tayín auténtico</h2>

<p class="wp-block-paragraph">El tayín es el plato emblemático de Marruecos. De pollo con aceitunas y limón confitado, de cordero con ciruelas, de verduras… Cada versión es una delicia. ¿La mejor forma de probarlo? En casa de una familia o en un riad.</p>

<h2 class="wp-block-heading">7. Explorar el valle del Ourika</h2>

<p class="wp-block-paragraph">A solo 45 minutos de Marrakech, el valle del Ourika ofrece paisajes espectaculares: cascadas, pueblos berberiscos colgados de la montaña y comida en casa de una familia local.</p>

<h2 class="wp-block-heading">8. Tomar un té con menta en una terraza</h2>

<p class="wp-block-paragraph">No hay nada más marroquí que tomar un té con menta contemplando la medina desde una terraza. En el Riad Bilkis, nuestra terraza ofrece una vista panorámica de los tejados y de las montañas del Atlas.</p>

<h2 class="wp-block-heading">9. Pasear en dromedario por la Palmeraie</h2>

<p class="wp-block-paragraph">La Palmeraie de Marrakech, con sus miles de palmeras, es el marco ideal para un paseo en dromedario al atardecer. ¡Una experiencia mágica y muy fotogénica!</p>

<h2 class="wp-block-heading">10. Dormir en un riad</h2>

<p class="wp-block-paragraph">Alojarse en un riad es la experiencia definitiva en Marrakech. Estas casas tradicionales con patio, fuente y decoración artesanal ofrecen un marco íntimo y auténtico. En el <a href="/es/">Riad Bilkis</a> le recibimos en un joyero de zellijes y tadelakt en el corazón de la medina.</p>

<hr class="wp-block-separator has-alpha-channel-opacity"/>

<p class="wp-block-paragraph"><strong>¿Está planificando un viaje a Marrakech?</strong> <a href="/es/reserva/">Reserve su estancia en el Riad Bilkis</a> y aproveche nuestra ubicación ideal para descubrir todas estas maravillas.</p>
HTML;

    $a['blog-choisir-riad'] = array(
        'fr'   => '/comment-choisir-riad-marrakech/',
        'en'   => '/en/blog/how-to-choose-a-riad-in-marrakech/',
        'es'   => '/es/blog/como-elegir-un-riad-en-marrakech/',
        'post' => 'comment-choisir-riad-marrakech',
        'title' => array(
            'en' => 'Complete guide: how to choose your riad in Marrakech',
            'es' => 'Guía completa: cómo elegir su riad en Marrakech',
        ),
        'seo' => array(
            'en' => array('How to choose your riad in Marrakech | Riad Bilkis blog',
                'Riad or hotel, which district, which criteria and what budget: our complete guide to choosing the right riad in Marrakech before you book.'),
            'es' => array('Cómo elegir su riad en Marrakech | Blog del Riad Bilkis',
                'Riad u hotel, qué barrio, qué criterios y qué presupuesto: nuestra guía completa para elegir el riad adecuado en Marrakech antes de reservar.'),
        ),
    );

    $a['blog-choisir-riad']['body']['en'] = <<<'HTML'
<p class="wp-block-paragraph">Marrakech has hundreds of riads turned into guest houses. How do you make the right choice? Here is our complete guide to finding the perfect riad for your plans and your budget.</p>

<h2 class="wp-block-heading">Riad or hotel: what is the difference?</h2>

<p class="wp-block-paragraph">A riad is a traditional Moroccan house built around a central patio. Unlike conventional hotels, riads offer an intimate and authentic experience, usually with only four to ten rooms. The welcome is personal, the decoration is unique and breakfast is often homemade.</p>

<h2 class="wp-block-heading">Which district should you choose?</h2>

<p class="wp-block-paragraph"><strong>The medina (historic centre):</strong> the ideal choice for an authentic experience. You will be within walking distance of the souks, Jemaa el-Fna and the main monuments. Riad Bilkis is in the heart of the medina, a five-minute walk from the main square.</p>

<p class="wp-block-paragraph"><strong>Guéliz (the new town):</strong> more modern, with contemporary shops and restaurants. Less authentic but more practical if you prefer a classic city setting.</p>

<h2 class="wp-block-heading">Which criteria should you check?</h2>

<ul class="wp-block-list"><li><strong>Pool or plunge pool:</strong> essential in summer when it goes above 40°C</li><li><strong>Terrace:</strong> to enjoy the sunsets over the medina</li><li><strong>Air conditioning:</strong> check that it is in the bedrooms themselves</li><li><strong>Breakfast included:</strong> breakfast in riads is often exceptional</li><li><strong>Airport transfer:</strong> very handy so you do not get lost in the medina</li><li><strong>Guest reviews:</strong> check TripAdvisor and Google Reviews</li></ul>

<h2 class="wp-block-heading">What budget should you plan for?</h2>

<p class="wp-block-paragraph">Rates vary a great deal depending on the standard and the season:</p>

<ul class="wp-block-list"><li><strong>Budget:</strong> €30-60 per night — simple but charming riads</li><li><strong>Mid-range:</strong> €60-120 per night — excellent value for money, beautiful decoration</li><li><strong>Luxury:</strong> €120-300 per night — spacious suites, spa, high-end service</li></ul>

<p class="wp-block-paragraph">At <a href="/en/rooms/">Riad Bilkis</a>, our rooms start at €80 per night with breakfast included, which is excellent value in the mid-range and charm category.</p>

<hr class="wp-block-separator has-alpha-channel-opacity"/>

<p class="wp-block-paragraph"><strong>Would you like to discover our riad?</strong> Have a look at our <a href="/en/rooms/">rooms and suites</a> or <a href="/en/booking/">book direct</a> at the best guaranteed rate.</p>
HTML;

    $a['blog-choisir-riad']['body']['es'] = <<<'HTML'
<p class="wp-block-paragraph">Marrakech cuenta con cientos de riads convertidos en casas de huéspedes. ¿Cómo elegir bien? Esta es nuestra guía completa para encontrar el riad perfecto según sus gustos y su presupuesto.</p>

<h2 class="wp-block-heading">Riad u hotel: ¿cuál es la diferencia?</h2>

<p class="wp-block-paragraph">Un riad es una casa tradicional marroquí organizada alrededor de un patio central. A diferencia de los hoteles clásicos, los riads ofrecen una experiencia íntima y auténtica, en general con solo cuatro a diez habitaciones. La acogida es personalizada, la decoración es única y el desayuno suele ser casero.</p>

<h2 class="wp-block-heading">¿Qué barrio elegir?</h2>

<p class="wp-block-paragraph"><strong>La medina (centro histórico):</strong> es la mejor opción para vivir la experiencia auténtica. Estará a pie de los souks, de Jemaa el-Fna y de los principales monumentos. El Riad Bilkis se encuentra en el corazón de la medina, a cinco minutos a pie de la plaza principal.</p>

<p class="wp-block-paragraph"><strong>Guéliz (ciudad nueva):</strong> más moderno, con tiendas y restaurantes contemporáneos. Menos auténtico, pero más práctico si prefiere un entorno urbano clásico.</p>

<h2 class="wp-block-heading">¿Qué criterios comprobar?</h2>

<ul class="wp-block-list"><li><strong>Piscina o alberca:</strong> imprescindible en verano, cuando se superan los 40 °C</li><li><strong>Terraza:</strong> para disfrutar de las puestas de sol sobre la medina</li><li><strong>Aire acondicionado:</strong> compruebe que está en las habitaciones</li><li><strong>Desayuno incluido:</strong> los desayunos de los riads suelen ser excepcionales</li><li><strong>Traslado del aeropuerto:</strong> muy práctico para no perderse en la medina</li><li><strong>Opiniones de los clientes:</strong> consulte TripAdvisor y Google Reviews</li></ul>

<h2 class="wp-block-heading">¿Qué presupuesto prever?</h2>

<p class="wp-block-paragraph">Las tarifas varían mucho según la categoría y la temporada:</p>

<ul class="wp-block-list"><li><strong>Económico:</strong> 30-60 € por noche — riads sencillos pero con encanto</li><li><strong>Gama media:</strong> 60-120 € por noche — excelente relación calidad-precio, bonita decoración</li><li><strong>Lujo:</strong> 120-300 € por noche — suites amplias, spa, servicio de alta gama</li></ul>

<p class="wp-block-paragraph">En el <a href="/es/habitaciones/">Riad Bilkis</a>, nuestras habitaciones parten de 80 € por noche con desayuno incluido, una excelente relación calidad-precio en la categoría de gama media y con encanto.</p>

<hr class="wp-block-separator has-alpha-channel-opacity"/>

<p class="wp-block-paragraph"><strong>¿Quiere descubrir nuestro riad?</strong> Consulte nuestras <a href="/es/habitaciones/">habitaciones y suites</a> o <a href="/es/reserva/">reserve directamente</a> con la mejor tarifa garantizada.</p>
HTML;

    $a['blog-tajine'] = array(
        'fr'   => '/recette-tajine-poulet-olives-citron-confit/',
        'en'   => '/en/blog/chicken-tagine-with-olives-and-preserved-lemon-recipe/',
        'es'   => '/es/blog/receta-tayin-de-pollo-con-aceitunas-y-limon-confitado/',
        'post' => 'recette-tajine-poulet-olives-citron-confit',
        'title' => array(
            'en' => 'Chicken tagine with olives and preserved lemon recipe',
            'es' => 'Receta del tayín de pollo con aceitunas y limón confitado',
        ),
        'seo' => array(
            'en' => array('Chicken tagine with olives and preserved lemon | Riad Bilkis blog',
                'The authentic recipe of our cook at Riad Bilkis: ingredients, marinade, cooking and the secrets of a tender chicken tagine with olives and preserved lemon.'),
            'es' => array('Tayín de pollo con aceitunas y limón confitado | Blog del Riad Bilkis',
                'La receta auténtica de nuestra cocinera del Riad Bilkis: ingredientes, adobo, cocción y los secretos de un tayín de pollo tierno con aceitunas y limón confitado.'),
        ),
    );

    $a['blog-tajine']['body']['en'] = <<<'HTML'
<p class="wp-block-paragraph">Chicken tagine with olives and preserved lemon is the most emblematic dish of Moroccan cooking. Here is the authentic recipe of our cook at Riad Bilkis, step by step.</p>

<h2 class="wp-block-heading">Ingredients (serves 4)</h2>

<ul class="wp-block-list"><li>1 free-range chicken, cut into pieces</li><li>200 g pitted green olives</li><li>2 preserved lemons</li><li>2 onions, finely sliced</li><li>3 cloves of garlic</li><li>1 bunch of fresh coriander and parsley</li><li>1 teaspoon of ground ginger</li><li>1 teaspoon of turmeric</li><li>1/2 teaspoon of pepper</li><li>1 pinch of saffron</li><li>4 tablespoons of olive oil</li><li>Salt to taste</li></ul>

<h2 class="wp-block-heading">Method</h2>

<h3 class="wp-block-heading">Step 1: the marinade</h3>

<p class="wp-block-paragraph">In a large dish, mix the chicken with the sliced onion, the crushed garlic, the ginger, the turmeric, the pepper, the saffron and the olive oil. Leave to marinate for at least one hour (ideally overnight in the fridge).</p>

<h3 class="wp-block-heading">Step 2: the cooking</h3>

<p class="wp-block-paragraph">Place the marinated chicken in your tagine (or a casserole dish). Add a glass of water, cover and cook over a low heat for 45 minutes. Turn the pieces of chicken halfway through.</p>

<h3 class="wp-block-heading">Step 3: the olives and preserved lemons</h3>

<p class="wp-block-paragraph">Rinse the olives and the preserved lemons. Cut the lemons into quarters. Add them to the tagine 15 minutes before the end of cooking. Sprinkle with chopped fresh coriander and parsley.</p>

<h3 class="wp-block-heading">Step 4: serving</h3>

<p class="wp-block-paragraph">Serve straight from the tagine, with Moroccan bread (khobz) to mop up the sauce. Arrange the olives and preserved lemons neatly on top.</p>

<h2 class="wp-block-heading">Our cook's secrets</h2>

<ul class="wp-block-list"><li>Use an earthenware tagine for slow, fragrant cooking</li><li>Saffron makes all the difference — use real Moroccan saffron</li><li>Homemade preserved lemons are far better than shop-bought ones</li><li>Very gentle cooking is the secret of a tender tagine</li></ul>

<hr class="wp-block-separator has-alpha-channel-opacity"/>

<p class="wp-block-paragraph"><strong>Would you like to taste this tagine here?</strong> <a href="/en/moroccan-dinner">Book a dinner at Riad Bilkis</a> or join our <a href="/en/cooking-class">Moroccan cooking class</a> to learn how to prepare it yourself!</p>
HTML;

    $a['blog-tajine']['body']['es'] = <<<'HTML'
<p class="wp-block-paragraph">El tayín de pollo con aceitunas y limón confitado es el plato más emblemático de la cocina marroquí. Esta es la receta auténtica de nuestra cocinera del Riad Bilkis, paso a paso.</p>

<h2 class="wp-block-heading">Ingredientes (para 4 personas)</h2>

<ul class="wp-block-list"><li>1 pollo de corral cortado en trozos</li><li>200 g de aceitunas verdes sin hueso</li><li>2 limones confitados</li><li>2 cebollas en juliana</li><li>3 dientes de ajo</li><li>1 manojo de cilantro y perejil frescos</li><li>1 cucharadita de jengibre molido</li><li>1 cucharadita de cúrcuma</li><li>1/2 cucharadita de pimienta</li><li>1 pizca de azafrán</li><li>4 cucharadas de aceite de oliva</li><li>Sal al gusto</li></ul>

<h2 class="wp-block-heading">Preparación</h2>

<h3 class="wp-block-heading">Paso 1: el adobo</h3>

<p class="wp-block-paragraph">En una fuente grande, mezcle el pollo con la cebolla en juliana, el ajo machacado, el jengibre, la cúrcuma, la pimienta, el azafrán y el aceite de oliva. Deje macerar al menos una hora (idealmente toda la noche en la nevera).</p>

<h3 class="wp-block-heading">Paso 2: la cocción</h3>

<p class="wp-block-paragraph">Coloque el pollo adobado en el tayín (o en una cazuela). Añada un vaso de agua, tape y cueza a fuego lento durante 45 minutos. Dé la vuelta a los trozos de pollo a media cocción.</p>

<h3 class="wp-block-heading">Paso 3: las aceitunas y los limones confitados</h3>

<p class="wp-block-paragraph">Enjuague las aceitunas y los limones confitados. Corte los limones en cuartos. Añádalos al tayín 15 minutos antes del final de la cocción. Espolvoree con cilantro y perejil frescos picados.</p>

<h3 class="wp-block-heading">Paso 4: el servicio</h3>

<p class="wp-block-paragraph">Sirva directamente en el tayín, con pan marroquí (jobz) para mojar en la salsa. Disponga las aceitunas y los limones confitados por encima.</p>

<h2 class="wp-block-heading">Los secretos de nuestra cocinera</h2>

<ul class="wp-block-list"><li>Utilice un tayín de barro para una cocción lenta y aromática</li><li>El azafrán marca la diferencia: use azafrán marroquí auténtico</li><li>Los limones confitados caseros son mucho mejores que los comprados</li><li>La cocción a fuego muy suave es el secreto de un tayín tierno</li></ul>

<hr class="wp-block-separator has-alpha-channel-opacity"/>

<p class="wp-block-paragraph"><strong>¿Quiere probar este tayín aquí?</strong> <a href="/es/cena-marroqui">Reserve una cena en el Riad Bilkis</a> o participe en nuestro <a href="/es/clase-de-cocina">taller de cocina marroquí</a> para aprender a prepararlo usted mismo.</p>
HTML;

    $articles = $a;
    return $articles;
}

// URL des articles par langue, fusionnées dans la table des pages : le
// sélecteur de langue et la traduction des liens internes les utilisent.
function riad_bilkis_blog_i18n_pages() {
    $pages = array();
    foreach (riad_bilkis_blog_articles() as $key => $art) {
        $pages[$key] = array('fr' => $art['fr'], 'en' => $art['en'], 'es' => $art['es']);
    }
    return $pages;
}

// Article demandé par l'URL courante : clé, langue et slug WordPress.
function riad_bilkis_blog_route() {
    static $route = null;
    if ($route !== null) return $route;
    $route = false;
    $path  = function_exists('riad_bilkis_i18n_path') ? riad_bilkis_i18n_path()
           : '/' . trim(strtok(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', '?'), '/');
    foreach (riad_bilkis_blog_articles() as $key => $art) {
        foreach (array('fr', 'en', 'es') as $lang) {
            if ('/' . trim($art[$lang], '/') === $path) {
                $route = array('key' => $key, 'lang' => $lang, 'post' => $art['post']);
                return $route;
            }
        }
    }
    return $route;
}

// L'article français fournit le contenu des URL /en/blog/… et /es/blog/…
add_action('parse_request', function ($wp) {
    $route = riad_bilkis_blog_route();
    if (!$route || $route['lang'] === 'fr') return;
    $wp->query_vars = array('name' => $route['post'], 'post_type' => 'post', 'lang' => 'fr');
    $wp->matched_rule  = '';
    $wp->matched_query = '';
    remove_action('template_redirect', 'redirect_canonical');
    add_filter('pll_check_canonical_url', '__return_false', 99);
    add_filter('redirect_canonical', '__return_false', 99);
}, 1);

// Titre et corps traduits (après la traduction générique des pages).
add_filter('the_title', function ($title) {
    $route = riad_bilkis_blog_route();
    if (!$route || $route['lang'] === 'fr' || !is_singular()) return $title;
    $art = riad_bilkis_blog_articles()[$route['key']];
    return isset($art['title'][$route['lang']]) ? $art['title'][$route['lang']] : $title;
}, 1000);

add_filter('the_content', function ($content) {
    $route = riad_bilkis_blog_route();
    if (!$route || $route['lang'] === 'fr' || !is_singular() || !in_the_loop()) return $content;
    $art = riad_bilkis_blog_articles()[$route['key']];
    return isset($art['body'][$route['lang']]) ? $art['body'][$route['lang']] : $content;
}, 1000);

// Titre de l'onglet et description traduits.
add_filter('pre_get_document_title', function ($title) {
    $seo = riad_bilkis_blog_seo();
    return $seo ? $seo['title'] : $title;
}, 1000);

function riad_bilkis_blog_seo() {
    $route = riad_bilkis_blog_route();
    if (!$route || $route['lang'] === 'fr') return null;
    $art = riad_bilkis_blog_articles()[$route['key']];
    if (empty($art['seo'][$route['lang']])) return null;
    return array('title' => $art['seo'][$route['lang']][0], 'description' => $art['seo'][$route['lang']][1]);
}

// ── Articles traduits dans wp-sitemap.xml ────────────────────────────────────
add_action('init', function () {
    if (!class_exists('WP_Sitemaps_Provider')) return;

    class Riad_Bilkis_Blog_Sitemap_Provider extends WP_Sitemaps_Provider {
        public function __construct() {
            $this->name = 'riadbilkisblog';
            $this->object_type = 'post';
        }
        public function get_url_list($page_num, $object_subtype = '') {
            $urls = array();
            foreach (riad_bilkis_blog_articles() as $art) {
                foreach (array('en', 'es') as $lang) {
                    $urls[] = array('loc' => home_url($art[$lang]));
                }
            }
            return $urls;
        }
        public function get_max_num_pages($object_subtype = '') {
            return 1;
        }
    }

    wp_register_sitemap_provider('riadbilkisblog', new Riad_Bilkis_Blog_Sitemap_Provider());
});

// Canonique, hreflang, description et Open Graph des articles (les trois langues).
add_action('wp_head', function () {
    $route = riad_bilkis_blog_route();
    if (!$route) return;
    $art  = riad_bilkis_blog_articles()[$route['key']];
    $lang = $route['lang'];
    remove_action('wp_head', 'rel_canonical');
    echo '<link rel="canonical" href="' . esc_url(home_url($art[$lang])) . '" />' . "\n";
    foreach (array('fr', 'en', 'es') as $code) {
        echo '<link rel="alternate" hreflang="' . esc_attr($code) . '" href="'
            . esc_url(home_url($art[$code])) . '" />' . "\n";
    }
    $seo = riad_bilkis_blog_seo();
    if (!$seo) return;
    $locales = array('en' => 'en_GB', 'es' => 'es_ES');
    echo '<meta name="description" content="' . esc_attr($seo['description']) . '" />' . "\n";
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($seo['title']) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($seo['description']) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url(home_url($art[$lang])) . '" />' . "\n";
    echo '<meta property="og:site_name" content="Riad Bilkis Marrakech" />' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($locales[$lang]) . '" />' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($seo['title']) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($seo['description']) . '" />' . "\n";
    echo '<script type="application/ld+json">' . wp_json_encode(array(
        '@context'         => 'https://schema.org',
        '@type'            => 'BlogPosting',
        'headline'         => $art['title'][$lang],
        'description'      => $seo['description'],
        'inLanguage'       => $lang,
        'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => home_url($art[$lang])),
        'publisher'        => array('@type' => 'Organization', 'name' => 'Riad Bilkis Marrakech', 'url' => 'https://riadbilkis.com'),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}, 1);
