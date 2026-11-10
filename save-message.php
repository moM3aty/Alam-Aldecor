<?php
define('APP_BOOT', true);
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$city    = trim($_POST['city']    ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '') { http_response_code(400); exit('{}'); }

// simple rate limit: 1 msg / 10s per session
$now = time();
if (!empty($_SESSION['last_msg']) && $now - $_SESSION['last_msg'] < 10) { exit('{"ok":1}'); }
$_SESSION['last_msg'] = $now;

$stmt = $pdo->prepare('INSERT INTO messages (name, phone, city, service, message) VALUES (?,?,?,?,?)');
$stmt->execute([
    mb_substr($name, 0, 100),
    mb_substr($phone, 0, 30),
    mb_substr($city, 0, 100),
    mb_substr($service, 0, 150),
    mb_substr($message, 0, 5000),
]);

echo json_encode(['ok' => true]);