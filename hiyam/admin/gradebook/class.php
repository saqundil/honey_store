<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$actorId = current_user_id();
$classId = (int) ($_GET['id'] ?? 0);

$repository = new App\Repositories\GradebookRepository(db(), $actorId, is_super_admin());
$class = $repository->classCard($classId);
if (!$class) {
    http_response_code(404);
    exit('الصف غير موجود.');
}

$exams = $repository->classExams($classId);

$counts = ['all' => count($exams), 'draft' => 0, 'open' => 0, 'locked' => 0];
foreach ($exams as $exam) {
    $counts[$exam['status']] = ($counts[$exam['status']] ?? 0) + 1;
}

$breadcrumbs = [
    ['الصفوف', 'admin/classes/index.php'],
    [$class['name'], null],
];

teacher_shell_header(
    $class['name'],
    $breadcrumbs,
    ['assets/css/teacher-class.css']
);

$statusLabels = ['draft' => 'مسودة', 'open' => 'مفتوح', 'locked' => 'مقفل'];
?>
<section class="class-context" aria-labelledby="class-title">
    <div class="class-context-title">
        <h2 id="class-title"><?= e($class['name']) ?></h2>
        <p><?= e($class['stage_name'] . ' · ' . $class['term_name'] . ' ' . $class['academic_year_name']) ?></p>
    </div>
    <div class="class-context-meta">
        <div>
            <strong><?= (int) $class['students_count'] ?></strong>
            <small><?= (int) $class['students_count'] === 1 ? 'طالب' : 'طالبًا' ?></small>
        </div>
        <div>
            <strong><?= (int) $counts['all'] ?></strong>
            <small><?= (int) $counts['all'] === 1 ? 'اختبار' : 'اختبارًا' ?></small>
        </div>
        <a class="tbtn tbtn-primary" href="<?= e(url('admin/gradebook/new_exam.php?class_id=' . $classId)) ?>">
            <span aria-hidden="true">＋</span>
            إنشاء اختبار
        </a>
    </div>
</section>

<nav class="class-actions" aria-label="إجراءات الصف">
    <a class="class-action" href="<?= e(url('admin/students/index.php?class_id=' . $classId)) ?>">
        <strong>الطلاب</strong>
        <small><?= (int) $class['students_count'] ?> مسجّلًا · إضافة واستيراد</small>
    </a>
    <?php if ($counts['all'] > 0): ?>
        <a class="class-action" href="<?= e(url('admin/gradebook/report.php?id=' . (int) $class['active_assignment_id'])) ?>">
            <strong>تقرير الصف</strong>
            <small>نتائج الطلاب في كل الاختبارات</small>
        </a>
    <?php endif; ?>
</nav>

<div class="exam-list-wrap">

    <div class="exam-list-head">
        <h3>الاختبارات <span class="exam-count">(<?= (int) $counts['all'] ?>)</span></h3>
    </div>

    <?php if ($exams): ?>
        <div class="exam-filters" role="group" aria-label="تصفية حسب الحالة">
            <button type="button" class="exam-filter" data-filter="all" aria-pressed="true">
                الكل <span class="n"><?= (int) $counts['all'] ?></span>
            </button>
            <button type="button" class="exam-filter" data-filter="draft" aria-pressed="false">
                مسودة <span class="n"><?= (int) ($counts['draft'] ?? 0) ?></span>
            </button>
            <button type="button" class="exam-filter" data-filter="open" aria-pressed="false">
                مفتوح <span class="n"><?= (int) ($counts['open'] ?? 0) ?></span>
            </button>
            <button type="button" class="exam-filter" data-filter="locked" aria-pressed="false">
                مقفل <span class="n"><?= (int) ($counts['locked'] ?? 0) ?></span>
            </button>
        </div>

        <div class="exam-grid" role="list">
            <?php foreach ($exams as $exam):
                $entryUrl = url('admin/gradebook/entry.php?id=' . (int) $exam['id']);
                $recorded = (int) $exam['recorded_count'];
                $total = max(0, (int) $exam['students_count']);
                $pct = $total > 0 ? min(100, (int) round($recorded / $total * 100)) : 0;
                $status = $exam['status'];
                $dateDisplay = $exam['exam_date']
                    ? date('Y-m-d', strtotime((string) $exam['exam_date']))
                    : date('Y-m-d', strtotime((string) $exam['created_at']));
            ?>
                <article class="exam-card" role="listitem" data-status="<?= e($status) ?>">
                    <div class="exam-card-primary">
                        <div class="exam-card-title">
                            <a href="<?= e($entryUrl) ?>"><?= e($exam['assessment_name']) ?></a>
                        </div>
                        <div class="exam-card-meta">
                            <?php if ($exam['template_name']): ?>
                                <span><?= e($exam['template_name']) ?></span>
                                <span class="dot" aria-hidden="true"></span>
                            <?php endif; ?>
                            <?php if ($exam['exam_date']): ?>
                                <time datetime="<?= e($exam['exam_date']) ?>"><?= e($dateDisplay) ?></time>
                            <?php else: ?>
                                <time datetime="<?= e($exam['created_at']) ?>" title="تاريخ الإنشاء"><?= e($dateDisplay) ?></time>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="exam-card-actions">
                        <span class="exam-card-status" data-status="<?= e($status) ?>">
                            <?= e($statusLabels[$status] ?? $status) ?>
                        </span>
                        <a class="exam-card-open" href="<?= e($entryUrl) ?>">
                            <span>فتح</span>
                            <span class="arrow" aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="exam-card-progress">
                        <span class="exam-progress-bar" aria-hidden="true">
                            <span class="exam-progress-fill" style="width: <?= $pct ?>%"></span>
                        </span>
                        <span class="exam-progress-text"><?= $recorded ?>/<?= $total ?> طالبًا</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="exam-empty exam-empty-filter" hidden>
            <h4>لا اختبارات في هذه الحالة</h4>
            <p>غيّر التصفية لعرض اختبارات أخرى.</p>
        </div>
    <?php else: ?>
        <div class="exam-empty">
            <h4>لا يوجد اختبارات بعد</h4>
            <p>أنشئ أول اختبار للصف باختيار قالب وعنوان وتاريخ.</p>
            <a class="tbtn tbtn-primary" href="<?= e(url('admin/gradebook/new_exam.php?class_id=' . $classId)) ?>">
                <span aria-hidden="true">＋</span>
                إنشاء أول اختبار
            </a>
        </div>
    <?php endif; ?>
</div>



<script>
    window.APP = {
        baseUrl: <?= json_encode(url()) ?>,
        csrf: <?= json_encode(csrf_token()) ?>
    };
    window.CLASS_DATA = { class_id: <?= (int) $classId ?> };
</script>

<?php
teacher_shell_footer(['assets/js/teacher-class.js']);
