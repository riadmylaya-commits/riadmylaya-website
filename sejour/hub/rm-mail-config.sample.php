<?php
/* Copier en rm-mail-config.php (non versionné) à la racine du sous-domaine. */
return array(
    'smtp_host' => 'mail.privateemail.com',
    'smtp_port' => 465,
    'smtp_user' => 'contact@riadmylaya.com',
    'smtp_pass' => '********',
    /* compte SMTP unique : l'expéditeur reste contact@riadmylaya.com,
       le destinataire (to_email) et le nom changent selon l'établissement */
    'from_email' => 'contact@riadmylaya.com',
    'ehlo_host' => 'sejour.riadmylaya.com',
    'turnstile_secret' => '',
    'turnstile_hosts' => 'sejour.riadmylaya.com,riadbilkis.com,www.riadbilkis.com',
    'turnstile_mode' => 'strict',
);
