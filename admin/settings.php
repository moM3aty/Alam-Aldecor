<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

$allowedKeys = [
    'site_name','site_tagline','phone','phone_intl','whatsapp','address',
    'meta_title','meta_description','meta_keywords','og_title','og_description',
    'stat_1_num','stat_1_label','stat_2_num','stat_2_label','stat_3_num','stat_3_label',
    'footer_about','credit_text','credit_url'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $updates = [];
    foreach ($allowedKeys as $k) {
        $updates[$k] = trim((string)($_POST[$k] ?? ''));
    }

    /* Validation */
    if ($updates['site_name'] === '')           $errors[] = 'اسم الموقع مطلوب.';
    if ($updates['phone'] === '')               $errors[] = 'رقم الجوال مطلوب.';
    if (!preg_match('/^\+?\d{9,15}$/', preg_replace('/[^\d+]/', '', $updates['phone_intl']))) {
        $errors[] = 'رقم الجوال الدولي غير صالح (مثال: +966544371877).';
    }
    if (!preg_match('/^\d{9,15}$/', preg_replace('/[^\d]/', '', $updates['whatsapp']))) {
        $errors[] = 'رقم واتساب غير صالح (أرقام فقط، بدون +).';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value)
                               VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($updates as $k => $v) {
            $stmt->execute([$k, $v]);
        }
        setFlash('ok', 'تم تحديث جميع الإعدادات بنجاح.');
        redirect('settings.php');
    } else {
        setFlash('err', implode(' ', $errors));
    }
}

$S = getSettings();
$pageTitle = 'الإعدادات';
include 'includes/header.php';
?>

