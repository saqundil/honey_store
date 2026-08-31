<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use PDO;
use Throwable;

final class TeacherSetupService
{
    public function __construct(private readonly PDO $db) {}

    public function state(int $teacherId): array
    {
        $years = $this->fetchAll('SELECT * FROM academic_years WHERE teacher_id = ? ORDER BY is_current DESC, name DESC', [$teacherId]);
        $terms = $this->fetchAll('SELECT t.*, y.name academic_year_name FROM academic_terms t JOIN academic_years y ON y.id = t.academic_year_id WHERE t.teacher_id = ? ORDER BY y.is_current DESC, t.sort_order, t.id', [$teacherId]);
        $stages = $this->fetchAll('SELECT * FROM stages WHERE teacher_id = ? AND status = ? ORDER BY sort_order, name', [$teacherId, 'active']);
        $classes = $this->fetchAll('SELECT c.*, s.name stage_name, t.name term_name FROM classes c JOIN stages s ON s.id = c.stage_id JOIN academic_terms t ON t.id = c.academic_term_id WHERE c.teacher_id = ? AND c.status = ? ORDER BY s.sort_order, c.name', [$teacherId, 'active']);
        return [
            'years' => $years,
            'terms' => $terms,
            'stages' => $stages,
            'classes' => $classes,
            'complete' => $years !== [] && $terms !== [] && $stages !== [] && $classes !== [],
        ];
    }

    public function saveAcademicContext(int $teacherId, string $yearName, string $termName): void
    {
        $yearName = trim($yearName);
        $termName = trim($termName);
        if (!preg_match('/^\d{4}\/\d{4}$/', $yearName) || $termName === '' || mb_strlen($termName) > 80) {
            throw new InvalidArgumentException('أدخل عامًا بصيغة 2027/2026 واسم فصل صالحًا.');
        }
        $this->transaction(function () use ($teacherId, $yearName, $termName): void {
            $this->db->prepare('UPDATE academic_years SET is_current = 0 WHERE teacher_id = ?')->execute([$teacherId]);
            $statement = $this->db->prepare('INSERT INTO academic_years(teacher_id, name, is_current) VALUES(?,?,1) ON DUPLICATE KEY UPDATE is_current = 1');
            $statement->execute([$teacherId, $yearName]);
            $yearId = (int) $this->scalar('SELECT id FROM academic_years WHERE teacher_id = ? AND name = ?', [$teacherId, $yearName]);
            $statement = $this->db->prepare('INSERT INTO academic_terms(teacher_id, academic_year_id, name, sort_order, status) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
            $statement->execute([$teacherId, $yearId, $termName, 1, 'active']);
        });
    }

    public function addStage(int $teacherId, string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('اسم المرحلة مطلوب ولا يتجاوز 120 حرفًا.');
        }
        $statement = $this->db->prepare('INSERT INTO stages(teacher_id, name, sort_order, status) VALUES(?, ?, (SELECT next_order FROM (SELECT COALESCE(MAX(sort_order),0)+1 next_order FROM stages WHERE teacher_id=?) x), ?) ON DUPLICATE KEY UPDATE status = VALUES(status)');
        $statement->execute([$teacherId, $name, $teacherId, 'active']);
    }

    public function addClass(int $teacherId, int $termId, int $stageId, string $name): void
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100) {
            throw new InvalidArgumentException('اسم الصف مطلوب ولا يتجاوز 100 حرف.');
        }
        if (!$this->scalar('SELECT 1 FROM academic_terms WHERE id = ? AND teacher_id = ? AND status = ?', [$termId, $teacherId, 'active'])) {
            throw new InvalidArgumentException('الفصل الدراسي لا ينتمي إلى حسابك.');
        }
        if (!$this->scalar('SELECT 1 FROM stages WHERE id = ? AND teacher_id = ? AND status = ?', [$stageId, $teacherId, 'active'])) {
            throw new InvalidArgumentException('المرحلة لا تنتمي إلى حسابك.');
        }
        $statement = $this->db->prepare('INSERT INTO classes(teacher_id, academic_term_id, stage_id, name, status) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE status = VALUES(status), stage_id = VALUES(stage_id)');
        $statement->execute([$teacherId, $termId, $stageId, $name, 'active']);
    }

    private function fetchAll(string $sql, array $params): array
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    private function scalar(string $sql, array $params): mixed
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchColumn();
    }

    private function transaction(callable $callback): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $callback();
            if ($ownsTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
