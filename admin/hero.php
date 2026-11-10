<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

$errors = [];

$row = $pdo->query('SELECT * FROM hero LIMIT 1')->fetch();
if (!$row) {
    /* Auto-create if missing */
    $pdo->exec("INSERT INTO hero (badge, title, highlight, subtitle) VALUES ('', '', '', '')");
    $row = $pdo->query('SELECT * FROM hero LIMIT 1')->fetch();
}

$data = [
    'badge'     => $row['badge']     ?? '',
    'title'     => $row['title']     ?? '',
    'highlight' => $row['highlight'] ?? '',
    'subtitle'  => $row['subtitle']  ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $data['badge']     = trim((string)($_POST['badge']     ?? ''));
    $data['title']     = trim((string)($_POST['title']     ?? ''));
    $data['highlight'] = trim((string)($_POST['highlight'] ?? ''));
    $data['subtitle']  = trim((string)($_POST['subtitle']  ?? ''));

    if ($data['title'] === '')     $errors[] = 'العنوان الرئيسي مطلوب.';
    if (mb_strlen($data['title']) > 200)     $errors[] = 'العنوان طويل جداً.';
    if (mb_strlen($data['highlight']) > 60)  $errors[] = 'الكلمة المميزة طويلة جداً.';
    if (mb_strlen($data['subtitle']) > 500)  $errors[] = 'النص الفرعي طويل جداً.';
    if (mb_strlen($data['badge']) > 150)     $errors[] = 'الشارة طويلة جداً.';

    if (empty($errors)) {
        $pdo->prepare('UPDATE hero SET badge=?, title=?, highlight=?, subtitle=? WHERE id=?')
            ->execute([$data['badge'], $data['title'], $data['highlight'], $data['subtitle'], $row['id']]);
        setFlash('ok', 'تم تحديث قسم البطل بنجاح.');
        redirect('hero.php');
    } else {
        setFlash('err', implode(' ', $errors));
    }
}

$pageTitle = 'قسم البطل';
include 'includes/header.php';
?>

<!-- ========== INTRO BANNER ========== -->
<section class="edit-hero-banner">
  <div class="ehb-info">
    <span class="ehb-chip">قسم البطل</span>
    <h2>تحكم في <span>أول ما يراه الزائر</span></h2>
    <p>حدّث نصوص القسم الرئيسي (Hero) — الشارة، العنوان، والنص الفرعي.</p>
  </div>
  <a href="../index.php#home" target="_blank" rel="noopener" class="ehb-action">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
      <polyline points="15 3 21 3 21 9"/>
      <line x1="10" y1="14" x2="21" y2="3"/>
    </svg>
    عرض في الموقع
  </a>
</section>

