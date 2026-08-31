<?php
/**
 * Modele de configuration SMTP pour rb-request.php.
 *
 * A copier sur le serveur en dehors de la racine web, dans
 * /home/riaductd/rb-mail-config.php, puis renseigner le mot de passe
 * d'application Gmail. Ce fichier ne doit jamais contenir de secret dans Git.
 */

return array(
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'riadbilkis@gmail.com',
    'smtp_password' => '',
    'from_email' => 'riadbilkis@gmail.com',
    'from_name' => 'Riad Bilkis',
    'notify_email' => 'riadbilkis@gmail.com',
);
