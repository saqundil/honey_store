ALTER TABLE assessment_scheme_versions
    ADD COLUMN subject_id BIGINT UNSIGNED NULL AFTER academic_term_id;

UPDATE assessment_scheme_versions v
JOIN assessment_schemes s ON s.id = v.assessment_scheme_id
SET v.subject_id = s.subject_id;

ALTER TABLE assessment_scheme_versions
    MODIFY subject_id BIGINT UNSIGNED NOT NULL,
    ADD UNIQUE KEY uq_scheme_version_subject_owner (id, teacher_id, academic_term_id, subject_id),
    ADD CONSTRAINT fk_scheme_version_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT;

ALTER TABLE class_scheme_assignments
    DROP FOREIGN KEY fk_assignment_scheme_owner;

ALTER TABLE class_scheme_assignments
    ADD CONSTRAINT fk_assignment_scheme_owner FOREIGN KEY (assessment_scheme_version_id, teacher_id, academic_term_id, subject_id) REFERENCES assessment_scheme_versions(id, teacher_id, academic_term_id, subject_id) ON DELETE RESTRICT;
