<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Repositories\TemplateRepository;
use App\Services\TemplateService;

$pdo = db();
$pdo->beginTransaction();
try {
    $password = getenv('SEED_ADMIN_PASSWORD') ?: 'ChangeMe!2026';
    $statement = $pdo->prepare('INSERT INTO admin_users(name,email,password_hash,role,must_change_password) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE name=VALUES(name)');
    $statement->execute(['مدير النظام', 'admin@example.com', password_hash($password, PASSWORD_DEFAULT), 'super_admin']);
    $adminId = (int) $pdo->query("SELECT id FROM admin_users WHERE email='admin@example.com'")->fetchColumn();
    $statement = $pdo->prepare("INSERT INTO academic_years(teacher_id,name,is_current) VALUES(?,'2027/2026',1) ON DUPLICATE KEY UPDATE is_current=1");
    $statement->execute([$adminId]);
    $yearId = (int) $pdo->query("SELECT id FROM academic_years WHERE teacher_id={$adminId} AND name='2027/2026'")->fetchColumn();
    $statement = $pdo->prepare("INSERT INTO academic_terms(teacher_id,academic_year_id,name,sort_order,status) VALUES(?,?,'الفصل الثاني',2,'active') ON DUPLICATE KEY UPDATE status='active'");
    $statement->execute([$adminId, $yearId]);
    $termId = (int) $pdo->query("SELECT id FROM academic_terms WHERE teacher_id={$adminId} AND academic_year_id={$yearId} AND name='الفصل الثاني'")->fetchColumn();
    $statement = $pdo->prepare("INSERT INTO stages(teacher_id,name,sort_order,status) VALUES(?,'الصف السادس',6,'active') ON DUPLICATE KEY UPDATE status='active'");
    $statement->execute([$adminId]);
    $stageId = (int) $pdo->query("SELECT id FROM stages WHERE teacher_id={$adminId} AND name='الصف السادس'")->fetchColumn();
    $statement = $pdo->prepare("INSERT INTO classes(teacher_id,academic_term_id,stage_id,name) VALUES(?,?,?,'السادس ب'),(?,?,?,'السادس أ') ON DUPLICATE KEY UPDATE status='active'");
    $statement->execute([$adminId, $termId, $stageId, $adminId, $termId, $stageId]);
    $pdo->exec("INSERT IGNORE INTO subjects(name) VALUES('اللغة العربية')");
    $classId = (int) $pdo->query("SELECT id FROM classes WHERE teacher_id={$adminId} AND academic_term_id={$termId} AND name='السادس ب'")->fetchColumn();
    $students = ['إبراهيم أمين إبراهيم العتر','آدم خالد عناد كناعنة','آر أحمد محمد الرشود','باسل سري مسلم عوينه','أحمد محمد','الطالب السادس','الطالب السابع','الطالب الثامن','الطالب التاسع','الطالب العاشر','الطالب الحادي عشر','الطالب الثاني عشر'];
    $insert = $pdo->prepare('INSERT INTO students(teacher_id,student_number,name,class_id) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),name=VALUES(name),class_id=VALUES(class_id),status="active"');
    $enroll = $pdo->prepare('INSERT INTO class_enrollments(teacher_id,academic_term_id,class_id,student_id,status) VALUES(?,?,?,?,"active") ON DUPLICATE KEY UPDATE class_id=VALUES(class_id),status="active"');
    foreach ($students as $index => $name) { $insert->execute([$adminId, sprintf('6B-%03d', $index + 1), $name, $classId]); $enroll->execute([$adminId, $termId, $classId, (int) $pdo->lastInsertId()]); }
    $pdo->commit();

    $exists = $pdo->query("SELECT id FROM table_templates WHERE name='تقييم مهارة الكتابة' LIMIT 1")->fetchColumn();
    if (!$exists) {
        $groups = [
            ['group_key'=>'month_1','name'=>'الشهر الأول','parent_key'=>null,'sort_order'=>1],
            ['group_key'=>'month_2','name'=>'الشهر الثاني','parent_key'=>null,'sort_order'=>2],
        ];
        $columns = [
            ['column_key'=>'student_number','name'=>'الرقم','type'=>'student_number','max_mark'=>'','width_mm'=>10,'sort_order'=>1,'is_visible'=>1,'display_direction'=>'horizontal'],
            ['column_key'=>'student_name','name'=>'اسم الطالب','type'=>'student_name','max_mark'=>'','width_mm'=>52,'sort_order'=>2,'is_visible'=>1,'display_direction'=>'horizontal'],
        ];
        $criteria = ['ideas'=>'تنظيم الأفكار وتسلسل الفقرات','links'=>'الترابط بين الجمل','language'=>'سلامة اللغة','style'=>'الأسلوب واستخدام المفردات'];
        $order = 3;
        foreach ([1,2] as $month) {
            foreach ($criteria as $key => $label) $columns[] = ['column_key'=>"month_{$month}_{$key}",'name'=>$label,'header_label'=>$label,'type'=>'manual_mark','max_mark'=>1,'step_value'=>0.25,'width_mm'=>11,'sort_order'=>$order++,'is_visible'=>1,'header_group_key'=>"month_{$month}",'display_direction'=>'vertical'];
            $sources = array_map(fn(string $key): string => "month_{$month}_{$key}", array_keys($criteria));
            $columns[] = ['column_key'=>"month_{$month}_total",'name'=>'المجموع','header_label'=>'المجموع','type'=>'calculated_total','max_mark'=>4,'width_mm'=>11,'sort_order'=>$order++,'is_visible'=>1,'header_group_key'=>"month_{$month}",'display_direction'=>'vertical','formula'=>['type'=>'SUM','sources'=>$sources,'missing'=>'blank','base'=>null,'decimals'=>2]];
        }
        $columns[] = ['column_key'=>'final_total','name'=>'مجموع الشهرين','type'=>'calculated_total','max_mark'=>8,'width_mm'=>12,'sort_order'=>$order,'is_visible'=>1,'display_direction'=>'vertical','formula'=>['type'=>'SUM','sources'=>['month_1_total','month_2_total'],'missing'=>'blank','base'=>null,'decimals'=>2]];
        (new TemplateService($pdo, new TemplateRepository($pdo, $adminId, true)))->save(['name'=>'تقييم مهارة الكتابة','description'=>'قالب تجريبي ديناميكي مطابق للنموذج المرجعي الثاني','groups'=>$groups,'columns'=>$columns,'settings'=>['title'=>'مهارة الكتابة (التعبير)']], $adminId);
    }
    $quizExists = $pdo->query("SELECT id FROM table_templates WHERE name='خلاصة الاختبارات القصيرة' LIMIT 1")->fetchColumn();
    if (!$quizExists) {
        $quizColumns = [
            ['column_key'=>'student_number','name'=>'الرقم','type'=>'student_number','max_mark'=>'','width_mm'=>14,'sort_order'=>1,'is_visible'=>1],
            ['column_key'=>'student_name','name'=>'اسم الطالب','type'=>'student_name','max_mark'=>'','width_mm'=>70,'sort_order'=>2,'is_visible'=>1],
            ['column_key'=>'quiz_1','name'=>'اختبار قصير','type'=>'manual_mark','max_mark'=>2.5,'step_value'=>0.25,'width_mm'=>34,'sort_order'=>3,'is_visible'=>1],
            ['column_key'=>'quiz_2','name'=>'اختبار قصير','type'=>'manual_mark','max_mark'=>2.5,'step_value'=>0.25,'width_mm'=>34,'sort_order'=>4,'is_visible'=>1],
            ['column_key'=>'final_mark','name'=>'العلامة النهائية','type'=>'calculated_total','max_mark'=>5,'width_mm'=>36,'sort_order'=>5,'is_visible'=>1,'formula'=>['type'=>'SUM','sources'=>['quiz_1','quiz_2'],'missing'=>'blank','base'=>null,'decimals'=>2]],
        ];
        (new TemplateService($pdo, new TemplateRepository($pdo, $adminId, true)))->save(['name'=>'خلاصة الاختبارات القصيرة','description'=>'قالب ديناميكي مطابق للنموذج المرجعي الأول','groups'=>[],'columns'=>$quizColumns,'settings'=>['title'=>'خلاصة الاختبارات القصيرة']], $adminId);
    }
    echo "Seed completed. Admin: admin@example.com / {$password}\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}