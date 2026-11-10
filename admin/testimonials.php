<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* ============ AJAX endpoints ============ */
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        csrfCheck();
        $action = $_POST['action'] ?? '';

        if ($action === 'reorder') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || !$ids) throw new Exception('invalid');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE testimonials SET sort_order = ? WHERE id = ?');
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
            $pdo->prepare('UPDATE testimonials SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $new = (int)$pdo->query("SELECT is_active FROM testimonials WHERE id = $id")->fetchColumn();
            echo json_encode(['ok' => true, 'is_active' => $new]);
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

/* ============ POST: create / update / delete ============ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $pdo->prepare('DELETE FROM testimonials WHERE id = ?')->execute([$id]);
        setFlash('ok', 'تم حذف الرأي.');
        redirect('testimonials.php');
    }

    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim((string)($_POST['name'] ?? ''));
    $body   = trim((string)($_POST['body'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (mb_strlen($name) < 2)   $errors[] = 'اسم العميل مطلوب (حرفان على الأقل).';
    if (mb_strlen($body) < 5)   $errors[] = 'نص الرأي قصير جداً.';
    if (mb_strlen($body) > 800) $errors[] = 'نص الرأي طويل جداً (800 حرف كحد أقصى).';

    if ($errors) {
        setFlash('err', implode(' ', $errors));
        redirect($id ? "testimonials.php?edit=$id" : 'testimonials.php');
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE testimonials SET name=?, body=?, is_active=? WHERE id=?')
            ->execute([$name, $body, $active, $id]);
        setFlash('ok', 'تم تحديث الرأي بنجاح.');
    } else {
        $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM testimonials')->fetchColumn();
        $pdo->prepare('INSERT INTO testimonials (name, body, sort_order, is_active) VALUES (?,?,?,?)')
            ->execute([$name, $body, $next, $active]);
        setFlash('ok', 'تمت إضافة الرأي بنجاح.');
    }
    redirect('testimonials.php');
}

/* ============ Load ============ */
$search = trim((string)($_GET['q'] ?? ''));
$list = $search !== ''
    ? (function() use ($pdo, $search) {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare('SELECT * FROM testimonials WHERE name LIKE ? OR body LIKE ? ORDER BY sort_order, id');
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
      })()
    : $pdo->query('SELECT * FROM testimonials ORDER BY sort_order, id')->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? $pdo->query("SELECT * FROM testimonials WHERE id = $editId")->fetch() : null;
if ($editId && !$edit) {
    setFlash('err', 'الرأي غير موجود.');
    redirect('testimonials.php');
}

/* Current form values */
$form = [
    'id'        => $edit['id']        ?? 0,
    'name'      => $edit['name']      ?? '',
    'body'      => $edit['body']      ?? '',
    'is_active' => $edit ? (int)$edit['is_active'] : 1,
];

$totalAll      = (int)$pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
$totalActive   = (int)$pdo->query('SELECT COUNT(*) FROM testimonials WHERE is_active=1')->fetchColumn();
$totalInactive = $totalAll - $totalActive;

$pageTitle = 'آراء العملاء';
include 'includes/header.php';
?>

<!-- ========== COMPOSE CARD (Add / Edit) ========== -->
<section class="tst-compose <?= $edit ? 'is-editing' : '' ?>">
  <header class="tst-compose-head">
    <span class="compose-ico">
      <?php if ($edit): ?>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
        </svg>
      <?php else: ?>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
      <?php endif; ?>
    </span>
    <div class="tst-compose-title">
      <h3>
        <?= $edit ? 'تعديل رأي عميل' : 'إضافة رأي جديد' ?>
        <?php if ($edit): ?><span class="edit-pill">تعديل</span><?php endif; ?>
      </h3>
      <p><?= $edit ? 'قم بتحديث بيانات الرأي ثم احفظ' : 'أضف رأياً جديداً لعرضه في الصفحة الرئيسية' ?></p>
    </div>
    <?php if ($edit): ?>
      <a href="testimonials.php" class="btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
        إلغاء التعديل
      </a>
    <?php endif; ?>
  </header>

  <div class="tst-compose-body">
    <form method="post" class="tst-form" id="tstForm">
      <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

      <div class="fld">
        <div class="fld-head">
          <label for="tstName">اسم العميل <span style="color:var(--red)">*</span></label>
          <span class="char-count" data-for="tstName" data-max="100">0 / 100</span>
        </div>
        <input id="tstName" name="name" type="text" required maxlength="100"
               value="<?= e($form['name']) ?>"
               placeholder="مثال: عبدالله القحطاني">
      </div>

      <div class="fld">
        <div class="fld-head">
          <label for="tstBody">نص الرأي <span style="color:var(--red)">*</span></label>
          <span class="char-count" data-for="tstBody" data-max="800">0 / 800</span>
        </div>
        <textarea id="tstBody" name="body" rows="4" required maxlength="800"
                  placeholder="نص رأي العميل..."><?= e($form['body']) ?></textarea>
        <div class="fld-hint">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
          </svg>
          أفضل الآراء ما بين 60 و200 حرف.
        </div>
      </div>

      <label class="fld-inline" style="margin-bottom:0">
        <input type="checkbox" name="is_active" id="tstActive" <?= $form['is_active'] ? 'checked' : '' ?>>
        <label for="tstActive">مفعّل ويظهر في الموقع</label>
      </label>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <?php if ($edit): ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            <span>حفظ التعديلات</span>
          <?php else: ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 5v14M5 12h14"/>
            </svg>
            <span>إضافة الرأي</span>
          <?php endif; ?>
        </button>
        <?php if ($edit): ?>
          <a href="testimonials.php" class="btn btn-ghost">إلغاء</a>
        <?php else: ?>
          <button type="reset" class="btn btn-ghost">مسح</button>
        <?php endif; ?>
      </div>
    </form>

    <div class="tst-preview-wrap">
      <span class="tst-preview-label">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
        </svg>
        معاينة مباشرة
      </span>
      <div class="tst-preview">
        <div class="tst-preview-body" id="pvBody">نص الرأي سيظهر هنا...</div>
        <div class="tst-preview-name">
          <b id="pvName">اسم العميل</b>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== TOOLBAR ========== -->
<div class="tst-toolbar">
  <form method="get" class="search-box" action="testimonials.php">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="ابحث في الآراء أو الأسماء...">
    <span class="search-ico">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
    </span>
  </form>
  <div class="grow"></div>
  <span class="mini-stat gold"><span class="mdot"></span> الكل <b><?= $totalAll ?></b></span>
  <span class="mini-stat green"><span class="mdot"></span> مفعّلة <b><?= $totalActive ?></b></span>
  <span class="mini-stat gray"><span class="mdot"></span> معطّلة <b><?= $totalInactive ?></b></span>
</div>

<!-- ========== LIST ========== -->
<?php if (!$list): ?>

  <div class="tst-empty-admin">
    <?php if ($search !== ''): ?>
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <h3>لا توجد نتائج مطابقة</h3>
      <p>لم نجد أي رأي يطابق "<?= e($search) ?>"</p>
      <a href="testimonials.php" class="btn-sm btn-primary" style="margin-top:14px">عرض كل الآراء</a>
    <?php else: ?>
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <h3>لا توجد آراء بعد</h3>
      <p>ابدأ بإضافة أول رأي عميل لعرضه في الموقع.</p>
    <?php endif; ?>
  </div>

<?php else: ?>

  <div class="tst-list" id="tstList">
    <?php foreach ($list as $i => $t): ?>
      <div class="tst-card-admin <?= $t['is_active'] ? '' : 'is-inactive' ?>"
           data-id="<?= (int)$t['id'] ?>"
           style="animation-delay:<?= $i * 0.03 ?>s">

        <button type="button" class="tst-drag" aria-label="سحب" tabindex="-1">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
            <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
          </svg>
        </button>

        <span class="tst-order"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>

        <div style="display:flex;gap:12px;align-items:flex-start;min-width:0">
          <span class="tst-avatar-admin"><?= e(mb_substr($t['name'], 0, 1)) ?></span>
          <div class="tst-body-admin">
            <div class="tst-name-line">
              <b><?= e($t['name']) ?></b>
              <?php if (!$t['is_active']): ?>
                <span class="pill">معطّل</span>
              <?php endif; ?>
            </div>
            <p class="tst-quote"><?= e($t['body']) ?></p>
          </div>
        </div>

        <div class="tst-actions-admin">
          <label class="toggle" title="<?= $t['is_active'] ? 'مفعّل' : 'معطّل' ?>">
            <input type="checkbox" class="js-toggle" data-id="<?= (int)$t['id'] ?>" <?= $t['is_active'] ? 'checked' : '' ?>>
            <span class="track"></span>
          </label>

          <a href="testimonials.php?edit=<?= (int)$t['id'] ?>" class="btn-icon" title="تعديل">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
            </svg>
          </a>

          <form method="post" style="display:inline" data-confirm="حذف هذا الرأي نهائياً؟">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="delete_id" value="<?= (int)$t['id'] ?>">
            <button type="submit" class="btn-icon" style="color:var(--red)" title="حذف">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              </svg>
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="muted" style="margin-top:14px;font-size:.84rem;display:flex;align-items:center;gap:6px">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
    اسحب البطاقات لإعادة ترتيبها — الترتيب يُحدّث تلقائياً.
  </p>

  <script>
  (function () {
    'use strict';

    var list  = document.getElementById('tstList');
    if (!list) return;
    var CSRF  = <?= json_encode(csrfToken()) ?>;

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

    /* ===== Live preview ===== */
    var inpName = document.getElementById('tstName');
    var inpBody = document.getElementById('tstBody');
    var pvName  = document.getElementById('pvName');
    var pvBody  = document.getElementById('pvBody');

    function updatePreview() {
      if (pvName) pvName.textContent = inpName.value.trim() || 'اسم العميل';
      if (pvBody) pvBody.textContent = inpBody.value.trim() || 'نص الرأي سيظهر هنا...';
    }
    if (inpName) inpName.addEventListener('input', updatePreview);
    if (inpBody) inpBody.addEventListener('input', updatePreview);

    /* ===== Toggle active ===== */
    list.querySelectorAll('.js-toggle').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var id     = cb.getAttribute('data-id');
        var isOn   = cb.checked;
        var card   = cb.closest('.tst-card-admin');
        cb.disabled = true;

        var fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('action', 'toggle');
        fd.append('id', id);

        fetch('testimonials.php', {
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
            cb.checked = !isOn;
          }
        })
        .catch(function () { cb.checked = !isOn; })
        .finally(function () { cb.disabled = false; });
      });
    });

    /* ===== Drag reorder ===== */
    var dragEl = null;
    var cards = function () { return Array.prototype.slice.call(list.children); };

    cards().forEach(function (card) {
      card.setAttribute('draggable', 'false');
      var handle = card.querySelector('.tst-drag');
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
        cards().forEach(function (c) { c.classList.remove('drop-top', 'drop-bottom'); });
        dragEl = null;
      });

      card.addEventListener('dragover', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var r = card.getBoundingClientRect();
        var after = (e.clientY - r.top) > r.height / 2;
        card.classList.toggle('drop-top', !after);
        card.classList.toggle('drop-bottom', after);
      });

      card.addEventListener('dragleave', function () {
        card.classList.remove('drop-top', 'drop-bottom');
      });

      card.addEventListener('drop', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var r = card.getBoundingClientRect();
        var after = (e.clientY - r.top) > r.height / 2;
        if (after) list.insertBefore(dragEl, card.nextSibling);
        else       list.insertBefore(dragEl, card);
        card.classList.remove('drop-top', 'drop-bottom');
        saveOrder();
      });
    });

    function saveOrder() {
      var ids = cards().map(function (c) { return c.dataset.id; });
      cards().forEach(function (c, i) {
        var ob = c.querySelector('.tst-order');
        if (ob) ob.textContent = String(i + 1).padStart(2, '0');
      });
      var fd = new FormData();
      fd.append('_csrf', CSRF);
      fd.append('action', 'reorder');
      ids.forEach(function (id, i) { fd.append('ids[' + i + ']', id); });
      fetch('testimonials.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(function () {});
    }
  })();
  </script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>