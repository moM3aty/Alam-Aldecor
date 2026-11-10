<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* Helper */
if (!function_exists('timeAgo')) {
    function timeAgo($dt) {
        $t = strtotime($dt);
        $diff = time() - $t;
        if ($diff < 60)     return 'الآن';
        if ($diff < 3600)   return 'منذ ' . floor($diff / 60) . ' دقيقة';
        if ($diff < 86400)  return 'منذ ' . floor($diff / 3600) . ' ساعة';
        if ($diff < 172800) return 'أمس';
        if ($diff < 604800) return 'منذ ' . floor($diff / 86400) . ' أيام';
        return date('Y-m-d', $t);
    }
}

/* Read filters */
$filterBy = $_GET['filter'] ?? 'all';
if (!in_array($filterBy, ['all', 'unread', 'read'], true)) $filterBy = 'all';
$search  = trim((string)($_GET['q'] ?? ''));
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

/* Stats */
$stats = [
    'all'    => (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
    'unread' => (int)$pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn(),
    'read'   => (int)$pdo->query('SELECT COUNT(*) FROM messages WHERE is_read = 1')->fetchColumn(),
];

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $action = $_POST['action'] ?? '';

    $qs = [];
    if ($filterBy !== 'all') $qs['filter'] = $filterBy;
    if ($search !== '')      $qs['q']      = $search;
    if ($page > 1)           $qs['page']   = $page;
    $redirectUrl = 'messages.php' . ($qs ? '?' . http_build_query($qs) : '');

    if (in_array($action, ['mark_read', 'mark_unread', 'delete'], true) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($action === 'mark_read') {
            $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$id]);
            setFlash('ok', 'تم تعليم الرسالة كمقروءة.');
        } elseif ($action === 'mark_unread') {
            $pdo->prepare('UPDATE messages SET is_read = 0 WHERE id = ?')->execute([$id]);
            setFlash('ok', 'تم تعليم الرسالة كغير مقروءة.');
        } else {
            $pdo->prepare('DELETE FROM messages WHERE id = ?')->execute([$id]);
            setFlash('ok', 'تم حذف الرسالة.');
        }
    } elseif ($action === 'bulk' && !empty($_POST['ids'])) {
        $ids = array_values(array_filter(array_map('intval', (array)$_POST['ids'])));
        $bulkAction = $_POST['bulk_action'] ?? '';
        if ($ids && in_array($bulkAction, ['mark_read', 'mark_unread', 'delete'], true)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            if ($bulkAction === 'mark_read') {
                $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id IN ($ph)")->execute($ids);
                setFlash('ok', 'تم تعليم ' . count($ids) . ' رسالة كمقروءة.');
            } elseif ($bulkAction === 'mark_unread') {
                $pdo->prepare("UPDATE messages SET is_read = 0 WHERE id IN ($ph)")->execute($ids);
                setFlash('ok', 'تم تعليم ' . count($ids) . ' رسالة كغير مقروءة.');
            } else {
                $pdo->prepare("DELETE FROM messages WHERE id IN ($ph)")->execute($ids);
                setFlash('ok', 'تم حذف ' . count($ids) . ' رسالة.');
            }
        }
    }
    redirect($redirectUrl);
}

/* Query */
$where  = [];
$params = [];
if ($filterBy === 'unread') $where[] = 'is_read = 0';
elseif ($filterBy === 'read') $where[] = 'is_read = 1';

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = '(name LIKE ? OR phone LIKE ? OR city LIKE ? OR service LIKE ? OR message LIKE ?)';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* Count */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* Fetch */
$stmt = $pdo->prepare("SELECT * FROM messages $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$messages = $stmt->fetchAll();

/* Pagination helper */
function renderPagination($current, $totalPages, $baseUrl, $params) {
    if ($totalPages <= 1) return '';

    $html = '<nav class="pagination" aria-label="تصفح الصفحات">';

    /* Prev (in RTL: arrow pointing right) */
    $prevUrl = $current > 1
        ? $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $current - 1]))
        : '#';
    $html .= '<a href="' . e($prevUrl) . '" class="pg-btn ' . ($current <= 1 ? 'is-disabled' : '') . '" aria-label="السابق">';
    $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
    $html .= '</a>';

    /* Pages */
    $range = 2;
    $show = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === 1 || $i === $totalPages || ($i >= $current - $range && $i <= $current + $range)) {
            $show[] = $i;
        }
    }
    $prev = 0;
    foreach ($show as $p) {
        if ($p - $prev > 1) $html .= '<span class="pg-ellipsis">…</span>';
        $url = $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $p]));
        $cls = $p === $current ? 'is-current' : '';
        $html .= '<a href="' . e($url) . '" class="pg-btn ' . $cls . '">' . $p . '</a>';
        $prev = $p;
    }

    /* Next */
    $nextUrl = $current < $totalPages
        ? $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $current + 1]))
        : '#';
    $html .= '<a href="' . e($nextUrl) . '" class="pg-btn ' . ($current >= $totalPages ? 'is-disabled' : '') . '" aria-label="التالي">';
    $html .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
    $html .= '</a>';

    return $html . '</nav>';
}

