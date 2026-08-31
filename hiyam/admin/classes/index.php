<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$teacherId = current_user_id();
$setup = new App\Services\TeacherSetupService(db());
$state = $setup->state($teacherId);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	verify_csrf($_POST['csrf_token'] ?? null);
	try {
		$setup->addClass($teacherId, (int) ($_POST['term_id'] ?? 0), (int) ($_POST['stage_id'] ?? 0), (string) ($_POST['name'] ?? ''));
		redirect('admin/classes/index.php');
	} catch (InvalidArgumentException $exception) {
		$error = $exception->getMessage();
	}
}

$state = $setup->state($teacherId);
page_header('الصفوف', 'classes');
// تجميع الصفوف تحت مراحلها ليقرأها المعلم كما يفكّر بها
$byStage = [];
foreach ($state['classes'] as $item) {
	$byStage[$item['stage_name']][] = $item;
}
?>
<div class="section-head">
	<div>
		<p class="eyebrow"><?= e(($state['terms'][0]['academic_year_name'] ?? '') . ' · ' . ($state['terms'][0]['name'] ?? '')) ?></p>
		<h2>الصفوف</h2>
	</div>
	<?php if ($state['terms'] && $state['stages']): ?>
		<button class="button primary" type="button" data-toggle-add-class>＋ إضافة صف</button>
	<?php endif; ?>
</div>

<?php if ($error): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if (!$state['terms'] || !$state['stages']): ?>
	<div class="empty-state">
		<div class="empty-state-mark" aria-hidden="true">!</div>
		<h2>أكمل الإعداد أولًا</h2>
		<p>الصف يحتاج إلى فصل دراسي ومرحلة قبل إنشائه. اضبط السياق الدراسي مرة واحدة ثم عُد إلى هنا.</p>
		<a class="button primary" href="<?= e(url('admin/setup.php')) ?>">فتح الإعداد</a>
	</div>
<?php else: ?>
	<section class="panel add-class-panel" <?= $error ? '' : 'hidden' ?>>
		<h2>إضافة صف</h2>
		<form method="post" class="form-grid">
			<?= csrf_field() ?>
			<label>اسم الصف<input name="name" placeholder="مثال: السابع أ" required></label>
			<label>المرحلة<select name="stage_id" required><?php foreach ($state['stages'] as $stage): ?><option value="<?= (int) $stage['id'] ?>"><?= e($stage['name']) ?></option><?php endforeach; ?></select></label>
			<label>الفصل الدراسي<select name="term_id" required><?php foreach ($state['terms'] as $term): ?><option value="<?= (int) $term['id'] ?>"><?= e($term['academic_year_name'] . ' · ' . $term['name']) ?></option><?php endforeach; ?></select></label>
			<div class="button-row full">
				<button class="button primary">إضافة الصف</button>
				<button class="button ghost" type="button" data-toggle-add-class>إلغاء</button>
			</div>
		</form>
	</section>

	<?php if ($byStage): ?>
		<?php foreach ($byStage as $stageName => $items): ?>
			<section class="stage-block">
				<h3 class="stage-title"><?= e($stageName) ?> <span><?= count($items) ?></span></h3>
				<div class="class-links">
					<?php foreach ($items as $item): ?>
						<a class="class-link" href="<?= e(url('admin/gradebook/class.php?id=' . (int) $item['id'])) ?>">
							<strong><?= e($item['name']) ?></strong>
							<small><?= e($item['term_name']) ?></small>
							<span class="go" aria-hidden="true">→</span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php else: ?>
		<div class="empty-state">
			<div class="empty-state-mark" aria-hidden="true">＋</div>
			<h2>لا توجد صفوف بعد</h2>
			<p>أضف أول صف لتبدأ بتسجيل الطلاب وإنشاء الاختبارات.</p>
			<button class="button primary" type="button" data-toggle-add-class>إضافة أول صف</button>
		</div>
	<?php endif; ?>
<?php endif; ?>

<script>
document.querySelectorAll('[data-toggle-add-class]').forEach(button => {
	button.addEventListener('click', () => {
		const panel = document.querySelector('.add-class-panel');
		panel.hidden = !panel.hidden;
		if (!panel.hidden) panel.querySelector('input[name="name"]').focus();
	});
});
</script>
<?php page_footer(); ?>
