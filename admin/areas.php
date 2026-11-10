<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* ============ AJAX: reorder / toggle ============ */
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
            $stmt = $pdo->prepare('UPDATE areas SET sort_order = ? WHERE id = ?');
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
            $pdo->prepare('UPDATE areas SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $new = (int)$pdo->query("SELECT is_active FROM areas WHERE id = $id")->fetchColumn();
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
        $pdo->prepare('DELETE FROM areas WHERE id = ?')->execute([$id]);
        setFlash('ok', 'تم حذف المنطقة.');
        redirect('areas.php');
    }

    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim((string)($_POST['name'] ?? ''));
    $desc   = trim((string)($_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (mb_strlen($name) < 2)   $errors[] = 'اسم المنطقة مطلوب.';
    if (mb_strlen($name) > 100) $errors[] = 'اسم المنطقة طويل جداً.';
    if (mb_strlen($desc) > 500) $errors[] = 'الوصف طويل جداً (500 حرف كحد أقصى).';

    /* Check uniqueness */
    if (empty($errors)) {
        $checkSql = $id ? 'SELECT COUNT(*) FROM areas WHERE name = ? AND id <> ?' : 'SELECT COUNT(*) FROM areas WHERE name = ?';
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute($id ? [$name, $id] : [$name]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'هذه المنطقة موجودة بالفعل.';
        }
    }

    if ($errors) {
        setFlash('err', implode(' ', $errors));
        redirect($id ? "areas.php?edit=$id" : 'areas.php');
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE areas SET name=?, description=?, is_active=? WHERE id=?')
            ->execute([$name, $desc, $active, $id]);
        setFlash('ok', 'تم تحديث المنطقة بنجاح.');
    } else {
        $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM areas')->fetchColumn();
        $pdo->prepare('INSERT INTO areas (name, description, sort_order, is_active) VALUES (?,?,?,?)')
            ->execute([$name, $desc, $next, $active]);
        setFlash('ok', 'تمت إضافة المنطقة بنجاح.');
    }
    redirect('areas.php');
}

/* ============ Load ============ */
$search = trim((string)($_GET['q'] ?? ''));
$list = $search !== ''
    ? (function() use ($pdo, $search) {
        $like = '%' . $search . '%';
        $stmt = $pdo->prepare('SELECT * FROM areas WHERE name LIKE ? OR description LIKE ? ORDER BY sort_order, id');
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll();
      })()
    : $pdo->query('SELECT * FROM areas ORDER BY sort_order, id')->fetchAll();

$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? $pdo->query("SELECT * FROM areas WHERE id = $editId")->fetch() : null;
if ($editId && !$edit) {
    setFlash('err', 'المنطقة غير موجودة.');
    redirect('areas.php');
}

$form = [
    'id'          => $edit['id']          ?? 0,
    'name'        => $edit['name']        ?? '',
    'description' => $edit['description'] ?? '',
    'is_active'   => $edit ? (int)$edit['is_active'] : 1,
];

$totalAll      = (int)$pdo->query('SELECT COUNT(*) FROM areas')->fetchColumn();
$totalActive   = (int)$pdo->query('SELECT COUNT(*) FROM areas WHERE is_active=1')->fetchColumn();
$totalInactive = $totalAll - $totalActive;

$pageTitle = 'مناطق الخدمة';
include 'includes/header.php';
?>

<!-- ========== COMPOSE CARD ========== -->
<section class="area-compose <?= $edit ? 'is-editing' : '' ?>">
  <header class="area-compose-head">
    <span class="compose-ico">
      <?php if ($edit): ?>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
        </svg>
      <?php else: ?>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
      <?php endif; ?>
    </span>
    <div class="area-compose-title">
      <h3>
        <?= $edit ? 'تعديل منطقة' : 'إضافة منطقة خدمة جديدة' ?>
        <?php if ($edit): ?><span class="edit-pill">تعديل</span><?php endif; ?>
      </h3>
      <p><?= $edit ? 'قم بتحديث بيانات المنطقة ثم احفظ' : 'أضف منطقة جديدة لعرضها في قسم مناطق الخدمة' ?></p>
    </div>
    <?php if ($edit): ?>
      <a href="areas.php" class="btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6L6 18M6 6l12 12"/>
        </svg>
        إلغاء
      </a>
    <?php endif; ?>
  </header>

  <div class="area-compose-body">
    <form method="post" class="area-form">
      <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

      <div class="fld">
        <div class="fld-head">
          <label for="areaName">اسم المنطقة <span style="color:var(--red)">*</span></label>
          <span class="char-count" data-for="areaName" data-max="100">0 / 100</span>
        </div>
        <input id="areaName" name="name" type="text" required maxlength="100"
               value="<?= e($form['name']) ?>"
               placeholder="مثال: خميس مشيط">
        <div class="fld-hint">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
          </svg>
          يُفضّل أن يكون الاسم مطابقاً لاسم المدينة/الحي الرسمي.
        </div>
      </div>

      <div class="fld">
        <div class="fld-head">
          <label for="areaDesc">الوصف المختصر</label>
          <span class="char-count" data-for="areaDesc" data-max="500">0 / 500</span>
        </div>
        <textarea id="areaDesc" name="description" rows="3" maxlength="500"
                  placeholder="مثال: جبس بورد ودهانات للفلل والشقق والمحلات في خميس مشيط وأحيائها."><?= e($form['description']) ?></textarea>
      </div>

      <label class="fld-inline" style="margin-bottom:0">
        <input type="checkbox" name="is_active" id="areaActive" <?= $form['is_active'] ? 'checked' : '' ?>>
        <label for="areaActive">مفعّلة وتظهر في الموقع</label>
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
            <span>إضافة المنطقة</span>
          <?php endif; ?>
        </button>
        <?php if ($edit): ?>
          <a href="areas.php" class="btn btn-ghost">إلغاء</a>
        <?php else: ?>
          <button type="reset" class="btn btn-ghost">مسح</button>
        <?php endif; ?>
      </div>
    </form>

    <div class="area-preview-wrap">
      <span class="area-preview-label">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
        </svg>
        معاينة مباشرة
      </span>
      <div class="area-preview">
        <div class="area-preview-ico">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
        </div>
        <div class="area-preview-name" id="pvName"><?= e($form['name'] ?: 'اسم المنطقة') ?></div>
        <div class="area-preview-desc <?= $form['description'] === '' ? 'empty' : '' ?>" id="pvDesc"><?= e($form['description'] ?: 'الوصف المختصر للمنطقة سيظهر هنا...') ?></div>
      </div>
    </div>
  </div>
</section>

<!-- ========== TOOLBAR ========== -->
<div class="area-toolbar">
  <form method="get" class="search-box" action="areas.php">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="ابحث في المناطق...">
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

<!-- ========== GRID ========== -->
<?php if (!$list): ?>

  <div class="area-empty">
    <?php if ($search !== ''): ?>
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <h3>لا توجد نتائج مطابقة</h3>
      <p>لم نجد أي منطقة تطابق "<?= e($search) ?>"</p>
      <a href="areas.php" class="btn-sm btn-primary" style="margin-top:14px">عرض كل المناطق</a>
    <?php else: ?>
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
      </svg>
      <h3>لا توجد مناطق خدمة بعد</h3>
      <p>ابدأ بإضافة أول منطقة لعرضها في الموقع.</p>
    <?php endif; ?>
  </div>

<?php else: ?>

  <div class="area-admin-grid" id="areaGrid">
    <?php foreach ($list as $i => $a): ?>
      <div class="area-admin-card <?= $a['is_active'] ? '' : 'is-inactive' ?>"
           data-id="<?= (int)$a['id'] ?>"
           style="animation-delay:<?= $i * 0.03 ?>s">

        <?php if (!$a['is_active']): ?>
          <span class="area-inactive-badge">معطّلة</span>
        <?php endif; ?>

        <div class="area-card-top">
          <span class="area-card-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="area-card-ico">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
            </svg>
          </span>
          <div class="area-card-tools">
            <button type="button" class="area-drag" aria-label="اسحب" tabindex="-1">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
                <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="area-card-name"><?= e($a['name']) ?></div>
        <div class="area-card-desc <?= empty($a['description']) ? 'empty' : '' ?>">
          <?= e($a['description'] ?: 'لا يوجد وصف') ?>
        </div>

        <div class="area-card-actions">
          <label class="toggle" title="<?= $a['is_active'] ? 'مفعّلة' : 'معطّلة' ?>">
            <input type="checkbox" class="js-toggle" data-id="<?= (int)$a['id'] ?>" <?= $a['is_active'] ? 'checked' : '' ?>>
            <span class="track"></span>
          </label>
          <div class="grow"></div>
          <a href="areas.php?edit=<?= (int)$a['id'] ?>" class="btn-icon" title="تعديل">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
            </svg>
          </a>
          <form method="post" style="display:inline" data-confirm="حذف هذه المنطقة نهائياً؟">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="delete_id" value="<?= (int)$a['id'] ?>">
            <button type="submit" class="btn-icon" style="color:var(--red)" title="حذف">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
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
    اسحب البطاقات لإعادة ترتيب المناطق — الترتيب يُحدّث تلقائياً.
  </p>

  <script>
  (function () {
    'use strict';

    var grid = document.getElementById('areaGrid');
    if (!grid) return;
    var CSRF = <?= json_encode(csrfToken()) ?>;

    /* ===== Char counters ===== */
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
    var inpName = document.getElementById('areaName');
    var inpDesc = document.getElementById('areaDesc');
    var pvName  = document.getElementById('pvName');
    var pvDesc  = document.getElementById('pvDesc');

    function updatePreview() {
      if (pvName) pvName.textContent = (inpName.value.trim() || 'اسم المنطقة');
      if (pvDesc) {
        var v = inpDesc.value.trim();
        pvDesc.textContent = v || 'الوصف المختصر للمنطقة سيظهر هنا...';
        pvDesc.classList.toggle('empty', v === '');
      }
    }
    if (inpName) inpName.addEventListener('input', updatePreview);
    if (inpDesc) inpDesc.addEventListener('input', updatePreview);

    /* ===== Toggle ===== */
    grid.querySelectorAll('.js-toggle').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var id     = cb.getAttribute('data-id');
        var isOn   = cb.checked;
        var card   = cb.closest('.area-admin-card');
        cb.disabled = true;

        var fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('action', 'toggle');
        fd.append('id', id);

        fetch('areas.php', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.ok) {
            card.classList.toggle('is-inactive', !res.is_active);
            cb.checked = !!res.is_active;

            var badge = card.querySelector('.area-inactive-badge');
            if (res.is_active && badge) badge.remove();
            else if (!res.is_active && !badge) {
              var b = document.createElement('span');
              b.className = 'area-inactive-badge';
              b.textContent = 'معطّلة';
              card.appendChild(b);
            }
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
    var cards = function () { return Array.prototype.slice.call(grid.children); };

    cards().forEach(function (card) {
      card.setAttribute('draggable', 'false');
      var handle = card.querySelector('.area-drag');
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
        cards().forEach(function (c) { c.classList.remove('drop-left', 'drop-right'); });
        dragEl = null;
      });

      card.addEventListener('dragover', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var r = card.getBoundingClientRect();
        var isRtl = document.documentElement.dir === 'rtl';
        var after = (e.clientX - r.left) > r.width / 2;
        var before = isRtl ? !after : after;
        card.classList.toggle('drop-right', before);
        card.classList.toggle('drop-left', !before);
      });

      card.addEventListener('dragleave', function () {
        card.classList.remove('drop-left', 'drop-right');
      });

      card.addEventListener('drop', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var r = card.getBoundingClientRect();
        var isRtl = document.documentElement.dir === 'rtl';
        var after = (e.clientX - r.left) > r.width / 2;
        var before = isRtl ? !after : after;
        if (before) grid.insertBefore(dragEl, card);
        else        grid.insertBefore(dragEl, card.nextSibling);
        card.classList.remove('drop-left', 'drop-right');
        saveOrder();
      });
    });

    function saveOrder() {
      var ids = cards().map(function (c) { return c.dataset.id; });
      cards().forEach(function (c, i) {
        var num = c.querySelector('.area-card-num');
        if (num) num.textContent = String(i + 1).padStart(2, '0');
      });
      var fd = new FormData();
      fd.append('_csrf', CSRF);
      fd.append('action', 'reorder');
      ids.forEach(function (id, i) { fd.append('ids[' + i + ']', id); });
      fetch('areas.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      }).catch(function () {});
    }
  })();
  </script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>