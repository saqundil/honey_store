<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require_role('teacher');

$teacherId = current_user_id();
$setup = new App\Services\TeacherSetupService(db());
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'academic_context') {
            $setup->saveAcademicContext($teacherId, (string) ($_POST['academic_year'] ?? ''), (string) ($_POST['term_name'] ?? ''));
        } elseif ($action === 'stage') {
            $setup->addStage($teacherId, (string) ($_POST['stage_name'] ?? ''));
        } elseif ($action === 'class') {
            $setup->addClass($teacherId, (int) ($_POST['term_id'] ?? 0), (int) ($_POST['stage_id'] ?? 0), (string) ($_POST['class_name'] ?? ''));
        }
        $state = $setup->state($teacherId);
        if ($state['complete'] && isset($_POST['finish'])) {
            school_redirect('admin/index.php');
        }
        school_redirect('admin/setup.php');
    } catch (InvalidArgumentException $exception) {
        $error = $exception->getMessage();
    }
}

$state = $setup->state($teacherId);
page_header('إعداد حساب المعلم', 'setup', ['assets/css/teacher-setup.css']);
?>
<div class="setup-shell">
    <header class="setup-heading">
        <p class="eyebrow">الإعداد الأول</p>
        <h2>جهّز سجل علاماتك</h2>
        <p>أضف السياق الدراسي الذي ستعمل عليه. يمكنك تعديل هذه البيانات لاحقًا من الإعدادات.</p>
    </header>

    <?php if ($error): ?><div class="alert error"><?= school_e($error) ?></div><?php endif; ?>

    <ol class="setup-progress" aria-label="خطوات الإعداد">
        <li class="<?= $state['terms'] ? 'done' : 'active' ?>"><span>1</span>العام والفصل</li>
        <li class="<?= $state['stages'] ? 'done' : ($state['terms'] ? 'active' : '') ?>"><span>2</span>المراحل</li>
        <li class="<?= $state['classes'] ? 'done' : ($state['stages'] ? 'active' : '') ?>"><span>3</span>الصفوف</li>
    </ol>

    <section class="setup-step">
        <div class="step-title"><span>01</span><div><h3>العام الدراسي والفصل</h3><p>كل فصل مرتبط بعام دراسي ومملوك لحسابك.</p></div></div>
        <form method="post" class="form-grid setup-form">
            <?= school_csrf_field() ?><input type="hidden" name="action" value="academic_context">
            <label>العام الدراسي<input name="academic_year" required pattern="\d{4}/\d{4}" placeholder="2027/2026" value="<?= school_e($state['years'][0]['name'] ?? '') ?>"></label>
            <label>الفصل الدراسي<input name="term_name" required placeholder="الفصل الأول"></label>
            <button class="button primary" type="submit">حفظ السياق الدراسي</button>
        </form>
        <?php if ($state['terms']): ?><div class="setup-items"><?php foreach ($state['terms'] as $term): ?><span><?= school_e($term['academic_year_name'] . ' · ' . $term['name']) ?></span><?php endforeach; ?></div><?php endif; ?>
    </section>

    <section class="setup-step <?= !$state['terms'] ? 'is-disabled' : '' ?>">
        <div class="step-title"><span>02</span><div><h3>المراحل الدراسية</h3><p>أضف المراحل التي تدرّسها بترتيبها الطبيعي.</p></div></div>
        <form method="post" class="inline-form setup-form">
            <?= school_csrf_field() ?><input type="hidden" name="action" value="stage">
            <input name="stage_name" required placeholder="مثال: الصف السابع" <?= !$state['terms'] ? 'disabled' : '' ?>>
            <button class="button primary" type="submit" <?= !$state['terms'] ? 'disabled' : '' ?>>إضافة مرحلة</button>
        </form>
        <?php if ($state['stages']): ?><div class="setup-items"><?php foreach ($state['stages'] as $stage): ?><span><?= school_e($stage['name']) ?></span><?php endforeach; ?></div><?php endif; ?>
    </section>

    <section class="setup-step <?= !$state['stages'] ? 'is-disabled' : '' ?>">
        <div class="step-title"><span>03</span><div><h3>الصفوف</h3><p>اربط كل صف بمرحلته والفصل الدراسي الصحيح.</p></div></div>
        <form method="post" class="form-grid setup-form">
            <?= school_csrf_field() ?><input type="hidden" name="action" value="class">
            <label>الفصل<select name="term_id" required <?= !$state['stages'] ? 'disabled' : '' ?>><?php foreach ($state['terms'] as $term): ?><option value="<?= (int) $term['id'] ?>"><?= school_e($term['academic_year_name'] . ' · ' . $term['name']) ?></option><?php endforeach; ?></select></label>
            <label>المرحلة<select name="stage_id" required <?= !$state['stages'] ? 'disabled' : '' ?>><?php foreach ($state['stages'] as $stage): ?><option value="<?= (int) $stage['id'] ?>"><?= school_e($stage['name']) ?></option><?php endforeach; ?></select></label>
            <label>اسم الصف<input name="class_name" required placeholder="مثال: السابع أ" <?= !$state['stages'] ? 'disabled' : '' ?>></label>
            <button class="button primary" type="submit" <?= !$state['stages'] ? 'disabled' : '' ?>>إضافة صف</button>
        </form>
        <?php if ($state['classes']): ?><div class="setup-items"><?php foreach ($state['classes'] as $class): ?><span><?= school_e($class['stage_name'] . ' · ' . $class['name']) ?></span><?php endforeach; ?></div><?php endif; ?>
    </section>

    <?php if ($state['complete']): ?>
        <form method="post" class="setup-finish"><?= school_csrf_field() ?><input type="hidden" name="action" value=""><button class="button primary" name="finish" value="1">الانتقال إلى لوحة التحكم</button></form>
    <?php endif; ?>
</div>
<?php page_footer(); ?>
