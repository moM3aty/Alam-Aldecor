<?php
define('APP_BOOT', true);
require __DIR__ . '/config.php';
require __DIR__ . '/functions.php';

$S       = getSettings();
$hero    = getHero();
$about   = getAbout();
$svcs    = getServices();
$gal     = getGallery();
$areas   = getAreas();
$tst     = getTestimonials();
$whyC    = getWhyCards();
$whyS    = getWhySteps();

$aboutPoints = array_filter(array_map('trim', explode("\n", (string)($about['points'] ?? ''))));
?>
<!-- index.php -->
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(setting('meta_title', 'عالم الديكور')) ?></title>
<meta name="description" content="<?= e(setting('meta_description')) ?>">
<meta name="keywords" content="<?= e(setting('meta_keywords')) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="author" content="<?= e(setting('site_name','عالم الديكور')) ?>">
<meta name="theme-color" content="#161B22">
<meta property="og:type" content="website">
<meta property="og:locale" content="ar_SA">
<meta property="og:site_name" content="<?= e(setting('site_name')) ?>">
<meta property="og:title" content="<?= e(setting('og_title')) ?>">
<meta property="og:description" content="<?= e(setting('og_description')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script type="application/ld+json">
{ "@context": "https://schema.org", "@type": "HomeAndConstructionBusiness",
  "name": "<?= e(setting('site_name')) ?>",
  "telephone": "<?= e(setting('phone_intl')) ?>",
  "address": { "@type":"PostalAddress","addressLocality":"خميس مشيط","addressRegion":"عسير","addressCountry":"SA" }
}
</script>
</head>
<body>

<a class="skip-link" href="#main">تخطي إلى المحتوى</a>

<header class="site-header" id="siteHeader">
  <div class="container header-inner">
    <a href="#home" class="brand" aria-label="<?= e(setting('site_name')) ?>">
      <span class="brand-logo"><img src="images/logo.png" alt="شعار" width="64" height="64"></span>
      <span class="brand-text">
        <strong><?= e(setting('site_name','عالم الديكور')) ?></strong>
        <small><?= e(setting('site_tagline','جبس بورد ودهانات')) ?></small>
      </span>
    </a>

    <nav class="main-nav" aria-label="القائمة الرئيسية"><ul>
      <li><a href="#home" class="nav-link" data-section="home">الرئيسية</a></li>
      <li><a href="#about" class="nav-link" data-section="about">من نحن</a></li>
      <li><a href="#services" class="nav-link" data-section="services">خدماتنا</a></li>
      <li><a href="#gallery" class="nav-link" data-section="gallery">معرض أعمالنا</a></li>
      <li><a href="#areas" class="nav-link" data-section="areas">مناطق الخدمة</a></li>
      <li><a href="#testimonials" class="nav-link" data-section="testimonials">آراء العملاء</a></li>
      <li><a href="#why-us" class="nav-link" data-section="why-us">لماذا نحن</a></li>
      <li><a href="#contact" class="nav-link" data-section="contact">تواصل معنا</a></li>
    </ul></nav>

    <a href="tel:<?= e(setting('phone_intl')) ?>" class="call-btn">
      <span class="call-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
      <span class="call-text"><small>اتصل الآن</small><b><?= e(setting('phone','0544371877')) ?></b></span>
    </a>

    <button class="menu-toggle" id="menuToggle" type="button" aria-label="فتح القائمة" aria-expanded="false" aria-controls="mobileMenu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<div class="menu-overlay" id="menuOverlay"></div>

