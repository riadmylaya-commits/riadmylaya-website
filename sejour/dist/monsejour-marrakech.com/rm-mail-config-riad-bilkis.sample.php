<?php
/*
 * Copier en rm-mail-config-riad-bilkis.php (non versionné) à la racine du hub.
 * Les demandes et accusés de réception du Riad Bilkis partent alors de
 * riadbilkis@gmail.com (Gmail SMTP) au lieu du compte commun contact@riadmylaya.com.
 * Le mot de passe d'application Gmail est relu dans /home/riaductd/rb-mail-config.php
 * (le même que pour rb-request.php et la fiche de police) : il n'est stocké qu'une fois.
 */
$rb = include '/home/riaductd/rb-mail-config.php';
if (!is_array($rb) || empty($rb['smtp_password'])) {
    return array();
}
return array(
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_user' => $rb['smtp_user'],
    'smtp_pass' => $rb['smtp_password'],
    'from_email' => $rb['from_email'],
);
