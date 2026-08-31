<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
$teacherId = current_user_id();
$authorization = new App\Services\AuthorizationService(db());
$references = new App\Repositories\ReferenceRepository(db(), $teacherId);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $id = (int) ($_POST['id'] ?? 0);
    if (isset($_POST['import_csv'])) {
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK || $_FILES['csv']['size'] > 2_000_000) {
            throw new RuntimeException('ملف CSV غير صالح أو أكبر من 2MB.');
        }
        $classId=(int)$_POST['import_class_id'];$authorization->requireAccess('class',$classId,user());$classStatement=db()->prepare('SELECT academic_term_id FROM classes WHERE id=? AND teacher_id=?');$classStatement->execute([$classId,$teacherId]);$termId=(int)$classStatement->fetchColumn();$handle=fopen($_FILES['csv']['tmp_name'],'rb');$pdo=db();$pdo->beginTransaction();
        try{$insert=$pdo->prepare('INSERT INTO students(teacher_id,student_number,name,class_id) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),name=VALUES(name),class_id=VALUES(class_id),status="active"');$enroll=$pdo->prepare('INSERT INTO class_enrollments(teacher_id,academic_term_id,class_id,student_id,status) VALUES(?,?,?,?,"active") ON DUPLICATE KEY UPDATE class_id=VALUES(class_id),status="active",left_at=NULL');$row=0;while(($data=fgetcsv($handle,2000,','))!==false){$row++;if($row===1&&strtolower(trim((string)($data[0]??'')))==='student_number')continue;$number=trim((string)($data[0]??''));$name=trim((string)($data[1]??''));if($number===''||$name==='')continue;$insert->execute([$teacherId,$number,$name,$classId]);$studentId=(int)$pdo->lastInsertId();$enroll->execute([$teacherId,$termId,$classId,$studentId]);}$pdo->commit();fclose($handle);}catch(Throwable $exception){$pdo->rollBack();fclose($handle);throw $exception;}
    } elseif (isset($_POST['toggle'])) { $authorization->requireAccess('student',$id,user());$statement=db()->prepare("UPDATE students SET status=IF(status='active','inactive','active') WHERE id=? AND teacher_id=?"); $statement->execute([$id,$teacherId]); }
    else {
        $classId=(int)$_POST['class_id'];$authorization->requireAccess('class',$classId,user());if($id)$authorization->requireAccess('student',$id,user());$classStatement=db()->prepare('SELECT academic_term_id FROM classes WHERE id=? AND teacher_id=?');$classStatement->execute([$classId,$teacherId]);$termId=(int)$classStatement->fetchColumn();$pdo=db();$pdo->beginTransaction();
        try{$number=trim((string)$_POST['student_number']);$name=trim((string)$_POST['name']);if($id){$statement=$pdo->prepare('UPDATE students SET student_number=?,name=?,class_id=? WHERE id=? AND teacher_id=?');$statement->execute([$number,$name,$classId,$id,$teacherId]);}else{$statement=$pdo->prepare('INSERT INTO students(teacher_id,student_number,name,class_id) VALUES(?,?,?,?)');$statement->execute([$teacherId,$number,$name,$classId]);$id=(int)$pdo->lastInsertId();}$statement=$pdo->prepare('INSERT INTO class_enrollments(teacher_id,academic_term_id,class_id,student_id,status) VALUES(?,?,?,?,"active") ON DUPLICATE KEY UPDATE class_id=VALUES(class_id),status="active",left_at=NULL');$statement->execute([$teacherId,$termId,$classId,$id]);$pdo->commit();}catch(Throwable $exception){$pdo->rollBack();throw $exception;}
    }
    redirect('admin/students/index.php');
}
$contextClassId = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;
if ($contextClassId) { $authorization->requireAccess('class', $contextClassId, user()); }
$students=$references->students($contextClassId,trim((string)($_GET['q']??''))); $classes=$references->classes();
$contextClass = null;
foreach ($classes as $classRow) { if ((int) $classRow['id'] === $contextClassId) { $contextClass = $classRow; break; } }
$editing=null;if(isset($_GET['edit'])){$authorization->requireAccess('student',(int)$_GET['edit'],user());$statement=db()->prepare('SELECT * FROM students WHERE id=? AND teacher_id=?');$statement->execute([(int)$_GET['edit'],$teacherId]);$editing=$statement->fetch()?:null;}
page_header('الطلاب','students');
?>
<?php if ($contextClass): ?>
	<div class="section-head">
		<div>
			<p class="eyebrow"><a href="<?= e(url('admin/gradebook/class.php?id=' . $contextClassId)) ?>">→ <?= e($contextClass['name']) ?></a></p>
			<h2>طلاب <?= e($contextClass['name']) ?></h2>
		</div>
		<a class="button" href="<?= e(url('admin/students/index.php')) ?>">كل الطلاب</a>
	</div>
