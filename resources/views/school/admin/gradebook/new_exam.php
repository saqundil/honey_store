<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$classId = (int) ($_GET['class_id'] ?? 0);

(new App\Services\AuthorizationService(db()))->requireAccess('class', $classId, user());

$repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
$class = $repository->classCard($classId);
if (!$class) {
    http_response_code(404);
    exit('الصف غير موجود.');
}

/**
 * الأقسام الجاهزة لإعادة الاستخدام.
 *
 * القسم في الواجهة = جدول علامات؛ نعرض الإصدار الحالي فقط ونسمّيه باسمه،
 * فلا يرى المعلم كلمة «قالب» ولا رقم إصدار.
 */
$reusable = [];
foreach ((new App\Repositories\TemplateRepository(db(), $actorId, is_super_admin()))->availableVersions() as $version) {
    if ((int) $version['id'] !== (int) $version['current_version_id']) {
        continue;
    }
    $reusable[] = ['id' => (int) $version['id'], 'name' => (string) $version['name']];
}

teacher_shell_header(
    'إنشاء اختبار',
    [
        ['الصفوف', 'admin/classes/index.php'],
        [$class['name'], 'admin/gradebook/class.php?id=' . $classId],
        ['إنشاء اختبار', null],
    ],
    ['assets/css/exam-wizard.css']
);
?>
<div class="wizard" id="wizard">

    <ol class="wizard-steps" aria-label="خطوات إنشاء الاختبار">
        <li data-step-tab="1" aria-current="step"><span>1</span> معلومات الاختبار</li>
        <li data-step-tab="2"><span>2</span> أقسام الاختبار</li>
        <li data-step-tab="3"><span>3</span> المراجعة</li>
    </ol>

    <!-- الخطوة ١ — معلومات الاختبار -->
    <section class="wizard-panel is-active" data-step="1">
        <header class="wizard-head">
            <h2>معلومات الاختبار</h2>
            <p>الصف والفصل معروفان من سياقك، فلا حاجة لاختيارهما.</p>
        </header>

        <div class="wizard-context">
            <div><small>الصف</small><strong><?= school_e($class['name']) ?></strong></div>
            <div><small>الفصل</small><strong><?= school_e($class['term_name']) ?></strong></div>
            <div><small>العام الدراسي</small><strong><?= school_e($class['academic_year_name']) ?></strong></div>
            <div><small>الطلاب</small><strong><?= (int) $class['students_count'] ?></strong></div>
        </div>

        <div class="wizard-fields">
            <label>اسم الاختبار
                <input id="exam-name" type="text" maxlength="190" required autocomplete="off" placeholder="مثال: اختبار شهري 1">
            </label>
            <label>التاريخ
                <input id="exam-date" type="date" value="<?= school_e(date('Y-m-d')) ?>">
            </label>
        </div>

        <div class="wizard-error" role="alert" hidden></div>

        <footer class="wizard-foot">
            <a class="tbtn" href="<?= school_e(school_url('admin/gradebook/class.php?id=' . $classId)) ?>">إلغاء</a>
            <button type="button" class="tbtn tbtn-primary" data-next>التالي</button>
        </footer>
    </section>

    <!-- الخطوة ٢ — الأقسام -->
    <section class="wizard-panel" data-step="2">
        <header class="wizard-head">
            <h2>أقسام الاختبار</h2>
            <p>قسّم الاختبار إلى أقسام مثل: محادثة، كتابة، قراءة، إملاء. لكل قسم أعمدته وعلاماته.</p>
        </header>

        <div id="section-list" class="section-list"></div>

        <div class="wizard-error" role="alert" hidden></div>

        <footer class="wizard-foot">
            <button type="button" class="tbtn" data-back>رجوع</button>
            <button type="button" class="tbtn tbtn-primary" data-next>التالي</button>
        </footer>
    </section>

    <!-- الخطوة ٣ — المراجعة -->
    <section class="wizard-panel" data-step="3">
        <header class="wizard-head">
            <h2>مراجعة الاختبار</h2>
            <p>راجع الأقسام قبل الإنشاء. يمكنك الرجوع وتعديل أي قسم.</p>
        </header>

        <div id="review" class="review"></div>

        <div class="wizard-error" role="alert" hidden></div>

        <footer class="wizard-foot">
            <button type="button" class="tbtn" data-back>رجوع</button>
            <button type="button" class="tbtn tbtn-primary" id="create-exam">إنشاء الاختبار</button>
        </footer>
    </section>
</div>

<!-- حوار القسم: اختيار جاهز أو إنشاء جديد بأعمدته -->
<div class="t-modal-scrim" id="section-dialog" hidden>
    <div class="t-modal section-modal" role="dialog" aria-modal="true" aria-labelledby="section-dialog-title">
        <h3 id="section-dialog-title">إضافة قسم</h3>

        <div class="section-mode" role="radiogroup" aria-label="طريقة إضافة القسم">
            <label class="mode-choice">
                <input type="radio" name="section-mode" value="new" checked>
                <span><strong>إنشاء قسم جديد</strong><small>تحدّد اسمه وأعمدته</small></span>
            </label>
            <label class="mode-choice" <?= $reusable ? '' : 'hidden' ?>>
                <input type="radio" name="section-mode" value="existing">
                <span><strong>استخدام قسم موجود</strong><small>من أقسام أنشأتها سابقًا</small></span>
            </label>
        </div>

        <div class="section-form" data-mode="new">
            <label>اسم القسم
                <input id="section-name" type="text" maxlength="190" placeholder="مثال: المحادثة" autocomplete="off">
            </label>

            <div class="columns-head">
                <strong>الأعمدة</strong>
                <span id="section-total" class="section-total">0 درجة</span>
            </div>
            <div id="column-list" class="column-list"></div>
            <button type="button" class="tbtn tbtn-sm" id="add-column">＋ إضافة عمود</button>
        </div>

        <div class="section-form" data-mode="existing" hidden>
            <label>القسم
                <select id="section-existing">
                    <?php foreach ($reusable as $item): ?>
                        <option value="<?= $item['id'] ?>"><?= school_e($item['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small>يُستخدم بأعمدته وعلاماته كما هي.</small>
            </label>
        </div>

        <div class="section-error" role="alert" hidden></div>

        <div class="t-modal-actions">
            <button type="button" class="tbtn tbtn-primary" id="save-section">حفظ القسم</button>
            <button type="button" class="tbtn" data-close-section>إلغاء</button>
        </div>
    </div>
</div>

<script>
    window.APP = { baseUrl: <?= json_encode(school_url()) ?>, csrf: <?= json_encode(school_csrf_token()) ?> };
    window.WIZARD = {
        class_id: <?= $classId ?>,
        class_name: <?= json_encode($class['name'], JSON_UNESCAPED_UNICODE) ?>,
        reusable: <?= json_encode($reusable, JSON_UNESCAPED_UNICODE) ?>
    };
</script>

<?php
teacher_shell_footer(['assets/js/exam-wizard.js']);
