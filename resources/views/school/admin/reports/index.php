<?php
declare(strict_types=1);require dirname(__DIR__,2).'/includes/bootstrap.php';require_admin();$reports=(new App\Repositories\ReportRepository(db(),current_user_id(),is_super_admin()))->all();page_header('التقارير','reports');
?>
<div class="section-head">
    <div>
        <p class="eyebrow">السجلات</p>
        <h2>تقارير التقييم</h2>
    </div>
    <a class="button primary" href="<?= school_e(school_url('admin/reports/create.php')) ?>">تقرير جديد</a>
</div>

<?php if ($reports): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">العنوان</th>
                    <th scope="col">القالب</th>
                    <th scope="col">الصف</th>
                    <th scope="col">المادة</th>
                    <th scope="col">التاريخ</th>
                    <th scope="col"><span class="sr-only">إجراءات</span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><strong><?= school_e($report['title']) ?></strong></td>
                    <td><?= school_e($report['template_name']) ?><small>الإصدار <?= (int) $report['version_number'] ?></small></td>
                    <td><?= school_e($report['class_name']) ?></td>
                    <td><?= school_e($report['subject_name']) ?></td>
                    <td class="num"><?= school_e($report['report_date']) ?></td>
                    <td class="actions-cell"><a href="<?= school_e(school_url('admin/reports/view.php?id=' . $report['id'])) ?>">فتح</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-mark" aria-hidden="true">＋</div>
        <h2>لا توجد تقارير بعد</h2>
        <p>التقرير يجمّد نسخة من القالب مع قائمة الطلاب، فتبقى العلامات المطبوعة ثابتة مهما تغيّر القالب لاحقًا.</p>
        <a class="button primary" href="<?= school_e(school_url('admin/reports/create.php')) ?>">إنشاء أول تقرير</a>
    </div>
<?php endif; ?>
<?php page_footer(); ?>
