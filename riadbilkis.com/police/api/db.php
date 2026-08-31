<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function initDatabase(): void {
    $pdo = getDB();

    $pdo->exec("CREATE TABLE IF NOT EXISTS staff_users (
        id VARCHAR(36) PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        hashed_password VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS registrations (
        id VARCHAR(36) PRIMARY KEY,
        room VARCHAR(50) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        date_of_birth VARCHAR(50) NOT NULL,
        place_of_birth VARCHAR(100) NOT NULL,
        nationality VARCHAR(100) NOT NULL,
        occupation VARCHAR(100) NOT NULL,
        cin_number VARCHAR(100) NOT NULL,
        morocco_entry_number VARCHAR(100) NOT NULL,
        arrival_date VARCHAR(50) NOT NULL,
        departure_date VARCHAR(50) NOT NULL,
        accompanying_children INT DEFAULT 0,
        coming_from VARCHAR(200) NOT NULL,
        going_to VARCHAR(200) NOT NULL,
        passport_number VARCHAR(100) NOT NULL,
        passport_issue_date VARCHAR(50) NOT NULL,
        passport_issue_place VARCHAR(100) NOT NULL,
        permanent_address VARCHAR(500) NOT NULL,
        passport_photo LONGTEXT,
        signature LONGTEXT,
        registration_date VARCHAR(50) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        token VARCHAR(255) UNIQUE NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create default admin if not exists
    $stmt = $pdo->prepare("SELECT id FROM staff_users WHERE username = ?");
    $stmt->execute([DEFAULT_ADMIN_USER]);
    if (!$stmt->fetch()) {
        $id = generateUUID();
        $hash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO staff_users (id, username, email, hashed_password, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$id, DEFAULT_ADMIN_USER, DEFAULT_ADMIN_EMAIL, $hash]);
    }
}

function generateUUID(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
