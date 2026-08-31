<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$assignmentId = (int) ($_GET['id'] ?? 0);
$gradebooks = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
$service = new App\Services\GradebookReportService($gradebooks, new App\Repositories\TemplateRepository(db(), $actorId, is_super_admin()));
try {
    $report = $service->assignmentReport($assignmentId);
} catch (InvalidArgumentException $exception) {
    http_response_code(404);
    exit(e($exception->getMessage()));
}
$context = $report['context'];
page_header('تقارير ' . $context['class_name'], 'gradebook', ['assets/css/gradebook-report.css']);
?>
<div class="report-hub">
    <header class="report-hub-head"><div><p class="eyebrow"><?= e($context['scheme_name'] . ' · v' . $context['version_number']) ?></p><h2><?= e($context['class_name'] . ' · ' . $context['subject_name']) ?></h2><p><?= e($context['academic_year_name'] . ' · ' . $context['term_name']) ?></p></div><div class="orientation-actions"><a class="button" target="_blank" href="<?= e(url('gradebook/print.php?type=class&id=' . $assignmentId . '&orientation=portrait')) ?>">طباعة طولية</a><a class="button primary" target="_blank" href="<?= e(url('gradebook/print.php?type=class&id=' . $assignmentId . '&orientation=landscape')) ?>">طباعة عرضية</a></div></header>

    <section class="panel summary-panel">
        <div class="section-head"><div><p class="eyebrow">ملخص الفصل</p><h3>نتائج الطلاب حسب الاختبار</h3></div><span><?= count($report['students']) ?> طالب</span></div>
        <div class="table-wrap"><table class="data-table grade-summary-table"><thead><tr><th>م</th><th>اسم الطالب</th><?php foreach ($report['assessments'] as $assessment): ?><th><?= e($assessment['short_name']) ?><small>/ <?= $service->format((float) $assessment['maximum']) ?></small></th><?php endforeach; ?><th>المجموع</th><th>النسبة</th><th></th></tr></thead><tbody>
        <?php foreach ($report['students'] as $index => $student): ?><tr><td><?= $index + 1 ?></td><td><strong><?= e($student['name']) ?></strong><small><?= e($student['student_number']) ?></small></td><?php foreach ($report['assessments'] as $assessment): ?><td><?= e($service->format($student['assessments'][(int) $assessment['id']] ?? null)) ?></td><?php endforeach; ?><td><?= e($service->format($student['earned'])) ?> / <?= e($service->format($student['maximum'])) ?></td><td><?= $student['percentage'] === null ? '—' : e($service->format($student['percentage'])) . '%' ?></td><td><a href="<?= e(url('gradebook/print.php?type=student&id=' . $assignmentId . '&enrollment=' . $student['enrollment_id'] . '&orientation=portrait')) ?>" target="_blank">كشف الطالب</a></td></tr><?php endforeach; ?>
        <?php if (!$report['students']): ?><tr><td colspan="<?= count($report['assessments']) + 5 ?>" class="empty">لا يوجد طلاب مسجلون في هذا الفصل.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="panel assessment-report-list">
        <div class="section-head"><div><p class="eyebrow">تفاصيل الاختبارات</p><h3>كشوف القوالب</h3></div></div>
        <?php foreach ($report['assessments'] as $assessment): ?><article><div><strong><?= e($assessment['name']) ?></strong><small><?= (int) $assessment['template_count'] ?> قالب · <?= e($assessment['status']) ?></small></div><div><a target="_blank" href="<?= e(url('gradebook/print.php?type=assessment&id=' . $assessment['id'] . '&orientation=portrait')) ?>">طولي</a><a target="_blank" href="<?= e(url('gradebook/print.php?type=assessment&id=' . $assessment['id'] . '&orientation=landscape')) ?>">عرضي</a></div></article><?php endforeach; ?>
    </section>
</div>
<?php page_footer(); ?>
