<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$teacherId = current_user_id();
if (!is_super_admin() && !(new App\Services\TeacherSetupService(db()))->state($teacherId)['complete']) {
	redirect('admin/setup.php');
}
$counts = [];
foreach (['القوالب' => ['table_templates', 'created_by'], 'الطلاب' => ['students', 'teacher_id'], 'التقارير' => ['reports', 'created_by']] as $label => [$table, $ownerColumn]) {
	if (is_super_admin()) {
		$counts[$label] = (int) db()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
	} else {
		$statement = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$ownerColumn} = ?");
		$statement->execute([$teacherId]);
		$counts[$label] = (int) $statement->fetchColumn();
	}
}
page_header('لوحة التحكم', 'dashboard');
?>
<section class="welcome">
	<p class="eyebrow">نظرة عامة</p>
	<h2>إدارة تقييمات الطلاب</h2>
	<p>أنشئ القوالب، أدخل العلامات، واطبع تقارير منظمة من مكان واحد.</p>
</section>

<section class="stats">
	<?php foreach ($counts as $label => $count): ?>
		<article>
			<span><?= e($label) ?></span>
			<strong><?= $count ?></strong>
		</article>
	<?php endforeach; ?>
</section>

<section class="section">
	<div class="section-head">
		<div>
			<p class="eyebrow">اختصارات</p>
			<h2>ابدأ مهمة</h2>
		</div>
	</div>
	<div class="quick-actions">
		<a href="<?= e(url('admin/templates/edit.php')) ?>">إنشاء قالب جديد</a>
		<a href="<?= e(url('admin/templates/import.php')) ?>">استيراد قالب من جدول</a>
		<a href="<?= e(url('admin/reports/create.php')) ?>">إنشاء تقرير</a>
		<!-- <a href="<?= e(url('admin/students/index.php')) ?>">إضافة طالب</a>
		<a href="<?= e(url('admin/gradebook/index.php')) ?>">فتح سجل العلامات</a> -->
	</div>
</section>
<?php page_footer(); ?>
