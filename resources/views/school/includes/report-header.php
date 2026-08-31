<?php /** @var array<string, mixed> $report */ ?>
<header class="school-header">
	<div class="report-heading-title">
		<strong><?= school_e($report['title']) ?></strong>
		<span><?= school_e($report['class_name']) ?></span>
	</div>
	<div class="report-school-logo">
		<img src="<?= school_e(school_url(school_config('school.logo'))) ?>" alt="شعار المدرسة">
	</div>
	<div class="report-heading-subject">
		<strong>مبحث <?= school_e($report['subject_name']) ?></strong>
		<span>الفصل الدراسي <?= school_e($report['semester']) ?></span>
		<time datetime="<?= school_e($report['academic_year']) ?>"><?= school_e($report['academic_year']) ?></time>
	</div>
</header>