<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* ===== Load existing ===== */
$id  = (int)($_GET['id'] ?? 0);
$row = $id ? $pdo->query("SELECT * FROM services WHERE id = $id")->fetch() : null;
if ($id && !$row) {
    setFlash('err', 'الخدمة غير موجودة.');
    redirect('services.php');
}

$errors = [];
$data = [
    'kicker'       => $row['kicker']       ?? '',
    'title'        => $row['title']        ?? '',
    'description1' => $row['description1'] ?? '',
    'description2' => $row['description2'] ?? '',
    'checks'       => $row['checks']       ?? '',
    'image'        => $row['image']        ?? '',
    'is_active'    => $row ? (int)$row['is_active'] : 1,
];

/* ===== Handle POST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $data['kicker']       = trim($_POST['kicker']       ?? '');
    $data['title']        = trim($_POST['title']        ?? '');
    $data['description1'] = trim($_POST['description1'] ?? '');
    $data['description2'] = trim($_POST['description2'] ?? '');
    $data['checks']       = trim($_POST['checks']       ?? '');
    $data['is_active']    = isset($_POST['is_active']) ? 1 : 0;

    if ($data['title'] === '') {
        $errors['title'] = 'عنوان الخدمة مطلوب.';
    } elseif (mb_strlen($data['title']) > 200) {
        $errors['title'] = 'العنوان طويل جداً (200 حرف كحد أقصى).';
    }

    /* Image upload */
    if (!empty($_FILES['image']['name'])) {
        $up = uploadImage($_FILES['image'], 'service');
        if ($up) {
            if (!empty($data['image']) && $data['image'] !== $up) {
                deleteUploadedFile($data['image']);
            }
            $data['image'] = $up;
        } else {
            $errors['image'] = 'فشل رفع الصورة (jpg/png/webp — أقل من 6MB).';
        }
    }

    /* Remove image */
    if (!empty($_POST['remove_image']) && !empty($data['image'])) {
        deleteUploadedFile($data['image']);
        $data['image'] = '';
    }

    if (empty($errors)) {
        if ($row) {
            $stmt = $pdo->prepare('UPDATE services
                SET kicker=?, title=?, description1=?, description2=?, checks=?, image=?, is_active=?
                WHERE id=?');
            $stmt->execute([
                $data['kicker'], $data['title'], $data['description1'],
                $data['description2'], $data['checks'], $data['image'],
                $data['is_active'], $id
            ]);
            setFlash('ok', 'تم تحديث الخدمة بنجاح.');
        } else {
            $nextOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM services')->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO services
                (kicker,title,description1,description2,checks,image,sort_order,is_active)
                VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $data['kicker'], $data['title'], $data['description1'],
                $data['description2'], $data['checks'], $data['image'],
                $nextOrder, $data['is_active']
            ]);
            setFlash('ok', 'تمت إضافة الخدمة بنجاح.');
        }
        redirect('services.php');
    }
}

$pageTitle = $row ? 'تعديل خدمة' : 'إضافة خدمة جديدة';
include 'includes/header.php';
?>

