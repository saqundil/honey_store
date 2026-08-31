<?php /** @var array<string, mixed> $report */ ?>
<header class="school-header">
	<div class="report-heading-title">
		<strong><?= e($report['title']) ?></strong>
		<span><?= e($report['class_name']) ?></span>
	</div>
	<div class="report-school-logo">
		<img src="<?= e(url(config('school.logo'))) ?>" alt="شعار المدرسة">
	</div>
	<div class="report-heading-subject">
		<strong>مبحث <?= e($report['subject_name']) ?></strong>
		<span>الفصل الدراسي <?= e($report['semester']) ?></span>
		<time datetime="<?= e($report['academic_year']) ?>"><?= e($report['academic_year']) ?></time>
	</div>
</header>