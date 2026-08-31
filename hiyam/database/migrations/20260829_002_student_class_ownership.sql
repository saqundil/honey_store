ALTER TABLE classes
    ADD UNIQUE KEY uq_class_id_teacher (id, teacher_id);

ALTER TABLE students
    DROP FOREIGN KEY fk_student_class,
    ADD CONSTRAINT fk_student_class_owner FOREIGN KEY (class_id, teacher_id) REFERENCES classes(id, teacher_id) ON DELETE RESTRICT;