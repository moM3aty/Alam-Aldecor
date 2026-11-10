<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

$adminId = (int)$_SESSION['admin_id'];
$admin = $pdo->query("SELECT * FROM admins WHERE id = $adminId")->fetch();
if (!$admin) {
    session_destroy();
    redirect('login.php');
}

$errors = [];
$activeSection = 'profile';

/* ===== Update profile ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    csrfCheck();
    $activeSection = 'profile';

    $name  = trim((string)($_POST['name']  ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));

    if (mb_strlen($name) < 2)    $errors[] = 'الاسم قصير جداً.';
    if (mb_strlen($name) > 100)  $errors[] = 'الاسم طويل جداً.';
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح.';
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE admins SET name = ?, email = ? WHERE id = ?')
            ->execute([$name, $email ?: null, $adminId]);
        $_SESSION['admin_name'] = $name;
        setFlash('ok', 'تم تحديث بيانات الحساب.');
        redirect('account.php');
    }
}

/* ===== Update username ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'username') {
    csrfCheck();
    $activeSection = 'username';

    $username = trim((string)($_POST['username'] ?? ''));

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $errors[] = 'اسم المستخدم يجب أن يكون 3–50 حرفاً (حروف إنجليزية، أرقام، أو _ . -).';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = ? AND id <> ?');
        $stmt->execute([$username, $adminId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'اسم المستخدم مستخدم بالفعل.';
        }
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE admins SET username = ? WHERE id = ?')
            ->execute([$username, $adminId]);
        setFlash('ok', 'تم تحديث اسم المستخدم.');
        redirect('account.php');
    }
}

/* ===== Change password ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    csrfCheck();
    $activeSection = 'password';

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $admin['password_hash'])) {
        $errors[] = 'كلمة المرور الحالية غير صحيحة.';
    }
    if (strlen($new) < 8) {
        $errors[] = 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.';
    }
    if ($new !== $confirm) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }
    if ($new === $current) {
        $errors[] = 'كلمة المرور الجديدة يجب أن تختلف عن الحالية.';
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $adminId]);
        session_regenerate_id(true);
        setFlash('ok', 'تم تغيير كلمة المرور بنجاح.');
        redirect('account.php');
    }
}

if ($errors) {
    setFlash('err', implode(' ', $errors));
}

$pageTitle = 'حسابي';
include 'includes/header.php';
?>

<div class="acct-grid">

  <!-- ============ LEFT: Profile summary ============ -->
  <aside>
    <div class="acct-profile">
      <div class="acct-avatar"><?= e(mb_substr($admin['name'], 0, 1)) ?></div>
      <div class="acct-name"><?= e($admin['name']) ?></div>
      <div class="acct-username">@<?= e($admin['username']) ?></div>

      <ul class="acct-meta">
        <?php if (!empty($admin['email'])): ?>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <?= e($admin['email']) ?>
          </li>
        <?php endif; ?>
        <li>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="3"/>
            <path d="M16 2v4M8 2v4M3 10h18"/>
          </svg>
          عضو منذ <?= date('Y-m-d', strtotime($admin['created_at'])) ?>
        </li>
        <?php if (!empty($admin['last_login'])): ?>
          <li>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 6v6l4 2"/>
            </svg>
            آخر دخول: <?= date('Y-m-d H:i', strtotime($admin['last_login'])) ?>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </aside>

  <!-- ============ RIGHT: Forms ============ -->
  <div>

    <!-- ==== Profile info ==== -->
    <div class="acct-section">
      <div class="acct-section-head">
        <span class="asc-ico">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>
          </svg>
        </span>
        <h3>البيانات الأساسية</h3>
      </div>
      <div class="acct-section-body">
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="profile">

          <div class="grid-2">
            <div class="fld">
              <label for="name">الاسم</label>
              <input id="name" name="name" type="text" required maxlength="100"
                     value="<?= e($admin['name']) ?>">
            </div>
            <div class="fld">
              <label for="email">البريد الإلكتروني</label>
              <input id="email" name="email" type="email" maxlength="150"
                     value="<?= e($admin['email'] ?? '') ?>" dir="ltr"
                     placeholder="اختياري">
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/>
              <polyline points="7 3 7 8 15 8"/>
            </svg>
            <span>حفظ البيانات</span>
          </button>
        </form>
      </div>
    </div>

    <!-- ==== Username ==== -->
    <div class="acct-section">
      <div class="acct-section-head">
        <span class="asc-ico teal">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 7h16M4 12h10M4 17h7"/>
          </svg>
        </span>
        <h3>اسم المستخدم</h3>
      </div>
      <div class="acct-section-body">
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="username">

          <div class="fld">
            <label for="username">اسم المستخدم</label>
            <input id="username" name="username" type="text" required
                   minlength="3" maxlength="50" dir="ltr"
                   value="<?= e($admin['username']) ?>">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              حروف إنجليزية، أرقام، أو _ . - (3–50 حرف).
            </div>
          </div>

          <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/>
              <polyline points="7 3 7 8 15 8"/>
            </svg>
            <span>تحديث اسم المستخدم</span>
          </button>
        </form>
      </div>
    </div>

    <!-- ==== Password ==== -->
    <div class="acct-section">
      <div class="acct-section-head">
        <span class="asc-ico" style="background:linear-gradient(145deg,#d64545,#e06666);color:#fff;box-shadow:0 6px 16px rgba(214,69,69,.3)">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="11" width="16" height="10" rx="2"/>
            <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
          </svg>
        </span>
        <h3>تغيير كلمة المرور</h3>
      </div>
      <div class="acct-section-body">
        <form method="post" id="pwForm">
          <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
          <input type="hidden" name="action" value="password">

          <div class="fld">
            <label for="current_password">كلمة المرور الحالية</label>
            <input id="current_password" name="current_password" type="password" required>
          </div>

          <div class="grid-2">
            <div class="fld" style="margin-bottom:0">
              <label for="new_password">كلمة المرور الجديدة</label>
              <input id="new_password" name="new_password" type="password" required minlength="8">
              <div class="pw-strength" id="pwStrength">
                <div class="pw-strength-bar">
                  <span></span><span></span><span></span><span></span>
                </div>
                <div class="pw-strength-label" id="pwStrengthLabel">قصيرة جداً</div>
              </div>
            </div>
            <div class="fld" style="margin-bottom:0">
              <label for="confirm_password">تأكيد كلمة المرور</label>
              <input id="confirm_password" name="confirm_password" type="password" required minlength="8">
              <div class="fld-hint" id="pwMatchHint" style="display:none">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
                كلمتا المرور متطابقتان.
              </div>
            </div>
          </div>

          <div class="fld-hint" style="margin-top:14px;margin-bottom:14px">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
            </svg>
            8 أحرف على الأقل، يُفضّل استخدام حروف كبيرة وصغيرة وأرقام ورموز.
          </div>

          <button type="submit" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="11" width="16" height="10" rx="2"/>
              <path d="M8 11V8a4 4 0 0 1 8 0v3"/>
            </svg>
            <span>تغيير كلمة المرور</span>
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
(function () {
  'use strict';

  var pwInput  = document.getElementById('new_password');
  var pwConf   = document.getElementById('confirm_password');
  var strength = document.getElementById('pwStrength');
  var label    = document.getElementById('pwStrengthLabel');
  var match    = document.getElementById('pwMatchHint');

  function calcStrength(pw) {
    var score = 0;
    if (pw.length >= 8) score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/\d/.test(pw) && /[^A-Za-z0-9]/.test(pw)) score++;

    if (pw.length === 0) return { cls: '', text: '' };
    if (pw.length < 8) return { cls: 'weak', text: 'قصيرة جداً (8 أحرف على الأقل)' };
    if (score <= 1) return { cls: 'weak',   text: 'ضعيفة' };
    if (score === 2) return { cls: 'fair',   text: 'متوسطة' };
    if (score === 3) return { cls: 'good',   text: 'قوية' };
    return { cls: 'strong', text: 'قوية جداً' };
  }

  if (pwInput) {
    pwInput.addEventListener('input', function () {
      var s = calcStrength(pwInput.value);
      if (!s.cls) {
        strength.classList.remove('show', 'weak', 'fair', 'good', 'strong');
        return;
      }
      strength.classList.add('show');
      strength.classList.remove('weak', 'fair', 'good', 'strong');
      strength.classList.add(s.cls);
      label.textContent = s.text;
    });
  }

  function checkMatch() {
    if (!match || !pwInput || !pwConf) return;
    var show = pwConf.value !== '' && pwInput.value === pwConf.value;
    match.style.display = show ? 'flex' : 'none';
    match.style.color = show ? 'var(--green)' : '';
    match.querySelector('svg').style.color = show ? 'var(--green)' : '';
  }

  if (pwConf) pwConf.addEventListener('input', checkMatch);
  if (pwInput) pwInput.addEventListener('input', checkMatch);
})();
</script>

<?php include 'includes/footer.php'; ?>