<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReferenceRepository
{
    public function __construct(private readonly PDO $db, private readonly int $teacherId, private readonly bool $canViewAll = false) {}

    public function classes(): array
    {
        $sql = "SELECT c.*,t.name term_name,y.name academic_year_name,st.name stage_name,u.name teacher_name FROM classes c JOIN academic_terms t ON t.id=c.academic_term_id AND t.teacher_id=c.teacher_id JOIN academic_years y ON y.id=t.academic_year_id JOIN stages st ON st.id=c.stage_id JOIN admin_users u ON u.id=c.teacher_id WHERE c.status='active'";
        $params = [];
        if (!$this->canViewAll) { $sql .= ' AND c.teacher_id=?'; $params[] = $this->teacherId; }
        $sql .= ' ORDER BY c.name,u.name';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }
    public function subjects(): array { return $this->db->query("SELECT * FROM subjects WHERE status='active' ORDER BY name")->fetchAll(); }
    public function years(): array
    {
        $sql = 'SELECT y.*,u.name teacher_name FROM academic_years y JOIN admin_users u ON u.id=y.teacher_id';
        $params = [];
        if (!$this->canViewAll) { $sql .= ' WHERE y.teacher_id=?'; $params[] = $this->teacherId; }
        $sql .= ' ORDER BY y.is_current DESC,y.name DESC,u.name';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function students(?int $classId = null, string $search = ''): array
    {
        $sql = "SELECT s.*,c.name class_name,u.name teacher_name FROM students s JOIN classes c ON c.id=s.class_id JOIN admin_users u ON u.id=s.teacher_id WHERE c.status='active'";
        $params = [];
        if (!$this->canViewAll) { $sql .= ' AND s.teacher_id=?'; $params[] = $this->teacherId; }
        if ($classId) { $sql .= ' AND s.class_id=?'; $params[] = $classId; }
        if ($search !== '') { $sql .= ' AND (s.name LIKE ? OR s.student_number LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
        $sql .= ' ORDER BY c.name,s.name';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }
}