<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* ================= AJAX: reorder & toggle ================= */
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
            $stmt = $pdo->prepare('UPDATE services SET sort_order = ? WHERE id = ?');
            foreach (array_values($ids) as $i => $id) {
                $stmt->execute([$i + 1, (int)$id]);
            }
            $pdo->commit();
            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('invalid');
            $pdo->prepare('UPDATE services SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $new = (int)$pdo->query("SELECT is_active FROM services WHERE id = $id")->fetchColumn();
            echo json_encode(['ok' => true, 'is_active' => $new]);
            exit;
        }

        throw new Exception('unknown action');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
}

/* ================= POST: delete ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    csrfCheck();
    $id = (int)$_POST['delete_id'];
    $img = $pdo->query("SELECT image FROM services WHERE id = $id")->fetchColumn();
    if ($img) deleteUploadedFile($img);
    $pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
    setFlash('ok', 'تم حذف الخدمة بنجاح.');
    redirect('services.php');
}

/* ================= List ================= */
$search = trim((string)($_GET['q'] ?? ''));
$svcList = $search !== ''
    ? (function() use ($pdo, $search) {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare("SELECT * FROM services
                               WHERE title LIKE ? OR description1 LIKE ? OR kicker LIKE ?
                               ORDER BY sort_order, id");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
      })()
    : $pdo->query('SELECT * FROM services ORDER BY sort_order, id')->fetchAll();

$totalActive   = (int)$pdo->query('SELECT COUNT(*) FROM services WHERE is_active=1')->fetchColumn();
$totalInactive = (int)$pdo->query('SELECT COUNT(*) FROM services WHERE is_active=0')->fetchColumn();
$totalAll      = $totalActive + $totalInactive;

$pageTitle = 'الخدمات';
include 'includes/header.php';
?>

<!-- ========== TOOLBAR ========== -->
<div class="page-toolbar">
  <form method="get" class="form-search" action="services.php">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="ابحث في الخدمات...">
    <span class="search-ico">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </span>
  </form>

  <div class="grow"></div>

  <a href="service-edit.php" class="btn btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
    <span>إضافة خدمة جديدة</span>
  </a>
</div>

<!-- ========== MINI STATS ========== -->
<div class="mini-stats">
  <span class="mini-stat gold"><span class="mdot"></span> الكل <b><?= $totalAll ?></b></span>
  <span class="mini-stat green"><span class="mdot"></span> مفعّلة <b><?= $totalActive ?></b></span>
  <span class="mini-stat gray"><span class="mdot"></span> معطّلة <b><?= $totalInactive ?></b></span>
</div>

<?php if (empty($svcList)): ?>

  <!-- ===== Empty state ===== -->
  <div class="card">
    <div class="empty-state">
      <?php if ($search !== ''): ?>
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3>لا توجد نتائج مطابقة</h3>
        <p>لم نجد أي خدمة تطابق "<?= e($search) ?>"</p>
        <a href="services.php" class="btn btn-primary" style="margin-top:16px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
          <span>عرض كل الخدمات</span>
        </a>
      <?php else: ?>
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        <h3>لا توجد خدمات بعد</h3>
        <p>ابدأ بإضافة أول خدمة لعرضها في الموقع.</p>
        <a href="service-edit.php" class="btn btn-primary" style="margin-top:16px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          <span>إضافة أول خدمة</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

<?php else: ?>

  <!-- ========== SERVICES LIST (sortable) ========== -->
  <div class="svc-list" id="svcList">
    <?php foreach ($svcList as $i => $s): ?>
      <div class="svc-card <?= $s['is_active'] ? '' : 'is-inactive' ?>"
           data-id="<?= (int)$s['id'] ?>"
           style="animation-delay:<?= $i * 0.03 ?>s">

        <!-- Drag handle -->
        <button type="button" class="svc-drag" aria-label="اسحب لإعادة الترتيب" tabindex="-1">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
            <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
          </svg>
        </button>

        <!-- Order badge -->
        <span class="svc-order"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>

        <!-- Thumb -->
        <div class="svc-thumb">
          <?php if (!empty($s['image'])): ?>
            <img src="../<?= e($s['image']) ?>" alt="<?= e($s['title']) ?>" loading="lazy">
          <?php else: ?>
            <span class="no-img">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            </span>
          <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="svc-info">
          <?php if (!empty($s['kicker'])): ?>
            <span class="svc-kicker-badge"><?= e($s['kicker']) ?></span>
          <?php endif; ?>
          <div class="svc-title-line"><?= e($s['title']) ?></div>
          <div class="svc-meta">
            <span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="10"/></svg>
              ترتيب #<?= (int)$s['sort_order'] ?>
            </span>
            <span>
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <?= mb_strlen($s['description1'] ?? '') ?> حرف
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="svc-card-actions">
          <!-- Toggle -->
          <label class="toggle" title="<?= $s['is_active'] ? 'مفعّلة — اضغط للتعطيل' : 'معطّلة — اضغط للتفعيل' ?>">
            <input type="checkbox" class="js-toggle" data-id="<?= (int)$s['id'] ?>" <?= $s['is_active'] ? 'checked' : '' ?>>
            <span class="track"></span>
          </label>

          <!-- Edit -->
          <a href="service-edit.php?id=<?= (int)$s['id'] ?>" class="btn-icon" title="تعديل">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/></svg>
          </a>

          <!-- Delete -->
          <form method="post" style="display:inline" data-confirm="هل تريد حذف هذه الخدمة نهائياً؟">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="delete_id" value="<?= (int)$s['id'] ?>">
            <button type="submit" class="btn-icon" style="color:var(--red)" title="حذف">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

  <p class="muted" style="margin-top:14px;font-size:.84rem;display:flex;align-items:center;gap:6px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    اسحب البطاقات لإعادة ترتيبها — الترتيب يُحدّث تلقائياً.
  </p>

  <script>
  (function () {
    'use strict';

    var list  = document.getElementById('svcList');
    if (!list) return;
    var CSRF  = <?= json_encode(csrfToken()) ?>;

    /* ========== Toggle active/inactive ========== */
    list.querySelectorAll('.js-toggle').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var id     = cb.getAttribute('data-id');
        var isOn   = cb.checked;
        var card   = cb.closest('.svc-card');
        cb.disabled = true;

        var fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('action', 'toggle');
        fd.append('id', id);

        fetch('services.php', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.ok) {
            card.classList.toggle('is-inactive', !res.is_active);
            cb.checked = !!res.is_active;
          } else {
            cb.checked = !isOn; // revert
          }
        })
        .catch(function () { cb.checked = !isOn; })
        .finally(function () { cb.disabled = false; });
      });
    });

    /* ========== Drag & drop reorder ========== */
    var dragEl = null;
    var cards  = function () { return Array.prototype.slice.call(list.children); };

    list.querySelectorAll('.svc-card').forEach(function (card) {
      card.setAttribute('draggable', 'false');

      var handle = card.querySelector('.svc-drag');
      if (handle) {
        handle.addEventListener('mousedown', function () { card.setAttribute('draggable', 'true'); });
        handle.addEventListener('touchstart', function () { card.setAttribute('draggable', 'true'); }, { passive: true });
      }

      card.addEventListener('dragstart', function (e) {
        dragEl = card;
        card.classList.add('is-dragging');
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'move';
          try { e.dataTransfer.setData('text/plain', card.dataset.id); } catch (err) {}
        }
      });

      card.addEventListener('dragend', function () {
        card.classList.remove('is-dragging');
        card.setAttribute('draggable', 'false');
        list.querySelectorAll('.svc-card').forEach(function (c) {
          c.classList.remove('drag-over-top', 'drag-over-bottom');
        });
        dragEl = null;
      });

      card.addEventListener('dragover', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var rect = card.getBoundingClientRect();
        var after = (e.clientY - rect.top) > rect.height / 2;
        card.classList.toggle('drag-over-top', !after);
        card.classList.toggle('drag-over-bottom', after);
      });

      card.addEventListener('dragleave', function () {
        card.classList.remove('drag-over-top', 'drag-over-bottom');
      });

      card.addEventListener('drop', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var rect  = card.getBoundingClientRect();
        var after = (e.clientY - rect.top) > rect.height / 2;
        if (after) {
          card.parentNode.insertBefore(dragEl, card.nextSibling);
        } else {
          card.parentNode.insertBefore(dragEl, card);
        }
        card.classList.remove('drag-over-top', 'drag-over-bottom');
        saveOrder();
      });
    });

    function saveOrder() {
      var ids = cards().map(function (c) { return c.dataset.id; });
      // Update order badges immediately
      cards().forEach(function (c, i) {
        var ob = c.querySelector('.svc-order');
        if (ob) ob.textContent = String(i + 1).padStart(2, '0');
      });
      var fd = new FormData();
      fd.append('_csrf', CSRF);
      fd.append('action', 'reorder');
      ids.forEach(function (id, i) { fd.append('ids[' + i + ']', id); });

      fetch('services.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(function () {});
    }
  })();
  </script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>