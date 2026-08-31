<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$pdo = db();
$requiredTriggers = [
    'trg_scheme_version_no_update',
    'trg_scheme_version_no_delete',
    'trg_assessment_no_update',
    'trg_assessment_no_delete',
    'trg_assessment_template_no_update',
    'trg_assessment_template_no_delete',
    'trg_grade_audit_no_update',
    'trg_grade_audit_no_delete',
];
$placeholders = implode(',', array_fill(0, count($requiredTriggers), '?'));
$triggerQuery = $pdo->prepare("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME IN ({$placeholders})");
$triggerQuery->execute($requiredTriggers);
$installedTriggers = $triggerQuery->fetchAll(PDO::FETCH_COLUMN);
sort($requiredTriggers);
sort($installedTriggers);
assert($installedTriggers === $requiredTriggers);

$migration = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE migration=?');
$migration->execute(['20260829_006_immutable_history.sql']);
assert((int) $migration->fetchColumn() === 1);

$integrityChecks = [
    'SELECT COUNT(*) FROM assessment_scheme_versions v LEFT JOIN assessment_schemes s ON s.id=v.assessment_scheme_id AND s.teacher_id=v.teacher_id AND s.academic_term_id=v.academic_term_id WHERE s.id IS NULL',
    'SELECT COUNT(*) FROM assessments a LEFT JOIN assessment_scheme_versions v ON v.id=a.assessment_scheme_version_id AND v.teacher_id=a.teacher_id AND v.academic_term_id=a.academic_term_id WHERE v.id IS NULL',
    'SELECT COUNT(*) FROM assessment_templates at LEFT JOIN assessments a ON a.id=at.assessment_id AND a.assessment_scheme_version_id=at.assessment_scheme_version_id AND a.teacher_id=at.teacher_id LEFT JOIN table_template_versions tv ON tv.id=at.template_version_id AND tv.created_by=at.teacher_id WHERE a.id IS NULL OR tv.id IS NULL',
    'SELECT COUNT(*) FROM class_scheme_assignments csa LEFT JOIN classes c ON c.id=csa.class_id AND c.teacher_id=csa.teacher_id AND c.academic_term_id=csa.academic_term_id LEFT JOIN assessment_scheme_versions v ON v.id=csa.assessment_scheme_version_id AND v.teacher_id=csa.teacher_id AND v.academic_term_id=csa.academic_term_id WHERE c.id IS NULL OR v.id IS NULL',
    'SELECT COUNT(*) FROM grade_values gv LEFT JOIN class_assessments ca ON ca.id=gv.class_assessment_id AND ca.assessment_id=gv.assessment_id AND ca.class_id=gv.class_id LEFT JOIN assessment_templates at ON at.id=gv.assessment_template_id AND at.assessment_id=gv.assessment_id AND at.template_version_id=gv.template_version_id LEFT JOIN class_enrollments ce ON ce.id=gv.enrollment_id AND ce.class_id=gv.class_id LEFT JOIN table_columns tc ON tc.id=gv.column_id AND tc.template_version_id=gv.template_version_id WHERE ca.id IS NULL OR at.id IS NULL OR ce.id IS NULL OR tc.id IS NULL',
];
foreach ($integrityChecks as $sql) {
    assert((int) $pdo->query($sql)->fetchColumn() === 0);
}

echo "Phase 7 database tests passed.\n";