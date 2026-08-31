<?php
/**
 * Riad Bilkis - reception des demandes envoyees par les pages sejour
 * (diner marocain et transfert aeroport).
 *
 * Recoit un POST JSON, envoie un e-mail au riad et un accuse de reception
 * au visiteur. La configuration SMTP se trouve hors racine web dans
 * rb-mail-config.php (voir rb-mail-config.sample.php).
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(array('ok' => false, 'error' => 'method_not_allowed'));
    exit;
}

$configPaths = array(
    dirname(__DIR__) . '/rb-mail-config.php',
    __DIR__ . '/rb-mail-config.php',
);
$config = null;
foreach ($configPaths as $path) {
    if (is_readable($path)) {
        $config = require $path;
        break;
    }
}
if (!is_array($config)) {
    $config = array();
}
$config += array(
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'riadbilkis@gmail.com',
    'smtp_password' => '',
    'from_email' => 'riadbilkis@gmail.com',
    'from_name' => 'Riad Bilkis',
    'notify_email' => 'riadbilkis@gmail.com',
);

$mailerPaths = array(
    __DIR__ . '/rb-lib/PHPMailer',
    dirname(__DIR__) . '/police.riadbilkis.com/api/vendor/PHPMailer',
);
$mailerDir = null;
foreach ($mailerPaths as $dir) {
    if (is_readable($dir . '/PHPMailer.php')) {
        $mailerDir = $dir;
        break;
    }
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

function rb_field($data, $key, $max = 400)
{
    $value = isset($data[$key]) ? trim((string) $data[$key]) : '';
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value);
    return mb_substr($value, 0, $max);
}

$type = rb_field($data, 'type', 20);
if (!in_array($type, array('dinner', 'transfer', 'info'), true)) {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'error' => 'invalid_type'));
    exit;
}

$lang = rb_field($data, 'lang', 2);
if (!in_array($lang, array('fr', 'en', 'es'), true)) {
    $lang = 'fr';
}

$name = rb_field($data, 'name', 120);
$email = rb_field($data, 'email', 160);
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(array('ok' => false, 'error' => 'invalid_contact'));
    exit;
}

$labels = array(
    'dinner' => array(
        'phone' => 'Telephone', 'date' => 'Date', 'time' => 'Heure',
        'guests' => 'Personnes', 'menu' => 'Formule', 'message' => 'Message',
    ),
    'transfer' => array(
        'phone' => 'Telephone', 'guests' => 'Personnes', 'transferType' => 'Type de transfert',
        'arrivalDate' => 'Date arrivee', 'arrivalTime' => 'Heure arrivee', 'arrivalFlight' => 'Vol arrivee',
        'departureDate' => 'Date depart', 'departureTime' => 'Heure depart', 'departureFlight' => 'Vol depart',
        'message' => 'Message',
    ),
    'info' => array(
        'phone' => 'Telephone', 'message' => 'Message',
    ),
);

$rows = array('Nom' => $name, 'E-mail' => $email);
foreach ($labels[$type] as $key => $label) {
    $value = rb_field($data, $key, 1000);
    if ($value !== '') {
        $rows[$label] = $value;
    }
}
$rows['Langue'] = strtoupper($lang);
$rows['Page'] = rb_field($data, 'page', 200);

$subjects = array(
    'dinner' => 'Demande de diner - ' . $name,
    'transfer' => 'Demande de transfert aeroport - ' . $name,
    'info' => 'Demande d\'informations - ' . $name,
);

$guestSubjects = array(
    'dinner' => array(
        'fr' => 'Riad Bilkis - votre demande de diner',
        'en' => 'Riad Bilkis - your dinner request',
        'es' => 'Riad Bilkis - su solicitud de cena',
    ),
    'transfer' => array(
        'fr' => 'Riad Bilkis - votre demande de transfert',
        'en' => 'Riad Bilkis - your transfer request',
        'es' => 'Riad Bilkis - su solicitud de traslado',
    ),
    'info' => array(
        'fr' => 'Riad Bilkis - votre demande d\'informations',
        'en' => 'Riad Bilkis - your information request',
        'es' => 'Riad Bilkis - su solicitud de informacion',
    ),
);

$guestBodies = array(
    'dinner' => array(
        'fr' => 'Merci pour votre demande de diner au Riad Bilkis. Nous verifions la disponibilite et vous confirmons rapidement par e-mail. Pour toute precision, ecrivez-nous sur WhatsApp au +212 625 675 494.',
        'en' => 'Thank you for your dinner request at Riad Bilkis. We are checking availability and will confirm by email shortly. For any detail, write to us on WhatsApp at +212 625 675 494.',
        'es' => 'Gracias por su solicitud de cena en el Riad Bilkis. Estamos comprobando la disponibilidad y le confirmaremos por correo electronico. Para cualquier detalle, escribanos por WhatsApp al +212 625 675 494.',
    ),
    'transfer' => array(
        'fr' => 'Merci pour votre demande de transfert aeroport. Nous organisons le chauffeur et vous confirmons par e-mail avec les details du rendez-vous.',
        'en' => 'Thank you for your airport transfer request. We are arranging the driver and will confirm by email with the meeting details.',
        'es' => 'Gracias por su solicitud de traslado al aeropuerto. Estamos organizando el chofer y le confirmaremos por correo electronico con los detalles del encuentro.',
    ),
    'info' => array(
        'fr' => 'Merci pour votre message. Nous vous repondons rapidement par e-mail. Pour une reponse immediate, ecrivez-nous sur WhatsApp au +212 625 675 494.',
        'en' => 'Thank you for your message. We will reply by email shortly. For an immediate answer, write to us on WhatsApp at +212 625 675 494.',
        'es' => 'Gracias por su mensaje. Le responderemos por correo electronico en breve. Para una respuesta inmediata, escribanos por WhatsApp al +212 625 675 494.',
    ),
);

$textLines = array();
$htmlRows = '';
foreach ($rows as $label => $value) {
    if ($value === '') {
        continue;
    }
    $textLines[] = $label . ' : ' . $value;
    $htmlRows .= '<tr><td style="padding:6px 10px;font-weight:bold;width:38%;border-bottom:1px solid #ebe0cc;">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</td><td style="padding:6px 10px;border-bottom:1px solid #ebe0cc;">'
        . nl2br(htmlspecialchars($value, ENT_QUOTES, 'UTF-8')) . '</td></tr>';
}
$textBody = implode("\n", $textLines);
$htmlBody = '<html><body style="font-family:Arial,Helvetica,sans-serif;color:#3D3229;background:#FBF7F2;padding:20px;">'
    . '<div style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #E6D3C4;border-radius:12px;padding:24px;">'
    . '<h2 style="font-family:Georgia,serif;color:#8a5a3c;margin-top:0;">'
    . htmlspecialchars($subjects[$type], ENT_QUOTES, 'UTF-8') . '</h2>'
    . '<table style="width:100%;border-collapse:collapse;font-size:14px;">' . $htmlRows . '</table>'
    . '</div></body></html>';

$sent = false;
$error = 'mailer_unavailable';

if ($mailerDir !== null && $config['smtp_password'] !== '') {
    require_once $mailerDir . '/Exception.php';
    require_once $mailerDir . '/PHPMailer.php';
    require_once $mailerDir . '/SMTP.php';

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $config['smtp_port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($config['notify_email']);
        $mail->addReplyTo($email, $name);
        $mail->Subject = $subjects[$type];
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        $mail->send();
        $sent = true;

        $guest = clone $mail;
        $guest->clearAllRecipients();
        $guest->clearReplyTos();
        $guest->addAddress($email, $name);
        $guest->addReplyTo($config['notify_email'], $config['from_name']);
        $guest->Subject = $guestSubjects[$type][$lang];
        $guest->Body = '<html><body style="font-family:Arial,Helvetica,sans-serif;color:#3D3229;">'
            . '<p>' . htmlspecialchars($guestBodies[$type][$lang], ENT_QUOTES, 'UTF-8') . '</p>'
            . '<table style="border-collapse:collapse;font-size:14px;">' . $htmlRows . '</table>'
            . '<p style="color:#8a7a6c;font-size:13px;">Riad Bilkis &mdash; 117 Derb Jdid, Bab Doukkala, Marrakech</p>'
            . '</body></html>';
        $guest->AltBody = $guestBodies[$type][$lang] . "\n\n" . $textBody;
        $guest->send();
    } catch (Exception $e) {
        $error = 'send_failed';
    }
}

if (!$sent) {
    error_log('[rb-request] ' . $error . ' | ' . str_replace("\n", ' | ', $textBody));
    http_response_code(502);
    echo json_encode(array('ok' => false, 'error' => $error));
    exit;
}

echo json_encode(array('ok' => true));