<form method="post" enctype="multipart/form-data" id="svcForm" novalidate>
  <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">

  <div class="edit-layout">

    <!-- ============ LEFT: FORM ============ -->
    <div>

      <!-- Section 1: Basic -->
      <div class="form-section">
        <div class="form-section-head">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h10M4 17h7"/></svg>
          <h3>المعلومات الأساسية</h3>
        </div>

        <div class="grid-2">
          <div class="fld <?= isset($errors['title']) ? 'has-error' : '' ?>">
            <div class="fld-head">
              <label for="title">عنوان الخدمة <span style="color:var(--red)">*</span></label>
              <span class="char-count" data-for="title" data-max="200">0 / 200</span>
            </div>
            <input id="title" name="title" type="text" required
                   value="<?= e($data['title']) ?>"
                   placeholder="مثال: أسقف الجبس بورد المعلقة والمخفية">
            <?php if (isset($errors['title'])): ?>
              <div class="fld-error"><?= e($errors['title']) ?></div>
            <?php endif; ?>
          </div>

          <div class="fld">
            <label for="kicker">التصنيف (kicker)</label>
            <input id="kicker" name="kicker" type="text"
                   value="<?= e($data['kicker']) ?>"
                   placeholder="الجبس بورد / الدهانات / العزل">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
              يظهر كوسم صغير فوق العنوان
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Descriptions -->
      <div class="form-section">
        <div class="form-section-head">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
          <h3>الوصف</h3>
        </div>

        <div class="fld">
          <div class="fld-head">
            <label for="description1">الوصف الأول</label>
            <span class="char-count" data-for="description1" data-max="500">0 / 500</span>
          </div>
          <textarea id="description1" name="description1" rows="3"
                    placeholder="فقرة تمهيدية عن الخدمة..."><?= e($data['description1']) ?></textarea>
        </div>

        <div class="fld">
          <div class="fld-head">
            <label for="description2">الوصف الثاني</label>
            <span class="char-count" data-for="description2" data-max="500">0 / 500</span>
          </div>
          <textarea id="description2" name="description2" rows="3"
                    placeholder="تفاصيل إضافية والطريقة التي تعمل بها..."><?= e($data['description2']) ?></textarea>
        </div>
      </div>

      <!-- Section 3: Features -->
      <div class="form-section">
        <div class="form-section-head">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
          <h3>المميزات</h3>
          <small>سطر لكل ميزة</small>
        </div>

        <div class="fld">
          <label for="checks">نقاط الميزات</label>
          <textarea id="checks" name="checks" rows="5"
                    placeholder="أسقف مستوية وبمستويات&#10;تصاميم دائرية وانسيابية&#10;تجهيز أماكن الإضاءة والمكيفات&#10;للفلل والشقق والمحلات"><?= e($data['checks']) ?></textarea>
          <div class="fld-hint">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/></svg>
            كل سطر يظهر كنقطة مع علامة ✓ في الموقع
          </div>
        </div>
      </div>

      <!-- Section 4: Image -->
      <div class="form-section">
        <div class="form-section-head">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          <h3>صورة الخدمة</h3>
        </div>

        <div class="img-up" id="uploader">
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" id="fileInput">
          <input type="hidden" name="remove_image" id="removeImage" value="0">

          <div class="img-up-empty" id="imgEmpty" <?= !empty($data['image']) ? 'hidden' : '' ?>>
            <span class="up-ico">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            </span>
            <b>اسحب الصورة هنا أو اضغط للاختيار</b>
            <small>JPG · PNG · WEBP — حتى 6 ميجابايت</small>
          </div>

          <div class="img-up-preview" id="imgPreview" <?= empty($data['image']) ? 'hidden' : '' ?>>
            <img id="previewImg" src="<?= !empty($data['image']) ? '../' . e($data['image']) : '' ?>" alt="">
            <div class="img-up-preview-actions">
              <button type="button" id="changeImg">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/></svg>
                تغيير
              </button>
              <button type="button" class="danger" id="removeImg">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                إزالة
              </button>
            </div>
          </div>
        </div>

        <?php if (isset($errors['image'])): ?>
          <div class="alert alert-error" style="margin-top:12px;margin-bottom:0">
            <span class="alert-ico">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
            </span>
            <span><?= e($errors['image']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Section 5: Status -->
      <div class="form-section">
        <div class="form-section-head">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <h3>حالة النشر</h3>
        </div>

        <label class="fld-inline" style="margin:0">
          <input type="checkbox" name="is_active" id="activeCb" <?= $data['is_active'] ? 'checked' : '' ?>>
          <label for="activeCb" style="font-weight:800;font-size:.95rem">مفعّلة وتظهر في الموقع</label>
        </label>
        <div class="fld-hint" style="margin-top:8px">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          عندما تكون غير مفعّلة، لن تظهر هذه الخدمة للزوار في الموقع.
        </div>
      </div>

    </div>

    <!-- ============ RIGHT: LIVE PREVIEW ============ -->
    <aside class="edit-preview">
      <div class="preview-card">
        <div class="preview-card-label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          معاينة مباشرة
        </div>

        <div class="preview-body">
          <div class="preview-media <?= empty($data['image']) ? 'no-img' : '' ?>" id="pvMedia">
            <?php if (!empty($data['image'])): ?>
              <img id="pvImg" src="../<?= e($data['image']) ?>" alt="">
            <?php else: ?>
              <span id="pvImgPlaceholder">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              </span>
            <?php endif; ?>
            <span class="preview-num" id="pvNum">01</span>
          </div>

          <span class="preview-kicker" id="pvKicker" <?= empty($data['kicker']) ? 'hidden' : '' ?>>
            <?= e($data['kicker']) ?>
          </span>

          <h4 class="preview-title" id="pvTitle">
            <?= e($data['title'] ?: 'عنوان الخدمة سيظهر هنا') ?>
          </h4>

          <p class="preview-desc" id="pvDesc">
            <?= e(mb_substr($data['description1'], 0, 180)) ?: 'وصف الخدمة سيظهر هنا بعد الكتابة...' ?>
          </p>

          <ul class="preview-checks" id="pvChecks">
            <?php
              $checkLines = array_filter(array_map('trim', explode("\n", $data['checks'])));
              foreach (array_slice($checkLines, 0, 4) as $line):
            ?>
              <li><?= e($line) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </aside>

  </div>

  <!-- Sticky form actions -->
  <div class="form-actions-bar">
    <button type="submit" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      <span><?= $row ? 'حفظ التعديلات' : 'إنشاء الخدمة' ?></span>
    </button>
    <a href="services.php" class="btn btn-ghost">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      <span>إلغاء</span>
    </a>
    <div class="grow"></div>
    <?php if ($row): ?>
      <a href="../index.php#services" target="_blank" rel="noopener" class="btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        عرض في الموقع
      </a>
    <?php endif; ?>
  </div>
</form>

<script>
(function () {
  'use strict';

  /* ===== Character counters ===== */
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

  /* ===== Image uploader ===== */
  var uploader   = document.getElementById('uploader');
  var fileInput  = document.getElementById('fileInput');
  var imgEmpty   = document.getElementById('imgEmpty');
  var imgPreview = document.getElementById('imgPreview');
  var previewImg = document.getElementById('previewImg');
  var changeImg  = document.getElementById('changeImg');
  var removeImg  = document.getElementById('removeImg');
  var removeFlag = document.getElementById('removeImage');

  function showPreview(src) {
    if (previewImg) previewImg.src = src;
    if (imgPreview) imgPreview.hidden = false;
    if (imgEmpty)   imgEmpty.hidden   = true;
  }
  function showEmpty() {
    if (imgPreview) imgPreview.hidden = true;
    if (imgEmpty)   imgEmpty.hidden   = false;
    if (fileInput)  fileInput.value   = '';
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

  /* ===== Live preview ===== */
  var inpTitle  = document.getElementById('title');
  var inpKicker = document.getElementById('kicker');
  var inpDesc   = document.getElementById('description1');
  var inpChecks = document.getElementById('checks');
  var pvTitle   = document.getElementById('pvTitle');
  var pvKicker  = document.getElementById('pvKicker');
  var pvDesc    = document.getElementById('pvDesc');
  var pvChecks  = document.getElementById('pvChecks');
  var pvMedia   = document.getElementById('pvMedia');

  function updatePreview() {
    if (pvTitle) {
      pvTitle.textContent = inpTitle.value.trim() || 'عنوان الخدمة سيظهر هنا';
    }
    if (pvKicker) {
      var k = inpKicker.value.trim();
      pvKicker.textContent = k;
      pvKicker.hidden = k === '';
    }
    if (pvDesc) {
      var d = inpDesc.value.trim();
      pvDesc.textContent = d ? (d.length > 180 ? d.slice(0, 180) + '…' : d) : 'وصف الخدمة سيظهر هنا بعد الكتابة...';
    }
    if (pvChecks) {
      var lines = inpChecks.value.split('\n').map(function (l) { return l.trim(); }).filter(Boolean).slice(0, 4);
      pvChecks.innerHTML = '';
      lines.forEach(function (line) {
        var li = document.createElement('li');
        li.textContent = line;
        pvChecks.appendChild(li);
      });
    }
  }

  [inpTitle, inpKicker, inpDesc, inpChecks].forEach(function (el) {
    if (el) el.addEventListener('input', updatePreview);
  });

  /* Update preview image on file change */
  if (fileInput) {
    var mo = new MutationObserver(function () {
      if (previewImg && previewImg.src && pvMedia) {
        // sync
        var existing = pvMedia.querySelector('img');
        var placeholder = document.getElementById('pvImgPlaceholder');
        if (!existing) {
          if (placeholder) placeholder.remove();
          var im = document.createElement('img');
          im.id = 'pvImg';
          im.src = previewImg.src;
          im.alt = '';
          pvMedia.insertBefore(im, pvMedia.firstChild);
        } else {
          existing.src = previewImg.src;
        }
        pvMedia.classList.remove('no-img');
      }
    });
    if (imgPreview) mo.observe(imgPreview, { attributes: true, attributeFilter: ['hidden'], subtree: true, childList: true });
  }

  /* Remove preview image on demand */
  if (removeImg) {
    removeImg.addEventListener('click', function () {
      var existing = pvMedia && pvMedia.querySelector('img');
      if (existing) existing.remove();
      if (pvMedia) {
        pvMedia.classList.add('no-img');
        if (!pvMedia.querySelector('#pvImgPlaceholder')) {
          var ph = document.createElement('span');
          ph.id = 'pvImgPlaceholder';
          ph.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
          pvMedia.insertBefore(ph, pvMedia.firstChild);
        }
      }
    });
  }
})();
</script>

<?php include 'includes/footer.php'; ?>