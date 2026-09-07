<?php
/**
 * Riad Mylaya — réception des formulaires du site.
 *
 * Remplace FormSubmit : reçoit le POST d'un formulaire, construit un e-mail HTML
 * mis en page (compatible Gmail / Outlook / Apple Mail), l'envoie en SMTP
 * authentifié puis redirige le visiteur vers la page de remerciement (_next).
 *
 * Si l'envoi SMTP échoue, le POST est réexpédié tel quel à FormSubmit pour
 * qu'aucune demande client ne puisse être perdue.
 *
 * Le mot de passe vit dans rm-mail-config.php, non versionné.
 */

$CONFIG = array(
    'smtp_host' => 'mail.privateemail.com',
    'smtp_port' => 465,
    'smtp_user' => '',
    'smtp_pass' => '',
    'from_email' => 'contact@riadmylaya.com',
    'from_name' => 'Riad Mylaya — Site web',
    'to_email' => 'contact@riadmylaya.com',
    'to_name' => 'Riad Mylaya',
    'bcc_email' => '',
    'whatsapp_country' => '212',
    'fallback_url' => 'https://formsubmit.co/contact@riadmylaya.com',
    /* seules adresses qu'un formulaire peut mettre en copie via _cc */
    'cc_allow' => array('info@mythicoriental-spa.com'),
);
$local = __DIR__ . '/rm-mail-config.php';
if (is_readable($local)) {
    $override = include $local;
    if (is_array($override)) {
        $CONFIG = array_merge($CONFIG, $override);
    }
}

$T = array(
    'fr' => array(
        'title' => 'Nouvelle demande de réservation',
        'client' => 'Informations client',
        'details' => 'Détails de la réservation',
        'payment' => 'Paiement',
        'total' => 'Montant total',
        'cash' => 'À régler sur place en espèces.',
        'confirm' => 'Demande à confirmer par le riad',
        'call' => 'Appeler',
        'wa' => 'WhatsApp',
        'reply' => 'Répondre par e-mail',
        'received' => 'Reçue le',
        'service' => 'Demande client',
        'foot' => 'Message envoyé automatiquement depuis riadmylaya.com',
        'name' => 'Nom complet de la réservation',
        'nomail' => 'Le client n’a pas laissé d’adresse e-mail.',
        'booked' => 'Service demandé',
        'title_q' => 'Nouvelle question d’un client',
        'message' => 'Message du client',
        'ack_subject' => 'Votre demande a bien été reçue – Riad Mylaya',
        'ack_title' => 'Votre demande a bien été reçue',
        'ack_hello' => 'Bonjour',
        'ack_intro' => 'Merci. Nous avons bien reçu votre demande concernant %s.',
        'ack_intro_q' => 'Merci. Nous avons bien reçu votre message.',
        'ack_recap' => 'Voici le récapitulatif de votre demande :',
        'ack_notice' => 'Votre demande a bien été reçue. Notre équipe va vérifier la disponibilité et vous confirmer la réservation. Cet e-mail confirme la réception de votre demande, mais pas encore la disponibilité définitive du service.',
        'ack_notice_q' => 'Notre équipe vous répondra rapidement.',
        'ack_back' => 'Retour à Préparer mon séjour',
        'ack_url' => 'https://riadmylaya.com/preparer-mon-sejour',
        'ack_sign' => "À très bientôt,\nL'équipe du Riad Mylaya",
        'ack_contact' => 'Tél. / WhatsApp : +212 661 351 989 · contact@riadmylaya.com',
    ),
    'en' => array(
        'title' => 'New booking request',
        'client' => 'Guest details',
        'details' => 'Booking details',
        'payment' => 'Payment',
        'total' => 'Total amount',
        'cash' => 'To be paid on site in cash.',
        'confirm' => 'Request to be confirmed by the riad',
        'call' => 'Call',
        'wa' => 'WhatsApp',
        'reply' => 'Reply by email',
        'received' => 'Received on',
        'service' => 'Guest request',
        'foot' => 'Message sent automatically from riadmylaya.com',
        'name' => 'Full name on the booking',
        'nomail' => 'The guest did not leave an email address.',
        'booked' => 'Requested service',
        'title_q' => 'New guest question',
        'message' => 'Guest message',
        'ack_subject' => 'We have received your request – Riad Mylaya',
        'ack_title' => 'We have received your request',
        'ack_hello' => 'Hello',
        'ack_intro' => 'Thank you. We have received your request regarding %s.',
        'ack_intro_q' => 'Thank you. We have received your message.',
        'ack_recap' => 'Here is a summary of your request:',
        'ack_notice' => 'Your request has been received. Our team will check availability and confirm the booking. This email confirms that we received your request, but not yet the final availability of the service.',
        'ack_notice_q' => 'Our team will get back to you shortly.',
        'ack_back' => 'Back to Prepare your stay',
        'ack_url' => 'https://riadmylaya.com/en/prepare-your-stay',
        'ack_sign' => "See you soon,\nThe Riad Mylaya team",
        'ack_contact' => 'Phone / WhatsApp: +212 661 351 989 · contact@riadmylaya.com',
    ),
    'es' => array(
        'title' => 'Nueva solicitud de reserva',
        'client' => 'Datos del cliente',
        'details' => 'Detalles de la reserva',
        'payment' => 'Pago',
        'total' => 'Importe total',
        'cash' => 'A pagar en el riad en efectivo.',
        'confirm' => 'Solicitud pendiente de confirmación por el riad',
        'call' => 'Llamar',
        'wa' => 'WhatsApp',
        'reply' => 'Responder por correo',
        'received' => 'Recibida el',
        'service' => 'Solicitud de cliente',
        'foot' => 'Mensaje enviado automáticamente desde riadmylaya.com',
        'name' => 'Nombre completo de la reserva',
        'nomail' => 'El cliente no ha dejado dirección de correo.',
        'booked' => 'Servicio solicitado',
        'title_q' => 'Nueva consulta de un cliente',
        'message' => 'Mensaje del cliente',
        'ack_subject' => 'Su solicitud ha sido recibida – Riad Mylaya',
        'ack_title' => 'Su solicitud ha sido recibida',
        'ack_hello' => 'Hola',
        'ack_intro' => 'Gracias. Hemos recibido su solicitud relativa a %s.',
        'ack_intro_q' => 'Gracias. Hemos recibido su mensaje.',
        'ack_recap' => 'Este es el resumen de su solicitud:',
        'ack_notice' => 'Su solicitud ha sido recibida. Nuestro equipo comprobará la disponibilidad y le confirmará la reserva. Este correo confirma la recepción de su solicitud, pero todavía no la disponibilidad definitiva del servicio.',
        'ack_notice_q' => 'Nuestro equipo le responderá en breve.',
        'ack_back' => 'Volver a Preparar mi estancia',
        'ack_url' => 'https://riadmylaya.com/es/preparar-mi-estancia',
        'ack_sign' => "Hasta pronto,\nEl equipo del Riad Mylaya",
        'ack_contact' => 'Teléfono / WhatsApp: +212 661 351 989 · contact@riadmylaya.com',
    ),
);

