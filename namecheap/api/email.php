<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/pdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendRegistrationEmail(array $reg): bool {
    if (empty(GMAIL_APP_PASSWORD)) return false;

    try {
        $pdfContent = generateRegistrationPDF($reg);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_EMAIL;
        $mail->Password = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(GMAIL_EMAIL, 'Riad Mylaya');
        $mail->addAddress(NOTIFICATION_EMAIL);

        $firstName = $reg['firstName'] ?? '';
        $lastName = $reg['lastName'] ?? '';
        $room = $reg['room'] ?? '';
        $arrival = $reg['arrivalDate'] ?? '';
        $departure = $reg['departureDate'] ?? '';

        $mail->Subject = "Nouvelle fiche - $firstName $lastName | Chambre $room | $arrival - $departure";

        $htmlBody = '<html><body style="font-family:Arial,sans-serif;color:#3b1a10;background:#faf7f2;padding:20px;">
<div style="max-width:600px;margin:0 auto;background:white;border-radius:12px;padding:24px;border:1px solid #ebe0cc;">
<h2 style="color:#5a2d1e;text-align:center;">Riad Mylaya &mdash; Nouvelle Fiche Client</h2><hr style="border-color:#ebe0cc;">
<table style="width:100%;border-collapse:collapse;">
<tr><td style="padding:6px;font-weight:bold;width:40%;">Chambre</td><td style="padding:6px;">' . htmlspecialchars($room) . '</td></tr>
<tr style="background:#faf7f2;"><td style="padding:6px;font-weight:bold;">Nom</td><td style="padding:6px;">' . htmlspecialchars($lastName) . '</td></tr>
<tr><td style="padding:6px;font-weight:bold;">Pr&eacute;nom</td><td style="padding:6px;">' . htmlspecialchars($firstName) . '</td></tr>
<tr style="background:#faf7f2;"><td style="padding:6px;font-weight:bold;">Arriv&eacute;e</td><td style="padding:6px;">' . htmlspecialchars($arrival) . '</td></tr>
<tr><td style="padding:6px;font-weight:bold;">D&eacute;part</td><td style="padding:6px;">' . htmlspecialchars($departure) . '</td></tr>
<tr style="background:#faf7f2;"><td style="padding:6px;font-weight:bold;">Nationalit&eacute;</td><td style="padding:6px;">' . htmlspecialchars($reg['nationality'] ?? '') . '</td></tr>
<tr><td style="padding:6px;font-weight:bold;">N&deg; Passeport</td><td style="padding:6px;">' . htmlspecialchars($reg['passportNumber'] ?? '') . '</td></tr>
</table><hr style="border-color:#ebe0cc;">
<p style="text-align:center;color:#6b3a2a;font-size:12px;">La fiche compl&egrave;te en PDF est jointe. Photo passeport et signature incluses.</p>
</div></body></html>';

        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = "Nouvelle fiche client - $firstName $lastName";

        // Attach PDF
        $mail->addStringAttachment($pdfContent, "fiche_{$lastName}_{$firstName}_{$arrival}.pdf", 'base64', 'application/pdf');

        // Attach passport photo
        $photo = $reg['passportPhoto'] ?? '';
        if ($photo && strpos($photo, 'base64') !== false) {
            $ext = (strpos($photo, 'image/jpeg') !== false) ? 'jpg' : 'png';
            $imgData = decodeBase64Image($photo);
            $mail->addStringAttachment($imgData, "passeport_{$lastName}.{$ext}", 'base64', "image/{$ext}");
        }

        // Attach signature
        $sig = $reg['signature'] ?? '';
        if ($sig && strpos($sig, 'base64') !== false) {
            $sigData = decodeBase64Image($sig);
            $mail->addStringAttachment($sigData, "signature_{$lastName}.png", 'base64', 'image/png');
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: " . $e->getMessage());
        return false;
    }
}

function sendPasswordResetEmail(string $email, string $username, string $resetUrl): bool {
    if (empty(GMAIL_APP_PASSWORD)) return false;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_EMAIL;
        $mail->Password = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(GMAIL_EMAIL, 'Riad Mylaya');
        $mail->addAddress($email);
        $mail->Subject = 'Riad Mylaya - Reinitialisation du mot de passe';

        $html = '<html><body style="font-family:Arial,sans-serif;color:#3b1a10;background:#faf7f2;padding:20px;">
<div style="max-width:500px;margin:0 auto;background:white;border-radius:12px;padding:24px;border:1px solid #ebe0cc;">
<h2 style="color:#5a2d1e;text-align:center;">Riad Mylaya</h2>
<p>Bonjour <strong>' . htmlspecialchars($username) . '</strong>,</p>
<p>Vous avez demand&eacute; la r&eacute;initialisation de votre mot de passe.</p>
<p style="text-align:center;"><a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:#5a2d1e;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;">R&eacute;initialiser mon mot de passe</a></p>
<p style="font-size:12px;color:#888;">Ce lien expire dans 30 minutes.</p>
</div></body></html>';

        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = "Reinitialisation: $resetUrl";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reset email failed: " . $e->getMessage());
        return false;
    }
}
