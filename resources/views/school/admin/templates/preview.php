<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/includes/bootstrap.php'; require_admin();
$template=(new App\Repositories\TemplateRepository(db(),current_user_id(),is_super_admin()))->currentConfiguration((int)($_GET['id']??0)); if(!$template){http_response_code(404);exit('القالب غير موجود');}
$students=[['id'=>1,'student_number'=>'1','name'=>'طالب تجريبي'],['id'=>2,'student_number'=>'2','name'=>'طالبة تجريبية']];
$html=(new App\Services\TableRenderer())->render($template,$students,[],false); page_header('معاينة: '.$template['name'],'templates',['assets/css/report-sheet.css','assets/css/template-preview.css']);
?><div class="preview-page"><div class="report-info"><strong><?= school_e($template['name']) ?></strong><span>الإصدار <?= (int)$template['version_number'] ?></span></div>
<?php // ورقة الطباعة نفسها ببيانات نموذجية، حتى تُرى القياسات الحقيقية قبل الاستخدام
$report=['title'=>$template['name'],'class_name'=>'الصف السادس (أ)','subject_name'=>'اللغة العربية','semester'=>'الأول','academic_year'=>date('Y').'/'.((int)date('Y')+1)]; ?>
<div id="template-preview-viewport" class="template-preview-viewport">
	<div id="template-preview-sheet" class="report-sheet report-sheet--dense template-preview-sheet"><?php require dirname(__DIR__,2).'/includes/report-header.php'; ?><?= $html ?></div>
</div>
</div><?php page_footer(['assets/js/template-preview.js']); ?>