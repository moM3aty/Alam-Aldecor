<?php
// functions.php
if (!defined('APP_BOOT')) { exit('Forbidden'); }

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function getSettings() {
    global $pdo;
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $r) {
        $cache[$r['setting_key']] = $r['setting_value'];
    }
    return $cache;
}

function setting($key, $default = '') {
    $s = getSettings();
    return $s[$key] ?? $default;
}

function getHero() {
    global $pdo;
    return $pdo->query('SELECT * FROM hero LIMIT 1')->fetch() ?: [];
}

function getAbout() {
    global $pdo;
    return $pdo->query('SELECT * FROM about_content LIMIT 1')->fetch() ?: [];
}

function getServices() {
    global $pdo;
    return $pdo->query('SELECT * FROM services WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
}

function getGallery() {
    global $pdo;
    return $pdo->query('SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
}

function getAreas() {
    global $pdo;
    return $pdo->query('SELECT * FROM areas WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
}

function getTestimonials() {
    global $pdo;
    return $pdo->query('SELECT * FROM testimonials WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
}

function getWhyCards() {
    global $pdo;
    return $pdo->query('SELECT * FROM why_cards WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
}

function getWhySteps() {
    global $pdo;
    return $pdo->query('SELECT * FROM why_steps WHERE is_active=1 ORDER BY sort_order, id')->fetchAll();
}

function redirect($url) { header('Location: ' . $url); exit; }

function isLoggedIn() { return !empty($_SESSION['admin_id']); }

function requireLogin() {
    if (!isLoggedIn()) redirect('login.php');
}

function csrfToken() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrfCheck() {
    if (empty($_POST['_csrf']) || empty($_SESSION['csrf'])
        || !hash_equals($_SESSION['csrf'], $_POST['_csrf'])) {
        http_response_code(403);
        exit('CSRF validation failed.');
    }
}

function setFlash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function getFlash() {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f;
}

/**
 * Handle image upload. Returns relative path or null.
 * @param array $file  $_FILES entry
 */
function uploadImage($file, $prefix = 'img') {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > 6 * 1024 * 1024) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) return null;

    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        return UPLOAD_URL . $name;
    }
    return null;
}

/** Delete an uploaded file (only if inside uploads/). */
function deleteUploadedFile($path) {
    if (!$path) return;
    $path = str_replace('..', '', $path);
    if (strpos($path, UPLOAD_URL) === 0) {
        $full = __DIR__ . '/' . $path;
        if (is_file($full)) @unlink($full);
    }
}