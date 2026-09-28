<?php
// ─── Installation Script ─────────────────────────────────────────────────────
// Visit this page ONCE after uploading files to create database tables.
// DELETE THIS FILE after successful installation!

require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/db.php';

$success = false;
$error = '';

try {
    initDatabase();
    $success = true;
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Riad Bilkis</title>
    <style>
        body { font-family: Arial, sans-serif; background: #faf7f2; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; padding: 32px; border: 1px solid #ebe0cc; }
        h1 { color: #5a2d1e; text-align: center; }
        .success { background: #d4edda; color: #155724; padding: 16px; border-radius: 8px; margin: 16px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 16px; border-radius: 8px; margin: 16px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 16px; border-radius: 8px; margin: 16px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🏠 Riad Bilkis — Installation</h1>
    
    <?php if ($success): ?>
        <div class="success">
            <strong>✅ Installation réussie !</strong><br><br>
            Les tables de la base de données ont été créées avec succès.<br>
            Le compte admin par défaut a été créé :<br><br>
            <strong>Utilisateur :</strong> <code><?= DEFAULT_ADMIN_USER ?></code><br>
            <strong>Mot de passe :</strong> <code><?= DEFAULT_ADMIN_PASS ?></code>
        </div>
        <div class="warning">
            <strong>⚠️ IMPORTANT :</strong> Supprimez ce fichier <code>install.php</code> de votre serveur maintenant pour des raisons de sécurité.
        </div>
        <p style="text-align:center;"><a href="/" style="color:#5a2d1e;font-weight:bold;">→ Accéder à l'application</a></p>
    <?php else: ?>
        <div class="error">
            <strong>❌ Erreur d'installation</strong><br><br>
            <?= htmlspecialchars($error) ?><br><br>
            Vérifiez que :<br>
            • La base de données MySQL existe<br>
            • Les identifiants dans <code>api/config.php</code> sont corrects<br>
            • L'utilisateur MySQL a les droits CREATE TABLE
        </div>
    <?php endif; ?>
</div>
</body>
</html>
