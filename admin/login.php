<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';

if (isLoggedIn()) redirect('index.php');

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $pdo->prepare('UPDATE admins SET last_login = NOW() WHERE id = ?')->execute([$admin['id']]);
        redirect('index.php');
    }

    sleep(1);
    $error = 'بيانات الدخول غير صحيحة. تحقق من اسم المستخدم وكلمة المرور.';
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>تسجيل الدخول · لوحة تحكم عالم الديكور</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body auth-body">

<div class="auth-shell">

  <!-- Brand panel -->
  <aside class="auth-brand">
    <div class="auth-brand-bg">
      <span class="auth-orb auth-orb-1"></span>
      <span class="auth-orb auth-orb-2"></span>
      <span class="auth-grid"></span>
      <svg class="auth-pattern" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" aria-hidden="true">
        <defs>
          <pattern id="decorLogin" width="120" height="120" patternUnits="userSpaceOnUse" patternTransform="rotate(-8)">
            <g fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <rect x="12" y="12" width="42" height="14" rx="4"/>
              <path d="M54 19h10v18H34v10"/>
              <rect x="28" y="52" width="8" height="26" rx="3"/>
              <path d="M92 24c0 0-10 12-10 20a10 10 0 0 0 20 0c0-8-10-20-10-20Z"/>
              <path d="M12 96h52v-8H26v-8H12z"/>
              <path d="M70 70h40v40H70z"/>
            </g>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#decorLogin)"/>
      </svg>
    </div>

    <div class="auth-brand-top">
      <div class="brand-mark">ع</div>
      <div class="brand-text">
        <strong>عالم الديكور</strong>
        <small>لوحة التحكم</small>
      </div>
    </div>

    <div class="auth-brand-mid">
      <h1>مرحباً بعودتك<br><span>أدر موقعك بكل سهولة</span></h1>
      <p>كل ما تحتاجه لإدارة محتوى موقعك من مكان واحد — خدمات، معرض أعمال، آراء العملاء، ورسائل الزوار.</p>

      <ul class="auth-features">
        <li>
          <span class="auth-feat-ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
          </span>
          <div>
            <b>حماية متقدمة</b>
            <small>تشفير كلمات المرور وحماية الجلسات</small>
          </div>
        </li>
        <li>
          <span class="auth-feat-ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          </span>
          <div>
            <b>أداء سريع</b>
            <small>واجهة خفيفة وتفاعل فوري</small>
          </div>
        </li>
        <li>
          <span class="auth-feat-ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
          </span>
          <div>
            <b>تحكم كامل</b>
            <small>إدارة جميع أقسام الموقع بسهولة</small>
          </div>
        </li>
      </ul>
    </div>

    <div class="auth-brand-foot">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
      <span>اتصال آمن ومشفر</span>
      <span class="auth-dot"></span>
      <span>&copy; <?= date('Y') ?> عالم الديكور</span>
    </div>
  </aside>

  <!-- Form side -->
  <main class="auth-form-side">
    <div class="auth-form-card">

      <div class="auth-form-head">
        <span class="auth-form-badge">تسجيل الدخول</span>
        <h2>أهلاً بك مجدداً</h2>
        <p>ادخل بيانات حسابك للوصول إلى لوحة التحكم</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
          <span class="alert-ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
          </span>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" class="auth-form" autocomplete="on">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">

        <div class="fld">
          <label for="username">اسم المستخدم</label>
          <div class="input-wrap">
            <input id="username" name="username" type="text" required autofocus
                   value="<?= e($username) ?>" placeholder="أدخل اسم المستخدم">
            <span class="input-ico">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </span>
          </div>
        </div>

        <div class="fld">
          <label for="password">كلمة المرور</label>
          <div class="input-wrap">
            <input id="password" name="password" type="password" required placeholder="أدخل كلمة المرور">
            <span class="input-ico">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <button type="button" class="input-toggle" id="pwToggle" aria-label="إظهار كلمة المرور" tabindex="-1">
              <svg id="pwIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <button class="btn btn-primary auth-submit" type="submit" id="loginBtn">
          <span class="btn-label">
            <span>دخول إلى اللوحة</span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
          </span>
        </button>
      </form>

      <div class="auth-form-foot">
        <p>نسيت كلمة المرور؟ تواصل مع مسؤول النظام لإعادة تعيينها.</p>
      </div>

    </div>
  </main>

</div>

<script>
(function () {
  'use strict';

  // Password toggle
  var pwInput  = document.getElementById('password');
  var pwToggle = document.getElementById('pwToggle');
  var pwIcon   = document.getElementById('pwIcon');
  var eyeOn    = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>';
  var eyeOff   = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/>';

  if (pwToggle) {
    pwToggle.addEventListener('click', function () {
      var showing = pwInput.type === 'text';
      pwInput.type = showing ? 'password' : 'text';
      pwIcon.innerHTML = showing ? eyeOn : eyeOff;
      pwToggle.setAttribute('aria-label', showing ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
      pwInput.focus();
    });
  }

  // Loading state
  var form = document.querySelector('.auth-form');
  if (form) {
    form.addEventListener('submit', function () {
      var btn = document.getElementById('loginBtn');
      if (btn) {
        btn.classList.add('is-loading');
        btn.disabled = true;
      }
    });
  }
})();
</script>

</body>
</html>