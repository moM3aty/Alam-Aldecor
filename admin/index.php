<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

/* ============ STATS ============ */
$counts = [
    'services'     => (int)$pdo->query('SELECT COUNT(*) FROM services WHERE is_active=1')->fetchColumn(),
    'gallery'      => (int)$pdo->query('SELECT COUNT(*) FROM gallery WHERE is_active=1')->fetchColumn(),
    'testimonials' => (int)$pdo->query('SELECT COUNT(*) FROM testimonials WHERE is_active=1')->fetchColumn(),
    'areas'        => (int)$pdo->query('SELECT COUNT(*) FROM areas WHERE is_active=1')->fetchColumn(),
    'messages'     => (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
    'unread'       => (int)$pdo->query('SELECT COUNT(*) FROM messages WHERE is_read=0')->fetchColumn(),
    'msgs_today'   => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'msgs_week'    => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetchColumn(),
];

/* ============ LAST 7 DAYS (BAR CHART) ============ */
$arDays = [
    'Sat'=>'السبت','Sun'=>'الأحد','Mon'=>'الإثنين','Tue'=>'الثلاثاء',
    'Wed'=>'الأربعاء','Thu'=>'الخميس','Fri'=>'الجمعة'
];
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $key = date('Y-m-d', strtotime("-$i days"));
    $days[$key] = [
        'key'   => $key,
        'label' => $arDays[date('D', strtotime($key))] ?? date('D', strtotime($key)),
        'count' => 0,
        'today' => $i === 0,
    ];
}
$stmt = $pdo->query("SELECT DATE(created_at) AS d, COUNT(*) AS c FROM messages
                     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                     GROUP BY DATE(created_at)");
foreach ($stmt as $r) {
    if (isset($days[$r['d']])) $days[$r['d']]['count'] = (int)$r['c'];
}
$days    = array_values($days);
$maxDay  = max(1, max(array_column($days, 'count')));

/* ============ DONUT — DISTRIBUTION BY SERVICE ============ */
$svcDist = $pdo->query("
    SELECT COALESCE(NULLIF(service,''), 'غير محدد') AS label, COUNT(*) AS total
    FROM messages
    GROUP BY label
    ORDER BY total DESC
")->fetchAll();
$totalMsgs = (int)array_sum(array_column($svcDist, 'total'));

/* Donut math */
$R          = 70;
$CIRC       = 2 * M_PI * $R;
$donutCols  = ['#C79A4B', '#2F5D5A', '#B8573D', '#52a09b', '#252d3d', '#D8C3A0', '#e2a93b'];

/* ============ RECENT MESSAGES ============ */
$recent = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 6")->fetchAll();

/* ============ TODAY'S DATE (AR) ============ */
$arMonths = ['01'=>'يناير','02'=>'فبراير','03'=>'مارس','04'=>'أبريل','05'=>'مايو','06'=>'يونيو',
             '07'=>'يوليو','08'=>'أغسطس','09'=>'سبتمبر','10'=>'أكتوبر','11'=>'نوفمبر','12'=>'ديسمبر'];
$todayStr = ($arDays[date('D')] ?? '') . '، ' . date('j') . ' ' . ($arMonths[date('m')] ?? '') . ' ' . date('Y');

/* ============ HELPERS ============ */
function timeAgo($dt) {
    $t = strtotime($dt);
    $diff = time() - $t;
    if ($diff < 60)    return 'الآن';
    if ($diff < 3600)  return 'منذ ' . floor($diff/60) . ' دقيقة';
    if ($diff < 86400) return 'منذ ' . floor($diff/3600) . ' ساعة';
    if ($diff < 604800) return 'منذ ' . floor($diff/86400) . ' يوم';
    return date('Y-m-d', $t);
}

$pageTitle = 'لوحة التحكم';
include 'includes/header.php';
?>

<!-- ============ WELCOME ============ -->
<section class="welcome">
  <div class="welcome-content">
    <span class="welcome-chip">مرحباً بعودتك</span>
    <h2>أهلاً، <span><?= e($_SESSION['admin_name']) ?></span></h2>
    <p>إليك ملخص شامل لأداء موقعك اليوم — تابع رسائلك وأدر محتواك بسهولة.</p>
  </div>
  <div class="welcome-date">
    <span class="wd-ico">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="3"/>
        <path d="M16 2v4M8 2v4M3 10h18"/>
      </svg>
    </span>
    <div>
      <small>التاريخ الحالي</small>
      <b><?= e($todayStr) ?></b>
    </div>
  </div>
</section>

<!-- ============ STAT CARDS ============ -->
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-ico gold">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
    </span>
    <div class="stat-body">
      <b data-count="<?= $counts['messages'] ?>"><?= $counts['messages'] ?></b>
      <small>إجمالي الرسائل</small>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-ico <?= $counts['unread'] ? 'red' : 'teal' ?>">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
    </span>
    <div class="stat-body">
      <b data-count="<?= $counts['unread'] ?>"><?= $counts['unread'] ?></b>
      <small>رسائل غير مقروءة</small>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-ico teal">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
      </svg>
    </span>
    <div class="stat-body">
      <b data-count="<?= $counts['services'] ?>"><?= $counts['services'] ?></b>
      <small>الخدمات المنشورة</small>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-ico ink">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <circle cx="8.5" cy="8.5" r="1.5"/>
        <path d="M21 15l-5-5L5 21"/>
      </svg>
    </span>
    <div class="stat-body">
      <b data-count="<?= $counts['gallery'] ?>"><?= $counts['gallery'] ?></b>
      <small>صور المعرض</small>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-ico terra">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
    </span>
    <div class="stat-body">
      <b data-count="<?= $counts['testimonials'] ?>"><?= $counts['testimonials'] ?></b>
      <small>آراء العملاء</small>
    </div>
  </div>

  <div class="stat-card">
    <span class="stat-ico green">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
        <circle cx="12" cy="10" r="3"/>
      </svg>
    </span>
    <div class="stat-body">
      <b data-count="<?= $counts['areas'] ?>"><?= $counts['areas'] ?></b>
      <small>مناطق الخدمة</small>
    </div>
  </div>
</div>

<!-- ============ CHARTS ============ -->
<div class="charts-grid">

  <!-- BAR CHART -->
  <div class="chart-card">
    <div class="chart-card-head">
      <div class="chart-title">
        <h3>حركة الرسائل</h3>
        <p>آخر 7 أيام</p>
      </div>
      <span class="chart-tag"><?= $counts['msgs_week'] ?> رسالة هذا الأسبوع</span>
    </div>

    <div class="bar-chart">
      <?php foreach ($days as $i => $d):
        $h = $d['count'] > 0 ? ($d['count'] / $maxDay) * 100 : 0;
        $cls = [];
        if ($d['count'] === 0) $cls[] = 'is-empty';
        if ($d['today'])       $cls[] = 'is-today';
      ?>
        <div class="bar-col <?= implode(' ', $cls) ?>" style="--i:<?= $i ?>">
          <div class="bar-val"><?= $d['count'] ?></div>
          <div class="bar-track">
            <div class="bar-fill" style="--h:<?= round($h, 1) ?>%"></div>
          </div>
          <div class="bar-label"><?= e($d['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- DONUT CHART -->
  <div class="chart-card">
    <div class="chart-card-head">
      <div class="chart-title">
        <h3>توزيع الطلبات</h3>
        <p>حسب نوع الخدمة</p>
      </div>
    </div>

    <?php if ($totalMsgs === 0 || empty($svcDist)): ?>
      <div class="donut-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
          <path d="M22 12A10 10 0 0 0 12 2v10z"/>
        </svg>
        <p>لا توجد طلبات بعد لعرض التوزيع</p>
      </div>
    <?php else: ?>
      <div class="donut-wrap">
        <div class="donut-svg-wrap">
          <svg class="donut-svg" viewBox="0 0 200 200">
            <circle class="donut-track" cx="100" cy="100" r="<?= $R ?>"/>
            <?php
              $cumulative = 0;
              foreach ($svcDist as $i => $s):
                $arc   = ($s['total'] / $totalMsgs) * $CIRC;
                $color = $donutCols[$i % count($donutCols)];
            ?>
              <circle class="donut-slice"
                      cx="100" cy="100" r="<?= $R ?>"
                      stroke="<?= $color ?>"
                      style="stroke-dasharray:0 <?= round($CIRC, 2) ?>; stroke-dashoffset:-<?= round($cumulative, 2) ?>; --arc:<?= round($arc, 2) ?>; --circ:<?= round($CIRC, 2) ?>; --delay:<?= 0.2 + $i * 0.12 ?>s;"/>
            <?php $cumulative += $arc; endforeach; ?>
          </svg>
          <div class="donut-center">
            <b><?= $totalMsgs ?></b>
            <small>طلب</small>
          </div>
        </div>

        <ul class="donut-legend">
          <?php foreach (array_slice($svcDist, 0, 5) as $i => $s): ?>
            <li>
              <span class="dot" style="background:<?= $donutCols[$i % count($donutCols)] ?>"></span>
              <span class="lbl"><?= e($s['label']) ?></span>
              <span class="pct"><?= round(($s['total'] / $totalMsgs) * 100) ?>%</span>
            </li>
          <?php endforeach; ?>
          <?php if (count($svcDist) > 5):
            $others = array_slice($svcDist, 5);
            $otherSum = array_sum(array_column($others, 'total'));
          ?>
            <li>
              <span class="dot" style="background:<?= $donutCols[count($donutCols) - 1] ?>"></span>
              <span class="lbl">أخرى</span>
              <span class="pct"><?= round(($otherSum / $totalMsgs) * 100) ?>%</span>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- ============ RECENT MESSAGES ============ -->
<div class="card">
  <div class="chart-card-head" style="margin-bottom:16px">
    <div class="chart-title">
      <h3>آخر الرسائل</h3>
      <p>أحدث 6 رسائل وصلت للموقع</p>
    </div>
    <a href="messages.php" class="btn-sm btn-primary">
      عرض الكل
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
    </a>
  </div>

  <?php if (!$recent): ?>
    <div class="empty-state">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
        <polyline points="22,6 12,13 2,6"/>
      </svg>
      <h3>لا توجد رسائل بعد</h3>
      <p>ستظهر الرسائل الجديدة الواردة من الموقع هنا</p>
    </div>
  <?php else: ?>
    <div class="msg-list">
      <?php foreach ($recent as $r): ?>
        <div class="msg-item <?= $r['is_read'] ? '' : 'unread' ?>">
          <span class="msg-avatar"><?= e(mb_substr($r['name'], 0, 1)) ?></span>
          <div class="msg-info">
            <div class="msg-line1">
              <span class="msg-name"><?= e($r['name']) ?></span>
              <?php if (!$r['is_read']): ?><span class="pill pill-warn">جديدة</span><?php endif; ?>
              <span class="msg-date"><?= e(timeAgo($r['created_at'])) ?></span>
            </div>
            <?php if ($r['message']): ?>
              <p class="msg-excerpt"><?= e($r['message']) ?></p>
            <?php endif; ?>
            <div class="msg-tags">
              <?php if ($r['city']): ?>
                <span class="pill">
                  <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <?= e($r['city']) ?>
                </span>
              <?php endif; ?>
              <?php if ($r['service']): ?>
                <span class="pill pill-info"><?= e($r['service']) ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ============ QUICK ACTIONS ============ -->
<div class="card">
  <div class="chart-card-head" style="margin-bottom:16px">
    <div class="chart-title">
      <h3>إجراءات سريعة</h3>
      <p>اختصارات لأكثر المهام استخداماً</p>
    </div>
  </div>
  <div class="qa-grid">
    <a href="services.php" class="qa-card">
      <span class="qa-ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
      </span>
      <div>
        <b>إضافة خدمة</b>
        <small>أضف خدمة جديدة للموقع</small>
      </div>
    </a>
    <a href="gallery.php" class="qa-card">
      <span class="qa-ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
      </span>
      <div>
        <b>رفع صور</b>
        <small>أضف صوراً لمعرض الأعمال</small>
      </div>
    </a>
    <a href="testimonials.php" class="qa-card">
      <span class="qa-ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </span>
      <div>
        <b>إضافة رأي</b>
        <small>سجّل رأي عميل جديد</small>
      </div>
    </a>
    <a href="settings.php" class="qa-card">
      <span class="qa-ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      </span>
      <div>
        <b>الإعدادات</b>
        <small>بيانات الموقع والتواصل</small>
      </div>
    </a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>