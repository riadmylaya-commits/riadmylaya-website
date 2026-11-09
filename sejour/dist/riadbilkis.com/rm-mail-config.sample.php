<?php
/*
 * Copier en rm-mail-config.php (non versionné) dans le dossier autonome
 * (ex. riadbilkis.com/sejour/). Aucun mot de passe ici : le compte Gmail de
 * l'établissement est relu dans le fichier hors racine web (/home/<compte>/rb-mail-config.php),
 * le même que pour rb-request.php.
 */
$rb = include dirname(dirname(__DIR__)) . '/rb-mail-config.php';
if (!is_array($rb) || empty($rb['smtp_password'])) {
    return array();
}
return array(
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_user' => $rb['smtp_user'],
    'smtp_pass' => $rb['smtp_password'],
    'from_email' => $rb['from_email'],
    'turnstile_hosts' => 'riadbilkis.com,www.riadbilkis.com',
    'ehlo_host' => 'riadbilkis.com',
    'rate_dir' => dirname(dirname(__DIR__)) . '/rm-rate-riadbilkis',
);
