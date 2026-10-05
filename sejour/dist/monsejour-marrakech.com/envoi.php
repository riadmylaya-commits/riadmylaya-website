<?php
/**
 * Espace client multi-établissements — point d'entrée des formulaires.
 *
 * Le formulaire indique l'établissement via le champ caché _etab ; on charge
 * son identité (etablissements.php, généré) puis on délègue tout le travail
 * (anti-robots, e-mail HTML, accusé de réception, redirection) au moteur
 * commun rm-envoi.php, identique à celui du Riad Mylaya.
 *
 * Réglages SMTP / Turnstile : rm-mail-config.php (non versionné, voir le .sample) ;
 * compte SMTP d'un établissement : rm-mail-config-<id>.php (facultatif).
 */

$etabs = include __DIR__ . '/etablissements.php';
$id = isset($_POST['_etab']) ? preg_replace('/[^a-z0-9-]/', '', (string) $_POST['_etab']) : '';
if ($id === '' || !isset($etabs[$id])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Etablissement inconnu.";
    exit;
}

$RM_BRAND = $etabs[$id]['brand'];
$RM_CONFIG_OVERRIDE = $etabs[$id]['mail'];
unset($RM_CONFIG_OVERRIDE['from_email']);

/* Compte SMTP propre à l'établissement (expéditeur = sa propre adresse) :
   rm-mail-config-<id>.php, non versionné, voir rm-mail-config-riad-bilkis.sample.php. */
$smtpLocal = __DIR__ . '/rm-mail-config-' . $id . '.php';
if (is_readable($smtpLocal)) {
    $smtp = include $smtpLocal;
    if (is_array($smtp)) {
        $RM_CONFIG_OVERRIDE = array_merge($RM_CONFIG_OVERRIDE, $smtp);
    }
}

require __DIR__ . '/rm-envoi.php';
