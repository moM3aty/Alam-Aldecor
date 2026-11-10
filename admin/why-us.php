<?php
define('APP_BOOT', true);
require __DIR__ . '/../config.php';
require __DIR__ . '/../functions.php';
requireLogin();

$activeTab = ($_GET['tab'] ?? 'cards') === 'steps' ? 'steps' : 'cards';

/* ============ AJAX ============ */
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        csrfCheck();
        $action = $_POST['action'] ?? '';
        $table  = $_POST['table']  ?? 'why_cards';
        if (!in_array($table, ['why_cards', 'why_steps'], true)) throw new Exception('invalid table');

        if ($action === 'reorder') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || !$ids) throw new Exception('invalid');
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE $table SET sort_order = ? WHERE id = ?");
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
            $pdo->prepare("UPDATE $table SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
            $new = (int)$pdo->query("SELECT is_active FROM $table WHERE id = $id")->fetchColumn();
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

/* ============ POST create/update/delete ============ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $tab = ($_POST['tab'] ?? 'cards') === 'steps' ? 'steps' : 'cards';
    $redirectBase = 'why-us.php?tab=' . $tab;

    /* Delete */
    if (isset($_POST['delete_id'])) {
        $id     = (int)$_POST['delete_id'];
        $table  = $tab === 'cards' ? 'why_cards' : 'why_steps';
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
        setFlash('ok', 'تم الحذف بنجاح.');
        redirect($redirectBase);
    }

    $id     = (int)($_POST['id'] ?? 0);
    $title  = trim((string)($_POST['title'] ?? ''));
    $desc   = trim((string)($_POST['description'] ?? ''));
    $active = isset($_POST['is_active']) ? 1 : 0;

    $errors = [];
    if (mb_strlen($title) < 2)   $errors[] = 'العنوان مطلوب.';
    if (mb_strlen($title) > 200) $errors[] = 'العنوان طويل جداً.';
    if (mb_strlen($desc) < 5)    $errors[] = 'الوصف قصير جداً.';
    if (mb_strlen($desc) > 500)  $errors[] = 'الوصف طويل جداً (500 حرف كحد أقصى).';

    if ($errors) {
        setFlash('err', implode(' ', $errors));
        redirect($id ? $redirectBase . '&edit=' . $id : $redirectBase);
    }

    if ($tab === 'cards') {
        if ($id > 0) {
            $pdo->prepare('UPDATE why_cards SET title=?, description=?, is_active=? WHERE id=?')
                ->execute([$title, $desc, $active, $id]);
            setFlash('ok', 'تم تحديث الكارت.');
        } else {
            $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM why_cards')->fetchColumn();
            $pdo->prepare('INSERT INTO why_cards (title, description, sort_order, is_active) VALUES (?,?,?,?)')
                ->execute([$title, $desc, $next, $active]);
            setFlash('ok', 'تمت إضافة الكارت.');
        }
    } else {
        $stepNum = (int)($_POST['step_number'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('UPDATE why_steps SET step_number=?, title=?, description=?, is_active=? WHERE id=?')
                ->execute([$stepNum, $title, $desc, $active, $id]);
            setFlash('ok', 'تم تحديث الخطوة.');
        } else {
            if ($stepNum <= 0) {
                $stepNum = (int)$pdo->query('SELECT COALESCE(MAX(step_number),0)+1 FROM why_steps')->fetchColumn();
            }
            $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM why_steps')->fetchColumn();
            $pdo->prepare('INSERT INTO why_steps (step_number, title, description, sort_order, is_active) VALUES (?,?,?,?,?)')
                ->execute([$stepNum, $title, $desc, $next, $active]);
            setFlash('ok', 'تمت إضافة الخطوة.');
        }
    }
    redirect($redirectBase);
}

/* ============ Load data ============ */
$cards = $pdo->query('SELECT * FROM why_cards ORDER BY sort_order, id')->fetchAll();
$steps = $pdo->query('SELECT * FROM why_steps ORDER BY sort_order, id')->fetchAll();

$editCardsId = (int)($_GET['edit'] ?? 0);
$editCards   = ($activeTab === 'cards' && $editCardsId)
    ? $pdo->query("SELECT * FROM why_cards WHERE id = $editCardsId")->fetch() : null;
$editStepsId = (int)($_GET['edit'] ?? 0);
$editSteps   = ($activeTab === 'steps' && $editStepsId)
    ? $pdo->query("SELECT * FROM why_steps WHERE id = $editStepsId")->fetch() : null;
$edit = $editCards ?: $editSteps;

$form = [
    'id'           => $edit['id']           ?? 0,
    'title'        => $edit['title']        ?? '',
    'description'  => $edit['description']  ?? '',
    'step_number'  => $edit['step_number']  ?? count($steps) + 1,
    'is_active'    => $edit ? (int)$edit['is_active'] : 1,
];

$cardsActive = count(array_filter($cards, fn($c) => $c['is_active']));
$stepsActive = count(array_filter($steps, fn($s) => $s['is_active']));

$pageTitle = 'لماذا نحن';
include 'includes/header.php';
?>

<!-- ========== TABS ========== -->
<div class="why-tabs" role="tablist">
  <a href="why-us.php?tab=cards" class="why-tab <?= $activeTab === 'cards' ? 'active' : '' ?>">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
    </svg>
    كروت "لماذا نحن"
    <span class="tab-count"><?= count($cards) ?></span>
  </a>
  <a href="why-us.php?tab=steps" class="why-tab <?= $activeTab === 'steps' ? 'active' : '' ?>">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
    </svg>
    خطوات العمل
    <span class="tab-count"><?= count($steps) ?></span>
  </a>
</div>

<?php /* ============================================================
          TAB 1: WHY CARDS
   ============================================================ */ ?>
<?php if ($activeTab === 'cards'): ?>

  <!-- Compose -->
  <section class="why-compose <?= $editCards ? 'is-editing' : '' ?>">
    <header class="why-compose-head">
      <span class="compose-ico">
        <?php if ($editCards): ?>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
          </svg>
        <?php else: ?>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        <?php endif; ?>
      </span>
      <div class="why-compose-title">
        <h3>
          <?= $editCards ? 'تعديل كارت' : 'إضافة كارت جديد' ?>
          <?php if ($editCards): ?><span class="edit-pill">تعديل</span><?php endif; ?>
        </h3>
        <p><?= $editCards ? 'قم بتحديث بيانات الكارت ثم احفظ' : 'أضف ميزة جديدة لعرضها في قسم "لماذا نحن"' ?></p>
      </div>
      <?php if ($editCards): ?>
        <a href="why-us.php?tab=cards" class="btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
          إلغاء
        </a>
      <?php endif; ?>
    </header>

    <div class="why-compose-body">
      <form method="post" class="tst-form">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="tab" value="cards">
        <input type="hidden" name="id" value="<?= (int)($editCards ? $form['id'] : 0) ?>">

        <div class="fld">
          <div class="fld-head">
            <label for="cardTitle">العنوان <span style="color:var(--red)">*</span></label>
            <span class="char-count" data-for="cardTitle" data-max="200">0 / 200</span>
          </div>
          <input id="cardTitle" name="title" type="text" required maxlength="200"
                 value="<?= e($editCards ? $form['title'] : '') ?>"
                 placeholder="مثال: تخصص واضح">
        </div>

        <div class="fld">
          <div class="fld-head">
            <label for="cardDesc">الوصف <span style="color:var(--red)">*</span></label>
            <span class="char-count" data-for="cardDesc" data-max="500">0 / 500</span>
          </div>
          <textarea id="cardDesc" name="description" rows="4" required maxlength="500"
                    placeholder="وصف الميزة..."><?= e($editCards ? $form['description'] : '') ?></textarea>
        </div>

        <label class="fld-inline" style="margin-bottom:0">
          <input type="checkbox" name="is_active" id="cardActive" <?= (!$editCards || $form['is_active']) ? 'checked' : '' ?>>
          <label for="cardActive">مفعّل ويظهر في الموقع</label>
        </label>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?php if ($editCards): ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
              </svg>
              <span>حفظ التعديلات</span>
            <?php else: ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              <span>إضافة الكارت</span>
            <?php endif; ?>
          </button>
          <?php if (!$editCards): ?>
            <button type="reset" class="btn btn-ghost">مسح</button>
          <?php endif; ?>
        </div>
      </form>

      <div class="why-preview-wrap">
        <span class="why-preview-label">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          معاينة مباشرة
        </span>
        <div class="why-preview">
          <span class="why-preview-num"><?= str_pad((string)count($cards) + 1, 2, '0', STR_PAD_LEFT) ?></span>
          <div class="why-preview-ico">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
          </div>
          <div class="why-preview-title" id="pvTitle"><?= e(($editCards ? $form['title'] : '') ?: 'عنوان الكارت') ?></div>
          <div class="why-preview-desc <?= ($editCards ? $form['description'] : '') === '' ? 'empty' : '' ?>" id="pvDesc"><?= e(($editCards ? $form['description'] : '') ?: 'وصف الميزة سيظهر هنا...') ?></div>
        </div>
      </div>
    </div>
  </section>

  <!-- Stats -->
  <div class="mini-stats" style="margin-bottom:16px">
    <span class="mini-stat gold"><span class="mdot"></span> الكل <b><?= count($cards) ?></b></span>
    <span class="mini-stat green"><span class="mdot"></span> مفعّلة <b><?= $cardsActive ?></b></span>
    <span class="mini-stat gray"><span class="mdot"></span> معطّلة <b><?= count($cards) - $cardsActive ?></b></span>
  </div>

  <!-- List -->
  <?php if (!$cards): ?>
    <div class="why-empty">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>
      <h3>لا توجد كروت بعد</h3>
      <p>ابدأ بإضافة أول ميزة لعرضها في الموقع.</p>
    </div>
  <?php else: ?>
    <div class="why-list" id="cardList">
      <?php foreach ($cards as $i => $c): ?>
        <div class="why-item <?= $c['is_active'] ? '' : 'is-inactive' ?>"
             data-id="<?= (int)$c['id'] ?>"
             data-table="why_cards"
             style="animation-delay:<?= $i * 0.03 ?>s">
          <button type="button" class="why-drag" aria-label="سحب" tabindex="-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
              <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
            </svg>
          </button>
          <span class="why-order"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="why-item-ico">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
          </span>
          <div class="why-item-body">
            <div class="why-item-title"><?= e($c['title']) ?></div>
            <div class="why-item-desc"><?= e($c['description']) ?></div>
          </div>
          <div class="why-item-actions">
            <label class="toggle" title="<?= $c['is_active'] ? 'مفعّل' : 'معطّل' ?>">
              <input type="checkbox" class="js-toggle" data-id="<?= (int)$c['id'] ?>" data-table="why_cards" <?= $c['is_active'] ? 'checked' : '' ?>>
              <span class="track"></span>
            </label>
            <a href="why-us.php?tab=cards&edit=<?= (int)$c['id'] ?>" class="btn-icon" title="تعديل">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
              </svg>
            </a>
            <form method="post" style="display:inline" data-confirm="حذف هذا الكارت نهائياً؟">
              <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="tab" value="cards">
              <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
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
  <?php endif; ?>

<?php /* ============================================================
          TAB 2: WHY STEPS
   ============================================================ */ ?>
<?php else: ?>

  <section class="why-compose <?= $editSteps ? 'is-editing' : '' ?>">
    <header class="why-compose-head">
      <span class="compose-ico">
        <?php if ($editSteps): ?>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
          </svg>
        <?php else: ?>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        <?php endif; ?>
      </span>
      <div class="why-compose-title">
        <h3>
          <?= $editSteps ? 'تعديل خطوة' : 'إضافة خطوة جديدة' ?>
          <?php if ($editSteps): ?><span class="edit-pill">تعديل</span><?php endif; ?>
        </h3>
        <p><?= $editSteps ? 'قم بتحديث بيانات الخطوة ثم احفظ' : 'أضف خطوة لعرضها في قسم "كيف نعمل"' ?></p>
      </div>
      <?php if ($editSteps): ?>
        <a href="why-us.php?tab=steps" class="btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
          إلغاء
        </a>
      <?php endif; ?>
    </header>

    <div class="why-compose-body">
      <form method="post" class="tst-form">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="tab" value="steps">
        <input type="hidden" name="id" value="<?= (int)($editSteps ? $form['id'] : 0) ?>">

        <div class="grid-2">
          <div class="fld">
            <label for="stepNum">رقم الخطوة</label>
            <input id="stepNum" name="step_number" type="number" min="1" max="20"
                   value="<?= (int)($editSteps ? $form['step_number'] : count($steps) + 1) ?>">
            <div class="fld-hint">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
              </svg>
              الرقم الظاهر داخل الدائرة
            </div>
          </div>

          <div class="fld">
            <div class="fld-head">
              <label for="stepTitle">العنوان <span style="color:var(--red)">*</span></label>
              <span class="char-count" data-for="stepTitle" data-max="200">0 / 200</span>
            </div>
            <input id="stepTitle" name="title" type="text" required maxlength="200"
                   value="<?= e($editSteps ? $form['title'] : '') ?>"
                   placeholder="مثال: التواصل والمعاينة">
          </div>
        </div>

        <div class="fld">
          <div class="fld-head">
            <label for="stepDesc">الوصف <span style="color:var(--red)">*</span></label>
            <span class="char-count" data-for="stepDesc" data-max="500">0 / 500</span>
          </div>
          <textarea id="stepDesc" name="description" rows="3" required maxlength="500"
                    placeholder="وصف الخطوة..."><?= e($editSteps ? $form['description'] : '') ?></textarea>
        </div>

        <label class="fld-inline" style="margin-bottom:0">
          <input type="checkbox" name="is_active" id="stepActive" <?= (!$editSteps || $form['is_active']) ? 'checked' : '' ?>>
          <label for="stepActive">مفعّلة وتظهر في الموقع</label>
        </label>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?php if ($editSteps): ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
              </svg>
              <span>حفظ التعديلات</span>
            <?php else: ?>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
              </svg>
              <span>إضافة الخطوة</span>
            <?php endif; ?>
          </button>
          <?php if (!$editSteps): ?>
            <button type="reset" class="btn btn-ghost">مسح</button>
          <?php endif; ?>
        </div>
      </form>

      <div class="step-preview-wrap">
        <span class="why-preview-label">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          معاينة مباشرة
        </span>
        <div class="step-preview">
          <div class="step-preview-circle" id="pvStepNum"><?= (int)($editSteps ? $form['step_number'] : count($steps) + 1) ?></div>
          <div class="step-preview-title" id="pvStepTitle"><?= e(($editSteps ? $form['title'] : '') ?: 'عنوان الخطوة') ?></div>
          <div class="step-preview-desc <?= ($editSteps ? $form['description'] : '') === '' ? 'empty' : '' ?>" id="pvStepDesc"><?= e(($editSteps ? $form['description'] : '') ?: 'وصف الخطوة سيظهر هنا...') ?></div>
        </div>
      </div>
    </div>
  </section>

  <div class="mini-stats" style="margin-bottom:16px">
    <span class="mini-stat gold"><span class="mdot"></span> الكل <b><?= count($steps) ?></b></span>
    <span class="mini-stat green"><span class="mdot"></span> مفعّلة <b><?= $stepsActive ?></b></span>
    <span class="mini-stat gray"><span class="mdot"></span> معطّلة <b><?= count($steps) - $stepsActive ?></b></span>
  </div>

  <?php if (!$steps): ?>
    <div class="why-empty">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
      </svg>
      <h3>لا توجد خطوات بعد</h3>
      <p>ابدأ بإضافة خطوات العمل لعرضها في الموقع.</p>
    </div>
  <?php else: ?>
    <div class="steps-list" id="stepList">
      <?php foreach ($steps as $i => $s): ?>
        <div class="step-item <?= $s['is_active'] ? '' : 'is-inactive' ?>"
             data-id="<?= (int)$s['id'] ?>"
             data-table="why_steps"
             style="animation-delay:<?= $i * 0.05 ?>s">

          <div class="step-num-circle"><?= (int)$s['step_number'] ?></div>
          <div class="step-item-title"><?= e($s['title']) ?></div>
          <div class="step-item-desc"><?= e($s['description']) ?></div>

          <div class="step-item-actions">
            <button type="button" class="step-drag" aria-label="سحب" tabindex="-1">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="9" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/>
                <circle cx="15" cy="6" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="15" cy="18" r="1.6"/>
              </svg>
            </button>

            <label class="toggle" title="<?= $s['is_active'] ? 'مفعّلة' : 'معطّلة' ?>">
              <input type="checkbox" class="js-toggle" data-id="<?= (int)$s['id'] ?>" data-table="why_steps" <?= $s['is_active'] ? 'checked' : '' ?>>
              <span class="track"></span>
            </label>

            <a href="why-us.php?tab=steps&edit=<?= (int)$s['id'] ?>" class="btn-icon" title="تعديل" style="width:30px;height:30px">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 20l4-1 11-11-3-3L5 16l-1 4z"/><path d="M14 6l3 3"/>
              </svg>
            </a>

            <form method="post" style="display:inline" data-confirm="حذف هذه الخطوة نهائياً؟">
              <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
              <input type="hidden" name="tab" value="steps">
              <input type="hidden" name="delete_id" value="<?= (int)$s['id'] ?>">
              <button type="submit" class="btn-icon" style="width:30px;height:30px;color:var(--red)" title="حذف">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                </svg>
              </button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<script>
(function () {
  'use strict';

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

  /* ===== LIVE PREVIEW: Cards ===== */
  var cardTitle = document.getElementById('cardTitle');
  var cardDesc  = document.getElementById('cardDesc');
  var pvTitle   = document.getElementById('pvTitle');
  var pvDesc    = document.getElementById('pvDesc');
  function updateCardPreview() {
    if (pvTitle) pvTitle.textContent = (cardTitle.value.trim() || 'عنوان الكارت');
    if (pvDesc) {
      var v = cardDesc.value.trim();
      pvDesc.textContent = v || 'وصف الميزة سيظهر هنا...';
      pvDesc.classList.toggle('empty', v === '');
    }
  }
  if (cardTitle) cardTitle.addEventListener('input', updateCardPreview);
  if (cardDesc)  cardDesc.addEventListener('input', updateCardPreview);

  /* ===== LIVE PREVIEW: Steps ===== */
  var stepNum   = document.getElementById('stepNum');
  var stepTitle = document.getElementById('stepTitle');
  var stepDesc  = document.getElementById('stepDesc');
  var pvStepNum = document.getElementById('pvStepNum');
  var pvStepTitle = document.getElementById('pvStepTitle');
  var pvStepDesc  = document.getElementById('pvStepDesc');
  function updateStepPreview() {
    if (pvStepNum && stepNum) pvStepNum.textContent = stepNum.value || '1';
    if (pvStepTitle) pvStepTitle.textContent = (stepTitle.value.trim() || 'عنوان الخطوة');
    if (pvStepDesc) {
      var v = stepDesc.value.trim();
      pvStepDesc.textContent = v || 'وصف الخطوة سيظهر هنا...';
      pvStepDesc.classList.toggle('empty', v === '');
    }
  }
  if (stepNum)   stepNum.addEventListener('input', updateStepPreview);
  if (stepTitle) stepTitle.addEventListener('input', updateStepPreview);
  if (stepDesc)  stepDesc.addEventListener('input', updateStepPreview);

  /* ===== Toggle active ===== */
  document.querySelectorAll('.js-toggle').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var id    = cb.getAttribute('data-id');
      var table = cb.getAttribute('data-table');
      var isOn  = cb.checked;
      var card  = cb.closest('.why-item, .step-item');
      cb.disabled = true;

      var fd = new FormData();
      fd.append('_csrf', CSRF);
      fd.append('action', 'toggle');
      fd.append('table', table);
      fd.append('id', id);

      fetch('why-us.php', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.ok) {
          if (card) card.classList.toggle('is-inactive', !res.is_active);
          cb.checked = !!res.is_active;
        } else {
          cb.checked = !isOn;
        }
      })
      .catch(function () { cb.checked = !isOn; })
      .finally(function () { cb.disabled = false; });
    });
  });

  /* ===== Drag reorder — Cards (vertical) ===== */
  (function initCardsDrag() {
    var list = document.getElementById('cardList');
    if (!list) return;
    var dragEl = null;
    var items = function () { return Array.prototype.slice.call(list.children); };

    items().forEach(function (card) {
      card.setAttribute('draggable', 'false');
      var handle = card.querySelector('.why-drag');
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
        items().forEach(function (c) { c.classList.remove('drop-top', 'drop-bottom'); });
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
        saveOrder(items(), 'why_cards');
      });
    });
  })();

  /* ===== Drag reorder — Steps (horizontal) ===== */
  (function initStepsDrag() {
    var list = document.getElementById('stepList');
    if (!list) return;
    var dragEl = null;
    var items = function () { return Array.prototype.slice.call(list.children); };

    items().forEach(function (card) {
      card.setAttribute('draggable', 'false');
      var handle = card.querySelector('.step-drag');
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
        items().forEach(function (c) { c.classList.remove('drop-before', 'drop-after'); });
        dragEl = null;
      });
      card.addEventListener('dragover', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var r = card.getBoundingClientRect();
        var isRtl = document.documentElement.dir === 'rtl';
        var after = (e.clientX - r.left) > r.width / 2;
        var before = isRtl ? !after : after;
        card.classList.toggle('drop-before', before);
        card.classList.toggle('drop-after', !before);
      });
      card.addEventListener('dragleave', function () {
        card.classList.remove('drop-before', 'drop-after');
      });
      card.addEventListener('drop', function (e) {
        if (!dragEl || dragEl === card) return;
        e.preventDefault();
        var r = card.getBoundingClientRect();
        var isRtl = document.documentElement.dir === 'rtl';
        var after = (e.clientX - r.left) > r.width / 2;
        var before = isRtl ? !after : after;
        if (before) list.insertBefore(dragEl, card);
        else        list.insertBefore(dragEl, card.nextSibling);
        card.classList.remove('drop-before', 'drop-after');
        saveOrder(items(), 'why_steps');
      });
    });
  })();

  function saveOrder(items, table) {
    var ids = items.map(function (c) { return c.dataset.id; });
    var fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('action', 'reorder');
    fd.append('table', table);
    ids.forEach(function (id, i) { fd.append('ids[' + i + ']', id); });
    fetch('why-us.php', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).catch(function () {});
  }
})();
</script>

<?php include 'includes/footer.php'; ?>