<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

$filter = $_GET['filter'] ?? 'all';
$search = trim((string)($_GET['q'] ?? ''));

$where  = [];
$params = [];

if ($filter === 'unread') $where[] = 'is_read = 0';
elseif ($filter === 'read') $where[] = 'is_read = 1';

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(name LIKE ? OR phone LIKE ? OR city LIKE ? OR service LIKE ? OR message LIKE ?)';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT id, name, phone, city, service, message, is_read, created_at
                       FROM messages $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'messages-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

/* BOM for Excel Arabic support */
fwrite($out, "\xEF\xBB\xBF");

/* Header row */
fputcsv($out, ['ID', 'الاسم', 'الجوال', 'المدينة', 'الخدمة', 'الرسالة', 'الحالة', 'التاريخ']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['name'],
        $r['phone'],
        $r['city'],
        $r['service'],
        $r['message'],
        $r['is_read'] ? 'مقروءة' : 'جديدة',
        $r['created_at'],
    ]);
}

fclose($out);
exit;