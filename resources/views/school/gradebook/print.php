<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$type = in_array($_GET['type'] ?? '', ['class', 'student', 'assessment'], true) ? (string) $_GET['type'] : 'class';
$orientation = ($_GET['orientation'] ?? '') === 'landscape' ? 'landscape' : 'portrait';
$resourceId = (int) ($_GET['id'] ?? 0);
$gradebooks = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
$templates = new App\Repositories\TemplateRepository(db(), $actorId, is_super_admin());
$service = new App\Services\GradebookReportService($gradebooks, $templates);
$renderer = new App\Services\TableRenderer();
try {
    $reportData = match ($type) {
        'student' => $service->studentReport($resourceId, (int) ($_GET['enrollment'] ?? 0)),
        'assessment' => $service->assessmentReport($resourceId),
        default => $service->assignmentReport($resourceId),
    };
} catch (InvalidArgumentException $exception) {
    http_response_code(404);
    exit(school_e($exception->getMessage()));
}
$context = $reportData['context'];
$title = match ($type) {
    'student' => 'كشف الطالب ' . $reportData['student']['name'],
    'assessment' => $context['assessment_name'],
    default => 'كشف علامات الصف ' . $context['class_name'],
};
$headerReport = ['title' => $title, 'class_name' => $context['class_name'], 'subject_name' => $context['subject_name'], 'semester' => $context['term_name'], 'academic_year' => $context['academic_year_name']];
?><!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= school_e($title) ?></title><link rel="stylesheet" href="<?= school_e(school_url('assets/css/report-sheet.css')) ?>"><link rel="stylesheet" href="<?= school_e(school_url('assets/css/report.css')) ?>"><link rel="stylesheet" href="<?= school_e(school_url('assets/css/school-brand.css')) ?>"><link rel="stylesheet" href="<?= school_e(school_url('assets/css/gradebook-report.css')) ?>"><style>@page{size:A4 <?= $orientation ?>;margin:0}</style></head><body class="gradebook-print <?= school_e($orientation) ?>"><div class="print-actions"><button onclick="window.print()">طباعة</button><a href="<?= school_e(school_url('gradebook/print.php?' . http_build_query(array_merge($_GET, ['orientation' => $orientation === 'portrait' ? 'landscape' : 'portrait'])))) ?>">اتجاه <?= $orientation === 'portrait' ? 'عرضي' : 'طولي' ?></a></div>
<?php if ($type === 'class'): ?>
<main class="gradebook-print-sheet"><div class="gradebook-print-frame"><?php $report = $headerReport; require dirname(__DIR__) . '/includes/report-header.php'; ?><div class="print-report-caption"><?= school_e($context['scheme_name'] . ' · الإصدار ' . $context['version_number']) ?></div><table class="grade-summary-print"><thead><tr><th>م</th><th>اسم الطالب</th><?php foreach ($reportData['assessments'] as $assessment): ?><th><?= school_e($assessment['short_name']) ?><small>/ <?= $service->format((float) $assessment['maximum']) ?></small></th><?php endforeach; ?><th>المجموع</th><th>%</th></tr></thead><tbody><?php foreach ($reportData['students'] as $index => $student): ?><tr><td><?= $index + 1 ?></td><td><?= school_e($student['name']) ?></td><?php foreach ($reportData['assessments'] as $assessment): ?><td><?= school_e($service->format($student['assessments'][(int) $assessment['id']] ?? null)) ?></td><?php endforeach; ?><td><?= school_e($service->format($student['earned'])) ?> / <?= school_e($service->format($student['maximum'])) ?></td><td><?= $student['percentage'] === null ? '—' : school_e($service->format($student['percentage'])) ?></td></tr><?php endforeach; ?></tbody></table></div></main>
<?php elseif ($type === 'student'): $student = $reportData['student']; ?>
<main class="gradebook-print-sheet student-print-sheet"><div class="gradebook-print-frame"><?php $report = $headerReport; require dirname(__DIR__) . '/includes/report-header.php'; ?><section class="student-report-identity"><strong><?= school_e($student['name']) ?></strong><span>الرقم المدرسي: <?= school_e($student['student_number']) ?></span></section><table class="grade-summary-print student-summary-print"><thead><tr><th>الاختبار</th><th>العلامة</th><th>من</th><th>النسبة</th><th>الحالة</th></tr></thead><tbody><?php foreach ($reportData['assessments'] as $assessment): $score = $student['assessments'][(int) $assessment['id']] ?? null; ?><tr><td><?= school_e($assessment['name']) ?></td><td><?= school_e($service->format($score)) ?></td><td><?= school_e($service->format((float) $assessment['maximum'])) ?></td><td><?= $score === null || !$assessment['maximum'] ? '—' : school_e($service->format($score / $assessment['maximum'] * 100)) . '%' ?></td><td><?= school_e($assessment['status']) ?></td></tr><?php endforeach; ?></tbody><tfoot><tr><th>المجموع</th><th><?= school_e($service->format($student['earned'])) ?></th><th><?= school_e($service->format($student['maximum'])) ?></th><th><?= $student['percentage'] === null ? '—' : school_e($service->format($student['percentage'])) . '%' ?></th><th></th></tr></tfoot></table></div></main>
<?php else: foreach ($reportData['sections'] as $section): ?>
<main class="gradebook-print-sheet assessment-print-sheet"><div class="gradebook-print-frame"><?php $report = array_merge($headerReport, ['title' => $context['assessment_name'] . ' · ' . $section['assignment']['label']]); require dirname(__DIR__) . '/includes/report-header.php'; ?><div class="print-report-caption"><?= school_e($section['assignment']['template_name'] . ' · v' . $section['assignment']['version_number']) ?></div><?= $renderer->render($section['template'], $reportData['students'], $section['values'], false) ?></div></main>
<?php endforeach; endif; ?></body></html>
