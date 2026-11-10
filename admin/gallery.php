<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* ============ AJAX: reorder / bulk / delete ============ */
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        csrfCheck();
        $action = $_POST['action'] ?? '';

        if ($action === 'reorder') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) throw new Exception('invalid');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE gallery SET sort_order = ? WHERE id = ?');
            foreach (array_values($ids) as $i => $id) {
                $stmt->execute([$i + 1, (int)$id]);
            }
            $pdo->commit();
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('invalid');
            $img = $pdo->query("SELECT image FROM gallery WHERE id = $id")->fetchColumn();
            if ($img) deleteUploadedFile($img);
            $pdo->prepare('DELETE FROM gallery WHERE id = ?')->execute([$id]);
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'bulk_delete') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) throw new Exception('invalid');
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (!$ids) throw new Exception('invalid');
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id IN ($ph)");
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $img) {
                if ($img) deleteUploadedFile($img);
            }
            $pdo->prepare("DELETE FROM gallery WHERE id IN ($ph)")->execute($ids);
            echo json_encode(['ok' => true, 'count' => count($ids)]);
            exit;
        }

        throw new Exception('unknown');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
}

/* ============ POST: multi-upload ============ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['images']['name'][0])) {
    csrfCheck();
    $files = $_FILES['images'];
    $added = 0;
    $failed = 0;
    $nextOrder = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM gallery')->fetchColumn();

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) { $failed++; continue; }
        $single = [
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
            'name'     => $files['name'][$i],
        ];
        $up = uploadImage($single, 'gal');
        if ($up) {
            $pdo->prepare('INSERT INTO gallery (image, alt_text, sort_order) VALUES (?, ?, ?)')
                ->execute([$up, 'معرض أعمالنا', $nextOrder++]);
            $added++;
        } else {
            $failed++;
        }
    }

    if ($added && !$failed) {
        setFlash('ok', "تم رفع {$added} صورة بنجاح.");
    } elseif ($added && $failed) {
        setFlash('ok', "تم رفع {$added} صورة، وفشلت {$failed} صورة.");
    } else {
        setFlash('err', 'لم يتم رفع أي صورة. تحقق من نوع الملف وحجمه.');
    }
    redirect('gallery.php');
}

/* ============ Load gallery ============ */
$rows = $pdo->query('SELECT * FROM gallery ORDER BY sort_order, id')->fetchAll();
$totalImages = count($rows);

$pageTitle = 'معرض الأعمال';
include 'includes/header.php';
?>

<!-- ========== UPLOAD HERO ========== -->
<section class="gal-upload">
  <div class="gal-upload-info">
    <h2>أضف صور <span>لمعرض أعمالك</span></h2>
    <p>ارفع عدة صور في وقت واحد. الصور ستظهر مباشرة في معرض الموقع.</p>
  </div>

  <form method="post" enctype="multipart/form-data" id="uploadForm" class="gal-drop" style="padding:0">
    <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
    <label for="imagesInput" style="display:block;padding:22px 20px;cursor:pointer">
      <span class="dz-ico">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/>
          <line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
      </span>
      <b>اسحب الصور هنا أو اضغط</b>
      <small>JPG · PNG · WEBP — متعدد</small>
    </label>
    <input type="file" name="images[]" id="imagesInput"
           accept="image/jpeg,image/png,image/webp,image/gif" multiple>
  </form>
</section>

<!-- ========== TOOLBAR ========== -->
<?php if ($totalImages > 0): ?>
<div class="gal-toolbar">
  <div class="gt-info">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="18" height="18" rx="2"/>
      <circle cx="8.5" cy="8.5" r="1.5"/>
      <path d="M21 15l-5-5L5 21"/>
    </svg>
    إجمالي الصور <b><?= $totalImages ?></b>
  </div>
  <div class="grow"></div>
  <span class="muted" style="font-size:.82rem;display:inline-flex;align-items:center;gap:6px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="10"/></svg>
    اسحب الصور لإعادة ترتيبها
  </span>
</div>

<!-- ========== SELECT BAR ========== -->
<div class="gal-selbar">
  <label class="mcard-checkbox" aria-label="تحديد الكل">
    <input type="checkbox" id="galSelectAll">
    <span class="cbox"></span>
  </label>
  <span>تحديد الكل</span>
  <span class="sel-count" id="galSelCount"></span>
