<?php
// ─── Configuration Riad Bilkis ───────────────────────────────────────────────
// Renseignez ces valeurs directement sur le serveur (cPanel > Gestionnaire de
// fichiers). Ne jamais committer de mot de passe reel dans ce depot.

define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_CPANEL_USERNAME_riadbilkis'); // ex. riaductd_riadbilkis
define('DB_USER', 'YOUR_CPANEL_USERNAME_bilkis');     // ex. riaductd_bilkis
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');          // cPanel > Bases de donnees MySQL

define('JWT_SECRET', 'CHANGE_THIS_TO_A_RANDOM_STRING_AT_LEAST_32_CHARS');
define('JWT_EXPIRY_HOURS', 8);

define('GMAIL_EMAIL', 'riadbilkis@gmail.com');
define('GMAIL_APP_PASSWORD', 'CHANGE_THIS_GMAIL_APP_PASSWORD');
define('NOTIFICATION_EMAIL', 'riadbilkis@gmail.com');

define('SITE_URL', 'https://police.riadbilkis.com');

define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'CHANGE_THIS_ADMIN_PASSWORD');
define('DEFAULT_ADMIN_EMAIL', 'riadbilkis@gmail.com');

date_default_timezone_set('Africa/Casablanca');
