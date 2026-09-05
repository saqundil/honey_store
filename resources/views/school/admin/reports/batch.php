<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$token = (string) ($_GET['batch'] ?? '');
$reports = (new App\Repositories\ReportRepository(db(), current_user_id(), is_super_admin()))->batch($token);
if (!$reports) {
    http_response_code(404);
    exit('مجموعة التقارير غير موجودة.');
}

page_header('تقارير ' . $reports[0]['group_name'], 'reports');
?>
<div class="section-head">
    <div>
        <p class="eyebrow">مجموعة قوالب</p>
        <h2><?= school_e($reports[0]['group_name']) ?></h2>
        <p><?= count($reports) ?> تقريرًا للصف <?= school_e($reports[0]['class_name']) ?></p>
    </div>
    <div class="quick-actions">
        <a class="button" href="<?= school_e(school_url('admin/reports/index.php')) ?>">كل التقارير</a>
        <a class="button primary" target="_blank" rel="noopener" href="<?= school_e(school_url('reports/print.php?batch=' . $token)) ?>">طباعة المجموعة</a>
    </div>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">القالب</th>
                <th scope="col">الإصدار</th>
                <th scope="col">الصف</th>
                <th scope="col">التاريخ</th>
                <th scope="col"><span class="sr-only">إجراءات</span></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><strong><?= school_e($report['template_name']) ?></strong></td>
                <td class="num">v<?= (int) $report['version_number'] ?></td>
                <td><?= school_e($report['class_name']) ?></td>
                <td class="num"><?= school_e($report['report_date']) ?></td>
                <td class="actions-cell"><a href="<?= school_e(school_url('admin/reports/view.php?id=' . $report['id'])) ?>">إدخال العلامات</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php page_footer(); ?>