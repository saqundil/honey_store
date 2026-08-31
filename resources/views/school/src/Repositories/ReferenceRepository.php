<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReferenceRepository
{
    public function __construct(private readonly PDO $db, private readonly int $teacherId) {}

    public function classes(): array
    {
        $statement = $this->db->prepare("SELECT c.*,t.name term_name FROM classes c JOIN academic_terms t ON t.id=c.academic_term_id AND t.teacher_id=c.teacher_id WHERE c.teacher_id=? AND c.status='active' ORDER BY c.name");
        $statement->execute([$this->teacherId]);
        return $statement->fetchAll();
    }
    public function subjects(): array { return $this->db->query("SELECT * FROM subjects WHERE status='active' ORDER BY name")->fetchAll(); }
    public function years(): array
    {
        $statement = $this->db->prepare('SELECT * FROM academic_years WHERE teacher_id=? ORDER BY is_current DESC,name DESC');
        $statement->execute([$this->teacherId]);
        return $statement->fetchAll();
    }

    public function students(?int $classId = null, string $search = ''): array
    {
        $sql = 'SELECT s.*,c.name class_name FROM students s JOIN classes c ON c.id=s.class_id WHERE s.teacher_id=?';
        $params = [$this->teacherId];
        if ($classId) { $sql .= ' AND s.class_id=?'; $params[] = $classId; }
        if ($search !== '') { $sql .= ' AND (s.name LIKE ? OR s.student_number LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
        $sql .= ' ORDER BY c.name,s.name';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }
}