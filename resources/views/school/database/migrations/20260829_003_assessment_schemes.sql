ALTER TABLE table_template_versions
    ADD UNIQUE KEY uq_template_version_owner (id, created_by);

ALTER TABLE table_columns
    ADD UNIQUE KEY uq_column_id_version (id, template_version_id);

CREATE TABLE assessment_schemes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    current_version_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_scheme_teacher_term_subject_name (teacher_id, academic_term_id, subject_id, name),
    UNIQUE KEY uq_scheme_id_owner_term (id, teacher_id, academic_term_id),
    INDEX idx_scheme_owner_status (teacher_id, academic_term_id, status),
    CONSTRAINT fk_scheme_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_term_owner FOREIGN KEY (academic_term_id, teacher_id) REFERENCES academic_terms(id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE assessment_scheme_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_scheme_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_scheme_version_number (assessment_scheme_id, version_number),
    UNIQUE KEY uq_scheme_version_owner (id, teacher_id, academic_term_id),
    INDEX idx_scheme_version_status (assessment_scheme_id, status),
    CONSTRAINT fk_scheme_version_scheme_owner FOREIGN KEY (assessment_scheme_id, teacher_id, academic_term_id) REFERENCES assessment_schemes(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_version_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE assessment_schemes
    ADD CONSTRAINT fk_scheme_current_version FOREIGN KEY (current_version_id) REFERENCES assessment_scheme_versions(id) ON DELETE SET NULL;

CREATE TABLE assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_scheme_version_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    short_name VARCHAR(80) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    maximum_mark DECIMAL(10,2) NULL,
    weight DECIMAL(10,2) NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assessment_version_name (assessment_scheme_version_id, name),
    UNIQUE KEY uq_assessment_id_version (id, assessment_scheme_version_id),
    UNIQUE KEY uq_assessment_id_context (id, assessment_scheme_version_id, teacher_id),
    INDEX idx_assessment_order (assessment_scheme_version_id, sort_order),
    CONSTRAINT fk_assessment_version_owner FOREIGN KEY (assessment_scheme_version_id, teacher_id, academic_term_id) REFERENCES assessment_scheme_versions(id, teacher_id, academic_term_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE assessment_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id BIGINT UNSIGNED NOT NULL,
    assessment_scheme_version_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    template_version_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(190) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    config_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assessment_template_version (assessment_id, template_version_id),
    UNIQUE KEY uq_assessment_template_id_context (id, assessment_id, template_version_id),
    INDEX idx_assessment_template_order (assessment_id, sort_order),
    CONSTRAINT fk_assessment_template_assessment_owner FOREIGN KEY (assessment_id, assessment_scheme_version_id, teacher_id) REFERENCES assessments(id, assessment_scheme_version_id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assessment_template_version_owner FOREIGN KEY (template_version_id, teacher_id) REFERENCES table_template_versions(id, created_by) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE class_scheme_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    assessment_scheme_version_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    assigned_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_scheme_version (class_id, assessment_scheme_version_id),
    UNIQUE KEY uq_assignment_id_context (id, class_id, assessment_scheme_version_id),
    INDEX idx_assignment_lookup (teacher_id, academic_term_id, class_id, subject_id, status),
    CONSTRAINT fk_assignment_class_owner FOREIGN KEY (class_id, teacher_id, academic_term_id) REFERENCES classes(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_scheme_owner FOREIGN KEY (assessment_scheme_version_id, teacher_id, academic_term_id) REFERENCES assessment_scheme_versions(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_actor FOREIGN KEY (assigned_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE class_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_scheme_assignment_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    assessment_scheme_version_id BIGINT UNSIGNED NOT NULL,
    assessment_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    opened_at TIMESTAMP NULL,
    opened_by BIGINT UNSIGNED NULL,
    locked_at TIMESTAMP NULL,
    locked_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_assessment (class_scheme_assignment_id, assessment_id),
    UNIQUE KEY uq_class_assessment_id_context (id, class_id, assessment_id),
    INDEX idx_class_assessment_status (class_id, status),
    CONSTRAINT fk_class_assessment_assignment FOREIGN KEY (class_scheme_assignment_id, class_id, assessment_scheme_version_id) REFERENCES class_scheme_assignments(id, class_id, assessment_scheme_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_assessment_definition FOREIGN KEY (assessment_id, assessment_scheme_version_id) REFERENCES assessments(id, assessment_scheme_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_assessment_opened_by FOREIGN KEY (opened_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_assessment_locked_by FOREIGN KEY (locked_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