</div>

<!-- ========== BULK BAR (hidden) ========== -->
<form method="post" id="galBulkForm" class="gal-bulk-bar" autocomplete="off">
  <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
  <span class="bulk-count" id="galBulkCount">0 محدد</span>
  <button type="button" class="danger" id="galBulkDelBtn">
    <span style="display:inline-flex;align-items:center;gap:4px">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
      </svg>
      حذف
    </span>
  </button>
  <button type="button" id="galBulkCancel">إلغاء</button>
</form>

<!-- ========== GRID ========== -->
<div class="gal-masonry" id="galGrid">
  <?php foreach ($rows as $i => $g): ?>
    <div class="gal-tile"
         data-id="<?= (int)$g['id'] ?>"
         data-full="../<?= e($g['image']) ?>"
         style="animation-delay:<?= $i * 0.025 ?>s">

      <img src="../<?= e($g['image']) ?>" alt="<?= e($g['alt_text'] ?: 'معرض') ?>" loading="lazy">

      <span class="gal-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>

      <label class="gal-check" aria-label="تحديد الصورة">
        <input type="checkbox" class="gal-cb" value="<?= (int)$g['id'] ?>">
        <span class="box"></span>
      </label>

      <div class="gal-actions">
        <button type="button" class="gal-act-btn view" data-lightbox>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          <span>عرض</span>
        </button>
        <button type="button" class="gal-act-btn del" data-del="<?= (int)$g['id'] ?>">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
          </svg>
          <span>حذف</span>
        </button>
      </div>

      <button type="button" class="gal-drag" aria-label="اسحب لإعادة الترتيب" tabindex="-1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
          <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
          <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
        </svg>
      </button>
    </div>
  <?php endforeach; ?>
</div>

<!-- ========== LIGHTBOX ========== -->
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="معاينة الصورة">
  <button type="button" class="lb-close" id="lbClose" aria-label="إغلاق">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
      <path d="M18 6L6 18M6 6l12 12"/>
    </svg>
  </button>
  <button type="button" class="lb-nav lb-prev" id="lbPrev" aria-label="السابق">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 18l6-6-6-6"/>
    </svg>
  </button>
  <button type="button" class="lb-nav lb-next" id="lbNext" aria-label="التالي">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
      <path d="M15 18l-6-6 6-6"/>
    </svg>
  </button>
  <div class="lightbox-img-wrap">
    <img id="lbImg" src="" alt="">
  </div>
  <div class="lb-info">
    <span class="lb-counter" id="lbCounter">1 / 1</span>
  </div>
</div>

<?php else: ?>

<!-- ========== EMPTY ========== -->
<div class="card">
  <div class="empty-state">
    <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
      <rect x="3" y="3" width="18" height="18" rx="2"/>
      <circle cx="8.5" cy="8.5" r="1.5"/>
      <path d="M21 15l-5-5L5 21"/>
    </svg>
    <h3>معرض الأعمال فارغ</h3>
    <p>ابدأ برفع صور أعمالك لعرضها في الموقع.</p>
  </div>
</div>

<?php endif; ?>

