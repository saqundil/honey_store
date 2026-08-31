<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Repositories\AssessmentSchemeRepository;
use App\Services\AssessmentSchemeService;
use App\Services\TeacherSetupService;

$pdo = db();
$pdo->beginTransaction();
try {
    $insertUser = $pdo->prepare('INSERT INTO admin_users(name,email,password_hash,role,must_change_password) VALUES(?,?,?,?,0)');
    $insertUser->execute(['معلم المرحلة الرابعة الأول', 'phase4-one@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'teacher']);
    $firstTeacher = (int) $pdo->lastInsertId();
    $insertUser->execute(['معلم المرحلة الرابعة الثاني', 'phase4-two@example.invalid', password_hash('TemporaryPass123!', PASSWORD_DEFAULT), 'teacher']);
    $secondTeacher = (int) $pdo->lastInsertId();

    $setup = new TeacherSetupService($pdo);
    foreach ([$firstTeacher, $secondTeacher] as $teacherId) {
        $setup->saveAcademicContext($teacherId, '2034/2035', 'الفصل الأول');
        $setup->addStage($teacherId, 'الصف الثامن');
        $context = $setup->state($teacherId);
        $setup->addClass($teacherId, (int) $context['terms'][0]['id'], (int) $context['stages'][0]['id'], 'الثامن أ');
    }
    $firstState = $setup->state($firstTeacher);
    $firstTerm = (int) $firstState['terms'][0]['id'];
    $firstClass = (int) $firstState['classes'][0]['id'];
    $secondTerm = (int) $setup->state($secondTeacher)['terms'][0]['id'];

    $subjectName = 'مادة اختبار المرحلة الرابعة ' . bin2hex(random_bytes(4));
    $pdo->prepare('INSERT INTO subjects(name) VALUES(?)')->execute([$subjectName]);
    $subjectId = (int) $pdo->lastInsertId();

    $insertTemplate = $pdo->prepare("INSERT INTO table_templates(name,status,created_by) VALUES(?,'active',?)");
    $insertVersion = $pdo->prepare('INSERT INTO table_template_versions(template_id,version_number,created_by) VALUES(?,1,?)');
    $templateVersions = [];
    foreach ([$firstTeacher, $secondTeacher] as $teacherId) {
        for ($index = 1; $index <= 8; $index++) {
            $insertTemplate->execute(["قالب {$index}", $teacherId]);
            $insertVersion->execute([(int) $pdo->lastInsertId(), $teacherId]);
            $templateVersions[$teacherId][] = (int) $pdo->lastInsertId();
        }
    }

    $buildAssessments = static function (array $versionIds, array $counts): array {
        $assessments = [];
        foreach ($counts as $assessmentIndex => $count) {
            $templates = [];
            for ($templateIndex = 0; $templateIndex < $count; $templateIndex++) {
                $templates[] = [
                    'template_version_id' => $versionIds[$templateIndex],
                    'label' => 'جزء ' . ($templateIndex + 1),
                    'is_required' => $templateIndex % 2 === 0,
                    'is_active' => true,
                ];
            }
            $assessments[] = ['name' => 'اختبار ' . ($assessmentIndex + 1), 'maximum_mark' => 100, 'templates' => $templates];
        }
        return $assessments;
    };

    $firstRepository = new AssessmentSchemeRepository($pdo, $firstTeacher);
    $firstService = new AssessmentSchemeService($pdo, $firstRepository);
    $firstSchemeId = $firstService->publish([
        'name' => 'مخطط موحد',
        'academic_term_id' => $firstTerm,
        'subject_id' => $subjectId,
        'assessments' => $buildAssessments($templateVersions[$firstTeacher], [1, 3, 6, 8]),
    ], $firstTeacher);

    $secondRepository = new AssessmentSchemeRepository($pdo, $secondTeacher);
    $secondService = new AssessmentSchemeService($pdo, $secondRepository);
    $secondSchemeId = $secondService->publish([
        'name' => 'مخطط موحد',
        'academic_term_id' => $secondTerm,
        'subject_id' => $subjectId,
        'assessments' => $buildAssessments($templateVersions[$secondTeacher], [1]),
    ], $secondTeacher);
    assert($firstSchemeId !== $secondSchemeId);
    assert(count($firstRepository->all()) === 1);
    assert(count($secondRepository->all()) === 1);
    assert($firstRepository->find($secondSchemeId) === null);

    $versionOne = $firstRepository->currentConfiguration($firstSchemeId);
    assert($versionOne !== null && (int) $versionOne['version_number'] === 1);
    assert(array_map(static fn(array $assessment): int => count($assessment['templates']), $versionOne['assessments']) === [1, 3, 6, 8]);
    $versionOneId = (int) $versionOne['id'];

    foreach ([
        ['UPDATE assessment_scheme_versions SET version_number=99 WHERE id=?', $versionOneId],
        ['UPDATE assessments SET name=? WHERE id=?', 'تعديل غير مسموح', (int) $versionOne['assessments'][0]['id']],
        ['DELETE FROM assessment_templates WHERE id=?', (int) $versionOne['assessments'][0]['templates'][0]['id']],
    ] as $immutableAttempt) {
        $sql = array_shift($immutableAttempt);
        $rejected = false;
        try {
            $pdo->prepare($sql)->execute($immutableAttempt);
        } catch (PDOException) {
            $rejected = true;
        }
        assert($rejected);
    }

    $otherSubjectName = 'مادة مختلفة ' . bin2hex(random_bytes(4));
    $pdo->prepare('INSERT INTO subjects(name) VALUES(?)')->execute([$otherSubjectName]);
    $otherSubjectId = (int) $pdo->lastInsertId();
    $subjectMismatchRejected = false;
    try {
        $pdo->prepare("INSERT INTO class_scheme_assignments(teacher_id,academic_term_id,class_id,subject_id,assessment_scheme_version_id,status,assigned_by) VALUES(?,?,?,?,?,'active',?)")
            ->execute([$firstTeacher, $firstTerm, $firstClass, $otherSubjectId, $versionOneId, $firstTeacher]);
    } catch (PDOException) {
        $subjectMismatchRejected = true;
    }
    assert($subjectMismatchRejected);

    $crossTermRejected = false;
    try {
        $firstService->publish(['name' => 'سياق خاطئ', 'academic_term_id' => $secondTerm, 'subject_id' => $subjectId, 'assessments' => $buildAssessments($templateVersions[$firstTeacher], [1])], $firstTeacher);
    } catch (InvalidArgumentException) {
        $crossTermRejected = true;
    }
    assert($crossTermRejected);

    $crossTemplateRejected = false;
    try {
        $firstService->publish(['name' => 'قالب خاطئ', 'academic_term_id' => $firstTerm, 'subject_id' => $subjectId, 'assessments' => $buildAssessments($templateVersions[$secondTeacher], [1])], $firstTeacher);
    } catch (InvalidArgumentException) {
        $crossTemplateRejected = true;
    }
    assert($crossTemplateRejected);

    $duplicateTemplateRejected = false;
    try {
        $firstService->publish([
            'name' => 'تكرار خاطئ', 'academic_term_id' => $firstTerm, 'subject_id' => $subjectId,
            'assessments' => [['name' => 'اختبار', 'templates' => [
                ['template_version_id' => $templateVersions[$firstTeacher][0], 'label' => 'أ'],
                ['template_version_id' => $templateVersions[$firstTeacher][0], 'label' => 'ب'],
            ]]],
        ], $firstTeacher);
    } catch (InvalidArgumentException) {
        $duplicateTemplateRejected = true;
    }
    assert($duplicateTemplateRejected);

    $firstService->publish([
        'id' => $firstSchemeId,
        'name' => 'مخطط موحد',
        'academic_term_id' => $firstTerm,
        'subject_id' => $subjectId,
        'assessments' => $buildAssessments($templateVersions[$firstTeacher], [3]),
    ], $firstTeacher);
    $versionTwo = $firstRepository->currentConfiguration($firstSchemeId);
    assert($versionTwo !== null && (int) $versionTwo['version_number'] === 2);
    assert(count($versionTwo['assessments']) === 1 && count($versionTwo['assessments'][0]['templates']) === 3);
    $historicalVersion = $firstRepository->configuration($versionOneId);
    assert($historicalVersion !== null && array_map(static fn(array $assessment): int => count($assessment['templates']), $historicalVersion['assessments']) === [1, 3, 6, 8]);
    assert(count($firstRepository->versions($firstSchemeId)) === 2);

    $pdo->rollBack();
    echo "Phase 4 tests passed.\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $exception;
}