<aside class="mobile-menu" id="mobileMenu" aria-hidden="true">
  <button class="menu-close" id="menuClose" type="button" aria-label="إغلاق">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
  </button>
  <div class="mobile-brand">
    <span class="mobile-logo"><img src="images/logo.png" alt="" width="56" height="56"></span>
    <strong><?= e(setting('site_name')) ?></strong>
    <small><?= e(setting('site_tagline')) ?></small>
  </div>
  <ul class="mobile-links">
    <li style="--i:1"><a href="#home" class="mobile-link" data-section="home">الرئيسية</a></li>
    <li style="--i:2"><a href="#about" class="mobile-link" data-section="about">من نحن</a></li>
    <li style="--i:3"><a href="#services" class="mobile-link" data-section="services">خدماتنا</a></li>
    <li style="--i:4"><a href="#gallery" class="mobile-link" data-section="gallery">معرض أعمالنا</a></li>
    <li style="--i:5"><a href="#areas" class="mobile-link" data-section="areas">مناطق الخدمة</a></li>
    <li style="--i:6"><a href="#testimonials" class="mobile-link" data-section="testimonials">آراء العملاء</a></li>
    <li style="--i:7"><a href="#why-us" class="mobile-link" data-section="why-us">لماذا نحن</a></li>
    <li style="--i:8"><a href="#contact" class="mobile-link" data-section="contact">تواصل معنا</a></li>
  </ul>
  <p class="mobile-foot">خميس مشيط – أبها – عسير</p>
</aside>