<form method="post" novalidate>
  <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">

  <div class="edit-content-grid">

    <!-- ============ LEFT: FORM ============ -->
    <div>

      <!-- Section 1: Badge -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.8l-6.2 4.5 2.4-7.4L2 9.4h7.6z"/>
            </svg>
          </span>
          <h3>الشارة العلوية</h3>
          <small>Badge</small>
        </div>
        <div class="sec-card-body">
          <div class="fld">
            <div class="fld-head">
              <label for="badge">النص الظاهر فوق العنوان</label>
              <span class="char-count" data-for="badge" data-max="150">0 / 150</span>
            </div>
            <input id="badge" name="badge" type="text" maxlength="150"
                   value="<?= e($data['badge']) ?>"
                   placeholder="مثال: جبس بورد ودهانات في خميس مشيط وأبها وعسير">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              يظهر داخل شارة صغيرة فوق العنوان الرئيسي.
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Title -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 7V4h16v3"/>
              <line x1="9" y1="20" x2="15" y2="20"/>
              <line x1="12" y1="4" x2="12" y2="20"/>
            </svg>
          </span>
          <h3>العنوان الرئيسي</h3>
          <small>Headline</small>
        </div>
        <div class="sec-card-body">
          <div class="grid-2">
            <div class="fld">
              <div class="fld-head">
                <label for="highlight">الكلمة المميزة (ذهبية)</label>
                <span class="char-count" data-for="highlight" data-max="60">0 / 60</span>
              </div>
              <input id="highlight" name="highlight" type="text" maxlength="60"
                     value="<?= e($data['highlight']) ?>"
                     placeholder="عالم الديكور">
              <div class="fld-hint">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
                </svg>
                تظهر بلون ذهبي لامع في العنوان.
              </div>
            </div>

            <div class="fld">
              <div class="fld-head">
                <label for="title">بقية العنوان <span style="color:var(--red)">*</span></label>
                <span class="char-count" data-for="title" data-max="200">0 / 200</span>
              </div>
              <input id="title" name="title" type="text" required maxlength="200"
                     value="<?= e($data['title']) ?>"
                     placeholder="نصنع لبيتك ومكانك مظهراً يليق بك">
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Subtitle -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="17" y1="10" x2="3" y2="10"/>
              <line x1="21" y1="6" x2="3" y2="6"/>
              <line x1="21" y1="14" x2="3" y2="14"/>
              <line x1="17" y1="18" x2="3" y2="18"/>
            </svg>
          </span>
          <h3>النص الفرعي</h3>
          <small>Subtitle</small>
        </div>
        <div class="sec-card-body">
          <div class="fld" style="margin-bottom:0">
            <div class="fld-head">
              <label for="subtitle">الوصف تحت العنوان</label>
              <span class="char-count" data-for="subtitle" data-max="500">0 / 500</span>
            </div>
            <textarea id="subtitle" name="subtitle" rows="4" maxlength="500"
                      placeholder="وصف مختصر للخدمات والمناطق..."><?= e($data['subtitle']) ?></textarea>
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              جملة أو جملتان — أفضل ما بين 100 و250 حرف.
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ============ RIGHT: LIVE PREVIEW ============ -->
    <aside class="edit-side-preview">
      <div class="hero-preview-wrap">
        <span class="hero-preview-label">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          معاينة مباشرة
        </span>
        <div class="hero-preview-card">
          <span class="hp-orb hp-orb-1"></span>
          <span class="hp-orb hp-orb-2"></span>

          <div class="hp-badge" id="pvBadgeWrap" <?= $data['badge'] === '' ? 'hidden' : '' ?>>
            <span class="hp-badge-text" id="pvBadge"><?= e($data['badge']) ?></span>
          </div>

          <h3 class="hp-title" id="pvTitle">
            <span class="hp-highlight" id="pvHighlight"><?= e($data['highlight'] ?: 'الكلمة المميزة') ?></span><span id="pvRest"></span>
          </h3>

          <p class="hp-sub <?= $data['subtitle'] === '' ? 'empty' : '' ?>" id="pvSub">
            <?= e($data['subtitle'] ?: 'النص الفرعي سيظهر هنا بعد الكتابة...') ?>
          </p>
        </div>
      </div>
    </aside>

  </div>

  <!-- Sticky save bar -->
  <div class="save-bar">
    <button type="submit" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
        <polyline points="17 21 17 13 7 13 7 21"/>
        <polyline points="7 3 7 8 15 8"/>
      </svg>
      <span>حفظ التعديلات</span>
    </button>
    <a href="../index.php#home" target="_blank" rel="noopener" class="btn btn-ghost">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
        <polyline points="15 3 21 3 21 9"/>
        <line x1="10" y1="14" x2="21" y2="3"/>
      </svg>
      معاينة الموقع
    </a>
    <div class="grow"></div>
    <span class="save-meta">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
      </svg>
      التغييرات تُطبّق فوراً بعد الحفظ
    </span>
  </div>

</form>

<script>
(function () {
  'use strict';

  /* Char counters */
  document.querySelectorAll('.char-count').forEach(function (el) {
    var input = document.getElementById(el.dataset.for);
    if (!input) return;
    var max = parseInt(el.dataset.max, 10) || 0;
    function update() {
      var n = input.value.length;
      el.textContent = n + ' / ' + max;
      el.classList.toggle('near-limit', n > max * 0.9 && n <= max);
      el.classList.toggle('at-limit', n > max);
    }
    update();
    input.addEventListener('input', update);
  });

  /* Live preview */
  var inBadge     = document.getElementById('badge');
  var inHighlight = document.getElementById('highlight');
  var inTitle     = document.getElementById('title');
  var inSub       = document.getElementById('subtitle');

  var pvBadgeWrap = document.getElementById('pvBadgeWrap');
  var pvBadge     = document.getElementById('pvBadge');
  var pvHighlight = document.getElementById('pvHighlight');
  var pvRest      = document.getElementById('pvRest');
  var pvSub       = document.getElementById('pvSub');
  var pvTitle     = document.getElementById('pvTitle');

  function update() {
    /* Badge */
    var b = inBadge.value.trim();
    pvBadge.textContent = b;
    pvBadgeWrap.hidden = b === '';

    /* Title */
    var hl = inHighlight.value.trim();
    var rest = inTitle.value.trim();
    pvHighlight.textContent = hl || 'الكلمة المميزة';

    if (hl && rest) {
      pvRest.textContent = '.. ' + rest;
    } else if (rest) {
      pvRest.textContent = rest;
    } else {
      pvRest.textContent = '';
    }
    if (!hl && !rest) pvHighlight.textContent = 'عنوان القسم الرئيسي';

    /* Sub */
    var s = inSub.value.trim();
    pvSub.textContent = s || 'النص الفرعي سيظهر هنا بعد الكتابة...';
    pvSub.classList.toggle('empty', s === '');
  }

  [inBadge, inHighlight, inTitle, inSub].forEach(function (el) {
    if (el) el.addEventListener('input', update);
  });

  update();
})();
</script>

<?php include 'includes/footer.php'; ?>