/* ------------------------------------------------------------------ outils */

function rm_clean($v)
{
    $v = is_array($v) ? implode(', ', $v) : (string) $v;
    $v = str_replace(array("\r\n", "\r"), "\n", $v);
    return trim($v);
}

function rm_h($v)
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function rm_lower($v)
{
    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
}

/** Enlève les accents pour reconnaître un libellé quelle que soit la langue. */
function rm_key($v)
{
    $v = rm_lower($v);
    $map = array(
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'º' => '', '°' => '', '.' => '', '_' => ' ',
    );
    $v = strtr($v, $map);
    return trim(preg_replace('/\s+/', ' ', $v));
}

function rm_is($label, $needles)
{
    $k = rm_key($label);
    foreach ($needles as $n) {
        if (strpos($k, $n) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Transforme un nom de champ technique (name, NombrePersonnes, Total_estime)
 * en intitulé lisible dans l'e-mail.
 */
function rm_label($key)
{
    static $map = array(
        'name' => 'Nom complet', 'nom' => 'Nom complet', 'nombre' => 'Nombre completo',
        'fullname' => 'Nom complet',
        'email' => 'E-mail', 'mail' => 'E-mail', 'correo' => 'E-mail',
        'phone' => 'Téléphone / WhatsApp', 'telephone' => 'Téléphone / WhatsApp',
        'tel' => 'Téléphone / WhatsApp', 'whatsapp' => 'Téléphone / WhatsApp',
        'message' => 'Message', 'question' => 'Message',
        'date' => 'Date', 'chambre' => 'Chambre',
        'nombrepersonnes' => 'Nombre de personnes',
        'creneau' => 'Créneau souhaité', 'creneaualternatif' => 'Créneau alternatif',
        'recapdetaille' => 'Récapitulatif de la demande', 'totalestime' => 'Total estimé',
    );
    $k = rm_key(str_replace(array('_', ' ', '-'), '', $key));
    if (isset($map[$k])) {
        return $map[$k];
    }
    $s = preg_replace('/([a-z\d])([A-Z])/', '$1 $2', str_replace('_', ' ', $key));
    $s = trim(preg_replace('/\s+/', ' ', $s));
    return function_exists('mb_strtoupper')
        ? mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, null, 'UTF-8')
        : ucfirst($s);
}

function rm_phone_digits($phone, $country)
{
    $d = preg_replace('/\D+/', '', $phone);
    if ($d === '') {
        return '';
    }
    if (strpos($d, '00') === 0) {
        return substr($d, 2);
    }
    if (strpos($d, '0') === 0) {
        return $country . substr($d, 1);
    }
    return $d;
}

/* ------------------------------------------------------- lecture du formulaire */

if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    header('Location: /', true, 303);
    exit;
}

$next = isset($_POST['_next']) ? rm_clean($_POST['_next']) : '/merci';
if (!preg_match('#^(https://riadmylaya\.com|https://www\.riadmylaya\.com|/)#', $next)) {
    $next = '/merci';
}

/* piège à robots : on fait comme si tout allait bien, sans rien envoyer */
if (isset($_POST['_honey']) && rm_clean($_POST['_honey']) !== '') {
    header('Location: ' . $next, true, 303);
    exit;
}

$lang = isset($_POST['_lang']) ? strtolower(substr(rm_clean($_POST['_lang']), 0, 2)) : '';
if (!isset($T[$lang])) {
    if (strpos($next, '/en/') !== false) {
        $lang = 'en';
    } elseif (strpos($next, '/es/') !== false) {
        $lang = 'es';
    } else {
        $lang = 'fr';
    }
}
$t = $T[$lang];

$subject = isset($_POST['_subject']) ? rm_clean($_POST['_subject']) : '';
$service = isset($_POST['_service']) ? rm_clean($_POST['_service']) : '';
if ($service === '' && $subject !== '') {
    $service = trim(preg_replace('/\s*[·|\-–]\s*Riad Mylaya\s*$/u', '', $subject));
}
if ($service === '') {
    $service = $t['service'];
}

$fields = array();
foreach ($_POST as $key => $value) {
    if (strpos($key, '_') === 0) {
        continue;
    }
    if (strtolower($key) === 'page') {
        continue;
    }
    $value = rm_clean($value);
    if ($value === '') {
        continue;
    }
    $fields[rm_label($key)] = $value;
}

$name = $ref = $phone = $email = $total = $recap = '';
foreach ($fields as $label => $value) {
    if ($name === '' && rm_is($label, array('nom complet', 'full name', 'nombre completo'))) {
        $name = $value;
    } elseif ($ref === '' && rm_is($label, array('n de reservation', 'booking number', 'n de reserva', 'numero de reservation'))) {
        $ref = $value;
    } elseif ($phone === '' && rm_is($label, array('telephone', 'phone', 'whatsapp', 'tel'))) {
        $phone = $value;
    } elseif ($email === '' && rm_is($label, array('e-mail', 'email', 'mail', 'correo'))) {
        $email = $value;
    } elseif ($total === '' && rm_is($label, array('total'))) {
        $total = $value;
    } elseif ($recap === '' && rm_is($label, array('recapitulatif', 'recap', 'resume', 'resumen', 'summary'))) {
        $recap = $value;
    }
}
if ($email === '' && isset($_POST['_replyto'])) {
    $email = rm_clean($_POST['_replyto']);
}

/* lignes de détail : d'abord le récapitulatif du moteur, puis les champs restants */
$rows = array();
$notes = array();
$isQuestion = false;
$seen = array();
foreach (array($name, $ref, $phone, $email, $total, $recap) as $v) {
    if ($v !== '') {
        $seen[$v] = true;
    }
}

if ($recap !== '') {
    foreach (explode("\n", $recap) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^(.{2,60}?)\s*:\s*(.+)$/u', $line, $m)) {
            $label = trim($m[1]);
            $value = trim($m[2]);
            if (rm_is($label, array('nom complet', 'full name', 'nombre completo', 'n de reservation',
                'booking number', 'n de reserva', 'numero de reservation', 'telephone', 'telefono', 'phone',
                'whatsapp', 'e-mail', 'email', 'correo'))) {
                continue;
            }
            if (rm_is($label, array('total'))) {
                if ($total === '') {
                    $total = $value;
                }
                continue;
            }
            $rows[] = array($label, $value);
        } elseif (!preg_match('/^(bonjour|hello|hola)/i', $line)
            && !preg_match('/confirmer par le riad|confirmed by the riad|confirmaci[oó]n por el riad/iu', $line)) {
            $notes[] = $line;
        }
    }
}

