<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php'; // <-- السطر ده كان ناقص وده كان سبب المشكلة

/* Handle confirmation */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من الـ CSRF
    csrfCheck();
    
    // تفريغ كل بيانات الجلسة
    $_SESSION = [];
    
    // مسح كوكيز الجلسة
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    
    // تدمير الجلسة نهائياً
    session_destroy();
    
    // التوجيه لصفحة الدخول
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
<style>
  /* تنسيقات إضافية عشان الصفحة تبان حلوة لو مفيش ملف CSS محمل */
  .logout-shell {
    display: grid;
    place-items: center;
    min-height: 100vh;
    background: var(--ink, #161B22);
    padding: 24px;
  }
  .logout-card {
    background: #fff;
    border-radius: 24px;
    padding: 40px 36px;
    max-width: 440px;
    width: 100%;
    text-align: center;
    box-shadow: 0 24px 60px rgba(0,0,0,.3);
  }
  .logout-ico {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    margin: 0 auto 20px;
    background: linear-gradient(145deg, #C79A4B, #E2BC76);
    color: #161B22;
  }
  .logout-card h1 { font-size: 1.35rem; font-weight: 900; color: #161B22; margin-bottom: 8px; }
  .logout-card p { font-size: .92rem; color: #4a5268; font-weight: 500; margin-bottom: 26px; line-height: 1.7; }
  .logout-actions { display: flex; flex-direction: column; gap: 10px; }
  .logout-actions .btn { width: 100%; display: flex; justify-content: center; align-items: center; gap: 8px; padding: 12px; border-radius: 12px; font-weight: 800; font-family: inherit; cursor: pointer; border: none; font-size: 1rem; }
  .logout-actions .btn-primary { background: linear-gradient(135deg, #C79A4B, #E2BC76); color: #161B22; }
  .logout-actions .btn-ghost { background: #f3f5fa; color: #4a5268; border: 1px solid #e3e8f1; text-decoration: none; }
  .logout-actions .btn-ghost:hover { background: #eef2f8; }
</style>
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
      <form method="post" style="margin:0">
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