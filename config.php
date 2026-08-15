<?php
// Configuration and Database connection for Photobox Application

define('DB_PATH', __DIR__ . '/database.sqlite');
define('UPLOAD_RAW_DIR', __DIR__ . '/uploads/raw/');
define('UPLOAD_FRAMED_DIR', __DIR__ . '/uploads/framed/');

// Ensure upload directories exist
if (!file_exists(UPLOAD_RAW_DIR)) {
    mkdir(UPLOAD_RAW_DIR, 0777, true);
}
if (!file_exists(UPLOAD_FRAMED_DIR)) {
    mkdir(UPLOAD_FRAMED_DIR, 0777, true);
}

function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Initialize tables if not exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS stats (
            id INTEGER PRIMARY KEY,
            total_taken INTEGER DEFAULT 0,
            total_printed INTEGER DEFAULT 0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS photos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_code TEXT NOT NULL,
            photo1 TEXT,
            photo2 TEXT,
            photo3 TEXT,
            framed_image TEXT NOT NULL,
            frame_id TEXT,
            status TEXT DEFAULT 'ready',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Ensure single stats row exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM stats WHERE id = 1");
        if ($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO stats (id, total_taken, total_printed) VALUES (1, 0, 0)");
        }
    }
    return $pdo;
}

function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script);
    $dir = rtrim(str_replace('\\', '/', $dir), '/');
    return $protocol . $host . ($dir ? $dir : '');
}