foreach ($fields as $label => $value) {
    if (isset($seen[$value])) {
        continue;
    }
    if (rm_is($label, array('recapitulatif', 'recap', 'resume', 'resumen', 'summary', 'total'))) {
        continue;
    }
    if (rm_is($label, array('message', 'question', 'demande', 'solicitud', 'mensaje'))) {
        $notes[] = $value;
        $isQuestion = true;
        continue;
    }
    $rows[] = array($label, $value);
}

/* ----------------------------------------------------------- e-mail HTML */

$ink = '#232323';
$terra = '#c0754a';
$terraD = '#8a6a3b';
$cream = '#fbf7f0';
$line = '#e7ddcd';
$muted = '#6c6257';

$waDigits = rm_phone_digits($phone, $CONFIG['whatsapp_country']);
$received = date('d/m/Y H:i');

$rowsHtml = '';
foreach ($rows as $r) {
    $rowsHtml .= '<tr>'
        . '<td style="padding:9px 0;border-bottom:1px solid ' . $line . ';font:400 13px/1.45 Arial,Helvetica,sans-serif;color:' . $muted . ';width:46%;vertical-align:top;">' . rm_h($r[0]) . '</td>'
        . '<td style="padding:9px 0;border-bottom:1px solid ' . $line . ';font:700 14px/1.45 Arial,Helvetica,sans-serif;color:' . $ink . ';vertical-align:top;">' . nl2br(rm_h($r[1])) . '</td>'
        . '</tr>';
}
$notesHtml = '';
foreach ($notes as $n) {
    $notesHtml .= '<p style="margin:12px 0 0;font:400 14px/1.6 Arial,Helvetica,sans-serif;color:' . $ink . ';">' . nl2br(rm_h($n)) . '</p>';
}

