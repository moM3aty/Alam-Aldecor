<?php
// config.php
if (!defined('APP_BOOT')) { exit('Forbidden'); }

define('DB_HOST', 'localhost');
define('DB_NAME', 'u315614949_ad');
define('DB_USER', 'u315614949_ad');
define('DB_PASS', '8lzJNyM^'); 

define('SITE_URL', 'https://alamdecor.online');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');

date_default_timezone_set('Asia/Riyadh');
mb_internal_encoding('UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('DB connection failed. Please check configuration.');
}