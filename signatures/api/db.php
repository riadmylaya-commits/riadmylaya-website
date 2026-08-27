<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        initDatabase($pdo);
    }
    return $pdo;
}

function initDatabase(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS signatures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        cin TEXT NOT NULL,
        phone TEXT,
        signature TEXT NOT NULL,
        ip TEXT,
        user_agent TEXT,
        created_at TEXT NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_signatures_cin ON signatures (cin)');
}
