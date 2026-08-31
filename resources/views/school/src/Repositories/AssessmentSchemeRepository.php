<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AssessmentSchemeRepository
{
    public function __construct(private readonly PDO $db, private readonly int $actorId, private readonly bool $canViewAll = false) {}

    public function all(): array
    {
        $sql = 'SELECT s.*, t.name term_name, y.name academic_year_name, sub.name subject_name, v.version_number FROM assessment_schemes s JOIN academic_terms t ON t.id=s.academic_term_id JOIN academic_years y ON y.id=t.academic_year_id JOIN subjects sub ON sub.id=s.subject_id LEFT JOIN assessment_scheme_versions v ON v.id=s.current_version_id';
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' WHERE s.teacher_id=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY y.name DESC,t.sort_order,s.updated_at DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $schemeId): ?array
    {
        $sql = 'SELECT * FROM assessment_schemes WHERE id=?';
        $params = [$schemeId];
        if (!$this->canViewAll) {
            $sql .= ' AND teacher_id=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function currentConfiguration(int $schemeId): ?array
    {
        $scheme = $this->find($schemeId);
        return $scheme && $scheme['current_version_id'] ? $this->configuration((int) $scheme['current_version_id']) : null;
    }

    public function configuration(int $versionId): ?array
    {
        $sql = 'SELECT v.*,s.name,s.description,s.subject_id,s.current_version_id FROM assessment_scheme_versions v JOIN assessment_schemes s ON s.id=v.assessment_scheme_id WHERE v.id=?';
        $params = [$versionId];
        if (!$this->canViewAll) {
            $sql .= ' AND v.teacher_id=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $scheme = $statement->fetch();
        if (!$scheme) return null;

        $statement = $this->db->prepare('SELECT * FROM assessments WHERE assessment_scheme_version_id=? ORDER BY sort_order,id');
        $statement->execute([$versionId]);
        $assessments = $statement->fetchAll();
        $assessmentIds = array_map(static fn(array $item): int => (int) $item['id'], $assessments);
        $templatesByAssessment = [];
        if ($assessmentIds) {
            $placeholders = implode(',', array_fill(0, count($assessmentIds), '?'));
            $statement = $this->db->prepare("SELECT at.*,tv.version_number,t.name template_name FROM assessment_templates at JOIN table_template_versions tv ON tv.id=at.template_version_id JOIN table_templates t ON t.id=tv.template_id WHERE at.assessment_id IN ({$placeholders}) ORDER BY at.assessment_id,at.sort_order,at.id");
            $statement->execute($assessmentIds);
            foreach ($statement->fetchAll() as $template) {
                $templatesByAssessment[(int) $template['assessment_id']][] = $template;
            }
        }
        foreach ($assessments as &$assessment) {
            $assessment['templates'] = $templatesByAssessment[(int) $assessment['id']] ?? [];
        }
        unset($assessment);
        $scheme['assessments'] = $assessments;
        return $scheme;
    }

    public function versions(int $schemeId): array
    {
        if (!$this->find($schemeId)) return [];
        $statement = $this->db->prepare('SELECT id,version_number,status,created_at,published_at FROM assessment_scheme_versions WHERE assessment_scheme_id=? ORDER BY version_number DESC');
        $statement->execute([$schemeId]);
        return $statement->fetchAll();
    }
}
