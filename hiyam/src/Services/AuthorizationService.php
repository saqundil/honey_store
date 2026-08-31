<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuthorizationService
{
    private const OWNERSHIP = [
        'academic_year' => ['academic_years', 'teacher_id'],
        'academic_term' => ['academic_terms', 'teacher_id'],
        'stage' => ['stages', 'teacher_id'],
        'class' => ['classes', 'teacher_id'],
        'student' => ['students', 'teacher_id'],
        'template' => ['table_templates', 'created_by'],
        'assessment_scheme' => ['assessment_schemes', 'teacher_id'],
        'gradebook_assignment' => ['class_scheme_assignments', 'teacher_id'],
        'report' => ['reports', 'created_by'],
    ];

    public function __construct(private readonly PDO $db) {}

    public function canAccess(string $resource, int $resourceId, array $actor): bool
    {
        if (($actor['role'] ?? null) === 'super_admin') {
            return true;
        }
        if (($actor['role'] ?? null) !== 'teacher' || $resourceId < 1) {
            return false;
        }
        [$table, $ownerColumn] = self::OWNERSHIP[$resource] ?? [null, null];
        if (!$table || !$ownerColumn) {
            return false;
        }
        $statement = $this->db->prepare("SELECT 1 FROM {$table} WHERE id = ? AND {$ownerColumn} = ? LIMIT 1");
        $statement->execute([$resourceId, (int) ($actor['id'] ?? 0)]);
        return (bool) $statement->fetchColumn();
    }

    public function requireAccess(string $resource, int $resourceId, array $actor): void
    {
        if (!$this->canAccess($resource, $resourceId, $actor)) {
            http_response_code(403);
            exit('غير مصرح بالوصول إلى هذا المورد.');
        }
    }
}