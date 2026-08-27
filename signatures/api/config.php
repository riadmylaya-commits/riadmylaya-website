<?php
// ─── Configuration — page de signatures électroniques ────────────────────────
// À adapter après l'upload sur l'hébergement (cPanel Namecheap).

// Mot de passe de la page d'administration (admin.html).
// ⚠️ À CHANGER avant la mise en ligne.
define('ADMIN_PASSWORD', 'CHANGEZ_CE_MOT_DE_PASSE');

// Emplacement de la base SQLite. Idéalement HORS du dossier public
// (ex. '/home/UTILISATEUR_CPANEL/signatures_data/signatures.sqlite').
define('DB_FILE', __DIR__ . '/data/signatures.sqlite');

// Empêche deux envois avec le même numéro de CIN.
define('UNIQUE_CIN', true);

// Taille maximale acceptée pour l'image de signature (octets, base64 inclus).
define('MAX_SIGNATURE_BYTES', 800 * 1024);

date_default_timezone_set('Africa/Casablanca');