function rm_btn($href, $label, $bg, $fg, $border)
{
    return '<td style="padding:0 6px 8px 0;">'
        . '<a href="' . rm_h($href) . '" style="display:inline-block;padding:11px 18px;border-radius:8px;'
        . 'background:' . $bg . ';color:' . $fg . ';border:1px solid ' . $border . ';text-decoration:none;'
        . 'font:700 14px/1 Arial,Helvetica,sans-serif;">' . rm_h($label) . '</a></td>';
}

$buttons = '';
if ($phone !== '') {
    $buttons .= rm_btn('tel:' . preg_replace('/[^\d+]/', '', $phone), $t['call'], '#ffffff', $ink, $line);
}
if ($waDigits !== '') {
    $buttons .= rm_btn('https://wa.me/' . $waDigits, $t['wa'], '#25d366', '#ffffff', '#25d366');
}
if ($email !== '') {
    $buttons .= rm_btn('mailto:' . $email . '?subject=' . rawurlencode('Re: ' . $service . ' — Riad Mylaya'), $t['reply'], $terraD, '#ffffff', $terraD);
}

$clientPlain = array();
if ($name !== '') {
    $clientPlain[$t['name']] = $name;
}
if ($ref !== '') {
    $clientPlain[$lang === 'en' ? 'Booking number' : ($lang === 'es' ? 'N.º de reserva' : 'N° de réservation')] = $ref;
}
if ($phone !== '') {
    $clientPlain[$lang === 'en' ? 'Phone / WhatsApp' : ($lang === 'es' ? 'Teléfono / WhatsApp' : 'Téléphone / WhatsApp')] = $phone;
}
if ($email !== '') {
    $clientPlain[$lang === 'en' ? 'Email' : ($lang === 'es' ? 'Correo electrónico' : 'E-mail')] = $email;
}

$clientRows = array();
if ($name !== '') {
    $clientRows[] = array($t['name'], rm_h($name));
}
if ($ref !== '') {
    $clientRows[] = array($lang === 'en' ? 'Booking number' : ($lang === 'es' ? 'N.º de reserva' : 'N° de réservation'), rm_h($ref));
}
if ($phone !== '') {
    $clientRows[] = array($lang === 'en' ? 'Phone / WhatsApp' : ($lang === 'es' ? 'Teléfono / WhatsApp' : 'Téléphone / WhatsApp'),
        '<a href="tel:' . rm_h(preg_replace('/[^\d+]/', '', $phone)) . '" style="color:' . $ink . ';text-decoration:none;">' . rm_h($phone) . '</a>');
}
$clientRows[] = array($lang === 'en' ? 'Email' : ($lang === 'es' ? 'Correo electrónico' : 'E-mail'),
    $email !== ''
        ? '<a href="mailto:' . rm_h($email) . '" style="color:' . $terraD . ';">' . rm_h($email) . '</a>'
        : '<span style="color:' . $muted . ';font-weight:400;">' . rm_h($t['nomail']) . '</span>');

$clientHtml = '';
foreach ($clientRows as $r) {
    $clientHtml .= '<tr>'
        . '<td style="padding:9px 0;border-bottom:1px solid ' . $line . ';font:400 13px/1.45 Arial,Helvetica,sans-serif;color:' . $muted . ';width:46%;vertical-align:top;">' . rm_h($r[0]) . '</td>'
        . '<td style="padding:9px 0;border-bottom:1px solid ' . $line . ';font:700 14px/1.45 Arial,Helvetica,sans-serif;color:' . $ink . ';vertical-align:top;">' . $r[1] . '</td>'
        . '</tr>';
}

function rm_section($title, $inner, $ink, $line)
{
    return '<tr><td style="padding:22px 24px 0;">'
        . '<p style="margin:0 0 6px;font:700 12px/1.2 Arial,Helvetica,sans-serif;letter-spacing:.10em;'
        . 'text-transform:uppercase;color:#8a6a3b;">' . rm_h($title) . '</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' . $inner . '</table>'
        . '</td></tr>';
}

