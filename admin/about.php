<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

$errors = [];

$row = $pdo->query('SELECT * FROM about_content LIMIT 1')->fetch();
if (!$row) {
    $pdo->exec("INSERT INTO about_content (chip, title, highlight, paragraph1, paragraph2, points, vision, mission, goals, image)
                VALUES ('', '', '', '', '', '', '', '', '', '')");
    $row = $pdo->query('SELECT * FROM about_content LIMIT 1')->fetch();
}

$data = [
    'chip'       => $row['chip']       ?? '',
    'title'      => $row['title']      ?? '',
    'highlight'  => $row['highlight']  ?? '',
    'paragraph1' => $row['paragraph1'] ?? '',
    'paragraph2' => $row['paragraph2'] ?? '',
    'points'     => $row['points']     ?? '',
    'vision'     => $row['vision']     ?? '',
    'mission'    => $row['mission']    ?? '',
    'goals'      => $row['goals']      ?? '',
    'image'      => $row['image']      ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $data['chip']       = trim((string)($_POST['chip']        ?? ''));
    $data['title']      = trim((string)($_POST['title']       ?? ''));
    $data['highlight']  = trim((string)($_POST['highlight']   ?? ''));
    $data['paragraph1'] = trim((string)($_POST['paragraph1']  ?? ''));
    $data['paragraph2'] = trim((string)($_POST['paragraph2']  ?? ''));
    $data['points']     = trim((string)($_POST['points']      ?? ''));
    $data['vision']     = trim((string)($_POST['vision']      ?? ''));
    $data['mission']    = trim((string)($_POST['mission']     ?? ''));
    $data['goals']      = trim((string)($_POST['goals']       ?? ''));

    /* Image upload */
    if (!empty($_FILES['image']['name'])) {
        $up = uploadImage($_FILES['image'], 'about');
        if ($up) {
            if (!empty($data['image']) && $data['image'] !== $up) {
                deleteUploadedFile($data['image']);
            }
            $data['image'] = $up;
        } else {
            $errors[] = 'فشل رفع الصورة (jpg/png/webp — أقل من 6MB).';
        }
    }

    if (!empty($_POST['remove_image']) && !empty($data['image'])) {
        deleteUploadedFile($data['image']);
        $data['image'] = '';
    }

    if ($data['title'] === '')     $errors[] = 'العنوان مطلوب.';
    if (mb_strlen($data['title']) > 200)     $errors[] = 'العنوان طويل جداً.';
    if (mb_strlen($data['paragraph1']) > 1000) $errors[] = 'الفقرة الأولى طويلة جداً.';
    if (mb_strlen($data['paragraph2']) > 1000) $errors[] = 'الفقرة الثانية طويلة جداً.';
    if (mb_strlen($data['vision']) > 500)    $errors[] = 'الرؤية طويلة جداً.';
    if (mb_strlen($data['mission']) > 500)   $errors[] = 'الرسالة طويلة جداً.';
    if (mb_strlen($data['goals']) > 500)     $errors[] = 'الأهداف طويلة جداً.';

    if ($data['highlight'] !== '' && $data['title'] !== ''
        && mb_strpos($data['title'], $data['highlight']) === false) {
        $errors[] = 'الكلمة المميزة يجب أن تكون موجودة داخل العنوان.';
    }

    if (empty($errors)) {
        $pdo->prepare('UPDATE about_content SET
            chip=?, title=?, highlight=?, paragraph1=?, paragraph2=?, points=?,
            vision=?, mission=?, goals=?, image=? WHERE id=?')
            ->execute([
                $data['chip'], $data['title'], $data['highlight'],
                $data['paragraph1'], $data['paragraph2'], $data['points'],
                $data['vision'], $data['mission'], $data['goals'],
                $data['image'], $row['id']
            ]);
        setFlash('ok', 'تم تحديث قسم "من نحن" بنجاح.');
        redirect('about.php');
    } else {
        setFlash('err', implode(' ', $errors));
    }
}

$aboutPoints = array_filter(array_map('trim', explode("\n", $data['points'])));

$pageTitle = 'من نحن';
include 'includes/header.php';
?>

<section class="edit-hero-banner">
  <div class="ehb-info">
    <span class="ehb-chip">من نحن</span>
    <h2>تحديث <span>قصة العلامة التجارية</span></h2>
    <p>حرّر النصوص والصورة التي تعرّف بشركتك وتظهر في قسم "من نحن".</p>
  </div>
  <a href="../index.php#about" target="_blank" rel="noopener" class="ehb-action">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
      <polyline points="15 3 21 3 21 9"/>
      <line x1="10" y1="14" x2="21" y2="3"/>
    </svg>
    عرض في الموقع
  </a>
</section>

<form method="post" enctype="multipart/form-data" novalidate>
  <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">

  <div class="edit-content-grid">

    <!-- ============ LEFT: FORM ============ -->
    <div>

      <!-- Section 1: Heading -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 7V4h16v3"/>
              <line x1="9" y1="20" x2="15" y2="20"/>
              <line x1="12" y1="4" x2="12" y2="20"/>
            </svg>
          </span>
          <h3>العنوان والشارة</h3>
        </div>
        <div class="sec-card-body">
          <div class="fld">
            <div class="fld-head">
              <label for="chip">الشارة</label>
              <span class="char-count" data-for="chip" data-max="80">0 / 80</span>
            </div>
            <input id="chip" name="chip" type="text" maxlength="80"
                   value="<?= e($data['chip']) ?>"
                   placeholder="مثال: من نحن">
          </div>

          <div class="fld">
            <div class="fld-head">
              <label for="title">العنوان الكامل <span style="color:var(--red)">*</span></label>
              <span class="char-count" data-for="title" data-max="200">0 / 200</span>
            </div>
            <input id="title" name="title" type="text" required maxlength="200"
                   value="<?= e($data['title']) ?>"
                   placeholder="عالم الديكور لأعمال الجبس بورد والدهانات في عسير">
          </div>

          <div class="fld" style="margin-bottom:0">
            <div class="fld-head">
              <label for="highlight">الكلمة المميزة (داخل العنوان)</label>
              <span class="char-count" data-for="highlight" data-max="60">0 / 60</span>
            </div>
            <input id="highlight" name="highlight" type="text" maxlength="60"
                   value="<?= e($data['highlight']) ?>"
                   placeholder="الجبس بورد">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              اكتبي كلمة موجودة حرفياً داخل العنوان — ستظهر بخط تركوازي تحتها.
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Paragraphs -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
              <path d="M14 2v6h6"/>
              <path d="M8 13h8M8 17h5"/>
            </svg>
          </span>
          <h3>الفقرات التعريفية</h3>
        </div>
        <div class="sec-card-body">
          <div class="fld">
            <div class="fld-head">
              <label for="paragraph1">الفقرة الأولى</label>
              <span class="char-count" data-for="paragraph1" data-max="1000">0 / 1000</span>
            </div>
            <textarea id="paragraph1" name="paragraph1" rows="4" maxlength="1000"
                      placeholder="من نحن وما الذي نقدمه..."><?= e($data['paragraph1']) ?></textarea>
          </div>

          <div class="fld" style="margin-bottom:0">
            <div class="fld-head">
              <label for="paragraph2">الفقرة الثانية</label>
              <span class="char-count" data-for="paragraph2" data-max="1000">0 / 1000</span>
            </div>
            <textarea id="paragraph2" name="paragraph2" rows="4" maxlength="1000"
                      placeholder="تفاصيل إضافية..."><?= e($data['paragraph2']) ?></textarea>
          </div>
        </div>
      </div>

      <!-- Section 3: Points -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 11 12 14 22 4"/>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
            </svg>
          </span>
          <h3>نقاط سريعة</h3>
          <small>سطر لكل نقطة</small>
        </div>
        <div class="sec-card-body">
          <div class="fld" style="margin-bottom:0">
            <label for="points">النقاط (سطر لكل نقطة)</label>
            <textarea id="points" name="points" rows="5"
                      placeholder="أسقف وديكورات جبس بورد&#10;دهانات داخلية وخارجية&#10;إضاءة مخفية وكرانيش&#10;ترميم وصيانة وتجديد"><?= e($data['points']) ?></textarea>
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4"/>
              </svg>
              كل سطر يظهر كنقطة مع علامة ✓ تركوازية.
            </div>
          </div>
        </div>
      </div>

      <!-- Section 4: Vision / Mission / Goals (Tabs) -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <circle cx="12" cy="12" r="6"/>
              <circle cx="12" cy="12" r="2"/>
            </svg>
          </span>
          <h3>الرؤية والرسالة والأهداف</h3>
        </div>
        <div class="sec-card-body">

          <div class="mini-tabs" role="tablist">
            <button type="button" class="mini-tab active" data-tab="vision" role="tab">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              رؤيتنا
            </button>
            <button type="button" class="mini-tab" data-tab="mission" role="tab">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
              </svg>
              رسالتنا
            </button>
            <button type="button" class="mini-tab" data-tab="goals" role="tab">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
              </svg>
              أهدافنا
            </button>
          </div>

          <div class="mini-panel active" data-panel="vision">
            <div class="fld" style="margin-bottom:0">
              <div class="fld-head">
                <label for="vision">الرؤية</label>
                <span class="char-count" data-for="vision" data-max="500">0 / 500</span>
              </div>
              <textarea id="vision" name="vision" rows="3" maxlength="500"
                        placeholder="أن نكون الخيار الأول..."><?= e($data['vision']) ?></textarea>
            </div>
          </div>

          <div class="mini-panel" data-panel="mission">
            <div class="fld" style="margin-bottom:0">
              <div class="fld-head">
                <label for="mission">الرسالة</label>
                <span class="char-count" data-for="mission" data-max="500">0 / 500</span>
              </div>
              <textarea id="mission" name="mission" rows="3" maxlength="500"
                        placeholder="تحويل المساحات..."><?= e($data['mission']) ?></textarea>
            </div>
          </div>

          <div class="mini-panel" data-panel="goals">
            <div class="fld" style="margin-bottom:0">
              <div class="fld-head">
                <label for="goals">الأهداف</label>
                <span class="char-count" data-for="goals" data-max="500">0 / 500</span>
              </div>
              <textarea id="goals" name="goals" rows="3" maxlength="500"
                        placeholder="تقديم أعمال متقنة..."><?= e($data['goals']) ?></textarea>
            </div>
          </div>

        </div>
      </div>

      <!-- Section 5: Image -->
      <div class="sec-card">
        <div class="sec-card-head">
          <span class="sec-ico">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <path d="M21 15l-5-5L5 21"/>
            </svg>
          </span>
          <h3>صورة القسم</h3>
        </div>
        <div class="sec-card-body">

          <div class="img-up" id="uploader">
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" id="fileInput">
            <input type="hidden" name="remove_image" id="removeImage" value="0">

            <div class="img-up-empty" id="imgEmpty" <?= !empty($data['image']) ? 'hidden' : '' ?>>
              <span class="up-ico">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="17 8 12 3 7 8"/>
                  <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
              </span>
              <b>اسحب الصورة هنا أو اضغط للاختيار</b>
              <small>JPG · PNG · WEBP — حتى 6 ميجابايت</small>
            </div>

            <div class="img-up-preview" id="imgPreview" <?= empty($data['image']) ? 'hidden' : '' ?>>
              <img id="previewImg" src="<?= !empty($data['image']) ? '../' . e($data['image']) : '' ?>" alt="">
              <div class="img-up-preview-actions">
                <button type="button" id="changeImg">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
                  </svg>
                  تغيير
                </button>
                <button type="button" class="danger" id="removeImg">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                  </svg>
                  إزالة
                </button>
              </div>
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

        <div class="about-preview-card">
          <div class="ap-img" id="apImgWrap">
            <?php if (!empty($data['image'])): ?>
              <img id="apImg" src="../<?= e($data['image']) ?>" alt="">
            <?php else: ?>
              <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <path d="M21 15l-5-5L5 21"/>
              </svg>
            <?php endif; ?>
          </div>

          <span class="ap-chip" id="apChip" <?= $data['chip'] === '' ? 'hidden' : '' ?>><?= e($data['chip']) ?></span>

          <div class="ap-title" id="apTitle">
            <span id="apBefore"></span><span class="ap-highlight" id="apHighlight"></span><span id="apAfter"></span>
          </div>

          <p class="ap-para <?= $data['paragraph1'] === '' ? 'empty' : '' ?>" id="apPara1">
            <?= e($data['paragraph1'] ?: 'الفقرة الأولى ستظهر هنا...') ?>
          </p>
          <p class="ap-para <?= $data['paragraph2'] === '' ? 'empty' : '' ?>" id="apPara2">
            <?= e($data['paragraph2'] ?: 'الفقرة الثانية ستظهر هنا...') ?>
          </p>

          <ul class="ap-points" id="apPoints">
            <?php foreach (array_slice($aboutPoints, 0, 4) as $pt): ?>
              <li><?= e($pt) ?></li>
            <?php endforeach; ?>
          </ul>
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
    <a href="../index.php#about" target="_blank" rel="noopener" class="btn btn-ghost">
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

  /* ===== Tabs (vision/mission/goals) ===== */
  document.querySelectorAll('.mini-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');
      document.querySelectorAll('.mini-tab').forEach(function (t) {
        t.classList.toggle('active', t === tab);
      });
      document.querySelectorAll('.mini-panel').forEach(function (p) {
        p.classList.toggle('active', p.getAttribute('data-panel') === target);
      });
    });
  });

  /* ===== Image uploader ===== */
  var uploader   = document.getElementById('uploader');
  var fileInput  = document.getElementById('fileInput');
  var imgEmpty   = document.getElementById('imgEmpty');
  var imgPreview = document.getElementById('imgPreview');
  var previewImg = document.getElementById('previewImg');
  var changeImg  = document.getElementById('changeImg');
  var removeImg  = document.getElementById('removeImg');
  var removeFlag = document.getElementById('removeImage');
  var apImgWrap  = document.getElementById('apImgWrap');

  function showPreview(src) {
    if (previewImg) previewImg.src = src;
    if (imgPreview) imgPreview.hidden = false;
    if (imgEmpty)   imgEmpty.hidden   = true;
    if (apImgWrap) {
      apImgWrap.innerHTML = '';
      var im = document.createElement('img');
      im.src = src;
      im.alt = '';
      apImgWrap.appendChild(im);
    }
  }

  function showEmpty() {
    if (imgPreview) imgPreview.hidden = true;
    if (imgEmpty)   imgEmpty.hidden   = false;
    if (fileInput)  fileInput.value   = '';
    if (apImgWrap) {
      apImgWrap.innerHTML = '<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
    }
  }

  if (uploader) {
    uploader.addEventListener('click', function (e) {
      if (e.target === fileInput) return;
      if (e.target.closest('.img-up-preview-actions')) return;
      if (imgEmpty && !imgEmpty.hidden) fileInput.click();
    });

    ['dragenter', 'dragover'].forEach(function (ev) {
      uploader.addEventListener(ev, function (e) {
        e.preventDefault(); e.stopPropagation();
        uploader.classList.add('drag-over');
      });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      uploader.addEventListener(ev, function (e) {
        e.preventDefault(); e.stopPropagation();
        uploader.classList.remove('drag-over');
      });
    });
    uploader.addEventListener('drop', function (e) {
      var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (f && /^image\//.test(f.type)) {
        fileInput.files = e.dataTransfer.files;
        var r = new FileReader();
        r.onload = function (ev) { showPreview(ev.target.result); };
        r.readAsDataURL(f);
        if (removeFlag) removeFlag.value = '0';
      }
    });

    fileInput.addEventListener('change', function () {
      var f = fileInput.files && fileInput.files[0];
      if (!f) return;
      var r = new FileReader();
      r.onload = function (ev) { showPreview(ev.target.result); };
      r.readAsDataURL(f);
      if (removeFlag) removeFlag.value = '0';
    });

    if (changeImg) {
      changeImg.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.click();
      });
    }
    if (removeImg) {
      removeImg.addEventListener('click', function (e) {
        e.stopPropagation();
        showEmpty();
        if (removeFlag) removeFlag.value = '1';
      });
    }
  }

  /* ===== Live preview for About ===== */
  var inChip      = document.getElementById('chip');
  var inHighlight = document.getElementById('highlight');
  var inTitle     = document.getElementById('title');
  var inPara1     = document.getElementById('paragraph1');
  var inPara2     = document.getElementById('paragraph2');
  var inPoints    = document.getElementById('points');

  var apChip      = document.getElementById('apChip');
  var apBefore    = document.getElementById('apBefore');
  var apHighlight = document.getElementById('apHighlight');
  var apAfter     = document.getElementById('apAfter');
  var apPara1     = document.getElementById('apPara1');
  var apPara2     = document.getElementById('apPara2');
  var apPoints    = document.getElementById('apPoints');

  function updateAboutPreview() {
    /* Chip */
    var c = inChip.value.trim();
    apChip.textContent = c;
    apChip.hidden = c === '';

    /* Title — highlight داخل title */
    var title = inTitle.value.trim();
    var hl    = inHighlight.value.trim();

    if (!title && !hl) {
      apBefore.textContent    = '';
      apHighlight.textContent = 'العنوان الرئيسي سيظهر هنا';
      apAfter.textContent     = '';
    } else if (!hl) {
      apBefore.textContent    = title;
      apHighlight.textContent = '';
      apAfter.textContent     = '';
    } else if (!title) {
      apBefore.textContent    = '';
      apHighlight.textContent = hl;
      apAfter.textContent     = '';
    } else {
      var idx = title.indexOf(hl);
      if (idx === -1) {
        apBefore.textContent    = title + ' ';
        apHighlight.textContent = hl;
        apAfter.textContent     = '';
      } else {
        apBefore.textContent    = title.substring(0, idx);
        apHighlight.textContent = hl;
        apAfter.textContent     = title.substring(idx + hl.length);
      }
    }

    /* Paragraphs */
    var p1 = inPara1.value.trim();
    apPara1.textContent = p1 || 'الفقرة الأولى ستظهر هنا...';
    apPara1.classList.toggle('empty', p1 === '');

    var p2 = inPara2.value.trim();
    apPara2.textContent = p2 || 'الفقرة الثانية ستظهر هنا...';
    apPara2.classList.toggle('empty', p2 === '');

    /* Points */
    var lines = inPoints.value.split('\n').map(function (l) { return l.trim(); }).filter(Boolean).slice(0, 4);
    apPoints.innerHTML = '';
    lines.forEach(function (line) {
      var li = document.createElement('li');
      li.textContent = line;
      apPoints.appendChild(li);
    });
  }

  [inChip, inHighlight, inTitle, inPara1, inPara2, inPoints].forEach(function (el) {
    if (el) el.addEventListener('input', updateAboutPreview);
  });

  updateAboutPreview();
})();
</script>

<?php include 'includes/footer.php'; ?>