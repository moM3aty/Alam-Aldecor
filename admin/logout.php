<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';

/* Handle confirmation */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

$adminName = $_SESSION['admin_name'] ?? '';
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>تسجيل الخروج · لوحة التحكم</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body">
<div class="logout-shell">
  <div class="logout-card">
    <div class="logout-ico">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </div>

    <h1>تسجيل الخروج</h1>
    <p>
      <?php if ($adminName): ?>
        وداعاً <b><?= e($adminName) ?></b>، هل تريد تسجيل الخروج من لوحة التحكم؟
      <?php else: ?>
        أنت على وشك تسجيل الخروج. يمكنك العودة في أي وقت بتسجيل الدخول مجدداً.
      <?php endif; ?>
    </p>

    <div class="logout-actions">
      <form method="post">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
        <button type="submit" class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          <span>تأكيد الخروج</span>
        </button>
      </form>
      <a href="index.php" class="btn btn-ghost">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        <span>البقاء في اللوحة</span>
      </a>
    </div>
  </div>
</div>
</body>
</html>