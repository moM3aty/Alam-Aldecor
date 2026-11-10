<?php
define('APP_BOOT', true);
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$done = false;
$error = '';

$hasAdmin = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;

if ($hasAdmin) {
    $done = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    if (strlen($username) < 3 || strlen($password) < 6 || $name === '') {
        $error = 'البيانات غير مكتملة (اسم المستخدم 3 أحرف، كلمة المرور 6 أحرف).';
    } else {
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, name) VALUES (?,?,?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name]);
        $done = true;
    }
}
?>
<!doctype html><html lang="ar" dir="rtl"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>تركيب عالم الديكور</title>
<style>
body{font-family:system-ui,Tahoma;background:#0f1117;color:#eee;margin:0;display:grid;place-items:center;min-height:100vh}
.card{background:#1c2230;padding:36px 32px;border-radius:18px;max-width:460px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.5);border:1px solid #2b3345}
h1{margin:0 0 6px;color:#E2BC76;font-size:1.5rem}
p{color:#a9b1c2;line-height:1.7;margin:0 0 20px}
label{display:block;font-size:.9rem;margin:14px 0 6px;color:#cdd3e0}
input{width:100%;padding:11px 14px;border-radius:10px;border:1px solid #2b3345;background:#0f131c;color:#fff;font-size:1rem;box-sizing:border-box}
button{margin-top:20px;width:100%;padding:13px;border:0;border-radius:10px;background:linear-gradient(135deg,#C79A4B,#E2BC76);color:#161B22;font-weight:800;font-size:1rem;cursor:pointer}
.ok{background:#123018;border-color:#1e5c30;color:#b8f0cc;padding:18px;border-radius:12px}
.err{color:#ff8a8a;margin-top:10px;font-size:.9rem}
</style></head><body>
<div class="card">
<?php if ($done): ?>
  <h1>✅ الإعداد مكتمل</h1>
  <div class="ok">
    <p>تم إنشاء حساب المدير بنجاح.</p>
    <p><b>الخطوة التالية:</b> احذف الملف <code>install.php</code> من الاستضافة فوراً، ثم ادخل إلى:</p>
    <p><a href="admin/login.php" style="color:#E2BC76">admin/login.php</a></p>
  </div>
<?php else: ?>
  <h1>إعداد لوحة التحكم</h1>
  <p>أنشئ حساب المدير الأول لعالم الديكور.</p>
  <form method="post">
    <label>اسم المدير</label>
    <input name="name" required value="مدير الموقع">
    <label>اسم المستخدم</label>
    <input name="username" required placeholder="admin">
    <label>كلمة المرور</label>
    <input name="password" type="password" required minlength="6">
    <?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <button type="submit">إنشاء الحساب</button>
  </form>
<?php endif; ?>
</div></body></html>