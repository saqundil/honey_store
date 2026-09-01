<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Repositories\ReferenceRepository;
use App\Services\AuthorizationService;
use App\Services\TeacherSetupService;

$pdo = db();
$pdo->beginTransaction();
try {
    $insertUser = $pdo->prepare('INSERT INTO admin_users(name,email,password_hash,role,must_change_password) VALUES(?,?,?,?,0)');
    $insertUser->execute(['المعلم الأول', 'phase3-one@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'teacher']);
    $firstTeacher = (int) $pdo->lastInsertId();
    $insertUser->execute(['المعلم الثاني', 'phase3-two@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'teacher']);
    $secondTeacher = (int) $pdo->lastInsertId();

    $setup = new TeacherSetupService($pdo);
    foreach ([$firstTeacher, $secondTeacher] as $teacherId) {
        $setup->saveAcademicContext($teacherId, '2031/2032', 'الفصل الأول');
        $setup->addStage($teacherId, 'الصف السابع');
        $state = $setup->state($teacherId);
        $setup->addClass($teacherId, (int) $state['terms'][0]['id'], (int) $state['stages'][0]['id'], 'السابع أ');
        assert($setup->state($teacherId)['complete']);
    }

    $firstState = $setup->state($firstTeacher);
    $secondState = $setup->state($secondTeacher);
    $firstClass = (int) $firstState['classes'][0]['id'];
    $secondClass = (int) $secondState['classes'][0]['id'];
    assert($firstClass !== $secondClass);

    $authorization = new AuthorizationService($pdo);
    assert($authorization->canAccess('class', $firstClass, ['id' => $firstTeacher, 'role' => 'teacher']));
    assert(!$authorization->canAccess('class', $firstClass, ['id' => $secondTeacher, 'role' => 'teacher']));
    assert($authorization->canAccess('class', $firstClass, ['id' => 0, 'role' => 'super_admin']));
    assert(!$authorization->canAccess('unknown', $firstClass, ['id' => $firstTeacher, 'role' => 'teacher']));

    $crossOwnerRejected = false;
    try {
        $setup->addClass($firstTeacher, (int) $firstState['terms'][0]['id'], (int) $secondState['stages'][0]['id'], 'صف غير صالح');
    } catch (InvalidArgumentException) {
        $crossOwnerRejected = true;
    }
    assert($crossOwnerRejected);

    $crossOwnerStudentRejected = false;
    try {
        $pdo->prepare('INSERT INTO students(teacher_id,student_number,name,class_id) VALUES(?,?,?,?)')
            ->execute([$firstTeacher, 'INVALID-OWNER', 'طالب غير صالح', $secondClass]);
    } catch (PDOException) {
        $crossOwnerStudentRejected = true;
    }
    assert($crossOwnerStudentRejected);

    $insertStudent = $pdo->prepare('INSERT INTO students(teacher_id,student_number,name,class_id) VALUES(?,?,?,?)');
    $insertStudent->execute([$firstTeacher, '7A-001', 'طالب أول', $firstClass]);
    $firstStudent = (int) $pdo->lastInsertId();
    $insertStudent->execute([$secondTeacher, '7A-001', 'طالب ثان', $secondClass]);
    assert((int) $pdo->lastInsertId() !== $firstStudent);

    $insertEnrollment = $pdo->prepare('INSERT INTO class_enrollments(teacher_id,academic_term_id,class_id,student_id,status) VALUES(?,?,?,?,?)');
    $insertEnrollment->execute([$firstTeacher, (int) $firstState['terms'][0]['id'], $firstClass, $firstStudent, 'active']);
    assert((int) $pdo->query("SELECT COUNT(*) FROM class_enrollments WHERE student_id={$firstStudent}")->fetchColumn() === 1);

    $firstReferences = new ReferenceRepository($pdo, $firstTeacher);
    $secondReferences = new ReferenceRepository($pdo, $secondTeacher);
    assert(count($firstReferences->classes()) === 1);
    assert(count($secondReferences->classes()) === 1);
    assert(count($firstReferences->students()) === 1);
    assert(count($secondReferences->students()) === 1);
    $adminReferences = new ReferenceRepository($pdo, $firstTeacher, true);
    $adminClassIds = array_map('intval', array_column($adminReferences->classes(), 'id'));
    assert(in_array($firstClass, $adminClassIds, true));
    assert(in_array($secondClass, $adminClassIds, true));
    $adminStudentIds = array_map('intval', array_column($adminReferences->students(), 'id'));
    assert(in_array($firstStudent, $adminStudentIds, true));

    $pdo->rollBack();
    echo "Phase 3 tests passed.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
