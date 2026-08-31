<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GradebookRepository;
use App\Repositories\TemplateRepository;
use InvalidArgumentException;

final class GradebookReportService
{
    public function __construct(
        private readonly GradebookRepository $gradebooks,
        private readonly TemplateRepository $templates,
    ) {}

    public function assignmentReport(int $assignmentId): array
    {
        $assessments = $this->gradebooks->classAssessments($assignmentId);
        if (!$assessments) throw new InvalidArgumentException('تعيين سجل العلامات غير متاح أو لا يحتوي اختبارات.');
        $firstContext = $this->gradebooks->context((int) $assessments[0]['id']);
        if (!$firstContext) throw new InvalidArgumentException('سياق سجل العلامات غير متاح.');
        $students = $this->gradebooks->enrollments((int) $assessments[0]['id']);
        $studentRows = [];
        foreach ($students as $student) {
            $studentRows[(int) $student['id']] = [
                'enrollment_id' => (int) $student['id'],
                'student_id' => (int) $student['student_id'],
                'student_number' => $student['student_number'],
                'name' => $student['name'],
                'assessments' => [],
                'earned' => 0.0,
                'maximum' => 0.0,
                'completed' => 0,
            ];
        }

        $assessmentRows = [];
        foreach ($assessments as $assessment) {
            $classAssessmentId = (int) $assessment['id'];
            $templates = $this->gradebooks->templates($classAssessmentId);
            $values = $this->gradebooks->values($classAssessmentId);
            $rawMaximum = 0.0;
            $manualColumns = [];
            foreach ($templates as $assignment) {
                $template = $this->templates->configuration((int) $assignment['template_version_id']);
                if (!$template) continue;
                $columns = array_values(array_filter($template['columns'], static fn(array $column): bool => $column['type'] === 'manual_mark'));
                if ((int) $assignment['is_required'] === 1) {
                    $manualColumns[(int) $assignment['id']] = $columns;
                    $rawMaximum += array_sum(array_map(static fn(array $column): float => (float) ($column['max_mark'] ?? 0), $columns));
                }
            }
            $displayMaximum = $assessment['maximum_mark'] !== null ? (float) $assessment['maximum_mark'] : $rawMaximum;
            $aggregateMaximum = $assessment['weight'] !== null ? (float) $assessment['weight'] : $displayMaximum;
            foreach ($studentRows as $enrollmentId => &$student) {
                $rawEarned = 0.0;
                $hasValue = false;
                foreach ($manualColumns as $assessmentTemplateId => $columns) {
                    foreach ($columns as $column) {
                        $value = $values[$assessmentTemplateId][$enrollmentId][$column['column_key']] ?? null;
                        if ($value !== null && $value !== '') {
                            $rawEarned += (float) $value;
                            $hasValue = true;
                        }
                    }
                }
                $score = $hasValue && $rawMaximum > 0 ? $rawEarned / $rawMaximum * $displayMaximum : null;
                $student['assessments'][$classAssessmentId] = $score;
                if ($score !== null) {
                    $student['earned'] += $displayMaximum > 0 ? $score / $displayMaximum * $aggregateMaximum : 0;
                    $student['maximum'] += $aggregateMaximum;
                    $student['completed']++;
                }
            }
            unset($student);
            $assessmentRows[] = [
                'id' => $classAssessmentId,
                'name' => $assessment['name'],
                'short_name' => $assessment['short_name'] ?: $assessment['name'],
                'maximum' => $displayMaximum,
                'weight' => $assessment['weight'] !== null ? (float) $assessment['weight'] : null,
                'status' => $assessment['status'],
                'template_count' => count($templates),
            ];
        }

        foreach ($studentRows as &$student) {
            $student['percentage'] = $student['maximum'] > 0 ? $student['earned'] / $student['maximum'] * 100 : null;
        }
        unset($student);
        return ['context' => $firstContext, 'assessments' => $assessmentRows, 'students' => array_values($studentRows)];
    }

    public function studentReport(int $assignmentId, int $enrollmentId): array
    {
        $report = $this->assignmentReport($assignmentId);
        foreach ($report['students'] as $student) {
            if ((int) $student['enrollment_id'] === $enrollmentId) {
                $report['student'] = $student;
                return $report;
            }
        }
        throw new InvalidArgumentException('الطالب غير مسجل في هذا الصف والفصل.');
    }

    public function assessmentReport(int $classAssessmentId): array
    {
        $context = $this->gradebooks->context($classAssessmentId);
        if (!$context) throw new InvalidArgumentException('اختبار الصف غير متاح.');
        $students = $this->gradebooks->enrollments($classAssessmentId);
        $values = $this->gradebooks->values($classAssessmentId);
        $sections = [];
        foreach ($this->gradebooks->templates($classAssessmentId) as $assignment) {
            $template = $this->templates->configuration((int) $assignment['template_version_id']);
            if (!$template) continue;
            $sectionValues = $values[(int) $assignment['id']] ?? [];
            $engine = new FormulaEngine();
            foreach ($students as $student) {
                $sectionValues[(int) $student['id']] = $engine->calculate($template['columns'], $sectionValues[(int) $student['id']] ?? []);
            }
            $sections[] = ['assignment' => $assignment, 'template' => $template, 'values' => $sectionValues];
        }
        return ['context' => $context, 'students' => $students, 'sections' => $sections];
    }

    public function format(?float $value): string
    {
        if ($value === null) return '—';
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