/* Filter URL helper */
function filterUrl($f, $search = '') {
    $q = [];
    if ($f !== 'all')   $q['filter'] = $f;
    if ($search !== '') $q['q']      = $search;
    return 'messages.php' . ($q ? '?' . http_build_query($q) : '');
}

$pageTitle = 'الرسائل';
include 'includes/header.php';
?>

<!-- ========== TOOLBAR ========== -->
<div class="msg-toolbar">
  <nav class="msg-filters" aria-label="تصفية الرسائل">
    <a href="<?= e(filterUrl('all', $search)) ?>" class="msg-filter <?= $filterBy === 'all' ? 'active' : '' ?>">
      الكل <span class="cnt"><?= $stats['all'] ?></span>
    </a>
    <a href="<?= e(filterUrl('unread', $search)) ?>" class="msg-filter <?= $filterBy === 'unread' ? 'active' : '' ?>">
      غير مقروءة <span class="cnt"><?= $stats['unread'] ?></span>
    </a>
    <a href="<?= e(filterUrl('read', $search)) ?>" class="msg-filter <?= $filterBy === 'read' ? 'active' : '' ?>">
      مقروءة <span class="cnt"><?= $stats['read'] ?></span>
    </a>
  </nav>

  <form method="get" class="msg-search" role="search" action="messages.php">
    <?php if ($filterBy !== 'all'): ?>
      <input type="hidden" name="filter" value="<?= e($filterBy) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= e($search) ?>"
           placeholder="ابحث بالاسم أو الجوال أو المدينة أو الخدمة..."
           id="msgSearch">
    <span class="search-ico">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </span>
    <?php if ($search !== ''): ?>
      <a href="<?= e(filterUrl($filterBy)) ?>" class="search-clear" aria-label="مسح البحث">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </a>
    <?php endif; ?>
  </form>
    </form>

  <?php
    $exportQs = [];
    if ($filterBy !== 'all') $exportQs['filter'] = $filterBy;
    if ($search !== '')      $exportQs['q']      = $search;
    $exportUrl = 'export-messages.php' . ($exportQs ? '?' . http_build_query($exportQs) : '');
  ?>
  <a href="<?= e($exportUrl) ?>" class="btn-export" title="تصدير النتائج الحالية إلى Excel/CSV">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
      <polyline points="7 10 12 15 17 10"/>
      <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    تصدير CSV
  </a>
</div>

<!-- ========== BULK BAR (fixed, hidden until selection) ========== -->
<form method="post" id="bulkForm" class="msg-bulk-bar" autocomplete="off">
  <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
  <input type="hidden" name="action" value="bulk">
  <span class="bulk-count" id="bulkCount">0 محدد</span>
  <button type="submit" name="bulk_action" value="mark_read" class="bulk-btn">تعليم كمقروءة</button>
  <button type="submit" name="bulk_action" value="mark_unread" class="bulk-btn">تعليم كغير مقروءة</button>
  <button type="submit" name="bulk_action" value="delete" class="bulk-btn danger"
          data-confirm="حذف الرسائل المحددة نهائياً؟">حذف</button>
  <button type="button" class="bulk-btn" id="bulkCancel">إلغاء</button>
</form>

<?php if (!empty($messages)): ?>
<!-- ========== LIST BAR ========== -->
<div class="list-bar">
  <label class="mcard-checkbox" aria-label="تحديد الكل">
    <input type="checkbox" id="selectAll">
    <span class="cbox"></span>
  </label>
  <span class="list-bar-label">تحديد الكل</span>
  <span class="list-bar-count">
    عرض <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> من <?= $total ?> رسالة
  </span>
</div>
<?php endif; ?>

<!-- ========== MESSAGES ========== -->
<?php if (empty($messages)):

    if ($stats['all'] === 0): ?>
      <div class="card">
        <div class="empty-search">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          <h3>لا توجد رسائل بعد</h3>
          <p>عندما يرسل زائر رسالة عبر الموقع، ستظهر هنا مباشرة.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="empty-search">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <h3>لا توجد نتائج مطابقة</h3>
          <p>جرّب تعديل كلمات البحث أو إزالة التصفية.</p>
          <a href="messages.php" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
            عرض كل الرسائل
          </a>
        </div>
      </div>
    <?php endif;

