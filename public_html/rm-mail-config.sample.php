<?php
/**
 * Copie ce fichier en rm-mail-config.php sur l'hébergement et renseigne le mot de passe.
 * rm-mail-config.php ne doit JAMAIS être versionné : il contient un mot de passe.
 */
return array(
    'smtp_host' => 'mail.privateemail.com',
    'smtp_port' => 465,
    'smtp_user' => 'contact@riadmylaya.com',
    'smtp_pass' => 'MOT_DE_PASSE_DE_LA_BOITE',
    'from_email' => 'contact@riadmylaya.com',
    'from_name' => 'Riad Mylaya — Site web',
    'to_email' => 'contact@riadmylaya.com',
    'to_name' => 'Riad Mylaya',
    'bcc_email' => '',
    'whatsapp_country' => '212',
    'fallback_url' => 'https://formsubmit.co/contact@riadmylaya.com',
    // Seules ces adresses peuvent recevoir une copie via le champ _cc d'un formulaire.
    'cc_allow' => array('info@mythicoriental-spa.com'),
);
