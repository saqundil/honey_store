<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$references = new App\Repositories\ReferenceRepository(db(), current_user_id());
$templateRepository = new App\Repositories\TemplateRepository(db(), current_user_id(), is_super_admin());
$authorization = new App\Services\AuthorizationService(db());
$classes = $references->classes();
$subjects = $references->subjects();
$years = $references->years();
$requestedTemplateId = (int) ($_GET['template'] ?? 0);
$versions = $templateRepository->availableVersions();
$selectedVersionId = 0;
foreach ($versions as $version) {
    if ((int) $version['template_id'] === $requestedTemplateId && (int) $version['id'] === (int) $version['current_version_id']) {
        $selectedVersionId = (int) $version['id'];
        break;
    }
}
$selectedVersion = current(array_filter($versions, static fn(array $version): bool => (int) $version['id'] === $selectedVersionId)) ?: ($versions[0] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $studentIds = array_values(array_unique(array_map('intval', $_POST['students'] ?? [])));
    if (!$studentIds) {
        die('يجب اختيار طالب واحد على الأقل.');
    }
    if (count($studentIds) > 32) {
        die('الحد الأقصى للتقرير الواحد هو 32 طالبًا.');
    }
    $classId = (int) ($_POST['class_id'] ?? 0);
    $yearId = (int) ($_POST['academic_year_id'] ?? 0);
    $versionId = (int) ($_POST['template_version_id'] ?? 0);
    $authorization->requireAccess('class', $classId, user());
    $authorization->requireAccess('academic_year', $yearId, user());
    $templateConfiguration = $templateRepository->configuration($versionId);
    if (!$templateConfiguration) {
        http_response_code(403);
        exit('إصدار القالب غير متاح لهذا الحساب.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT INTO reports(template_version_id,class_id,subject_id,academic_year_id,title,semester,report_date,created_by) VALUES(?,?,?,?,?,?,?,?)');
        $statement->execute([
            $versionId,
            $classId,
            (int) $_POST['subject_id'],
            $yearId,
            (string) $templateConfiguration['name'],
            trim((string) $_POST['semester']),
            $_POST['report_date'],
            (int) user()['id'],
        ]);
        $reportId = (int) $pdo->lastInsertId();
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $statement = $pdo->prepare("SELECT id,student_number,name FROM students WHERE class_id=? AND status='active' AND id IN ($placeholders) ORDER BY name");
        $statement->execute([$classId, ...$studentIds]);
        $insert = $pdo->prepare('INSERT INTO report_students(report_id,student_id,student_number_snapshot,student_name_snapshot,sort_order) VALUES(?,?,?,?,?)');
        foreach ($statement->fetchAll() as $order => $student) {
            $insert->execute([$reportId, $student['id'], $student['student_number'], $student['name'], $order + 1]);
        }
        $pdo->commit();
        redirect('admin/reports/view.php?id=' . $reportId);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

$allStudents = $references->students();
page_header('إنشاء تقرير', 'reports', ['assets/css/report-create.css', 'assets/css/report-create-visibility.css']);
?>
<form method="post" id="report-create-form" class="report-create-page">
    <?= csrf_field() ?>
    <header class="report-create-header">
        <div>
            <a href="<?= e(url('admin/reports/index.php')) ?>">التقارير <span aria-hidden="true">←</span></a>
            <p class="eyebrow">تقرير تقييم جديد</p>
            <h2>جهّز تقرير الصف</h2>
            <p>اختر القالب والطلاب، ثم انتقل مباشرة إلى إدخال العلامات.</p>
        </div>
        <div class="header-progress">
            <span class="active"><b>1</b> إعداد التقرير</span><i></i><span><b>2</b> إدخال العلامات</span>
        </div>
    </header>

    <div class="report-create-layout">
        <main class="report-setup">
            <section class="create-card">
                <div class="card-heading"><span>01</span><div><h3>بيانات التقرير</h3><p>المعلومات الأساسية التي ستظهر في رأس التقرير.</p></div></div>
                <div class="create-fields">
                    <label class="field-wide">إصدار القالب<select name="template_version_id" id="report-template" data-requested-template="<?= $requestedTemplateId > 0 ? 'true' : 'false' ?>" required><?php foreach ($versions as $version): ?><option value="<?= (int) $version['id'] ?>" <?= (int) $version['id'] === (int) ($selectedVersion['id'] ?? 0) ? 'selected' : '' ?>><?= e($version['name']) ?> · الإصدار <?= (int) $version['version_number'] ?></option><?php endforeach; ?></select></label>
                    <label>المادة<select name="subject_id" id="report-subject" required><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>"><?= e($subject['name']) ?></option><?php endforeach; ?></select></label>
                    <label>الصف<select name="class_id" id="report-class" required><?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" data-semester="<?= e($class['term_name']) ?>"><?= e($class['name']) ?></option><?php endforeach; ?></select></label>
                    <label>السنة الأكاديمية<select name="academic_year_id" id="report-year" required><?php foreach ($years as $year): ?><option value="<?= (int) $year['id'] ?>"><?= e($year['name']) ?></option><?php endforeach; ?></select></label>
                    <label>الفصل<input name="semester" id="report-semester" value="<?= e($classes[0]['term_name'] ?? '') ?>"></label>
                    <label>تاريخ التقرير<input type="date" name="report_date" id="report-date" required value="<?= date('Y-m-d') ?>"></label>
                </div>
            </section>
        </main>

        <aside class="student-selection">
            <div class="student-panel-head"><div><span>02</span><h3>طلاب التقرير</h3></div><strong id="student-count">0 / 32</strong></div>
            <div class="student-tools">
                <label class="student-search"><span aria-hidden="true">⌕</span><input id="student-search" type="search" placeholder="ابحث باسم الطالب..." autocomplete="off"></label>
                <div><button id="select-all-students" type="button">تحديد الكل</button><button id="clear-students" type="button">إلغاء التحديد</button></div>
            </div>
            <div id="student-list" class="student-list">
                <?php foreach ($allStudents as $student): ?>
                    <label class="student-row" data-class="<?= (int) $student['class_id'] ?>" data-name="<?= e(mb_strtolower($student['name'])) ?>">
                        <input type="checkbox" name="students[]" value="<?= (int) $student['id'] ?>" checked>
                        <span class="student-avatar"><?= e(mb_substr($student['name'], 0, 1)) ?></span>
                        <span class="student-info"><strong><?= e($student['name']) ?></strong><small><?= e($student['student_number'] ?? $student['class_name']) ?></small></span>
                        <span class="custom-check" aria-hidden="true">✓</span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p id="students-empty" class="students-empty" hidden>لا يوجد طلاب مطابقون للبحث.</p>
        </aside>
    </div>

    <footer class="create-footer">
        <div class="report-summary"><span>سيتم إنشاء تقرير لـ</span><strong><b id="summary-count">0</b> طالبًا</strong><i></i><span id="summary-template"></span></div>
        <button class="create-report-button" type="submit"><span>إنشاء التقرير</span><small>والانتقال لإدخال العلامات</small><b aria-hidden="true">←</b></button>
    </footer>
</form>
<?php page_footer(['assets/js/report-create.js']); ?>
