<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php'; require_admin();
$repository=new App\Repositories\TemplateRepository(db(),current_user_id(),is_super_admin()); $id=(int)($_GET['id']??0); $template=$id?$repository->currentConfiguration($id):null;
if($id&&!$template){http_response_code(404);exit('القالب غير موجود');}
if($template){
    $groupKeys=[]; foreach($template['groups'] as $group)$groupKeys[(int)$group['id']]=$group['group_key'];
    foreach($template['groups'] as &$group)$group['parent_key']=$group['parent_id']?($groupKeys[(int)$group['parent_id']]??null):null; unset($group);
    foreach($template['columns'] as &$column){$column['header_group_key']=$column['header_group_id']?($groupKeys[(int)$column['header_group_id']]??null):null;$column['is_visible']=(bool)$column['is_visible'];}unset($column);
}elseif(isset($_GET['import'])&&!empty($_SESSION['template_import'])){
    // مسودة قادمة من admin/templates/import.php: تُعرض للمراجعة ولا تُحفظ قبل «حفظ الإصدار»
    $template=$_SESSION['template_import']; unset($_SESSION['template_import']);
    $importNotes=$template['notes']??[]; unset($template['notes']);
}else{$template=['template_id'=>0,'name'=>'جدول علامات جديد','description'=>'','settings'=>[],'groups'=>[],'columns'=>[
    ['column_key'=>'student_number','name'=>'الرقم','header_label'=>'','type'=>'student_number','max_mark'=>'','step_value'=>0.25,'width_mm'=>10,'sort_order'=>1,'is_visible'=>true,'header_group_key'=>null,'text_direction'=>'rtl','display_direction'=>'horizontal'],
    ['column_key'=>'student_name','name'=>'اسم الطالب','header_label'=>'','type'=>'student_name','max_mark'=>'','step_value'=>0.25,'width_mm'=>48,'sort_order'=>2,'is_visible'=>true,'header_group_key'=>null,'text_direction'=>'rtl','display_direction'=>'horizontal'],
]];}
$importNotes=$importNotes??[];
page_header($id?'محرر جدول العلامات':'جدول علامات جديد','templates',array_merge(['assets/css/template-builder.css','assets/css/report-sheet.css'],isset($_GET['import'])?['assets/css/template-import.css']:[]));
?>
<div id="builder" class="tb">

    <header class="tb-bar">
        <div class="tb-bar-main">
            <a class="tb-back" href="<?= e(url('admin/templates/index.php')) ?>">
                <span aria-hidden="true">→</span> جداول العلامات
            </a>
            <h2><?= $id ? 'محرر جدول العلامات' : 'جدول علامات جديد' ?></h2>
        </div>
        <div class="tb-bar-actions">
            <span id="save-state" class="tb-save-state" role="status" aria-live="polite"><i aria-hidden="true"></i> غير محفوظ</span>
            <button id="save-template" class="button primary" type="button">حفظ</button>
        </div>
    </header>

    <?php if (isset($_GET['import'])):
        $noteCount = count($importNotes) + 1; ?>
        <details class="import-notes">
            <summary>
                <span class="import-notes-mark" aria-hidden="true">!</span>
                <strong>جدول مستورد — راجعه قبل الحفظ.</strong>
                <span class="import-notes-count"><?= $noteCount ?> ملاحظات</span>
            </summary>
            <ul>
                <li>تحقق من اسم كل عمود وعلامته القصوى؛ هذه مستنتجة من نص الرؤوس وليست مقروءة من المصدر.</li>
                <?php foreach ($importNotes as $note): ?><li><?= e($note) ?></li><?php endforeach; ?>
            </ul>
        </details>
    <?php endif; ?>

    <div class="tb-layout">

        <div class="tb-main">

            <!-- المعاينة هي واجهة التحرير: النقر على أي رأس يفتح إعداداته -->
            <section class="tb-card tb-preview-card">
                <header class="tb-card-head">
                    <strong>الجدول</strong>
                    <span class="tb-hint">اضغط على أي عنوان في الجدول لتعديله</span>
                </header>
                <div class="tb-preview-body">
                <?php $report = ['title' => $template['name'], 'class_name' => 'الصف السادس (أ)', 'subject_name' => 'اللغة العربية', 'semester' => 'الأول', 'academic_year' => date('Y') . '/' . ((int) date('Y') + 1)]; ?>
                    <div id="preview-sheet" class="report-sheet report-sheet--dense">
                        <?php require dirname(__DIR__, 2) . '/includes/report-header.php'; ?>
                        <div id="live-preview" class="builder-preview"></div>
                    </div>
                </div>
            </section>

            <!-- البنية: للسحب وإعادة الترتيب والإضافة -->
            <section class="tb-card">
                <header class="tb-card-head">
                    <strong>الترتيب</strong>
                    <span class="tb-hint">اسحب <span aria-hidden="true">☰</span> لإعادة الترتيب</span>
                </header>
                <div id="structure" class="tb-structure"></div>
                <div class="tb-structure-actions">
                    <button type="button" class="button secondary" id="add-group">＋ إضافة مجموعة</button>
                    <button type="button" class="button" id="add-root-column">＋ عمود خارج المجموعات</button>
                </div>
            </section>
        </div>

        <!-- لوحة إعدادات العنصر المحدد — ثابتة، فلا يفقد المستخدم سياق المعاينة -->
        <aside class="tb-panel" id="panel" aria-live="polite"></aside>
    </div>

    <footer class="tb-foot">
        <span>مجموع الجدول: <strong id="grand-total">0</strong> درجة</span>
        <button id="save-template-2" class="button primary" type="button">حفظ</button>
    </footer>
</div>

<script>
window.BUILDER_DATA = <?= json_encode(['template_id' => $id, 'name' => $template['name'], 'description' => $template['description'], 'groups' => $template['groups'], 'columns' => $template['columns'], 'settings' => $template['settings'] ?? []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.APP = { baseUrl: <?= json_encode(url()) ?>, csrf: <?= json_encode(csrf_token()) ?> };
</script>
<?php page_footer(['assets/js/table-builder.js']); ?>