<form method="post" novalidate>
  <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">

  <div class="settings-layout">

    <!-- ============ NAV ============ -->
    <aside class="settings-nav">
      <a href="#sec-general" class="active">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          <path d="M9 22V12h6v10"/>
        </svg>
        معلومات الموقع
      </a>
      <a href="#sec-contact">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
        </svg>
        التواصل
      </a>
      <a href="#sec-seo">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        SEO
      </a>
      <a href="#sec-stats">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 3v18h18"/>
          <path d="M18 17V9M13 17V5M8 17v-3"/>
        </svg>
        الإحصائيات
      </a>
      <a href="#sec-footer">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <path d="M3 15h18"/>
        </svg>
        التذييل والحقوق
      </a>
    </aside>

    <!-- ============ BODY ============ -->
    <div class="settings-body">

      <!-- ==== General ==== -->
      <div class="settings-section" id="sec-general">
        <div class="settings-section-head">
          <span class="ss-ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <path d="M9 22V12h6v10"/>
            </svg>
          </span>
          <div class="ss-info">
            <h3>معلومات الموقع</h3>
            <p>اسم العلامة التجارية والوصف المختصر</p>
          </div>
        </div>
        <div class="settings-section-body">
          <div class="grid-2">
            <div class="fld">
              <label for="site_name">اسم الموقع <span style="color:var(--red)">*</span></label>
              <input id="site_name" name="site_name" type="text" required maxlength="80"
                     value="<?= e($S['site_name'] ?? '') ?>" placeholder="عالم الديكور">
            </div>
            <div class="fld">
              <label for="site_tagline">الوصف المختصر</label>
              <input id="site_tagline" name="site_tagline" type="text" maxlength="60"
                     value="<?= e($S['site_tagline'] ?? '') ?>" placeholder="جبس بورد ودهانات">
            </div>
          </div>
        </div>
      </div>

      <!-- ==== Contact ==== -->
      <div class="settings-section" id="sec-contact">
        <div class="settings-section-head">
          <span class="ss-ico teal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
          </span>
          <div class="ss-info">
            <h3>بيانات التواصل</h3>
            <p>الهاتف والواتساب والعنوان</p>
          </div>
        </div>
        <div class="settings-section-body">
          <div class="grid-2">
            <div class="fld">
              <label for="phone">رقم الجوال (للعرض)</label>
              <input id="phone" name="phone" type="tel" maxlength="20"
                     value="<?= e($S['phone'] ?? '') ?>" dir="ltr" placeholder="0544371877">
            </div>
            <div class="fld">
              <label for="phone_intl">رقم الجوال الدولي</label>
              <input id="phone_intl" name="phone_intl" type="tel" maxlength="20"
                     value="<?= e($S['phone_intl'] ?? '') ?>" dir="ltr" placeholder="+966544371877">
              <div class="fld-hint">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
                </svg>
                يُستخدم في روابط الاتصال (tel:)
              </div>
            </div>
          </div>

          <div class="fld">
            <label for="whatsapp">رقم واتساب (بدون +)</label>
            <input id="whatsapp" name="whatsapp" type="tel" maxlength="20"
                   value="<?= e($S['whatsapp'] ?? '') ?>" dir="ltr" placeholder="966544371877">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              يُستخدم في روابط wa.me — يتطلب رمز الدولة بدون +
            </div>
          </div>

          <div class="fld" style="margin-bottom:0">
            <label for="address">العنوان</label>
            <input id="address" name="address" type="text" maxlength="200"
                   value="<?= e($S['address'] ?? '') ?>"
                   placeholder="خميس مشيط – أبها – عسير – المملكة العربية السعودية">
          </div>
        </div>
      </div>

      <!-- ==== SEO ==== -->
      <div class="settings-section" id="sec-seo">
        <div class="settings-section-head">
          <span class="ss-ico terra">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
          </span>
          <div class="ss-info">
            <h3>تحسين محركات البحث (SEO)</h3>
            <p>عناوين وأوصاف تظهر في نتائج البحث</p>
          </div>
        </div>
        <div class="settings-section-body">
          <div class="fld">
            <div class="fld-head">
              <label for="meta_title">Meta Title</label>
              <span class="char-count" data-for="meta_title" data-max="70">0 / 70</span>
            </div>
            <input id="meta_title" name="meta_title" type="text" maxlength="70"
                   value="<?= e($S['meta_title'] ?? '') ?>"
                   placeholder="عالم الديكور | جبس بورد ودهانات في خميس مشيط وأبها">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              الأفضل 50–60 حرفاً.
            </div>
          </div>

          <div class="fld">
            <div class="fld-head">
              <label for="meta_description">Meta Description</label>
              <span class="char-count" data-for="meta_description" data-max="160">0 / 160</span>
            </div>
            <textarea id="meta_description" name="meta_description" rows="2" maxlength="160"
                      placeholder="وصف موجز يظهر في نتائج البحث..."><?= e($S['meta_description'] ?? '') ?></textarea>
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              الأفضل 120–160 حرفاً.
            </div>
          </div>

          <div class="fld">
            <label for="meta_keywords">Meta Keywords</label>
            <textarea id="meta_keywords" name="meta_keywords" rows="2"
                      placeholder="جبس بورد خميس مشيط, دهانات أبها, ..."><?= e($S['meta_keywords'] ?? '') ?></textarea>
          </div>

          <div class="grid-2">
            <div class="fld">
              <label for="og_title">OG Title</label>
              <input id="og_title" name="og_title" type="text" maxlength="100"
                     value="<?= e($S['og_title'] ?? '') ?>" placeholder="عنوان المشاركة على السوشيال">
            </div>
            <div class="fld">
              <label for="og_description">OG Description</label>
              <input id="og_description" name="og_description" type="text" maxlength="200"
                     value="<?= e($S['og_description'] ?? '') ?>" placeholder="وصف المشاركة">
            </div>
          </div>
        </div>
      </div>

      <!-- ==== Stats ==== -->
      <div class="settings-section" id="sec-stats">
        <div class="settings-section-head">
          <span class="ss-ico">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 3v18h18"/>
              <path d="M18 17V9M13 17V5M8 17v-3"/>
            </svg>
          </span>
          <div class="ss-info">
            <h3>الأرقام والإحصائيات</h3>
            <p>تظهر في Hero و About و Why Us</p>
          </div>
        </div>
        <div class="settings-section-body">
          <div class="grid-3">
            <div class="fld">
              <label for="stat_1_num">الرقم 1</label>
              <input id="stat_1_num" name="stat_1_num" type="text" maxlength="6"
                     value="<?= e($S['stat_1_num'] ?? '') ?>" dir="ltr">
            </div>
            <div class="fld" style="grid-column: span 2;">
              <label for="stat_1_label">الوصف 1</label>
              <input id="stat_1_label" name="stat_1_label" type="text" maxlength="50"
                     value="<?= e($S['stat_1_label'] ?? '') ?>">
            </div>
          </div>

          <div class="grid-3">
            <div class="fld">
              <label for="stat_2_num">الرقم 2</label>
              <input id="stat_2_num" name="stat_2_num" type="text" maxlength="6"
                     value="<?= e($S['stat_2_num'] ?? '') ?>" dir="ltr">
            </div>
            <div class="fld" style="grid-column: span 2;">
              <label for="stat_2_label">الوصف 2</label>
              <input id="stat_2_label" name="stat_2_label" type="text" maxlength="50"
                     value="<?= e($S['stat_2_label'] ?? '') ?>">
            </div>
          </div>

          <div class="grid-3" style="margin-bottom:0">
            <div class="fld">
              <label for="stat_3_num">الرقم 3</label>
              <input id="stat_3_num" name="stat_3_num" type="text" maxlength="6"
                     value="<?= e($S['stat_3_num'] ?? '') ?>" dir="ltr">
            </div>
            <div class="fld" style="grid-column: span 2;">
              <label for="stat_3_label">الوصف 3</label>
              <input id="stat_3_label" name="stat_3_label" type="text" maxlength="50"
                     value="<?= e($S['stat_3_label'] ?? '') ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- ==== Footer ==== -->
      <div class="settings-section" id="sec-footer">
        <div class="settings-section-head">
          <span class="ss-ico teal">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <path d="M3 15h18"/>
            </svg>
          </span>
          <div class="ss-info">
            <h3>التذييل والحقوق</h3>
            <p>النص التعريفي في أسفل الموقع + حقوق المطور</p>
          </div>
        </div>
        <div class="settings-section-body">
          <div class="fld">
            <div class="fld-head">
              <label for="footer_about">نص التذييل</label>
              <span class="char-count" data-for="footer_about" data-max="500">0 / 500</span>
            </div>
            <textarea id="footer_about" name="footer_about" rows="3" maxlength="500"
                      placeholder="متخصصون في أعمال الجبس بورد والدهانات..."><?= e($S['footer_about'] ?? '') ?></textarea>
          </div>

          <div class="grid-2" style="margin-bottom:0">
            <div class="fld">
              <label for="credit_text">اسم المطور</label>
              <input id="credit_text" name="credit_text" type="text" maxlength="50"
                     value="<?= e($S['credit_text'] ?? '') ?>" placeholder="GMTWeb">
            </div>
            <div class="fld">
              <label for="credit_url">رابط المطور</label>
              <input id="credit_url" name="credit_url" type="url" maxlength="300"
                     value="<?= e($S['credit_url'] ?? '') ?>" dir="ltr" placeholder="https://...">
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Save bar -->
  <div class="settings-save-bar">
    <button type="submit" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
        <polyline points="17 21 17 13 7 13 7 21"/>
        <polyline points="7 3 7 8 15 8"/>
      </svg>
      <span>حفظ كل الإعدادات</span>
    </button>
    <a href="../index.php" target="_blank" rel="noopener" class="btn btn-ghost">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
        <polyline points="15 3 21 3 21 9"/>
        <line x1="10" y1="14" x2="21" y2="3"/>
      </svg>
      <span>معاينة الموقع</span>
    </a>
    <div class="grow"></div>
    <span class="ssb-hint">
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

  /* Smooth nav scroll + active state */
  var navLinks = document.querySelectorAll('.settings-nav a');
  var sections = document.querySelectorAll('.settings-section');

  navLinks.forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var id = link.getAttribute('href');
      var target = document.querySelector(id);
      if (!target) return;
      var top = target.getBoundingClientRect().top + window.pageYOffset - 100;
      window.scrollTo({ top: top, behavior: 'smooth' });
      navLinks.forEach(function (l) { l.classList.toggle('active', l === link); });
    });
  });

  /* Scroll spy */
  if ('IntersectionObserver' in window && sections.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          var id = '#' + en.target.id;
          navLinks.forEach(function (l) {
            l.classList.toggle('active', l.getAttribute('href') === id);
          });
        }
      });
    }, { rootMargin: '-30% 0px -60% 0px', threshold: 0 });

    sections.forEach(function (s) { io.observe(s); });
  }
})();
</script>

<?php include 'includes/footer.php'; ?>