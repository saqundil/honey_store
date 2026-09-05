<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReportRepository
{
    public function __construct(private readonly PDO $db, private readonly int $actorId, private readonly bool $canViewAll = false) {}

    public function all(): array
    {
        $sql = 'SELECT r.*,t.name template_name,c.name class_name,s.name subject_name,v.version_number FROM reports r JOIN table_template_versions v ON v.id=r.template_version_id JOIN table_templates t ON t.id=v.template_id JOIN classes c ON c.id=r.class_id JOIN subjects s ON s.id=r.subject_id';
        $params = [];
        if (!$this->canViewAll) {
            $sql .= ' WHERE r.created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY r.created_at DESC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql='SELECT r.*,t.name template_name,c.name class_name,s.name subject_name,y.name academic_year,v.version_number FROM reports r JOIN table_template_versions v ON v.id=r.template_version_id JOIN table_templates t ON t.id=v.template_id JOIN classes c ON c.id=r.class_id JOIN subjects s ON s.id=r.subject_id JOIN academic_years y ON y.id=r.academic_year_id WHERE r.id=?';
        $params=[$id];if(!$this->canViewAll){$sql.=' AND r.created_by=?';$params[]=$this->actorId;}$statement=$this->db->prepare($sql);
        $statement->execute($params); return $statement->fetch()?:null;
    }

    public function batch(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) return [];
        $sql = 'SELECT r.*,t.name template_name,g.name group_name,c.name class_name,s.name subject_name,y.name academic_year,v.version_number
                FROM reports r
                JOIN table_template_versions v ON v.id=r.template_version_id
                JOIN table_templates t ON t.id=v.template_id
                JOIN template_groups g ON g.id=t.group_id
                JOIN classes c ON c.id=r.class_id
                JOIN subjects s ON s.id=r.subject_id
                JOIN academic_years y ON y.id=r.academic_year_id
                WHERE r.batch_token=?';
        $params = [$token];
        if (!$this->canViewAll) {
            $sql .= ' AND r.created_by=?';
            $params[] = $this->actorId;
        }
        $statement = $this->db->prepare($sql . ' ORDER BY r.id');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function students(int $reportId): array
    {
        $sql='SELECT rs.id,rs.student_id,rs.student_number_snapshot student_number,rs.student_name_snapshot name,rs.sort_order FROM report_students rs JOIN reports r ON r.id=rs.report_id WHERE rs.report_id=?';$params=[$reportId];if(!$this->canViewAll){$sql.=' AND r.created_by=?';$params[]=$this->actorId;}$statement=$this->db->prepare($sql.' ORDER BY rs.sort_order,rs.id');
        $statement->execute($params); return $statement->fetchAll();
    }

    public function values(int $reportId): array
    {
        $sql='SELECT rv.report_student_id,c.column_key,COALESCE(rv.numeric_value,rv.calculated_value,rv.date_value,rv.text_value) value FROM report_values rv JOIN report_students rs ON rs.id=rv.report_student_id JOIN reports r ON r.id=rs.report_id JOIN table_columns c ON c.id=rv.column_id WHERE rs.report_id=?';$params=[$reportId];if(!$this->canViewAll){$sql.=' AND r.created_by=?';$params[]=$this->actorId;}$statement=$this->db->prepare($sql);
        $statement->execute($params); $values=[];
        foreach($statement->fetchAll() as $row)$values[(int)$row['report_student_id']][$row['column_key']]=$row['value'];
        return $values;
    }
}