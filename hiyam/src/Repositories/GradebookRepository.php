<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GradebookRepository
{
    public function __construct(private readonly PDO $db, private readonly int $actorId, private readonly bool $canViewAll = false) {}

    public function assignments(): array
    {
        $sql = 'SELECT csa.*,c.name class_name,s.name scheme_name,sv.version_number,sub.name subject_name,t.name term_name,y.name academic_year_name,COUNT(ca.id) assessment_count FROM class_scheme_assignments csa JOIN classes c ON c.id=csa.class_id JOIN assessment_scheme_versions sv ON sv.id=csa.assessment_scheme_version_id JOIN assessment_schemes s ON s.id=sv.assessment_scheme_id JOIN subjects sub ON sub.id=csa.subject_id JOIN academic_terms t ON t.id=csa.academic_term_id JOIN academic_years y ON y.id=t.academic_year_id LEFT JOIN class_assessments ca ON ca.class_scheme_assignment_id=csa.id';
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' WHERE csa.teacher_id=?';
            $params[] = $this->actorId;
        }
        $sql .= ' GROUP BY csa.id ORDER BY csa.updated_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function classAssessments(int $assignmentId): array
    {
        $sql = 'SELECT ca.*,a.name,a.short_name,a.maximum_mark,a.weight,a.is_required,a.is_active,COUNT(at.id) template_count FROM class_assessments ca JOIN class_scheme_assignments csa ON csa.id=ca.class_scheme_assignment_id JOIN assessments a ON a.id=ca.assessment_id LEFT JOIN assessment_templates at ON at.assessment_id=a.id AND at.is_active=1 WHERE ca.class_scheme_assignment_id=?';
        $params = [$assignmentId];
        if (!$this->canViewAll) {
            $sql .= ' AND csa.teacher_id=?';
            $params[] = $this->actorId;
        }
        $sql .= ' GROUP BY ca.id ORDER BY a.sort_order,a.id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function context(int $classAssessmentId): ?array
    {
        $sql = 'SELECT ca.*,a.name assessment_name,a.maximum_mark,csa.teacher_id,csa.academic_term_id,csa.subject_id,csa.class_id,c.name class_name,sub.name subject_name,t.name term_name,y.name academic_year_name,s.name scheme_name,sv.version_number FROM class_assessments ca JOIN assessments a ON a.id=ca.assessment_id JOIN class_scheme_assignments csa ON csa.id=ca.class_scheme_assignment_id JOIN classes c ON c.id=csa.class_id JOIN subjects sub ON sub.id=csa.subject_id JOIN academic_terms t ON t.id=csa.academic_term_id JOIN academic_years y ON y.id=t.academic_year_id JOIN assessment_scheme_versions sv ON sv.id=csa.assessment_scheme_version_id JOIN assessment_schemes s ON s.id=sv.assessment_scheme_id WHERE ca.id=?';
        $params = [$classAssessmentId];
        if (!$this->canViewAll) {
            $sql .= ' AND csa.teacher_id=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function templates(int $classAssessmentId): array
    {
        $context = $this->context($classAssessmentId);
        if (!$context) return [];
        $statement = $this->db->prepare('SELECT at.*,tv.version_number,t.name template_name FROM assessment_templates at JOIN table_template_versions tv ON tv.id=at.template_version_id JOIN table_templates t ON t.id=tv.template_id WHERE at.assessment_id=? AND at.is_active=1 ORDER BY at.sort_order,at.id');
        $statement->execute([(int) $context['assessment_id']]);
        return $statement->fetchAll();
    }

    public function enrollments(int $classAssessmentId): array
    {
        $context = $this->context($classAssessmentId);
        if (!$context) return [];
        $statement = $this->db->prepare("SELECT ce.id,s.id student_id,s.name,s.student_number FROM class_enrollments ce JOIN students s ON s.id=ce.student_id WHERE ce.class_id=? AND ce.academic_term_id=? AND ce.status='active' ORDER BY s.name,s.id");
        $statement->execute([(int) $context['class_id'], (int) $context['academic_term_id']]);
        return $statement->fetchAll();
    }

    public function values(int $classAssessmentId): array
    {
        if (!$this->context($classAssessmentId)) return [];
        $statement = $this->db->prepare('SELECT gv.*,c.column_key FROM grade_values gv JOIN table_columns c ON c.id=gv.column_id WHERE gv.class_assessment_id=?');
        $statement->execute([$classAssessmentId]);
        $values = [];
        foreach ($statement->fetchAll() as $value) {
            $cellValue = $value['calculated_value'] ?? $value['numeric_value'] ?? $value['text_value'] ?? $value['date_value'];
            if (($value['calculated_value'] !== null || $value['numeric_value'] !== null) && $cellValue !== null) {
                $cellValue = rtrim(rtrim(number_format((float) $cellValue, 4, '.', ''), '0'), '.');
            }
            $values[(int) $value['assessment_template_id']][(int) $value['enrollment_id']][$value['column_key']] = $cellValue;
        }
        return $values;
    }

    public function revisions(int $classAssessmentId): array
    {
        if (!$this->context($classAssessmentId)) return [];
        $statement = $this->db->prepare('SELECT assessment_template_id,enrollment_id,c.column_key,gv.revision FROM grade_values gv JOIN table_columns c ON c.id=gv.column_id WHERE gv.class_assessment_id=?');
        $statement->execute([$classAssessmentId]);
        $revisions = [];
        foreach ($statement->fetchAll() as $value) {
            $revisions[(int) $value['assessment_template_id']][(int) $value['enrollment_id']][$value['column_key']] = (int) $value['revision'];
        }
        return $revisions;
    }

    public function audits(int $classAssessmentId, int $limit = 100): array
    {
        if (!$this->context($classAssessmentId)) return [];
        $statement = $this->db->prepare('SELECT a.*,u.name actor_name FROM grade_value_audits a JOIN admin_users u ON u.id=a.actor_id WHERE a.class_assessment_id=? ORDER BY a.id DESC LIMIT ' . max(1, min(500, $limit)));
        $statement->execute([$classAssessmentId]);
        return $statement->fetchAll();
    }

    /**
     * The full "صفوفي" list — one card row per active class the teacher owns, with
     * enough aggregates to fill each card without an N+1 lookup.
     */
    public function teacherClasses(): array
    {
        $sql = 'SELECT
                    c.id, c.name, c.status, c.academic_term_id,
                    st.name  AS stage_name,
                    t.name   AS term_name,
                    y.name   AS academic_year_name,
                    (SELECT COUNT(*) FROM class_enrollments ce
                        WHERE ce.class_id=c.id AND ce.academic_term_id=c.academic_term_id AND ce.status="active") AS students_count,
                    (SELECT COUNT(*) FROM class_assessments ca
                        JOIN class_scheme_assignments csa ON csa.id=ca.class_scheme_assignment_id
                        WHERE ca.class_id=c.id AND csa.status="active" AND ca.status="draft") AS draft_count,
                    (SELECT COUNT(*) FROM class_assessments ca
                        JOIN class_scheme_assignments csa ON csa.id=ca.class_scheme_assignment_id
                        WHERE ca.class_id=c.id AND csa.status="active" AND ca.status="open") AS open_count,
                    (SELECT COUNT(*) FROM class_assessments ca
                        JOIN class_scheme_assignments csa ON csa.id=ca.class_scheme_assignment_id
                        WHERE ca.class_id=c.id AND csa.status="active" AND ca.status="locked") AS locked_count,
                    (SELECT csa.assessment_scheme_version_id FROM class_scheme_assignments csa
                        WHERE csa.class_id=c.id AND csa.status="active" LIMIT 1) AS active_scheme_version_id,
                    (SELECT GREATEST(
                        COALESCE((SELECT MAX(gv.updated_at) FROM grade_values gv WHERE gv.class_id=c.id), "1970-01-01"),
                        COALESCE((SELECT MAX(ca.updated_at) FROM class_assessments ca WHERE ca.class_id=c.id), "1970-01-01")
                    )) AS last_activity_at
                FROM classes c
                JOIN stages st ON st.id=c.stage_id
                JOIN academic_terms t ON t.id=c.academic_term_id
                JOIN academic_years y ON y.id=t.academic_year_id
                WHERE c.status="active"';
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' AND c.teacher_id=?';
            $params[] = $this->actorId;
        }
        $sql .= ' ORDER BY c.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();
        foreach ($rows as &$row) {
            $val = (string) ($row['last_activity_at'] ?? '');
            if ($val === '' || str_starts_with($val, '1970-01-01')) {
                $row['last_activity_at'] = null;
            }
        }
        return $rows;
    }

    /**
     * The class as seen by the teacher: name, stage, term info, and whether an active
     * scheme_version exists (which gates the "new exam" flow).
     */
    public function classCard(int $classId): ?array
    {
        $sql = 'SELECT c.id, c.name, c.status, c.academic_term_id,
                       st.name AS stage_name,
                       t.name  AS term_name,
                       y.name  AS academic_year_name,
                       (SELECT COUNT(*) FROM class_enrollments ce WHERE ce.class_id=c.id AND ce.academic_term_id=c.academic_term_id AND ce.status="active") AS students_count,
                       (SELECT csa.id FROM class_scheme_assignments csa WHERE csa.class_id=c.id AND csa.status="active" LIMIT 1) AS active_assignment_id,
                       (SELECT csa.assessment_scheme_version_id FROM class_scheme_assignments csa WHERE csa.class_id=c.id AND csa.status="active" LIMIT 1) AS active_scheme_version_id,
                       (SELECT csa.subject_id FROM class_scheme_assignments csa WHERE csa.class_id=c.id AND csa.status="active" LIMIT 1) AS active_subject_id
                FROM classes c
                JOIN stages st ON st.id=c.stage_id
                JOIN academic_terms t ON t.id=c.academic_term_id
                JOIN academic_years y ON y.id=t.academic_year_id
                WHERE c.id=?';
        $params = [$classId];
        if (!$this->canViewAll) {
            $sql .= ' AND c.teacher_id=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    /**
     * All class_assessments for a class (across active scheme_versions in the class's term),
     * enriched with template label + progress (recorded / total students).
     */
    public function classExams(int $classId): array
    {
        $class = $this->classCard($classId);
        if (!$class) return [];
        $sql = 'SELECT ca.id, ca.status, ca.exam_date, ca.created_at,
                       a.id AS assessment_id, a.name AS assessment_name, a.short_name, a.maximum_mark,
                       at.label AS template_label, at.template_version_id,
                       tt.name AS template_name, tv.version_number AS template_version_number,
                       (SELECT COUNT(DISTINCT gv.enrollment_id) FROM grade_values gv WHERE gv.class_assessment_id=ca.id) AS recorded_count
                FROM class_assessments ca
                JOIN class_scheme_assignments csa ON csa.id=ca.class_scheme_assignment_id
                JOIN assessments a ON a.id=ca.assessment_id
                LEFT JOIN assessment_templates at ON at.assessment_id=a.id AND at.is_active=1
                LEFT JOIN table_template_versions tv ON tv.id=at.template_version_id
                LEFT JOIN table_templates tt ON tt.id=tv.template_id
                WHERE ca.class_id=? AND csa.status="active"';
        $params = [$classId];
        if (!$this->canViewAll) {
            $sql .= ' AND csa.teacher_id=?';
            $params[] = $this->actorId;
        }
        $sql .= ' ORDER BY COALESCE(ca.exam_date, ca.created_at) DESC, ca.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();
        // Collapse to one row per class_assessment (in case >1 assessment_template exists we take first)
        $seen = []; $collapsed = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            $row['students_count'] = (int) $class['students_count'];
            $row['recorded_count'] = (int) $row['recorded_count'];
            $collapsed[] = $row;
        }
        return $collapsed;
    }

    /**
     * Templates the teacher owns and can pick from when creating a new exam.
     */
    public function availableTemplateVersions(): array
    {
        $sql = 'SELECT tv.id AS template_version_id, tv.version_number, tt.id AS template_id, tt.name
                FROM table_template_versions tv
                JOIN table_templates tt ON tt.id=tv.template_id
                WHERE tt.status="active"';
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' AND tt.created_by=?';
            $params[] = $this->actorId;
        }
        // Only the current version per template (avoids listing every historical version)
        $sql .= ' AND tv.id = tt.current_version_id ORDER BY tt.name, tv.version_number DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }
}
