<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TemplateRepository
{
    public function __construct(private readonly PDO $db, private readonly int $actorId, private readonly bool $canViewAll = false) {}

    public function all(): array
    {
        $sql = "SELECT t.*, g.name AS group_name, v.version_number FROM table_templates t JOIN template_groups g ON g.id=t.group_id LEFT JOIN table_template_versions v ON v.id=t.current_version_id WHERE t.status<>'archived'";
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' AND t.created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY g.sort_order, g.id, t.updated_at DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function groups(): array
    {
        $sql = 'SELECT id,name,sort_order,created_by FROM template_groups';
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' WHERE created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY sort_order,id');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function currentVersionsForGroup(int $groupId): array
    {
        $sql = "SELECT v.id, v.version_number, t.id AS template_id, t.current_version_id, t.name, g.id AS group_id, g.name AS group_name
                FROM table_templates t
                JOIN template_groups g ON g.id=t.group_id
                JOIN table_template_versions v ON v.id=t.current_version_id
                WHERE t.group_id=? AND t.status='active'";
        $params = [$groupId];
        if (!$this->canViewAll) {
            $sql .= ' AND t.created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY t.updated_at DESC,t.id');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function availableVersions(): array
    {
        $sql = "SELECT v.id, v.version_number, t.id AS template_id, t.current_version_id, t.name, g.name AS group_name FROM table_template_versions v JOIN table_templates t ON t.id=v.template_id JOIN template_groups g ON g.id=t.group_id WHERE t.status='active'";
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' AND t.created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY t.name, v.version_number DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT * FROM table_templates WHERE id=?';
        $params = [$id];
        if (!$this->canViewAll) {
            $sql .= ' AND created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function configuration(int $versionId): ?array
    {
        $sql = 'SELECT v.*, t.name, t.description, t.status, t.group_id, g.name AS group_name FROM table_template_versions v JOIN table_templates t ON t.id=v.template_id JOIN template_groups g ON g.id=t.group_id WHERE v.id=?';
        $params = [$versionId];
        if (!$this->canViewAll) {
            $sql .= ' AND t.created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        $template = $statement->fetch();
        if (!$template) return null;
        $statement = $this->db->prepare('SELECT * FROM table_header_groups WHERE template_version_id=? ORDER BY sort_order,id');
        $statement->execute([$versionId]);
        $template['groups'] = $statement->fetchAll();
        $statement = $this->db->prepare('SELECT c.*, f.formula_type, f.missing_value_behavior, f.percentage_base, f.divisor, f.decimal_places FROM table_columns c LEFT JOIN table_formulas f ON f.column_id=c.id WHERE c.template_version_id=? ORDER BY c.sort_order,c.id');
        $statement->execute([$versionId]);
        $columns = $statement->fetchAll();
        $sourceStatement = $this->db->prepare('SELECT source.column_key FROM table_formula_items i JOIN table_columns source ON source.id=i.source_column_id JOIN table_formulas f ON f.id=i.formula_id WHERE f.column_id=? ORDER BY i.sort_order');
        foreach ($columns as &$column) {
            if ($column['formula_type']) {
                $sourceStatement->execute([$column['id']]);
                $column['formula'] = [
                    'type' => $column['formula_type'], 'sources' => $sourceStatement->fetchAll(PDO::FETCH_COLUMN),
                    'missing' => $column['missing_value_behavior'], 'base' => $column['percentage_base'],
                    'divisor' => (float) ($column['divisor'] ?? 1), 'decimals' => (int) $column['decimal_places'],
                ];
            }
        }
        $template['columns'] = $columns;
        $template['settings'] = json_decode($template['settings_json'] ?: '{}', true) ?: [];
        return $template;
    }

    public function currentConfiguration(int $templateId): ?array
    {
        $template = $this->find($templateId);
        return $template && $template['current_version_id'] ? $this->configuration((int) $template['current_version_id']) : null;
    }

    public function versions(int $templateId): array
    {
        if (!$this->find($templateId)) return [];
        $statement = $this->db->prepare('SELECT id,version_number,created_at FROM table_template_versions WHERE template_id=? ORDER BY version_number DESC');
        $statement->execute([$templateId]);
        return $statement->fetchAll();
    }
}