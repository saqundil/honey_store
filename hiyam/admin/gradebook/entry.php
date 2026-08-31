<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$classAssessmentId = (int) ($_GET['id'] ?? 0);
$repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
$context = $repository->context($classAssessmentId);
if (!$context) {
    http_response_code(404);
    exit('اختبار الصف غير موجود.');
}

$templateAssignments = $repository->templates($classAssessmentId);
$enrollments = $repository->enrollments($classAssessmentId);
$values = $repository->values($classAssessmentId);
$revisions = $repository->revisions($classAssessmentId);
$templateRepository = new App\Repositories\TemplateRepository(db(), $actorId, is_super_admin());
$renderer = new App\Services\TableRenderer();
$engine = new App\Services\FormulaEngine();

$configurations = [];
foreach ($templateAssignments as $assignment) {
    $templateId = (int) $assignment['id'];
    $template = $templateRepository->configuration((int) $assignment['template_version_id']);
    if (!$template) continue;
    foreach ($enrollments as $enrollment) {
        $values[$templateId][(int) $enrollment['id']] = $engine->calculate(
            $template['columns'],
            $values[$templateId][(int) $enrollment['id']] ?? []
        );
    }
    $configurations[$templateId] = $template;
}

$locked = $context['status'] === 'locked';
$statusLabel = ['draft' => 'مسودة', 'open' => 'مفتوح', 'locked' => 'مقفل'][$context['status']] ?? $context['status'];
$className = $context['class_name'];
$breadcrumbs = [
    ['الصفوف', 'admin/classes/index.php'],
    [$className, 'admin/gradebook/class.php?id=' . (int) $context['class_id']],
    [$context['assessment_name'], null],
];

teacher_shell_header(
    $context['assessment_name'],
    $breadcrumbs,
    ['assets/css/teacher-entry.css']
);
?>
<section class="entry-context" aria-labelledby="entry-title">
    <div class="entry-context-title">
        <h2 id="entry-title"><?= e($context['assessment_name']) ?></h2>
        <p><?= e($className . ' · ' . $context['subject_name'] . ' · ' . $context['term_name'] . ' ' . $context['academic_year_name']) ?></p>
    </div>

    <div class="entry-progress" role="group" aria-label="نسبة اكتمال الرصد">
        <span class="entry-progress-bar" aria-hidden="true"><span class="entry-progress-fill" style="width:0%"></span></span>
        <span class="entry-progress-text" aria-live="polite">—</span>
    </div>

    <div class="entry-save" data-state="saved" aria-live="polite" aria-atomic="true">
        <span class="entry-save-dot" aria-hidden="true"></span>
        <span class="entry-save-text">جاهز</span>
        <button type="button" class="entry-save-retry" hidden>إعادة المحاولة</button>
    </div>

    <div class="entry-actions">
        <span class="entry-status" data-status="<?= e($context['status']) ?>"><?= e($statusLabel) ?></span>
        <?php if ($locked): ?>
            <button type="button" class="tbtn tbtn-sm" data-status-action="reopen">إعادة فتح</button>
        <?php else: ?>
            <button type="button" class="tbtn tbtn-sm tbtn-warn" data-status-action="lock">قفل الاختبار</button>
        <?php endif; ?>
    </div>
</section>

<?php if ($locked): ?>
<div class="entry-lock-notice" role="status">
    <span class="lock-icon" aria-hidden="true">🔒</span>
    <div>
        <strong>هذا الاختبار مقفل.</strong>
        الجدول للقراءة فقط. أعد فتحه إذا احتجت إلى تعديل العلامات.
    </div>
    <button type="button" class="tbtn tbtn-sm" data-status-action="reopen">إعادة فتح</button>
</div>
<?php endif; ?>

<?php if (count($templateAssignments) > 1): ?>
<nav class="entry-tabs" role="tablist" aria-label="قوالب الاختبار">
    <?php foreach ($templateAssignments as $index => $assignment): ?>
        <button
            type="button"
            role="tab"
            aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
            data-template-tab="<?= (int) $assignment['id'] ?>"
        ><?= e($assignment['label']) ?></button>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

<?php foreach ($templateAssignments as $index => $assignment):
    $assignmentId = (int) $assignment['id'];
    $template = $configurations[$assignmentId] ?? null;
    if (!$template) continue;
?>
    <section
        class="entry-panel <?= $index === 0 ? 'is-active' : '' ?>"
        role="tabpanel"
        aria-labelledby="tab-<?= $assignmentId ?>"
        data-template-panel="<?= $assignmentId ?>"
    >
        <div class="entry-grid-wrap" data-locked="<?= $locked ? 'true' : 'false' ?>">
            <?= $renderer->render($template, $enrollments, $values[$assignmentId] ?? [], !$locked) ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if (!$templateAssignments): ?>
    <div class="entry-grid-wrap" style="padding:24px;text-align:center;color:var(--t-ink-muted)">
        لا توجد قوالب نشطة في هذا الاختبار.
    </div>
<?php endif; ?>

<script>
    window.GRADEBOOK_DATA = <?= json_encode([
        'id' => $classAssessmentId,
        'locked' => $locked,
        'revisions' => $revisions,
        'templates' => array_map(
            static fn(array $t): array => [
                'id' => (int) $t['id'],
                'columns' => $configurations[(int) $t['id']]['columns'] ?? [],
            ],
            $templateAssignments
        ),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.APP = {
        baseUrl: <?= json_encode(url()) ?>,
        csrf: <?= json_encode(csrf_token()) ?>
    };
</script>

<?php
teacher_shell_footer(['assets/js/teacher-entry.js']);
