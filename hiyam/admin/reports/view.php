<?php
declare(strict_types=1);require dirname(__DIR__,2).'/includes/bootstrap.php';require_admin();$reports=new App\Repositories\ReportRepository(db(),current_user_id(),is_super_admin());$report=$reports->find((int)($_GET['id']??0));if(!$report){http_response_code(404);exit('التقرير غير موجود');}$template=(new App\Repositories\TemplateRepository(db(),current_user_id(),is_super_admin()))->configuration((int)$report['template_version_id']);$students=$reports->students((int)$report['id']);$values=$reports->values((int)$report['id']);$engine=new App\Services\FormulaEngine();foreach($students as $student)$values[(int)$student['id']]=$engine->calculate($template['columns'],$values[(int)$student['id']]??[]);$table=(new App\Services\TableRenderer())->render($template,$students,$values,true,32);page_header($report['title'], 'reports', ['assets/css/report-sheet.css']);
?>
<div class="report-actions">
    <a class="button" target="_blank" rel="noopener" href="<?= e(url('reports/print.php?id=' . $report['id'])) ?>">طباعة</a>
    <button class="button primary" id="save-values" type="button">حفظ العلامات</button>
    <span id="save-indicator" role="status" aria-live="polite"></span>
</div>

<div class="preview-page">
    <div class="report-sheet report-sheet--dense">
        <?php require dirname(__DIR__, 2) . '/includes/report-header.php'; ?>
        <?= $table ?>
    </div>
</div>

<script>
window.REPORT_DATA = {
    id: <?= (int) $report['id'] ?>,
    columns: <?= json_encode($template['columns'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
window.APP = { baseUrl: <?= json_encode(url()) ?>, csrf: <?= json_encode(csrf_token()) ?> };
</script>
<?php page_footer(['assets/js/report-entry.js']); ?>