/* une simple question : pas de réservation à confirmer, pas de montant */
$questionMode = $isQuestion && $total === '' && !$rows;
if ($questionMode) {
    $t['title'] = $t['title_q'];
    $t['details'] = $t['message'];
}
$colon = $lang === 'fr' ? ' : ' : ': ';

$totalBlock = $total !== ''
    ? '<p style="margin:6px 0 0;font:700 30px/1.15 Georgia,\'Times New Roman\',serif;color:' . $ink . ';">' . rm_h($total) . '</p>'
    : '';

$html = '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="utf-8">'
    . '<meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>' . rm_h($t['title']) . '</title></head>'
    . '<body style="margin:0;padding:0;background:#f2ede4;">'
    . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . rm_h($service . ($total !== '' ? ' — ' . $total : '') . ($name !== '' ? ' — ' . $name : '')) . '</div>'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f2ede4;">'
    . '<tr><td align="center" style="padding:18px 10px;">'
    . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid ' . $line . ';">'

    /* en-tête */
    . '<tr><td style="background:' . $terraD . ';padding:20px 24px;">'
    . '<p style="margin:0;font:700 18px/1.3 Georgia,\'Times New Roman\',serif;color:#ffffff;">' . rm_h($t['title']) . '</p>'
    . '<p style="margin:4px 0 0;font:400 13px/1.4 Arial,Helvetica,sans-serif;color:#f3e6d6;">Riad Mylaya · ' . rm_h($t['received']) . ' ' . rm_h($received) . '</p>'
    . '</td></tr>'

    /* service + total */
    . '<tr><td style="padding:22px 24px 0;">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $cream . ';border-left:4px solid ' . $terra . ';border-radius:10px;">'
    . '<tr><td style="padding:16px 18px;">'
    . '<p style="margin:0;font:700 12px/1.2 Arial,Helvetica,sans-serif;letter-spacing:.10em;text-transform:uppercase;color:' . $terraD . ';">' . rm_h($t['booked']) . '</p>'
    . '<p style="margin:6px 0 0;font:700 20px/1.3 Georgia,\'Times New Roman\',serif;color:' . $ink . ';">' . rm_h($service) . '</p>'
    . $totalBlock
    . ($questionMode ? '' : '<p style="margin:12px 0 0;"><span style="display:inline-block;padding:7px 12px;border-radius:99px;background:#fdf0e6;border:1px solid ' . $terra . ';font:700 12px/1.2 Arial,Helvetica,sans-serif;color:#9a4a1c;">' . rm_h($t['confirm']) . '</span></p>')
    . '</td></tr></table></td></tr>'

    /* boutons */
    . ($buttons !== ''
        ? '<tr><td style="padding:18px 24px 0;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>' . $buttons . '</tr></table></td></tr>'
        : '')

    . rm_section($t['client'], $clientHtml, $ink, $line)
    . (($rowsHtml !== '' || $notesHtml !== '')
        ? rm_section($t['details'], $rowsHtml, $ink, $line) . ($notesHtml !== '' ? '<tr><td style="padding:0 24px;">' . $notesHtml . '</td></tr>' : '')
        : '')

    /* paiement */
    . ($total !== ''
        ? '<tr><td style="padding:22px 24px 0;">'
          . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $cream . ';border-radius:10px;">'
          . '<tr><td style="padding:14px 18px;">'
          . '<p style="margin:0;font:700 12px/1.2 Arial,Helvetica,sans-serif;letter-spacing:.10em;text-transform:uppercase;color:' . $terraD . ';">' . rm_h($t['payment']) . '</p>'
          . '<p style="margin:6px 0 0;font:700 18px/1.3 Arial,Helvetica,sans-serif;color:' . $ink . ';">' . rm_h($t['total']) . rm_h($colon) . rm_h($total) . '</p>'
          . '<p style="margin:4px 0 0;font:400 14px/1.5 Arial,Helvetica,sans-serif;color:' . $muted . ';">' . rm_h($t['cash']) . '</p>'
          . '</td></tr></table></td></tr>'
        : '')

    . '<tr><td style="padding:22px 24px 24px;">'
    . '<p style="margin:0;border-top:1px solid ' . $line . ';padding-top:14px;font:400 12px/1.5 Arial,Helvetica,sans-serif;color:' . $muted . ';">'
    . rm_h($t['foot']) . '<br>Riad Mylaya — 163 Derb Bounba, Arset Ihiri, Médina, Marrakech</p>'
    . '</td></tr>'

    . '</table></td></tr></table></body></html>';

/* --------------------------------------------------------- version texte */