else:

    foreach ($messages as $i => $m): ?>
      <article class="mcard <?= $m['is_read'] ? '' : 'is-unread' ?>"
               style="animation-delay:<?= $i * 0.04 ?>s">
        <label class="mcard-checkbox" aria-label="تحديد الرسالة">
          <input type="checkbox" name="ids[]" value="<?= (int)$m['id'] ?>" form="bulkForm" class="msg-cb">
          <span class="cbox"></span>
        </label>

        <div class="mcard-content">
          <div class="mcard-main">
            <span class="mcard-avatar"><?= e(mb_substr($m['name'], 0, 1)) ?></span>
            <div class="mcard-body-content">

              <div class="mcard-top">
                <span class="mcard-name"><?= e($m['name']) ?></span>
                <a href="tel:<?= e($m['phone']) ?>" class="mcard-phone">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                  </svg>
                  <?= e($m['phone']) ?>
                </a>
                <?php if (!$m['is_read']): ?><span class="pill pill-warn">جديدة</span><?php endif; ?>
                <span class="mcard-time"><?= e(timeAgo($m['created_at'])) ?></span>
              </div>

              <?php if ($m['city'] || $m['service']): ?>
                <div class="mcard-badges">
                  <?php if ($m['city']): ?>
                    <span class="pill">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                      <?= e($m['city']) ?>
                    </span>
                  <?php endif; ?>
                  <?php if ($m['service']): ?>
                    <span class="pill pill-info"><?= e($m['service']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <?php if ($m['message']): ?>
                <p class="mcard-preview"><?= e($m['message']) ?></p>
                <div class="mcard-expanded"><?= e($m['message']) ?></div>
              <?php else: ?>
                <p class="mcard-preview" style="color:var(--text-3);font-style:italic">لا يوجد نص في الرسالة.</p>
              <?php endif; ?>

            </div>
          </div>

          <div class="mcard-actions">
            <?php if ($m['message']): ?>
              <button type="button" class="btn-sm expand-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                <span>عرض كامل</span>
              </button>
            <?php endif; ?>

            <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $m['phone'])) ?>?text=<?= urlencode('السلام عليكم ' . $m['name'] . '، شكراً لتواصلك مع عالم الديكور.') ?>"
               target="_blank" rel="noopener" class="btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.7-5.2A8.5 8.5 0 1 1 21 11.5z"/></svg>
              رد واتساب
            </a>

            <form method="post" style="display:inline">
              <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <input type="hidden" name="action" value="<?= $m['is_read'] ? 'mark_unread' : 'mark_read' ?>">
              <button type="submit" class="btn-sm">
                <?php if ($m['is_read']): ?>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                  كغير مقروءة
                <?php else: ?>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  تعليم كمقروءة
                <?php endif; ?>
              </button>
            </form>

            <form method="post" style="display:inline" data-confirm="حذف هذه الرسالة نهائياً؟">
              <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <button type="submit" class="btn-sm btn-danger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                حذف
              </button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach;

    /* Pagination */
    echo renderPagination($page, $totalPages, 'messages.php', $params);

endif; ?>

<script>
(function () {
  'use strict';

  var bulkBar    = document.getElementById('bulkForm');
  var bulkCount  = document.getElementById('bulkCount');
  var bulkCancel = document.getElementById('bulkCancel');
  var selectAll  = document.getElementById('selectAll');
  var checkboxes = Array.prototype.slice.call(document.querySelectorAll('.msg-cb'));

  function updateBulk() {
    var checked = document.querySelectorAll('.msg-cb:checked');
    var n = checked.length;
    if (bulkCount) bulkCount.textContent = n + ' محدد';
    if (bulkBar)   bulkBar.classList.toggle('visible', n > 0);
    if (selectAll) {
      selectAll.checked = n > 0 && n === checkboxes.length;
      selectAll.indeterminate = n > 0 && n < checkboxes.length;
    }
    document.querySelectorAll('.mcard').forEach(function (card) {
      var cb = card.querySelector('.msg-cb');
      card.classList.toggle('is-selected', !!(cb && cb.checked));
    });
  }

  checkboxes.forEach(function (cb) { cb.addEventListener('change', updateBulk); });

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checkboxes.forEach(function (cb) { cb.checked = selectAll.checked; });
      updateBulk();
    });
  }

  if (bulkCancel) {
    bulkCancel.addEventListener('click', function () {
      checkboxes.forEach(function (cb) { cb.checked = false; });
      updateBulk();
    });
  }

  /* Expand/collapse */
  document.querySelectorAll('.expand-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card     = btn.closest('.mcard');
      var expanded = card.classList.toggle('is-expanded');
      var label    = btn.querySelector('span');
      if (label) label.textContent = expanded ? 'إخفاء' : 'عرض كامل';
    });
  });

  /* Auto-search with debounce */
  var searchInput = document.getElementById('msgSearch');
  if (searchInput) {
    var timer;
    searchInput.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(function () {
        if (searchInput.form) searchInput.form.submit();
      }, 700);
    });
  }

  /* Prevent submit if no selection */
  if (bulkBar) {
    bulkBar.addEventListener('submit', function (e) {
      if (document.querySelectorAll('.msg-cb:checked').length === 0) {
        e.preventDefault();
      }
    });
  }
})();
</script>

<?php include 'includes/footer.php'; ?>