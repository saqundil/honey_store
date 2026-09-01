<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Repositories\AssessmentSchemeRepository;
use App\Repositories\GradebookRepository;
use App\Services\AssessmentSchemeService;
use App\Services\GradebookReportService;
use App\Services\GradebookService;
use App\Services\TeacherSetupService;

$pdo = db();
$pdo->beginTransaction();
try {
    $insertUser = $pdo->prepare('INSERT INTO admin_users(name,email,password_hash,role,must_change_password) VALUES(?,?,?,?,0)');
    $insertUser->execute(['معلم العلامات الأول', 'phase5-one@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'teacher']);
    $firstTeacher = (int) $pdo->lastInsertId();
    $insertUser->execute(['معلم العلامات الثاني', 'phase5-two@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'teacher']);
    $secondTeacher = (int) $pdo->lastInsertId();
    $insertUser->execute(['مدير اختبارات العلامات', 'phase5-admin@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'super_admin']);
    $superAdmin = (int) $pdo->lastInsertId();

    $setup = new TeacherSetupService($pdo);
    $contexts = [];
    foreach ([$firstTeacher, $secondTeacher] as $teacherId) {
        $setup->saveAcademicContext($teacherId, '2040/2041', 'الفصل الأول');
        $setup->addStage($teacherId, 'الصف العاشر');
        $state = $setup->state($teacherId);
        $setup->addClass($teacherId, (int) $state['terms'][0]['id'], (int) $state['stages'][0]['id'], 'العاشر أ');
        $contexts[$teacherId] = $setup->state($teacherId);
    }
    $firstClass = (int) $contexts[$firstTeacher]['classes'][0]['id'];
    $firstTerm = (int) $contexts[$firstTeacher]['terms'][0]['id'];
    $secondClass = (int) $contexts[$secondTeacher]['classes'][0]['id'];
    $secondTerm = (int) $contexts[$secondTeacher]['terms'][0]['id'];

    $pdo->prepare('INSERT INTO subjects(name) VALUES(?)')->execute(['مادة Phase 5 ' . bin2hex(random_bytes(4))]);
    $subjectId = (int) $pdo->lastInsertId();

    $insertStudent = $pdo->prepare('INSERT INTO students(teacher_id,student_number,name,class_id) VALUES(?,?,?,?)');
    $insertEnrollment = $pdo->prepare('INSERT INTO class_enrollments(teacher_id,academic_term_id,class_id,student_id,status) VALUES(?,?,?,?,?)');
    $enrollments = [];
    foreach ([[$firstTeacher, $firstTerm, $firstClass, 'طالب أول'], [$secondTeacher, $secondTerm, $secondClass, 'طالب ثان']] as $index => [$teacherId, $termId, $classId, $name]) {
        $insertStudent->execute([$teacherId, 'P5-' . ($index + 1), $name, $classId]);
        $studentId = (int) $pdo->lastInsertId();
        $insertEnrollment->execute([$teacherId, $termId, $classId, $studentId, 'active']);
        $enrollments[$teacherId] = (int) $pdo->lastInsertId();
    }

    $pdo->prepare('INSERT INTO template_groups(name,created_by) VALUES(?,?)')->execute(['مجموعة قوالب Phase 5', $firstTeacher]);
    $templateGroupId = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO table_templates(group_id,name,status,created_by) VALUES(?,?,'active',?)")->execute([$templateGroupId, 'قالب علامات Phase 5', $firstTeacher]);
    $templateId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO table_template_versions(template_id,version_number,created_by) VALUES(?,1,?)')->execute([$templateId, $firstTeacher]);
    $templateVersionId = (int) $pdo->lastInsertId();
    $insertColumn = $pdo->prepare('INSERT INTO table_columns(template_version_id,column_key,name,type,max_mark,step_value,sort_order,is_visible,is_calculated) VALUES(?,?,?,?,?,?,?,?,?)');
    $insertColumn->execute([$templateVersionId, 'student_no', 'م', 'student_number', null, 0.25, 0, 1, 0]);
    $insertColumn->execute([$templateVersionId, 'student_name', 'اسم الطالب', 'student_name', null, 0.25, 1, 1, 0]);
    $insertColumn->execute([$templateVersionId, 'mark_a', 'العلامة الأولى', 'manual_mark', 10, 0.5, 2, 1, 0]);
    $markAId = (int) $pdo->lastInsertId();
    $insertColumn->execute([$templateVersionId, 'mark_b', 'العلامة الثانية', 'manual_mark', 10, 0.5, 3, 1, 0]);
    $markBId = (int) $pdo->lastInsertId();
    $insertColumn->execute([$templateVersionId, 'total', 'المجموع', 'calculated_total', 20, 0.5, 4, 1, 1]);
    $totalId = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO table_formulas(column_id,formula_type,expression,missing_value_behavior,decimal_places) VALUES(?,'SUM','SUM(mark_a,mark_b)','blank',2)")->execute([$totalId]);
    $formulaId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO table_formula_items(formula_id,source_column_id,sort_order) VALUES(?,?,0),(?,?,1)')->execute([$formulaId, $markAId, $formulaId, $markBId]);
    $pdo->prepare('UPDATE table_templates SET current_version_id=? WHERE id=?')->execute([$templateVersionId, $templateId]);

    $schemeRepository = new AssessmentSchemeRepository($pdo, $firstTeacher);
    $schemeId = (new AssessmentSchemeService($pdo, $schemeRepository))->publish([
        'name' => 'مخطط علامات Phase 5', 'academic_term_id' => $firstTerm, 'subject_id' => $subjectId,
        'assessments' => [['name' => 'الاختبار الأول', 'weight' => 40, 'templates' => [['template_version_id' => $templateVersionId, 'label' => 'علامات الاختبار']]]],
    ], $firstTeacher);
    $scheme = $schemeRepository->currentConfiguration($schemeId);
    $schemeVersionId = (int) $scheme['id'];

    $gradebookRepository = new GradebookRepository($pdo, $firstTeacher);
    $service = new GradebookService($pdo, $gradebookRepository);
    $assignmentId = $service->assignScheme($firstClass, $schemeVersionId, $firstTeacher);
    $classAssessments = $gradebookRepository->classAssessments($assignmentId);
    assert(count($classAssessments) === 1);
    $classAssessmentId = (int) $classAssessments[0]['id'];
    $assessmentTemplateId = (int) $scheme['assessments'][0]['templates'][0]['id'];

    $pdo->prepare("INSERT INTO table_templates(group_id,name,status,created_by) VALUES(?,?, 'active',?)")->execute([$templateGroupId, 'قالب اختياري Phase 6', $firstTeacher]);
    $optionalTemplateId = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO table_template_versions(template_id,version_number,created_by) VALUES(?,1,?)')->execute([$optionalTemplateId, $firstTeacher]);
    $optionalVersionId = (int) $pdo->lastInsertId();
    $insertColumn->execute([$optionalVersionId, 'optional_no', 'م', 'student_number', null, 0.5, 0, 1, 0]);
    $insertColumn->execute([$optionalVersionId, 'optional_name', 'اسم الطالب', 'student_name', null, 0.5, 1, 1, 0]);
    $insertColumn->execute([$optionalVersionId, 'optional_mark', 'نشاط اختياري', 'manual_mark', 5, 0.5, 2, 1, 0]);
    $pdo->prepare('INSERT INTO assessment_templates(assessment_id,assessment_scheme_version_id,teacher_id,template_version_id,label,sort_order,is_required,is_active) VALUES(?,?,?,?,?,1,0,1)')
        ->execute([(int) $scheme['assessments'][0]['id'], $schemeVersionId, $firstTeacher, $optionalVersionId, 'نشاط اختياري']);

    $service->saveValues($classAssessmentId, $assessmentTemplateId, [[
        'enrollment_id' => $enrollments[$firstTeacher], 'values' => ['mark_a' => 7.5, 'mark_b' => 8],
    ]], $firstTeacher);
    $values = $gradebookRepository->values($classAssessmentId);
    assert((float) $values[$assessmentTemplateId][$enrollments[$firstTeacher]]['mark_a'] === 7.5);
    assert((float) $values[$assessmentTemplateId][$enrollments[$firstTeacher]]['total'] === 15.5);
    assert($gradebookRepository->context($classAssessmentId)['status'] === 'open');

    $initialAuditCount = count($gradebookRepository->audits($classAssessmentId));
    $currentRevisions = $gradebookRepository->revisions($classAssessmentId)[$assessmentTemplateId][$enrollments[$firstTeacher]];
    $service->saveValues($classAssessmentId, $assessmentTemplateId, [[
        'enrollment_id' => $enrollments[$firstTeacher], 'values' => ['mark_a' => 7.5, 'mark_b' => 8], 'revisions' => $currentRevisions,
    ]], $firstTeacher);
    assert(count($gradebookRepository->audits($classAssessmentId)) === $initialAuditCount);

    $staleRevisionRejected = false;
    try {
        $service->saveValues($classAssessmentId, $assessmentTemplateId, [[
            'enrollment_id' => $enrollments[$firstTeacher], 'values' => ['mark_a' => 9, 'mark_b' => 8], 'revisions' => ['mark_a' => 0, 'mark_b' => 0, 'total' => 0],
        ]], $firstTeacher);
    } catch (DomainException) {
        $staleRevisionRejected = true;
    }
    assert($staleRevisionRejected);
    assert((float) $gradebookRepository->values($classAssessmentId)[$assessmentTemplateId][$enrollments[$firstTeacher]]['mark_a'] === 7.5);
    assert(count($gradebookRepository->audits($classAssessmentId)) === $initialAuditCount);

    $unknownColumnRejected = false;
    try {
        $service->saveValues($classAssessmentId, $assessmentTemplateId, [[
            'enrollment_id' => $enrollments[$firstTeacher], 'values' => ['unknown_column' => 4], 'revisions' => [],
        ]], $firstTeacher);
    } catch (InvalidArgumentException) {
        $unknownColumnRejected = true;
    }
    assert($unknownColumnRejected);

    $atomicAuditCount = count($gradebookRepository->audits($classAssessmentId));
    $atomicBatchRejected = false;
    try {
        $service->saveValues($classAssessmentId, $assessmentTemplateId, [
            ['enrollment_id' => $enrollments[$firstTeacher], 'values' => ['mark_a' => 8, 'mark_b' => 8], 'revisions' => $currentRevisions],
            ['enrollment_id' => $enrollments[$secondTeacher], 'values' => ['mark_a' => 4], 'revisions' => []],
        ], $firstTeacher);
    } catch (InvalidArgumentException) {
        $atomicBatchRejected = true;
    }
    assert($atomicBatchRejected);
    assert((float) $gradebookRepository->values($classAssessmentId)[$assessmentTemplateId][$enrollments[$firstTeacher]]['mark_a'] === 7.5);
    assert(count($gradebookRepository->audits($classAssessmentId)) === $atomicAuditCount);

    $foreignEnrollmentRejected = false;
    try {
        $service->saveValues($classAssessmentId, $assessmentTemplateId, [['enrollment_id' => $enrollments[$secondTeacher], 'values' => ['mark_a' => 5]]], $firstTeacher);
    } catch (InvalidArgumentException) {
        $foreignEnrollmentRejected = true;
    }
    assert($foreignEnrollmentRejected);

    assert($service->changeStatus($classAssessmentId, 'lock', $firstTeacher) === 'locked');
    $lockedSaveRejected = false;
    try {
        $service->saveValues($classAssessmentId, $assessmentTemplateId, [['enrollment_id' => $enrollments[$firstTeacher], 'values' => ['mark_a' => 9]]], $firstTeacher);
    } catch (InvalidArgumentException) {
        $lockedSaveRejected = true;
    }
    assert($lockedSaveRejected);
    assert($service->changeStatus($classAssessmentId, 'reopen', $firstTeacher) === 'open');
    $service->saveValues($classAssessmentId, $assessmentTemplateId, [['enrollment_id' => $enrollments[$firstTeacher], 'values' => ['mark_a' => 9, 'mark_b' => 8], 'revisions' => $currentRevisions]], $firstTeacher);
    $values = $gradebookRepository->values($classAssessmentId);
    assert((float) $values[$assessmentTemplateId][$enrollments[$firstTeacher]]['total'] === 17.0);
    assert(count($gradebookRepository->audits($classAssessmentId)) >= $initialAuditCount + 4);
    $latestValueAudit = $pdo->query("SELECT * FROM grade_value_audits WHERE class_assessment_id={$classAssessmentId} AND grade_value_id IS NOT NULL ORDER BY id DESC LIMIT 1")->fetch();
    assert($latestValueAudit && json_decode($latestValueAudit['old_value_json'], true) !== null && json_decode($latestValueAudit['new_value_json'], true) !== null);
    foreach (['UPDATE grade_value_audits SET action=\'tampered\' WHERE id=?', 'DELETE FROM grade_value_audits WHERE id=?'] as $auditMutation) {
        $rejected = false;
        try {
            $pdo->prepare($auditMutation)->execute([(int) $latestValueAudit['id']]);
        } catch (PDOException) {
            $rejected = true;
        }
        assert($rejected);
    }

    $directCrossEnrollmentRejected = false;
    try {
        $pdo->prepare('INSERT INTO grade_values(class_assessment_id,assessment_id,class_id,assessment_template_id,template_version_id,enrollment_id,column_id,numeric_value,updated_by) VALUES(?,?,?,?,?,?,?,?,?)')
            ->execute([$classAssessmentId, (int) $scheme['assessments'][0]['id'], $firstClass, $assessmentTemplateId, $templateVersionId, $enrollments[$secondTeacher], $markAId, 4, $firstTeacher]);
    } catch (PDOException) {
        $directCrossEnrollmentRejected = true;
    }
    assert($directCrossEnrollmentRejected);

    $reportService = new GradebookReportService($gradebookRepository, new App\Repositories\TemplateRepository($pdo, $firstTeacher));
    $classReport = $reportService->assignmentReport($assignmentId);
    assert(count($classReport['students']) === 1);
    assert((float) $classReport['students'][0]['assessments'][$classAssessmentId] === 17.0);
    assert((float) $classReport['students'][0]['earned'] === 34.0);
    assert((float) $classReport['students'][0]['maximum'] === 40.0);
    assert((float) $classReport['students'][0]['percentage'] === 85.0);
    assert((float) $reportService->studentReport($assignmentId, $enrollments[$firstTeacher])['student']['earned'] === 34.0);
    $assessmentReport = $reportService->assessmentReport($classAssessmentId);
    assert(count($assessmentReport['sections']) === 2);
    assert((float) $assessmentReport['sections'][0]['values'][$enrollments[$firstTeacher]]['total'] === 17.0);

    $secondRepository = new GradebookRepository($pdo, $secondTeacher);
    assert($secondRepository->context($classAssessmentId) === null);
    $crossTeacherRejected = false;
    try {
        (new GradebookService($pdo, $secondRepository))->changeStatus($classAssessmentId, 'lock', $secondTeacher);
    } catch (InvalidArgumentException) {
        $crossTeacherRejected = true;
    }
    assert($crossTeacherRejected);

    $adminRepository = new GradebookRepository($pdo, $superAdmin, true);
    assert($adminRepository->context($classAssessmentId) !== null);
    $adminService = new GradebookService($pdo, $adminRepository, true);
    assert($adminService->changeStatus($classAssessmentId, 'lock', $superAdmin) === 'locked');
    assert($adminService->changeStatus($classAssessmentId, 'reopen', $superAdmin) === 'open');
    $adminReport = new GradebookReportService($adminRepository, new App\Repositories\TemplateRepository($pdo, $superAdmin, true));
    assert(count($adminReport->assignmentReport($assignmentId)['students']) === 1);

    $pdo->rollBack();
    echo "Phase 5 tests passed.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}