$textLines = array($t['title'] . ' — Riad Mylaya', $service);
if ($total !== '') {
    $textLines[] = $t['total'] . $colon . $total . ' (' . $t['cash'] . ')';
}
if (!$questionMode) {
    $textLines[] = $t['confirm'];
}
$textLines[] = '';
$textLines[] = $t['client'];
foreach ($clientPlain as $k => $v) {
    if ($v !== '') {
        $textLines[] = $k . $colon . $v;
    }
}
if ($rows || $notes) {
    $textLines[] = '';
    $textLines[] = $t['details'];
    foreach ($rows as $r) {
        $textLines[] = $r[0] . $colon . $r[1];
    }
    foreach ($notes as $n) {
        $textLines[] = $n;
    }
}
$text = implode("\n", $textLines);

/* ------------------------------------- confirmation envoyée au client */

$ackRows = array();
if ($name !== '') {
    $ackRows[] = array($t['name'], $name);
}
if ($ref !== '') {
    $ackRows[] = array($lang === 'en' ? 'Booking number' : ($lang === 'es' ? 'N.º de reserva' : 'N° de réservation'), $ref);
}
$ackRows[] = array($t['booked'], $service);
foreach ($rows as $r) {
    $ackRows[] = $r;
}

$ackRowsHtml = '';
foreach ($ackRows as $r) {
    $ackRowsHtml .= '<tr>'
        . '<td style="padding:9px 0;border-bottom:1px solid ' . $line . ';font:400 13px/1.45 Arial,Helvetica,sans-serif;color:' . $muted . ';width:46%;vertical-align:top;">' . rm_h($r[0]) . '</td>'
        . '<td style="padding:9px 0;border-bottom:1px solid ' . $line . ';font:700 14px/1.45 Arial,Helvetica,sans-serif;color:' . $ink . ';vertical-align:top;">' . nl2br(rm_h($r[1])) . '</td>'
        . '</tr>';
}

/* les anciens formulaires envoient un sujet du type « Nouvelle demande … » :
   il ne se lit pas dans une phrase adressée au client. */
