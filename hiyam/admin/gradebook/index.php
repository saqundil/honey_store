<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
$classes = $repository->teacherClasses();

$userName = user()['name'] ?? '';
$firstName = trim(explode(' ', $userName)[0] ?? '');
$greeting = 'مرحبًا' . ($firstName !== '' ? ", {$firstName}" : '');

teacher_shell_header(
    'الاختبارات والعلامات',
    [['الصفوف', 'admin/classes/index.php'], ['الاختبارات والعلامات', null]],
    ['assets/css/teacher-classes.css']
);
?>
<section class="classes-hero">
    <div class="classes-hero-inner">
        <div>
            <h1><?= e($greeting) ?></h1>
            <p>اختر صفًا لعرض اختباراته ورصد العلامات.</p>
        </div>
        <div class="classes-hero-user">
            <strong>صفوفي</strong>
            <span><?= count($classes) ?> صفًا نشطًا</span>
        </div>
    </div>
</section>

<div class="classes-wrap">
    <?php if ($classes): ?>
        <div class="classes-grid" role="list">
            <?php foreach ($classes as $class):
                $classId    = (int) $class['id'];
                $hasScheme  = !empty($class['active_scheme_version_id']);
                $open       = (int) $class['open_count'];
                $locked     = (int) $class['locked_count'];
                $draft      = (int) $class['draft_count'];
                $total      = $open + $locked + $draft;
                $students   = (int) $class['students_count'];
                $url        = url('admin/gradebook/class.php?id=' . $classId);
                $lastLabel  = ar_relative_time($class['last_activity_at']);
            ?>
                <a class="class-card"
                   href="<?= e($url) ?>"
                   role="listitem"
                   aria-label="<?= e($class['name'] . ' — ' . $students . ' طالبًا، ' . $total . ' اختبار') ?>"
                   data-empty="<?= $total === 0 ? 'true' : 'false' ?>">
                    <header class="class-card-head">
                        <div class="class-card-name"><?= e($class['name']) ?></div>
                        <div class="class-card-sub">
                            <?= e($class['stage_name']) ?>
                            · <?= e($class['term_name']) ?>
                            · <?= e($class['academic_year_name']) ?>
                        </div>
                    </header>

                    <div class="class-card-stats">
                        <div class="stat-students">
                            <strong><?= $students ?></strong>
                            <small><?= $students === 1 ? 'طالب' : 'طالبًا' ?></small>
                        </div>
                        <div class="stat-exams">
                            <?php if ($total === 0): ?>
                                <span class="stat-exams-none">لا توجد اختبارات بعد</span>
                            <?php else: ?>
                                <?php if ($open > 0): ?>
                                    <span class="stat-exam-chip" data-kind="open"><span class="n"><?= $open ?></span> مفتوح</span>
                                <?php endif; ?>
                                <?php if ($locked > 0): ?>
                                    <span class="stat-exam-chip" data-kind="locked"><span class="n"><?= $locked ?></span> مقفل</span>
                                <?php endif; ?>
                                <?php if ($draft > 0): ?>
                                    <span class="stat-exam-chip" data-kind="draft"><span class="n"><?= $draft ?></span> مسودة</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$hasScheme): ?>
                        <span class="class-card-warn">الصف يحتاج إعدادًا أوليًا</span>
                    <?php endif; ?>

                    <footer class="class-card-foot">
                        <time datetime="<?= e((string) $class['last_activity_at']) ?>"><?= e($lastLabel) ?></time>
                        <span class="go">
                            فتح الاختبارات
                            <span class="arrow" aria-hidden="true">→</span>
                        </span>
                    </footer>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="classes-empty">
            <div class="classes-empty-mark" aria-hidden="true">＋</div>
            <h2>لم تُنشئ أي صف بعد</h2>
            <p>أضف صفًا لتبدأ بتسجيل الطلاب وإنشاء الاختبارات ورصد العلامات.</p>
            <a class="tbtn tbtn-primary" href="<?= e(url('admin/classes/index.php')) ?>">إضافة أول صف</a>
        </div>
    <?php endif; ?>
</div>

<?php
teacher_shell_footer();