<script>
(function () {
  'use strict';

  var CSRF = <?= json_encode(csrfToken()) ?>;
  var grid = document.getElementById('galGrid');
  if (!grid) return;

  var tiles    = function () { return Array.prototype.slice.call(grid.children); };
  var selAll   = document.getElementById('galSelectAll');
  var selCount = document.getElementById('galSelCount');
  var bulkForm = document.getElementById('galBulkForm');
  var bulkCnt  = document.getElementById('galBulkCount');
  var bulkDel  = document.getElementById('galBulkDelBtn');
  var bulkCxl  = document.getElementById('galBulkCancel');

  /* ========== SELECTION ========== */
  function updateSelection() {
    var checked = document.querySelectorAll('.gal-cb:checked');
    var n = checked.length;
    var total = document.querySelectorAll('.gal-cb').length;

    if (bulkCnt)  bulkCnt.textContent = n + ' محدد';
    if (bulkForm) bulkForm.classList.toggle('visible', n > 0);
    if (selCount) selCount.textContent = n > 0 ? n + ' محدد' : '';
    if (selAll) {
      selAll.checked = n > 0 && n === total;
      selAll.indeterminate = n > 0 && n < total;
    }
    tiles().forEach(function (tile) {
      var cb = tile.querySelector('.gal-cb');
      tile.classList.toggle('is-selected', !!(cb && cb.checked));
    });
  }

  grid.addEventListener('change', function (e) {
    if (e.target.classList.contains('gal-cb')) updateSelection();
  });

  if (selAll) {
    selAll.addEventListener('change', function () {
      document.querySelectorAll('.gal-cb').forEach(function (cb) {
        cb.checked = selAll.checked;
      });
      updateSelection();
    });
  }

  if (bulkCxl) {
    bulkCxl.addEventListener('click', function () {
      document.querySelectorAll('.gal-cb').forEach(function (cb) { cb.checked = false; });
      updateSelection();
    });
  }

  /* ========== BULK DELETE ========== */
  if (bulkDel) {
    bulkDel.addEventListener('click', function () {
      var ids = Array.prototype.map.call(
        document.querySelectorAll('.gal-cb:checked'),
        function (cb) { return cb.value; }
      );
      if (!ids.length) return;
      if (!window.confirm('حذف ' + ids.length + ' صورة نهائياً؟')) return;

      var fd = new FormData();
      fd.append('_csrf', CSRF);
      fd.append('action', 'bulk_delete');
      ids.forEach(function (id, i) { fd.append('ids[' + i + ']', id); });

      fetch('gallery.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.ok) window.location.reload();
        else window.alert('تعذّر الحذف، حاول مجدداً.');
      })
      .catch(function () { window.alert('خطأ في الاتصال.'); });
    });
  }

  /* ========== SINGLE DELETE ========== */
  grid.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-del]');
    if (!btn) return;
    e.stopPropagation();
    if (!window.confirm('حذف هذه الصورة نهائياً؟')) return;
    var id = btn.getAttribute('data-del');
    var fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('action', 'delete');
    fd.append('id', id);

    fetch('gallery.php', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (res && res.ok) {
        var tile = btn.closest('.gal-tile');
        if (tile) {
          tile.style.transition = 'opacity .3s, transform .3s';
          tile.style.opacity = '0';
          tile.style.transform = 'scale(.85)';
          setTimeout(function () { window.location.reload(); }, 350);
        }
      }
    })
    .catch(function () {});
  });

  /* ========== LIGHTBOX ========== */
  var lb     = document.getElementById('lightbox');
  var lbImg  = document.getElementById('lbImg');
  var lbCnt  = document.getElementById('lbCounter');
  var lbPrev = document.getElementById('lbPrev');
  var lbNext = document.getElementById('lbNext');
  var lbClose= document.getElementById('lbClose');
  var currentIndex = 0;

  function allImages() {
    return tiles().map(function (t) { return t.getAttribute('data-full'); });
  }

  function openLb(index) {
    var imgs = allImages();
    if (!imgs.length) return;
    currentIndex = Math.max(0, Math.min(index, imgs.length - 1));
    lbImg.src = imgs[currentIndex];
    lbCnt.textContent = (currentIndex + 1) + ' / ' + imgs.length;
    lb.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeLb() {
    lb.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  function nav(dir) {
    var imgs = allImages();
    var n = imgs.length;
    if (!n) return;
    currentIndex = (currentIndex + dir + n) % n;
    lbImg.src = imgs[currentIndex];
    lbCnt.textContent = (currentIndex + 1) + ' / ' + n;
  }

  grid.addEventListener('click', function (e) {
    var tile = e.target.closest('.gal-tile');
    if (!tile) return;
    if (e.target.closest('.gal-check') ||
        e.target.closest('.gal-act-btn') ||
        e.target.closest('.gal-drag')) return;

    var idx = tiles().indexOf(tile);
    if (idx > -1) openLb(idx);
  });

  if (lbClose) lbClose.addEventListener('click', closeLb);
  if (lbPrev)  lbPrev.addEventListener('click', function () { nav(-1); });
  if (lbNext)  lbNext.addEventListener('click', function () { nav(1); });
  if (lb) {
    lb.addEventListener('click', function (e) {
      if (e.target === lb) closeLb();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (!lb || !lb.classList.contains('is-open')) return;
    if (e.key === 'Escape') closeLb();
    if (e.key === 'ArrowRight') nav(-1);
    if (e.key === 'ArrowLeft')  nav(1);
  });

  /* ========== DRAG & DROP REORDER ========== */
  var dragEl = null;

  tiles().forEach(function (tile) {
    var handle = tile.querySelector('.gal-drag');
    if (!handle) return;

    handle.addEventListener('mousedown', function () { tile.setAttribute('draggable', 'true'); });
    handle.addEventListener('touchstart', function () { tile.setAttribute('draggable', 'true'); }, { passive: true });

    tile.addEventListener('dragstart', function (e) {
      dragEl = tile;
      tile.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', tile.dataset.id); } catch (err) {}
      }
    });

    tile.addEventListener('dragend', function () {
      tile.classList.remove('is-dragging');
      tile.setAttribute('draggable', 'false');
      tiles().forEach(function (t) {
        t.classList.remove('drop-before', 'drop-after');
      });
      dragEl = null;
    });

    tile.addEventListener('dragover', function (e) {
      if (!dragEl || dragEl === tile) return;
      e.preventDefault();
      var r = tile.getBoundingClientRect();
      var after = (e.clientX - r.left) > r.width / 2; // RTL: right side = before
      // In RTL, "before" in visual order is to the right
      var isRtl = document.documentElement.dir === 'rtl';
      var before = isRtl ? !after : after;
      tile.classList.toggle('drop-before', before);
      tile.classList.toggle('drop-after', !before);
    });

    tile.addEventListener('dragleave', function () {
      tile.classList.remove('drop-before', 'drop-after');
    });

    tile.addEventListener('drop', function (e) {
      if (!dragEl || dragEl === tile) return;
      e.preventDefault();
      var r = tile.getBoundingClientRect();
      var isRtl = document.documentElement.dir === 'rtl';
      var after = (e.clientX - r.left) > r.width / 2;
      var before = isRtl ? !after : after;
      if (before) {
        grid.insertBefore(dragEl, tile);
      } else {
        grid.insertBefore(dragEl, tile.nextSibling);
      }
      tile.classList.remove('drop-before', 'drop-after');
      renumberAndSave();
    });
  });

  function renumberAndSave() {
    var ts = tiles();
    ts.forEach(function (t, i) {
      var num = t.querySelector('.gal-num');
      if (num) num.textContent = String(i + 1).padStart(2, '0');
    });
    var ids = ts.map(function (t) { return t.dataset.id; });
    var fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('action', 'reorder');
    ids.forEach(function (id, i) { fd.append('ids[' + i + ']', id); });

    fetch('gallery.php', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).catch(function () {});
  }

  /* ========== MULTI UPLOAD ========== */
  var uploadForm = document.getElementById('uploadForm');
  var imagesInput = document.getElementById('imagesInput');

  if (uploadForm && imagesInput) {
    // Auto-submit after file pick
    imagesInput.addEventListener('change', function () {
      if (imagesInput.files && imagesInput.files.length) {
        // Show loading state on the drop zone
        var dropEl = imagesInput.closest('.gal-drop');
        if (dropEl) {
          dropEl.style.pointerEvents = 'none';
          dropEl.style.opacity = '.75';
          var b = dropEl.querySelector('b');
          if (b) b.textContent = 'جارٍ الرفع...';
        }
        uploadForm.submit();
      }
    });

    // Drag/drop files onto the drop zone
    var dropEl = imagesInput.closest('.gal-drop');
    if (dropEl) {
      ['dragenter', 'dragover'].forEach(function (ev) {
        dropEl.addEventListener(ev, function (e) {
          e.preventDefault(); e.stopPropagation();
          dropEl.classList.add('drag-over');
        });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        dropEl.addEventListener(ev, function (e) {
          e.preventDefault(); e.stopPropagation();
          dropEl.classList.remove('drag-over');
        });
      });
      dropEl.addEventListener('drop', function (e) {
        var files = e.dataTransfer && e.dataTransfer.files;
        if (files && files.length) {
          imagesInput.files = files;
          // trigger change
          var evt = document.createEvent('HTMLEvents');
          evt.initEvent('change', true, true);
          imagesInput.dispatchEvent(evt);
        }
      });
    }
  }

  updateSelection();
})();
</script>

<?php include 'includes/footer.php'; ?>