<main id="main">

  <!-- HERO -->
  <section class="hero" id="home" aria-labelledby="heroTitle">
    <div class="hero-bg" aria-hidden="true">
      <div class="hero-parallax" id="heroParallax">
        <svg class="hero-pattern" xmlns="http://www.w3.org/2000/svg" width="100%" height="100%">
          <defs><pattern id="decorPattern" width="300" height="300" patternUnits="userSpaceOnUse" patternTransform="rotate(-6)">
            <g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="30" y="30" width="62" height="20" rx="5"/><path d="M92 40h14v26H61v14"/><rect x="55" y="80" width="12" height="40" rx="4"/>
              <path d="M190 40c0 0-16 20-16 32a16 16 0 0 0 32 0c0-12-16-32-16-32Z"/>
              <path d="M30 180h80v-12h-60v-12h-20z"/><path d="M30 200h80"/><path d="M180 170h70v70h-70z"/><path d="M190 180h50v50h-50z"/>
              <path d="M146 130l3 8 8 3-8 3-3 8-3-8-8-3 8-3z"/><path d="M240 90l14-14M246 100l14-14"/>
              <rect x="224" y="106" width="26" height="8" rx="3" transform="rotate(-45 237 110)"/>
            </g>
          </pattern></defs>
          <rect width="100%" height="100%" fill="url(#decorPattern)"/>
        </svg>
      </div>
      <span class="orb orb-1"></span><span class="orb orb-2"></span>
      <div class="particles" id="particles"></div>
    </div>

    <div class="container hero-inner">
      <div class="hero-badge reveal" style="--d:.2s">
        <span class="pulse-dot"></span>
        <?= e($hero['badge'] ?? '') ?>
      </div>
      <h1 id="heroTitle" class="reveal" style="--d:.4s">
        <span class="shine-text"><?= e($hero['highlight'] ?? '') ?></span>.. <?= e($hero['title'] ?? '') ?>
      </h1>
      <p class="hero-sub reveal" style="--d:.65s"><?= e($hero['subtitle'] ?? '') ?></p>

      <div class="hero-actions reveal" style="--d:.9s">
        <a href="#services" class="btn btn-primary"><span>اكتشف خدماتنا</span>
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M11 6l-6 6 6 6"/></svg>
        </a>
        <a href="#contact" class="btn btn-ghost"><span>تواصل معنا</span>
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
        </a>
      </div>

      <ul class="hero-stats reveal" style="--d:1.15s">
        <li><b><?= e(setting('stat_1_num','2')) ?></b><span><?= e(setting('stat_1_label','تخصصان رئيسيان')) ?></span></li>
        <li><b><?= e(setting('stat_2_num','8')) ?></b><span><?= e(setting('stat_2_label','خدمات متكاملة')) ?></span></li>
        <li><b><?= e(setting('stat_3_num','7')) ?></b><span><?= e(setting('stat_3_label','مناطق نخدمها')) ?></span></li>
      </ul>
    </div>
  </section>

  <!-- ABOUT -->
  <section class="about" id="about" aria-labelledby="aboutTitle">
    <svg class="about-wave" viewBox="0 0 1440 80" preserveAspectRatio="none"><path d="M0 0H1440V30C1200 80 960 80 720 48C480 16 240 16 0 60Z" fill="#161B22"/></svg>
    <div class="about-deco"><span class="deco-dots"></span><span class="deco-ring deco-ring-1"></span><span class="deco-ring deco-ring-2"></span></div>

    <div class="container about-grid">
      <div class="about-content">
        <span class="about-chip" data-rv="up"><i></i><?= e($about['chip'] ?? 'من نحن') ?></span>
        <h2 id="aboutTitle" class="about-title" data-rv="up">
          <span class="about-mark"><?= e($about['highlight'] ?? '') ?></span> <?= e($about['title'] ?? '') ?>
        </h2>
        <p class="about-text" data-rv="up"><?= e($about['paragraph1'] ?? '') ?></p>
        <p class="about-text" data-rv="up"><?= e($about['paragraph2'] ?? '') ?></p>

        <ul class="about-points" data-rv="up">
          <?php foreach ($aboutPoints as $pt): ?>
            <li><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg><?= e($pt) ?></li>
          <?php endforeach; ?>
        </ul>

        <div class="about-tabs" data-rv="up">
          <div class="about-tablist" role="tablist">
            <button class="about-tab is-active" role="tab" id="tab-vision" aria-selected="true" aria-controls="panel-vision" type="button">رؤيتنا</button>
            <button class="about-tab" role="tab" id="tab-mission" aria-selected="false" aria-controls="panel-mission" tabindex="-1" type="button">رسالتنا</button>
            <button class="about-tab" role="tab" id="tab-goals" aria-selected="false" aria-controls="panel-goals" tabindex="-1" type="button">أهدافنا</button>
          </div>
          <div class="about-panel" role="tabpanel" id="panel-vision"><?= e($about['vision'] ?? '') ?></div>
          <div class="about-panel" role="tabpanel" id="panel-mission" hidden><?= e($about['mission'] ?? '') ?></div>
          <div class="about-panel" role="tabpanel" id="panel-goals" hidden><?= e($about['goals'] ?? '') ?></div>
        </div>

        <ul class="about-stats" data-rv="up">
          <li><b><span data-count="<?= e(setting('stat_1_num','2')) ?>" data-suffix=""><?= e(setting('stat_1_num','2')) ?></span></b><small><?= e(setting('stat_1_label','تخصصان رئيسيان')) ?></small></li>
          <li><b><span data-count="<?= e(setting('stat_2_num','8')) ?>" data-suffix=""><?= e(setting('stat_2_num','8')) ?></span></b><small><?= e(setting('stat_2_label','خدمات متكاملة')) ?></small></li>
          <li><b><span data-count="<?= e(setting('stat_3_num','7')) ?>" data-suffix=""><?= e(setting('stat_3_num','7')) ?></span></b><small><?= e(setting('stat_3_label','مناطق نخدمها')) ?></small></li>
        </ul>

        <div class="about-actions" data-rv="up">
          <a href="#services" class="btn btn-primary"><span>تعرّف على خدماتنا</span>
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M11 6l-6 6 6 6"/></svg>
          </a>
          <a href="#contact" class="btn btn-outline-dark"><span>اطلب عرض سعر</span></a>
        </div>
      </div>

      <div class="about-media" data-rv="left">
        <div class="media-frame">
          <span class="media-block"></span><span class="media-outline"></span><span class="media-dots"></span>
          <div class="media-photo">
            <img src="<?= e($about['image'] ?? 'images/about.png') ?>" alt="أعمال عالم الديكور" width="800" height="940" loading="lazy">
            <span class="media-shine"></span>
          </div>
          <div class="media-card media-card-top">
            <span class="mc-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
            <span class="mc-text"><b>من خميس مشيط</b><small>إلى كل عسير</small></span>
          </div>
          <div class="media-card media-card-bottom">
            <span class="mc-icon mc-icon-gold"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z"/></svg></span>
            <span class="mc-text"><b>إتقان وذوق</b><small>في كل تفصيلة</small></span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="services" id="services" aria-labelledby="servicesTitle">
    <div class="svc-deco"><span class="svc-blob svc-blob-1"></span><span class="svc-blob svc-blob-2"></span><span class="svc-grid"></span></div>
    <div class="container">
      <header class="svc-head">
        <span class="about-chip" data-rv="up"><i></i>خدماتنا</span>
        <h2 id="servicesTitle" class="svc-title" data-rv="up">خدمات <span class="about-mark">جبس بورد</span> ودهانات وعوازل متكاملة</h2>
        <p class="svc-lead" data-rv="up">من الأسقف المعلقة والديكورات الجدارية والقواطع والإضاءة المخفية، إلى الدهانات الداخلية والخارجية والديكورية وأعمال الترميم وعزل الأسطح، نقدم لك كل ما يحتاجه بيتك أو محلك أو مكتبك من جهة واحدة.</p>
      </header>

      <ul class="svc-index" data-rv="up">
        <?php foreach ($svcs as $s): ?>
          <li><a href="#svc-<?= (int)$s['id'] ?>"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>

      <div class="svc-list">
        <?php foreach ($svcs as $i => $s): $checks = array_filter(array_map('trim', explode("\n", (string)$s['checks']))); ?>
          <article class="svc-row" id="svc-<?= (int)$s['id'] ?>">
            <div class="svc-row-media" data-rv="<?= $i % 2 ? 'left' : 'right' ?>">
              <div class="svc-photo">
                <img src="<?= e($s['image'] ?: 'images/serv1.png') ?>" alt="<?= e($s['title']) ?>" loading="lazy">
                <span class="svc-num"><?= str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) ?></span>
              </div>
            </div>
            <div class="svc-row-body" data-rv="up">
              <span class="svc-kicker"><?= e($s['kicker']) ?></span>
              <h3><?= e($s['title']) ?></h3>
              <p><?= e($s['description1']) ?></p>
              <p><?= e($s['description2']) ?></p>
              <ul class="svc-checks"><?php foreach ($checks as $c): ?><li><?= e($c) ?></li><?php endforeach; ?></ul>
              <div class="svc-row-foot">
                <a href="#contact" class="btn btn-primary"><span>اطلب هذه الخدمة</span></a>
                <a href="tel:<?= e(setting('phone_intl')) ?>" class="svc-call">أو اتصل: <b><?= e(setting('phone')) ?></b></a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="svc-cta" data-rv="up">
        <div class="svc-cta-text">
          <h3>عندك فكرة ولا تعرف من أين تبدأ؟</h3>
          <p>تواصل معنا واحكِ لنا عن مساحتك، وسنقترح عليك التصميم المناسب وعرض سعر واضحاً.</p>
        </div>
        <div class="svc-cta-actions">
          <a href="tel:<?= e(setting('phone_intl')) ?>" class="btn btn-primary"><span>اتصل الآن</span></a>
          <a href="#contact" class="btn btn-light"><span>اطلب عرض سعر</span></a>
        </div>
      </div>
    </div>
  </section>

  <!-- GALLERY -->
  <section class="gallery" id="gallery" aria-labelledby="galleryTitle">
    <div class="container">
      <header class="svc-head">
        <span class="about-chip" data-rv="up"><i></i>معرض أعمالنا</span>
        <h2 id="galleryTitle" class="svc-title" data-rv="up">نماذج من <span class="about-mark">أعمالنا</span> في الجبس بورد والدهانات</h2>
      </header>
      <ul class="gal-grid">
        <?php foreach ($gal as $g): ?>
          <li class="gal-item" data-gal><img src="<?= e($g['image']) ?>" alt="<?= e($g['alt_text'] ?: 'معرض أعمالنا') ?>" loading="lazy"></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- AREAS -->
  <section class="areas" id="areas" aria-labelledby="areasTitle">
    <div class="areas-deco"><span class="areas-orb areas-orb-1"></span><span class="areas-orb areas-orb-2"></span><span class="areas-grid"></span></div>
    <div class="container">
      <header class="svc-head">
        <span class="about-chip areas-chip" data-rv="up"><i></i>مناطق الخدمة</span>
        <h2 id="areasTitle" class="areas-title" data-rv="up">نخدمك في <span class="areas-mark">خميس مشيط وأبها</span> وجميع مناطق عسير القريبة</h2>
      </header>
      <ul class="areas-grid-list">
        <?php foreach ($areas as $a): ?>
          <li class="area-card" data-rv="up">
            <span class="area-ico"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
            <h3><?= e($a['name']) ?></h3><p><?= e($a['description']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="areas-cta" data-rv="up">
        <p>اذكر لنا اسم مدينتك أو حيّك عند الاتصال، ونحدد لك موعد المعاينة.</p>
        <a href="tel:<?= e(setting('phone_intl')) ?>" class="btn btn-primary"><span>اتصل: <?= e(setting('phone')) ?></span></a>
      </div>
    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="tst" id="testimonials" aria-labelledby="tstTitle">
    <div class="tst-deco"><span class="tst-shape tst-shape-1"></span><span class="tst-shape tst-shape-2"></span><span class="tst-dots"></span></div>
    <div class="container">
      <div class="tst-top">
        <header class="tst-head">
          <span class="about-chip" data-rv="up"><i></i>آراء العملاء</span>
          <h2 id="tstTitle" class="tst-title" data-rv="up">كلمات <span class="tst-mark">صادقة</span> من عملاء تعاملوا معنا</h2>
        </header>
        <div class="tst-badge" data-rv="left">
          <div class="tst-badge-icon"><svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor"><path d="M12 2.5l2.9 6 6.6.8-4.9 4.5 1.3 6.5L12 17.1 6.1 20.3l1.3-6.5L2.5 9.3l6.6-.8z"/></svg></div>
          <div class="tst-badge-text"><b>ذوق وإتقان</b><span>هدفنا أن يرضيك كل تفصيل</span></div>
        </div>
      </div>
      <div class="tst-slider" data-rv="up">
        <div class="tst-track" id="tstTrack" tabindex="0">
          <?php foreach ($tst as $idx => $t): ?>
            <figure class="tst-card">
              <span class="tst-index"><?= str_pad((string)($idx+1), 2, '0', STR_PAD_LEFT) ?></span>
              <span class="tst-qmark"><svg viewBox="0 0 48 48" width="26" height="26"><path fill="currentColor" d="M10 28c0-8 4-14 12-17l1.6 3C19 16 17.4 19 17.2 22H22v14H10V28zm18 0c0-8 4-14 12-17l1.6 3C37 16 35.4 19 35.2 22H40v14H28V28z"/></svg></span>
              <blockquote><?= e($t['body']) ?></blockquote>
              <figcaption><?= e($t['name']) ?></figcaption>
            </figure>
          <?php endforeach; ?>
        </div>
        <div class="tst-controls">
          <button class="tst-arrow" id="tstNext" type="button" aria-label="التالي"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>
          <div class="tst-pager" id="tstPager" role="tablist"></div>
          <button class="tst-arrow" id="tstPrev" type="button" aria-label="السابق"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>
        </div>
      </div>
    </div>
  </section>

  <!-- WHY US -->
  <section class="why" id="why-us" aria-labelledby="whyTitle">
    <div class="why-deco"><span class="why-orb why-orb-1"></span><span class="why-orb why-orb-2"></span><span class="why-grid"></span></div>
    <div class="container">
      <div class="why-layout">
        <div class="why-intro">
          <span class="about-chip why-chip" data-rv="up"><i></i>لماذا نحن</span>
          <h2 id="whyTitle" class="why-title" data-rv="up">لماذا تختار <span class="why-mark"><?= e(setting('site_name')) ?></span> لأعمال الجبس بورد والدهانات؟</h2>
          <p class="why-lead" data-rv="up">لأن الديكور الجيد يحتاج ذوقاً وإتقاناً وتنظيماً. نعمل بأسلوب واضح من المعاينة حتى التسليم.</p>
          <ul class="why-stats" data-rv="up">
            <li><b><?= e(setting('stat_1_num','2')) ?></b><small><?= e(setting('stat_1_label')) ?></small></li>
            <li><b><?= e(setting('stat_2_num','8')) ?></b><small><?= e(setting('stat_2_label')) ?></small></li>
            <li><b><?= e(setting('stat_3_num','7')) ?></b><small><?= e(setting('stat_3_label')) ?></small></li>
          </ul>
          <div class="why-actions" data-rv="up">
            <a href="tel:<?= e(setting('phone_intl')) ?>" class="btn btn-primary"><span>اتصل الآن</span></a>
            <a href="#contact" class="btn btn-light"><span>اطلب عرض سعر</span></a>
          </div>
        </div>
        <ul class="why-cards">
          <?php foreach ($whyC as $i => $w): ?>
            <li class="why-card" data-rv="up">
              <span class="why-num"><?= str_pad((string)($i+1), 2, '0', STR_PAD_LEFT) ?></span>
              <span class="why-icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z"/></svg></span>
              <h3><?= e($w['title']) ?></h3>
              <p><?= e($w['description']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="why-flow">
        <header class="why-flow-head" data-rv="up">
          <h3>كيف نعمل معك؟</h3>
          <p>أربع خطوات بسيطة من أول اتصال حتى تسليم العمل.</p>
        </header>
        <ol class="why-steps">
          <?php foreach ($whyS as $st): ?>
            <li class="why-step" data-rv="up">
              <span class="why-step-n"><?= (int)$st['step_number'] ?></span>
              <h4><?= e($st['title']) ?></h4>
              <p><?= e($st['description']) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="ct" id="contact" aria-labelledby="ctTitle">
    <div class="ct-deco"><span class="ct-shape ct-shape-1"></span><span class="ct-shape ct-shape-2"></span><span class="ct-dots"></span></div>
    <div class="container">
      <header class="svc-head">
        <span class="about-chip" data-rv="up"><i></i>تواصل معنا</span>
        <h2 id="ctTitle" class="svc-title" data-rv="up">نحن <span class="about-mark">جاهزون</span> لخدمتك</h2>
      </header>

      <div class="ct-grid">
        <div class="ct-info" data-rv="right">
          <h3>بيانات التواصل</h3>
          <ul class="ct-list">
            <li>
              <span class="ct-ico"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
              <div><small>اتصل بنا</small><a href="tel:<?= e(setting('phone_intl')) ?>" class="ct-val"><?= e(setting('phone')) ?></a></div>
            </li>
            <li>
              <span class="ct-ico ct-ico-wa"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.7-5.2A8.5 8.5 0 1 1 21 11.5z"/></svg></span>
              <div><small>واتساب</small><a href="https://wa.me/<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener" class="ct-val">راسلنا على واتساب</a></div>
            </li>
            <li>
              <span class="ct-ico"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.6"/></svg></span>
              <div><small>المنطقة</small><span class="ct-val"><?= e(setting('address')) ?></span></div>
            </li>
          </ul>
          <div class="ct-note"><span class="ct-pulse"></span>نخدم خميس مشيط وأبها ومحايل عسير والدرب والمناطق المجاورة</div>
        </div>

        <form class="ct-form" id="ctForm" novalidate data-rv="left">
          <h3>أرسل لنا طلبك</h3>
          <p class="ct-form-lead">املأ البيانات وسنفتح لك واتساب برسالة جاهزة للإرسال.</p>
          <div class="ct-row">
            <div class="ct-field"><label for="ctName">الاسم</label><input id="ctName" name="name" type="text" required></div>
            <div class="ct-field"><label for="ctPhone">رقم الجوال</label><input id="ctPhone" name="phone" type="tel" dir="ltr" required></div>
          </div>
          <div class="ct-field">
            <label for="ctCity">المدينة</label>
            <select id="ctCity" name="city">
              <option value="">اختر المدينة</option>
              <?php foreach ($areas as $a): ?><option><?= e($a['name']) ?></option><?php endforeach; ?>
              <option>منطقة مجاورة</option>
            </select>
          </div>
          <div class="ct-field">
            <label for="ctService">الخدمة المطلوبة</label>
            <select id="ctService" name="service">
              <option value="">اختر الخدمة</option>
              <?php foreach ($svcs as $s): ?><option><?= e($s['title']) ?></option><?php endforeach; ?>
              <option>خدمة أخرى</option>
            </select>
          </div>
          <div class="ct-field"><label for="ctMsg">تفاصيل الطلب</label><textarea id="ctMsg" name="message" rows="4"></textarea></div>
          <p class="ct-error" id="ctError" role="alert"></p>
          <button class="btn btn-primary ct-submit" type="submit"><span>إرسال عبر واتساب</span></button>
        </form>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="sf-cols">
      <div class="sf-col sf-about">
        <a href="#home" class="sf-logo">
          <span class="sf-logo-img"><img src="images/logo.png" alt="" width="52" height="52"></span>
          <strong><?= e(setting('site_name')) ?><small><?= e(setting('site_tagline')) ?></small></strong>
        </a>
        <p><?= e(setting('footer_about')) ?></p>
      </div>
      <nav class="sf-col"><h3>روابط سريعة</h3><ul>
        <li><a href="#home">الرئيسية</a></li><li><a href="#about">من نحن</a></li>
        <li><a href="#services">خدماتنا</a></li><li><a href="#gallery">معرض أعمالنا</a></li>
        <li><a href="#areas">مناطق الخدمة</a></li><li><a href="#testimonials">آراء العملاء</a></li>
        <li><a href="#why-us">لماذا نحن</a></li><li><a href="#contact">تواصل معنا</a></li>
      </ul></nav>
      <nav class="sf-col"><h3>خدماتنا</h3><ul>
        <?php foreach (array_slice($svcs, 0, 8) as $s): ?>
          <li><a href="#svc-<?= (int)$s['id'] ?>"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ul></nav>
      <div class="sf-col"><h3>بيانات التواصل</h3><ul class="sf-contact">
        <li><a href="tel:<?= e(setting('phone_intl')) ?>" class="sf-ltr"><?= e(setting('phone')) ?></a></li>
        <li><a href="https://wa.me/<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener">واتساب</a></li>
        <li><span><?= e(setting('address')) ?></span></li>
      </ul></div>
    </div>
    <div class="sf-bottom">
      <p class="sf-copy">&copy; <span id="sfYear"><?= date('Y') ?></span> جميع الحقوق محفوظة لـ<?= e(setting('site_name')) ?>.</p>
      <p class="sf-credit">تصميم وبرمجة <a href="<?= e(setting('credit_url')) ?>" target="_blank" rel="noopener"><?= e(setting('credit_text')) ?></a></p>
    </div>
  </div>
</footer>

<div class="float-btns" id="floatBtns">
  <a href="https://wa.me/<?= e(setting('whatsapp')) ?>" target="_blank" rel="noopener" class="float-btn float-wa" aria-label="واتساب"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.7-5.2A8.5 8.5 0 1 1 21 11.5z"/></svg></a>
  <a href="tel:<?= e(setting('phone_intl')) ?>" class="float-btn float-call" aria-label="اتصل"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
</div>

<script src="script.js" defer></script>
</body>
</html>