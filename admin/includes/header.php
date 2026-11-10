<?php
if (!defined('APP_BOOT')) exit;
requireLogin();
$flash   = getFlash();
$current = basename($_SERVER['PHP_SELF']);
$S       = getSettings();
$unread  = (int)$pdo->query('SELECT COUNT(*) FROM messages WHERE is_read=0')->fetchColumn();

/**
 * SVG icon helper — returns inline SVG.
 */
function navIcon($name, $size = 20) {
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'hero'      => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'about'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'services'  => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'gallery'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
        'areas'     => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'tst'       => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'why'       => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'messages'  => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        'site'      => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'check'     => '<polyline points="20 6 9 17 4 12"/>',
        'alert'     => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$path.'</svg>';
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($pageTitle ?? 'لوحة التحكم') ?> · <?= e($S['site_name'] ?? '') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body">
<div class="admin-shell">

<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <span class="sb-logo">ع</span>
    <div>
      <strong><?= e($S['site_name'] ?? '') ?></strong>
      <small>لوحة التحكم</small>
    </div>
  </div>

  <nav class="sb-nav">
    <a class="<?= $current==='index.php'?'active':'' ?>" href="index.php"><span class="ico"><?= navIcon('dashboard') ?></span>الرئيسية</a>
    <a class="<?= $current==='hero.php'?'active':'' ?>" href="hero.php"><span class="ico"><?= navIcon('hero') ?></span>قسم البطل</a>
    <a class="<?= $current==='about.php'?'active':'' ?>" href="about.php"><span class="ico"><?= navIcon('about') ?></span>من نحن</a>
    <a class="<?= ($current==='services.php'||$current==='service-edit.php')?'active':'' ?>" href="services.php"><span class="ico"><?= navIcon('services') ?></span>الخدمات</a>
    <a class="<?= $current==='gallery.php'?'active':'' ?>" href="gallery.php"><span class="ico"><?= navIcon('gallery') ?></span>معرض الأعمال</a>
    <a class="<?= $current==='areas.php'?'active':'' ?>" href="areas.php"><span class="ico"><?= navIcon('areas') ?></span>مناطق الخدمة</a>
    <a class="<?= $current==='testimonials.php'?'active':'' ?>" href="testimonials.php"><span class="ico"><?= navIcon('tst') ?></span>آراء العملاء</a>
    <a class="<?= $current==='why-us.php'?'active':'' ?>" href="why-us.php"><span class="ico"><?= navIcon('why') ?></span>لماذا نحن</a>
       <a class="<?= $current==='messages.php'?'active':'' ?>" href="messages.php">
      <span class="ico"><?= navIcon('messages') ?></span>الرسائل
      <?php if ($unread): ?><span class="badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <a class="<?= $current==='settings.php'?'active':'' ?>" href="settings.php"><span class="ico"><?= navIcon('settings') ?></span>الإعدادات</a>
    <a class="<?= $current==='account.php'?'active':'' ?>" href="account.php">
      <span class="ico">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
      </span>
      حسابي
    </a>
    <a href="../index.php" target="_blank" rel="noopener"><span class="ico"><?= navIcon('site') ?></span>عرض الموقع</a>
    <a href="logout.php" class="logout"><span class="ico"><?= navIcon('logout') ?></span>تسجيل الخروج</a>
  </nav>
</aside>

<main class="main">
  <header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="فتح القائمة">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <h1><?= e($pageTitle ?? 'لوحة التحكم') ?></h1>
    <a href="account.php" class="user-chip" style="text-decoration:none" title="حسابي">
      <span class="avatar"><?= e(mb_substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?></span>
      <span><?= e($_SESSION['admin_name'] ?? '') ?></span>
    </a>
  </header>

  <?php if ($flash): ?>
    <div class="flash <?= $flash['type']==='ok' ? 'flash-ok' : 'flash-err' ?>">
      <?= navIcon($flash['type']==='ok' ? 'check' : 'alert') ?>
      <span><?= e($flash['msg']) ?></span>
    </div>
  <?php endif; ?>

  <div class="content">