$ackService = preg_replace(
    array('/^demande\s+d[eu]\s+/iu', '/^demande\s+d[\'’]/iu', '/^solicitud\s+de\s+/iu', '/\s+request$/i'),
    '',
    $service
);
$ackService = function_exists('mb_strtoupper')
    ? mb_strtoupper(mb_substr($ackService, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($ackService, 1, null, 'UTF-8')
    : ucfirst($ackService);
$ackService = $lang === 'fr' ? '« ' . $ackService . ' »' : '“' . $ackService . '”';
$ackIntro = ($questionMode || preg_match('/^(nouvelle|nueva|new)\b/iu', $service))
    ? $t['ack_intro_q']
    : sprintf($t['ack_intro'], $ackService);
$ackNotice = $questionMode ? $t['ack_notice_q'] : $t['ack_notice'];
$ackGreeting = $t['ack_hello'] . ($name !== '' ? ' ' . $name : '');

$ackHtml = '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="utf-8">'
    . '<meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<title>' . rm_h($t['ack_title']) . '</title></head>'
    . '<body style="margin:0;padding:0;background:#f2ede4;">'
    . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . rm_h($ackNotice) . '</div>'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f2ede4;">'
    . '<tr><td align="center" style="padding:18px 10px;">'
    . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid ' . $line . ';">'

    . '<tr><td style="background:' . $terraD . ';padding:20px 24px;">'
    . '<p style="margin:0;font:700 18px/1.3 Georgia,\'Times New Roman\',serif;color:#ffffff;">' . rm_h($t['ack_title']) . '</p>'
    . '<p style="margin:4px 0 0;font:400 13px/1.4 Arial,Helvetica,sans-serif;color:#f3e6d6;">Riad Mylaya · Marrakech</p>'
    . '</td></tr>'

    . '<tr><td style="padding:22px 24px 0;">'
    . '<p style="margin:0;font:400 15px/1.6 Arial,Helvetica,sans-serif;color:' . $ink . ';">' . rm_h($ackGreeting) . ',</p>'
    . '<p style="margin:12px 0 0;font:400 15px/1.6 Arial,Helvetica,sans-serif;color:' . $ink . ';">' . rm_h($ackIntro) . '</p>'
    . ($questionMode ? '' : '<p style="margin:12px 0 0;font:400 15px/1.6 Arial,Helvetica,sans-serif;color:' . $ink . ';">' . rm_h($t['ack_recap']) . '</p>')
    . '</td></tr>'

    . ($questionMode ? '' : rm_section($t['details'], $ackRowsHtml, $ink, $line))
    . ($notesHtml !== '' ? '<tr><td style="padding:0 24px;">' . $notesHtml . '</td></tr>' : '')

    . ($total !== ''
        ? '<tr><td style="padding:22px 24px 0;">'
          . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . $cream . ';border-radius:10px;">'
          . '<tr><td style="padding:14px 18px;">'
          . '<p style="margin:0;font:700 12px/1.2 Arial,Helvetica,sans-serif;letter-spacing:.10em;text-transform:uppercase;color:' . $terraD . ';">' . rm_h($t['payment']) . '</p>'
          . '<p style="margin:6px 0 0;font:700 22px/1.25 Georgia,\'Times New Roman\',serif;color:' . $ink . ';">' . rm_h($t['total']) . rm_h($colon) . rm_h($total) . '</p>'
          . '<p style="margin:4px 0 0;font:400 14px/1.5 Arial,Helvetica,sans-serif;color:' . $muted . ';">' . rm_h($t['cash']) . '</p>'
          . '</td></tr></table></td></tr>'
        : '')

    /* réception ≠ disponibilité confirmée */
    . '<tr><td style="padding:22px 24px 0;">'
    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fdf0e6;border-left:4px solid ' . $terra . ';border-radius:10px;">'
    . '<tr><td style="padding:14px 18px;font:400 14px/1.6 Arial,Helvetica,sans-serif;color:#7a3c16;">' . rm_h($ackNotice) . '</td></tr>'
    . '</table></td></tr>'

    . '<tr><td style="padding:20px 24px 0;">'
    . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>'
    . rm_btn($t['ack_url'], $t['ack_back'], $terraD, '#ffffff', $terraD)
    . '</tr></table></td></tr>'

    . '<tr><td style="padding:20px 24px 24px;">'
    . '<p style="margin:0;font:400 14px/1.6 Arial,Helvetica,sans-serif;color:' . $ink . ';">' . nl2br(rm_h($t['ack_sign'])) . '</p>'
    . '<p style="margin:10px 0 0;border-top:1px solid ' . $line . ';padding-top:14px;font:400 12px/1.5 Arial,Helvetica,sans-serif;color:' . $muted . ';">'
    . rm_h($t['ack_contact']) . '<br>Riad Mylaya — 163 Derb Bounba, Arset Ihiri, Médina, Marrakech</p>'
    . '</td></tr>'

    . '</table></td></tr></table></body></html>';

$ackLines = array($ackGreeting . ',', '', $ackIntro);
if (!$questionMode) {
    $ackLines[] = '';
    $ackLines[] = $t['ack_recap'];
    foreach ($ackRows as $r) {
        $ackLines[] = $r[0] . $colon . $r[1];
    }
}
foreach ($notes as $n) {
    $ackLines[] = $n;
}
if ($total !== '') {
    $ackLines[] = '';
    $ackLines[] = $t['total'] . $colon . $total;
    $ackLines[] = $t['cash'];
}
$ackLines[] = '';
$ackLines[] = $ackNotice;
$ackLines[] = '';
$ackLines[] = $t['ack_back'] . $colon . $t['ack_url'];
$ackLines[] = '';
$ackLines[] = $t['ack_sign'];
$ackLines[] = $t['ack_contact'];
$ackText = implode("\n", $ackLines);

/* ------------------------------------------------------------------ SMTP */

function rm_smtp_send($cfg, $to, $headers, $body, &$err)
{
    $ctx = stream_context_create(array('ssl' => array('verify_peer' => true, 'verify_peer_name' => true)));
    $fp = @stream_socket_client('ssl://' . $cfg['smtp_host'] . ':' . $cfg['smtp_port'], $eno, $estr, 15,
        STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        $err = 'connect: ' . $estr;
        return false;
    }
    stream_set_timeout($fp, 20);

    $read = function () use ($fp) {
        $out = '';
        while (($l = fgets($fp, 1024)) !== false) {
            $out .= $l;
            if (strlen($l) < 4 || $l[3] !== '-') {
                break;
            }
        }
        return $out;
    };
    $cmd = function ($line, $expect) use ($fp, $read, &$err) {
        if ($line !== null) {
            fwrite($fp, $line . "\r\n");
        }
        $res = $read();
        if (strpos($res, (string) $expect) !== 0) {
            $err = trim(($line === null ? 'banner' : preg_replace('/^(AUTH|PASS).*/', '$1 ***', $line)) . ' -> ' . $res);
            return false;
        }
        return true;
    };

    $ok = $cmd(null, 220)
        && $cmd('EHLO riadmylaya.com', 250)
        && $cmd('AUTH LOGIN', 334)
        && $cmd(base64_encode($cfg['smtp_user']), 334)
        && $cmd(base64_encode($cfg['smtp_pass']), 235)
        && $cmd('MAIL FROM:<' . $cfg['from_email'] . '>', 250);
    if ($ok) {
        foreach ($to as $rcpt) {
            if (!$cmd('RCPT TO:<' . $rcpt . '>', 250)) {
                $ok = false;
                break;
            }
        }
    }
    if ($ok && $cmd('DATA', 354)) {
        $data = $headers . "\r\n" . $body;
        $data = preg_replace('/^\./m', '..', str_replace("\n", "\r\n", str_replace("\r\n", "\n", $data)));
        fwrite($fp, $data . "\r\n.\r\n");
        $ok = $cmd(null, 250);
    } else {
        $ok = false;
    }
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);
    return $ok;
}

function rm_mime_header($value)
{
    if (preg_match('/^[\x20-\x7e]*$/', $value)) {
        return $value;
    }
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

$mailSubject = $service;
if ($total !== '') {
    $mailSubject .= ' · ' . $total;
}
if ($name !== '') {
    $mailSubject .= ' · ' . $name;
}

$boundary = 'rmb' . bin2hex(random_bytes(8));
$headers = 'From: ' . rm_mime_header($CONFIG['from_name']) . ' <' . $CONFIG['from_email'] . ">\r\n"
    . 'To: ' . rm_mime_header($CONFIG['to_name']) . ' <' . $CONFIG['to_email'] . ">\r\n"
    . 'Subject: ' . rm_mime_header($mailSubject) . "\r\n"
    . 'Date: ' . date('r') . "\r\n"
    . 'Message-ID: <' . bin2hex(random_bytes(10)) . '@riadmylaya.com>' . "\r\n"
    . 'MIME-Version: 1.0' . "\r\n"
    . 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\r\n";
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $headers .= 'Reply-To: ' . ($name !== '' ? rm_mime_header($name) . ' ' : '') . '<' . $email . ">\r\n";
}

$body = '--' . $boundary . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
    . chunk_split(base64_encode($text)) . "\r\n"
    . '--' . $boundary . "\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
    . chunk_split(base64_encode($html)) . "\r\n"
    . '--' . $boundary . "--\r\n";

/* copie éventuelle vers un partenaire, uniquement si l'adresse est autorisée */
$cc = array();
if (isset($_POST['_cc'])) {
    $allow = isset($CONFIG['cc_allow']) && is_array($CONFIG['cc_allow']) ? $CONFIG['cc_allow'] : array();
    foreach (explode(',', rm_clean($_POST['_cc'])) as $one) {
        $one = trim($one);
        if ($one !== '' && in_array(strtolower($one), array_map('strtolower', $allow), true)) {
            $cc[] = $one;
        }
    }
}
if ($cc) {
    $headers .= 'Cc: ' . implode(', ', $cc) . "\r\n";
}

$recipients = array($CONFIG['to_email']);
foreach ($cc as $one) {
    $recipients[] = $one;
}
if (!empty($CONFIG['bcc_email'])) {
    $recipients[] = $CONFIG['bcc_email'];
}

$err = '';
$sent = false;
if ($CONFIG['smtp_pass'] !== '') {
    $sent = rm_smtp_send($CONFIG, $recipients, $headers, $body, $err);
}

/* confirmation de réception au client (contenu généré ici, pas par le formulaire) */
if ($sent && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $ackBoundary = 'rmack' . bin2hex(random_bytes(12));
    $ackHeaders = 'From: ' . rm_mime_header('Riad Mylaya') . ' <' . $CONFIG['from_email'] . ">\r\n"
        . 'To: ' . ($name !== '' ? rm_mime_header($name) . ' ' : '') . '<' . $email . ">\r\n"
        . 'Subject: ' . rm_mime_header($t['ack_subject']) . "\r\n"
        . 'Date: ' . date('r') . "\r\n"
        . 'Message-ID: <' . bin2hex(random_bytes(10)) . '@riadmylaya.com>' . "\r\n"
        . 'Reply-To: ' . rm_mime_header($CONFIG['to_name']) . ' <' . $CONFIG['to_email'] . ">\r\n"
        . 'Auto-Submitted: auto-replied' . "\r\n"
        . 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: multipart/alternative; boundary="' . $ackBoundary . '"' . "\r\n";
    $ackBody = '--' . $ackBoundary . "\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($ackText)) . "\r\n"
        . '--' . $ackBoundary . "\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($ackHtml)) . "\r\n"
        . '--' . $ackBoundary . "--\r\n";
    $ackErr = '';
    if (!rm_smtp_send($CONFIG, array($email), $ackHeaders, $ackBody, $ackErr)) {
        @error_log('[rm-envoi] client ack failed: ' . $ackErr);
    }
}

if (!$sent) {
    /* filet de sécurité : la demande repart vers FormSubmit, rien n'est perdu */
    @error_log('[rm-envoi] SMTP failed: ' . $err);
    $post = $_POST;
    unset($post['_next'], $post['_template'], $post['_captcha']);
    $post['_captcha'] = 'false';
    $post['_subject'] = $mailSubject;
    $ch = curl_init($CONFIG['fallback_url']);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => array('Referer: https://riadmylaya.com/'),
    ));
    curl_exec($ch);
    curl_close($ch);
}

header('Location: ' . $next, true, 303);
exit;
