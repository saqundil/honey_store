<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$schemeId = (int) ($_GET['id'] ?? 0);
$schemeRepository = new App\Repositories\AssessmentSchemeRepository(db(), $actorId);
$scheme = $schemeId ? $schemeRepository->currentConfiguration($schemeId) : null;
if ($schemeId && !$scheme) {
    http_response_code(404);
    exit('مخطط التقييم غير موجود.');
}
$setupState = (new App\Services\TeacherSetupService(db()))->state($actorId);
$subjects = (new App\Repositories\ReferenceRepository(db(), $actorId))->subjects();
$templateVersions = (new App\Repositories\TemplateRepository(db(), $actorId))->availableVersions();
$initial = $scheme ?: [
    'assessment_scheme_id' => 0,
    'name' => '',
    'description' => '',
    'academic_term_id' => $setupState['terms'][0]['id'] ?? 0,
    'subject_id' => $subjects[0]['id'] ?? 0,
    'assessments' => [],
];
page_header($scheme ? 'إصدار جديد من مخطط التقييم' : 'مخطط تقييم جديد', 'assessments', ['assets/css/assessments.css']);
?>
<div class="assessment-editor" id="assessment-editor">
    <?php if (!$setupState['terms'] || !$subjects || !$templateVersions): ?>
        <div class="alert error">يلزم وجود فصل دراسي ومادة وقالب منشور واحد على الأقل قبل إنشاء مخطط.</div>
    <?php endif; ?>
    <section class="panel scheme-details">
        <div class="section-head"><div><p class="eyebrow">تعريف المخطط</p><h2><?= $scheme ? 'سيُحفظ كإصدار v' . ((int) $scheme['version_number'] + 1) : 'مخطط جديد' ?></h2></div><span class="save-state" id="save-state"></span></div>
        <div class="form-grid three">
            <label>اسم المخطط<input id="scheme-name" value="<?= school_e($initial['name']) ?>" required></label>
            <label>الفصل الدراسي<select id="scheme-term" required><?php foreach ($setupState['terms'] as $term): ?><option value="<?= (int) $term['id'] ?>" <?= (int) $initial['academic_term_id'] === (int) $term['id'] ? 'selected' : '' ?>><?= school_e($term['academic_year_name'] . ' · ' . $term['name']) ?></option><?php endforeach; ?></select></label>
            <label>المادة<select id="scheme-subject" required><?php foreach ($subjects as $subject): ?><option value="<?= (int) $subject['id'] ?>" <?= (int) $initial['subject_id'] === (int) $subject['id'] ? 'selected' : '' ?>><?= school_e($subject['name']) ?></option><?php endforeach; ?></select></label>
        </div>
        <label>وصف اختياري<textarea id="scheme-description" rows="2"><?= school_e($initial['description']) ?></textarea></label>
    </section>

    <div class="editor-toolbar"><div><p class="eyebrow">بنية الإصدار</p><h2>الاختبارات والقوالب</h2></div><button class="button secondary" id="add-assessment" type="button">إضافة اختبار</button></div>
    <div class="assessment-list" id="assessment-list"></div>
    <div class="publish-bar"><span>النشر ينشئ إصدارًا ثابتًا جديدًا ولا يعدّل الإصدارات السابقة.</span><button class="button primary" id="publish-scheme" type="button" <?= (!$setupState['terms'] || !$subjects || !$templateVersions) ? 'disabled' : '' ?>>نشر الإصدار</button></div>
</div>
<script>
window.ASSESSMENT_EDITOR = <?= json_encode([
    'baseUrl' => school_url(),
    'csrf' => school_csrf_token(),
    'schemeId' => (int) ($initial['assessment_scheme_id'] ?? $schemeId),
    'initial' => $initial['assessments'],
    'templateVersions' => $templateVersions,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php page_footer(['assets/js/assessment-schemes.js']); ?>
