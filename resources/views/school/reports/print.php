<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$repository = new App\Repositories\ReportRepository(db(), current_user_id(), is_super_admin());
$batchToken = (string) ($_GET['batch'] ?? '');
$isBatch = $batchToken !== '';
$reports = $batchToken !== ''
	? $repository->batch($batchToken)
	: array_filter([$repository->find((int) ($_GET['id'] ?? 0))]);
if (!$reports) {
	http_response_code(404);
	exit('التقرير غير موجود');
}

$templateRepository = new App\Repositories\TemplateRepository(db(), current_user_id(), is_super_admin());
$engine = new App\Services\FormulaEngine();
$renderer = new App\Services\TableRenderer();
$pages = [];
foreach ($reports as $report) {
	$template = $templateRepository->configuration((int) $report['template_version_id']);
	if (!$template) continue;
	$students = $repository->students((int) $report['id']);
	$values = $repository->values((int) $report['id']);
	foreach ($students as $student) {
		$values[(int) $student['id']] = $engine->calculate($template['columns'], $values[(int) $student['id']] ?? []);
	}
	$pages[] = ['report' => $report, 'table' => $renderer->render($template, $students, $values, false, 32)];
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?= school_e($isBatch ? $reports[0]['group_name'] : $reports[0]['title']) ?></title>
	<link rel="stylesheet" href="<?= school_e(school_url('assets/css/report-sheet.css')) ?>">
	<link rel="stylesheet" href="<?= school_e(school_url('assets/css/report.css')) ?>">
	<link rel="stylesheet" href="<?= school_e(school_url('assets/css/school-brand.css')) ?>">
</head>
<body>
<div class="print-actions"><button onclick="window.print()">طباعة <?= $isBatch ? 'المجموعة' : 'التقرير' ?></button></div>
<?php foreach ($pages as $page): $report = $page['report']; ?>
	<main class="report-sheet report-sheet--dense"><?php require dirname(__DIR__) . '/includes/report-header.php'; ?><?= $page['table'] ?></main>
<?php endforeach; ?>
</body>
</html>