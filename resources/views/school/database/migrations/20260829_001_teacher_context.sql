UPDATE admin_users SET role = 'super_admin' WHERE role = 'admin';

ALTER TABLE academic_years
    ADD COLUMN teacher_id BIGINT UNSIGNED NULL AFTER id,
    ADD INDEX idx_year_teacher_current (teacher_id, is_current),
    ADD UNIQUE KEY uq_year_teacher_name (teacher_id, name),
    ADD UNIQUE KEY uq_year_id_teacher (id, teacher_id);

ALTER TABLE classes
    ADD COLUMN teacher_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN academic_term_id BIGINT UNSIGNED NULL AFTER teacher_id,
    ADD COLUMN stage_id BIGINT UNSIGNED NULL AFTER academic_term_id,
    ADD INDEX idx_class_teacher_term (teacher_id, academic_term_id, status),
    ADD UNIQUE KEY uq_class_id_teacher_term (id, teacher_id, academic_term_id);

ALTER TABLE students
    ADD COLUMN teacher_id BIGINT UNSIGNED NULL AFTER id,
    ADD INDEX idx_student_teacher_status (teacher_id, status),
    ADD UNIQUE KEY uq_student_id_teacher (id, teacher_id);

CREATE TABLE academic_terms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    starts_at DATE NULL,
    ends_at DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_term_teacher_year_name (teacher_id, academic_year_id, name),
    UNIQUE KEY uq_term_id_teacher (id, teacher_id),
    INDEX idx_term_teacher_status (teacher_id, status, academic_year_id),
    CONSTRAINT fk_term_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_term_year_owner FOREIGN KEY (academic_year_id, teacher_id) REFERENCES academic_years(id, teacher_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE stages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stage_teacher_name (teacher_id, name),
    UNIQUE KEY uq_stage_id_teacher (id, teacher_id),
    INDEX idx_stage_teacher_status (teacher_id, status, sort_order),
    CONSTRAINT fk_stage_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

SET @legacy_owner_id = (SELECT id FROM admin_users ORDER BY id LIMIT 1);
UPDATE academic_years SET teacher_id = @legacy_owner_id WHERE teacher_id IS NULL;
UPDATE students SET teacher_id = @legacy_owner_id WHERE teacher_id IS NULL;

INSERT INTO stages (teacher_id, name, sort_order, status)
SELECT @legacy_owner_id, 'مرحلة غير محددة', 0, 'active'
WHERE @legacy_owner_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM stages WHERE teacher_id = @legacy_owner_id AND name = 'مرحلة غير محددة');
SET @legacy_stage_id = (SELECT id FROM stages WHERE teacher_id = @legacy_owner_id AND name = 'مرحلة غير محددة' LIMIT 1);

INSERT INTO academic_terms (teacher_id, academic_year_id, name, sort_order, status)
SELECT @legacy_owner_id, y.id, 'بيانات سابقة', 0, 'active'
FROM academic_years y
WHERE y.teacher_id = @legacy_owner_id
  AND NOT EXISTS (
      SELECT 1 FROM academic_terms t
      WHERE t.teacher_id = @legacy_owner_id AND t.academic_year_id = y.id AND t.name = 'بيانات سابقة'
  );
SET @legacy_term_id = (
    SELECT t.id
    FROM academic_terms t
    JOIN academic_years y ON y.id = t.academic_year_id
    WHERE t.teacher_id = @legacy_owner_id AND t.name = 'بيانات سابقة'
    ORDER BY y.is_current DESC, y.id DESC
    LIMIT 1
);

UPDATE classes
SET teacher_id = @legacy_owner_id,
    academic_term_id = @legacy_term_id,
    stage_id = @legacy_stage_id
WHERE teacher_id IS NULL;

CREATE TABLE class_enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    enrolled_at DATE NULL,
    left_at DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment_term_student (teacher_id, academic_term_id, student_id),
    UNIQUE KEY uq_enrollment_class_student (class_id, student_id),
    UNIQUE KEY uq_enrollment_id_class (id, class_id),
    INDEX idx_enrollment_class_status (class_id, status),
    INDEX idx_enrollment_student_history (student_id, academic_term_id),
    CONSTRAINT fk_enrollment_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrollment_term_owner FOREIGN KEY (academic_term_id, teacher_id) REFERENCES academic_terms(id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrollment_class_owner FOREIGN KEY (class_id, teacher_id, academic_term_id) REFERENCES classes(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrollment_student_owner FOREIGN KEY (student_id, teacher_id) REFERENCES students(id, teacher_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO class_enrollments (teacher_id, academic_term_id, class_id, student_id, status)
SELECT c.teacher_id, c.academic_term_id, c.id, s.id,
       CASE WHEN s.status = 'active' THEN 'active' ELSE 'inactive' END
FROM students s
JOIN classes c ON c.id = s.class_id
LEFT JOIN class_enrollments ce ON ce.class_id = c.id AND ce.student_id = s.id
WHERE ce.id IS NULL AND c.teacher_id IS NOT NULL AND c.academic_term_id IS NOT NULL;

ALTER TABLE academic_years
    MODIFY teacher_id BIGINT UNSIGNED NOT NULL,
    DROP INDEX name,
    ADD CONSTRAINT fk_year_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT;
ALTER TABLE classes
    MODIFY teacher_id BIGINT UNSIGNED NOT NULL,
    MODIFY academic_term_id BIGINT UNSIGNED NOT NULL,
    MODIFY stage_id BIGINT UNSIGNED NOT NULL,
    DROP INDEX uq_class_name,
    ADD UNIQUE KEY uq_class_teacher_term_name (teacher_id, academic_term_id, name),
    ADD CONSTRAINT fk_class_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_class_term_owner FOREIGN KEY (academic_term_id, teacher_id) REFERENCES academic_terms(id, teacher_id) ON DELETE RESTRICT,
    ADD CONSTRAINT fk_class_stage_owner FOREIGN KEY (stage_id, teacher_id) REFERENCES stages(id, teacher_id) ON DELETE RESTRICT;
ALTER TABLE students
    MODIFY teacher_id BIGINT UNSIGNED NOT NULL,
    DROP INDEX uq_student_number,
    ADD UNIQUE KEY uq_student_teacher_number (teacher_id, student_number),
    ADD CONSTRAINT fk_student_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT;