<?php endif; ?>
<div class="split-view">
    <div>
        <section class="panel">
            <h2><?= $editing ? 'تعديل الطالب' : 'إضافة طالب' ?></h2>
            <form method="post" class="form-grid">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                <label class="full">رقم الطالب<input name="student_number" required value="<?= e($editing['student_number'] ?? '') ?>" placeholder="مثال: 1024"></label>
                <label class="full">اسم الطالب<input name="name" required value="<?= e($editing['name'] ?? '') ?>" placeholder="الاسم الرباعي"></label>
                <label class="full">الصف
                    <select name="class_id" required>
                        <?php foreach ($classes as $class):
                            $preselect = (int) ($editing['class_id'] ?? $contextClassId ?? 0) === (int) $class['id']; ?>
                            <option value="<?= (int) $class['id'] ?>" <?= $preselect ? 'selected' : '' ?>><?= e($class['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="button-row full">
                    <button class="button primary" type="submit"><?= $editing ? 'حفظ التعديل' : 'إضافة الطالب' ?></button>
                    <?php if ($editing): ?><a class="button ghost" href="<?= e(url('admin/students/index.php')) ?>">إلغاء</a><?php endif; ?>
                </div>
            </form>
        </section>

        <section class="panel import-panel">
            <h2><?= $contextClass ? "استيراد الطلاب إلى " . e($contextClass["name"]) : "استيراد الطلاب" ?></h2>
            <form method="post" enctype="multipart/form-data" class="form-grid">
                <?= csrf_field() ?>
                <label class="full">الصف
                    <select name="import_class_id" required>
                        <?php foreach ($classes as $class): ?><option value="<?= (int) $class['id'] ?>" <?= $contextClassId === (int) $class['id'] ? 'selected' : '' ?>><?= e($class['name']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="full">ملف CSV
                    <input type="file" name="csv" accept=".csv,text/csv" required>
                    <small>عمودان بالترتيب: <code>student_number</code> ثم <code>name</code>. الحد الأقصى 2 ميغابايت.</small>
                </label>
                <button class="button full" name="import_csv">استيراد الطلاب</button>
            </form>
        </section>
    </div>

    <section class="panel grow">
        <form class="filters" role="search">
            <input name="q" type="search" value="<?= e($_GET['q'] ?? '') ?>" placeholder="ابحث بالاسم أو الرقم" aria-label="بحث عن طالب">
            <select name="class_id" aria-label="تصفية حسب الصف">
                <option value="">كل الصفوف</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= (int) $class['id'] ?>" <?= (string) $class['id'] === ($_GET['class_id'] ?? '') ? 'selected' : '' ?>><?= e($class['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">بحث</button>
        </form>

        <?php if ($students): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">الرقم</th>
                            <th scope="col">الاسم</th>
                            <th scope="col">الصف</th>
                            <th scope="col">الحالة</th>
                            <th scope="col"><span class="sr-only">إجراءات</span></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="num"><?= e($student['student_number']) ?></td>
                            <td><strong><?= e($student['name']) ?></strong></td>
                            <td><?= e($student['class_name']) ?></td>
                            <td><span class="status <?= $student['status'] === 'active' ? 'active' : 'inactive' ?>"><?= $student['status'] === 'active' ? 'نشط' : 'غير نشط' ?></span></td>
                            <td class="actions-cell">
                                <a href="?edit=<?= (int) $student['id'] ?>">تعديل</a>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                    <button class="link-button" name="toggle"><?= $student['status'] === 'active' ? 'تعطيل' : 'تفعيل' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-mark" aria-hidden="true">⌕</div>
                <h2><?= ($_GET['q'] ?? '') !== '' || ($_GET['class_id'] ?? '') !== '' ? 'لا يوجد طلاب مطابقون' : 'لا يوجد طلاب بعد' ?></h2>
                <p><?= ($_GET['q'] ?? '') !== '' || ($_GET['class_id'] ?? '') !== ''
                    ? 'جرّب تعديل البحث أو اختيار صف آخر.'
                    : 'أضف الطلاب واحدًا واحدًا من النموذج المجاور، أو استورد كشفًا كاملًا بملف CSV.' ?></p>
                <?php if (($_GET['q'] ?? '') !== ''): ?>
                    <a class="button" href="<?= e(url('admin/students/index.php' . ($contextClassId ? '?class_id=' . $contextClassId : ''))) ?>">مسح البحث</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php page_footer(); ?>
