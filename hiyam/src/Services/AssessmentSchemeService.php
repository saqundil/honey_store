<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AssessmentSchemeRepository;
use InvalidArgumentException;
use PDO;
use Throwable;

final class AssessmentSchemeService
{
    public function __construct(private readonly PDO $db, private readonly AssessmentSchemeRepository $repository) {}

    public function publish(array $data, int $actorId): int
    {
        $schemeId = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $termId = (int) ($data['academic_term_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $assessments = $data['assessments'] ?? null;
        if ($name === '' || $termId < 1 || $subjectId < 1 || !is_array($assessments) || !$assessments) {
            throw new InvalidArgumentException('اسم المخطط والفصل والمادة واختبار واحد على الأقل مطلوبة.');
        }
        $this->requireTerm($termId, $actorId);
        $this->requireSubject($subjectId);
        $normalized = $this->normalizeAssessments($assessments, $actorId);

        $startedTransaction = !$this->db->inTransaction();
        if ($startedTransaction) $this->db->beginTransaction();
        try {
            if ($schemeId) {
                $scheme = $this->repository->find($schemeId);
                if (!$scheme || (int) $scheme['teacher_id'] !== $actorId) throw new InvalidArgumentException('مخطط التقييم غير متاح.');
                $statement = $this->db->prepare('UPDATE assessment_schemes SET academic_term_id=?,subject_id=?,name=?,description=? WHERE id=? AND teacher_id=?');
                $statement->execute([$termId, $subjectId, $name, $description ?: null, $schemeId, $actorId]);
            } else {
                $statement = $this->db->prepare('INSERT INTO assessment_schemes(teacher_id,academic_term_id,subject_id,name,description) VALUES(?,?,?,?,?)');
                $statement->execute([$actorId, $termId, $subjectId, $name, $description ?: null]);
                $schemeId = (int) $this->db->lastInsertId();
            }

            $statement = $this->db->prepare('SELECT COALESCE(MAX(version_number),0)+1 FROM assessment_scheme_versions WHERE assessment_scheme_id=? FOR UPDATE');
            $statement->execute([$schemeId]);
            $versionNumber = (int) $statement->fetchColumn();
            $statement = $this->db->prepare("INSERT INTO assessment_scheme_versions(assessment_scheme_id,teacher_id,academic_term_id,subject_id,version_number,status,created_by,published_at) VALUES(?,?,?,?,?,'published',?,CURRENT_TIMESTAMP)");
            $statement->execute([$schemeId, $actorId, $termId, $subjectId, $versionNumber, $actorId]);
            $versionId = (int) $this->db->lastInsertId();

            $insertAssessment = $this->db->prepare('INSERT INTO assessments(assessment_scheme_version_id,teacher_id,academic_term_id,name,short_name,sort_order,maximum_mark,weight,is_required,is_active) VALUES(?,?,?,?,?,?,?,?,?,?)');
            $insertTemplate = $this->db->prepare('INSERT INTO assessment_templates(assessment_id,assessment_scheme_version_id,teacher_id,template_version_id,label,sort_order,is_required,is_active,config_json) VALUES(?,?,?,?,?,?,?,?,?)');
            foreach ($normalized as $assessmentOrder => $assessment) {
                $insertAssessment->execute([$versionId, $actorId, $termId, $assessment['name'], $assessment['short_name'], $assessmentOrder, $assessment['maximum_mark'], $assessment['weight'], $assessment['is_required'], $assessment['is_active']]);
                $assessmentId = (int) $this->db->lastInsertId();
                foreach ($assessment['templates'] as $templateOrder => $template) {
                    $insertTemplate->execute([$assessmentId, $versionId, $actorId, $template['template_version_id'], $template['label'], $templateOrder, $template['is_required'], $template['is_active'], $template['config_json']]);
                }
            }
            $statement = $this->db->prepare('UPDATE assessment_schemes SET current_version_id=? WHERE id=? AND teacher_id=?');
            $statement->execute([$versionId, $schemeId, $actorId]);
            if ($startedTransaction) $this->db->commit();
            return $schemeId;
        } catch (Throwable $error) {
            if ($startedTransaction && $this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function normalizeAssessments(array $assessments, int $actorId): array
    {
        $normalized = [];
        $names = [];
        foreach ($assessments as $assessment) {
            if (!is_array($assessment)) throw new InvalidArgumentException('بيانات الاختبار غير صالحة.');
            $name = trim((string) ($assessment['name'] ?? ''));
            $templates = $assessment['templates'] ?? null;
            if ($name === '' || !is_array($templates) || !$templates) throw new InvalidArgumentException('كل اختبار يحتاج اسمًا وقالبًا واحدًا على الأقل.');
            $nameKey = mb_strtolower($name);
            if (isset($names[$nameKey])) throw new InvalidArgumentException('لا يمكن تكرار اسم الاختبار داخل الإصدار.');
            $names[$nameKey] = true;
            $templateIds = [];
            $normalizedTemplates = [];
            foreach ($templates as $template) {
                $templateVersionId = (int) ($template['template_version_id'] ?? 0);
                if ($templateVersionId < 1 || isset($templateIds[$templateVersionId])) throw new InvalidArgumentException('قوالب الاختبار غير صالحة أو مكررة.');
                $this->requireTemplateVersion($templateVersionId, $actorId);
                $templateIds[$templateVersionId] = true;
                $label = trim((string) ($template['label'] ?? ''));
                if ($label === '') throw new InvalidArgumentException('عنوان القالب داخل الاختبار مطلوب.');
                $config = $template['config'] ?? null;
                $normalizedTemplates[] = ['template_version_id' => $templateVersionId, 'label' => $label, 'is_required' => $this->flag($template, 'is_required', true), 'is_active' => $this->flag($template, 'is_active', true), 'config_json' => $config === null ? null : json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
            }
            $normalized[] = ['name' => $name, 'short_name' => trim((string) ($assessment['short_name'] ?? '')) ?: null, 'maximum_mark' => $this->decimal($assessment['maximum_mark'] ?? null), 'weight' => $this->decimal($assessment['weight'] ?? null), 'is_required' => $this->flag($assessment, 'is_required', true), 'is_active' => $this->flag($assessment, 'is_active', true), 'templates' => $normalizedTemplates];
        }
        return $normalized;
    }

    private function requireTerm(int $termId, int $actorId): void
    {
        $statement = $this->db->prepare('SELECT 1 FROM academic_terms WHERE id=? AND teacher_id=?');
        $statement->execute([$termId, $actorId]);
        if (!$statement->fetchColumn()) throw new InvalidArgumentException('الفصل الدراسي غير متاح.');
    }

    private function requireSubject(int $subjectId): void
    {
        $statement = $this->db->prepare("SELECT 1 FROM subjects WHERE id=? AND status='active'");
        $statement->execute([$subjectId]);
        if (!$statement->fetchColumn()) throw new InvalidArgumentException('المادة غير متاحة.');
    }

    private function requireTemplateVersion(int $versionId, int $actorId): void
    {
        $statement = $this->db->prepare('SELECT 1 FROM table_template_versions WHERE id=? AND created_by=?');
        $statement->execute([$versionId, $actorId]);
        if (!$statement->fetchColumn()) throw new InvalidArgumentException('إصدار القالب غير متاح.');
    }

    private function flag(array $data, string $key, bool $default): int
    {
        return array_key_exists($key, $data) ? (int) (bool) $data[$key] : (int) $default;
    }

    private function decimal(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value) || (float) $value < 0) throw new InvalidArgumentException('القيمة الرقمية غير صالحة.');
        return number_format((float) $value, 2, '.', '');
    }
}
