ALTER TABLE class_assessments
    ADD UNIQUE KEY uq_class_assessment_grade_context (id, assessment_id, class_id);

CREATE TABLE grade_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_assessment_id BIGINT UNSIGNED NOT NULL,
    assessment_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    assessment_template_id BIGINT UNSIGNED NOT NULL,
    template_version_id BIGINT UNSIGNED NOT NULL,
    enrollment_id BIGINT UNSIGNED NOT NULL,
    column_id BIGINT UNSIGNED NOT NULL,
    numeric_value DECIMAL(12,4) NULL,
    text_value TEXT NULL,
    date_value DATE NULL,
    calculated_value DECIMAL(12,4) NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    revision INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grade_cell (class_assessment_id, assessment_template_id, enrollment_id, column_id),
    INDEX idx_grade_enrollment (enrollment_id, class_assessment_id),
    INDEX idx_grade_template (assessment_template_id, class_assessment_id),
    CONSTRAINT fk_grade_class_assessment FOREIGN KEY (class_assessment_id, assessment_id, class_id) REFERENCES class_assessments(id, assessment_id, class_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_assessment_template FOREIGN KEY (assessment_template_id, assessment_id, template_version_id) REFERENCES assessment_templates(id, assessment_id, template_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_enrollment_class FOREIGN KEY (enrollment_id, class_id) REFERENCES class_enrollments(id, class_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_column_version FOREIGN KEY (column_id, template_version_id) REFERENCES table_columns(id, template_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_updated_by FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE grade_value_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_assessment_id BIGINT UNSIGNED NOT NULL,
    grade_value_id BIGINT UNSIGNED NULL,
    action VARCHAR(30) NOT NULL,
    old_value_json JSON NULL,
    new_value_json JSON NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_grade_audit_assessment (class_assessment_id, created_at),
    INDEX idx_grade_audit_value (grade_value_id, created_at),
    CONSTRAINT fk_grade_audit_assessment FOREIGN KEY (class_assessment_id) REFERENCES class_assessments(id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_audit_value FOREIGN KEY (grade_value_id) REFERENCES grade_values(id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_audit_actor FOREIGN KEY (actor_id) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
