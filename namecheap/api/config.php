<?php
// ─── Configuration Riad Mylaya ───────────────────────────────────────────────
// Modify these values to match your cPanel MySQL database settings

define('DB_HOST', 'localhost');
define('DB_NAME', 'YOUR_CPANEL_USERNAME_riadmylaya'); // e.g. riamyl_riadmylaya
define('DB_USER', 'YOUR_CPANEL_USERNAME_admin');      // e.g. riamyl_admin
define('DB_PASS', 'YOUR_DATABASE_PASSWORD');          // set in cPanel > MySQL

define('JWT_SECRET', 'CHANGE_THIS_TO_A_RANDOM_STRING_AT_LEAST_32_CHARS');
define('JWT_EXPIRY_HOURS', 8);

define('GMAIL_EMAIL', 'riadmylaya@gmail.com');
define('GMAIL_APP_PASSWORD', 'mjzv iefe qrdv stop');
define('NOTIFICATION_EMAIL', 'riadmylaya@gmail.com');

define('SITE_URL', 'https://yourdomain.com'); // Your actual domain

define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', 'mylaya2024');
define('DEFAULT_ADMIN_EMAIL', 'riadmylaya@gmail.com');

date_default_timezone_set('Africa/Casablanca');
