<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$repository = new App\Repositories\AssessmentSchemeRepository(db(), current_user_id());
$schemes = $repository->all();
page_header('خطط التقييم', 'assessments', ['assets/css/assessments.css']);
?>
<div class="section-head">
    <div>
        <p class="eyebrow">الاختبارات الديناميكية</p>
        <h2>مخططات التقييم</h2>
    </div>
    <a class="button primary" href="<?= school_e(school_url('admin/assessments/edit.php')) ?>">مخطط جديد</a>
</div>

<?php if ($schemes): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">المخطط</th>
                    <th scope="col">السياق</th>
                    <th scope="col">الإصدار الحالي</th>
                    <th scope="col">الحالة</th>
                    <th scope="col"><span class="sr-only">إجراءات</span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($schemes as $scheme): ?>
                <tr>
                    <td>
                        <strong><?= school_e($scheme['name']) ?></strong>
                        <?php if ($scheme['description']): ?><small><?= school_e($scheme['description']) ?></small><?php endif; ?>
                    </td>
                    <td>
                        <?= school_e($scheme['subject_name']) ?>
                        <small><?= school_e($scheme['academic_year_name'] . ' · ' . $scheme['term_name']) ?></small>
                    </td>
                    <td class="num"><?= $scheme['version_number'] ? 'v' . (int) $scheme['version_number'] : '—' ?></td>
                    <td><span class="status <?= school_e($scheme['status']) ?>"><?= $scheme['status'] === 'active' ? 'نشط' : 'معطل' ?></span></td>
                    <td class="actions-cell"><a href="<?= school_e(school_url('admin/assessments/edit.php?id=' . $scheme['id'])) ?>">إنشاء إصدار</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-mark" aria-hidden="true">＋</div>
        <h2>لا توجد مخططات تقييم بعد</h2>
        <p>المخطط يربط اختبارات الفصل بقوالبها وعلاماتها القصوى. كل نشر ينشئ إصدارًا ثابتًا لا تُغيّره التعديلات اللاحقة.</p>
        <a class="button primary" href="<?= school_e(school_url('admin/assessments/edit.php')) ?>">إنشاء أول مخطط</a>
    </div>
<?php endif; ?>
<?php page_footer(); ?>
