CREATE DATABASE IF NOT EXISTS student_assessment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE student_assessment;

CREATE TABLE schema_migrations (
    migration VARCHAR(190) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO schema_migrations(migration) VALUES
    ('20260829_001_teacher_context.sql'),
    ('20260829_002_student_class_ownership.sql'),
    ('20260829_003_assessment_schemes.sql'),
    ('20260829_004_assessment_subject_ownership.sql'),
    ('20260829_005_grade_entry.sql'),
    ('20260829_006_immutable_history.sql'),
    ('20260830_007_class_assessment_exam_date.sql'),
    ('20260830_008_formula_divisor.sql'),
    ('20260901_009_template_groups.sql'),
    ('20260905_010_report_batches.sql'),
    ('20260905_011_template_group_order.sql');

CREATE TABLE admin_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'teacher',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_status (status)
) ENGINE=InnoDB;

CREATE TABLE academic_years (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, name VARCHAR(30) NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY uq_year_teacher_name (teacher_id, name), UNIQUE KEY uq_year_id_teacher (id, teacher_id),
    INDEX idx_year_teacher_current (teacher_id, is_current),
    CONSTRAINT fk_year_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE academic_terms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL, sort_order INT NOT NULL DEFAULT 0, starts_at DATE NULL, ends_at DATE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_term_teacher_year_name (teacher_id, academic_year_id, name), UNIQUE KEY uq_term_id_teacher (id, teacher_id),
    INDEX idx_term_teacher_status (teacher_id, status, academic_year_id),
    CONSTRAINT fk_term_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_term_year_owner FOREIGN KEY (academic_year_id, teacher_id) REFERENCES academic_years(id, teacher_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE stages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stage_teacher_name (teacher_id, name), UNIQUE KEY uq_stage_id_teacher (id, teacher_id),
    INDEX idx_stage_teacher_status (teacher_id, status, sort_order),
    CONSTRAINT fk_stage_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, academic_term_id BIGINT UNSIGNED NOT NULL,
    stage_id BIGINT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_class_teacher_term_name (teacher_id, academic_term_id, name),
    UNIQUE KEY uq_class_id_teacher (id, teacher_id), UNIQUE KEY uq_class_id_teacher_term (id, teacher_id, academic_term_id),
    INDEX idx_class_teacher_term (teacher_id, academic_term_id, status),
    CONSTRAINT fk_class_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_term_owner FOREIGN KEY (academic_term_id, teacher_id) REFERENCES academic_terms(id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_stage_owner FOREIGN KEY (stage_id, teacher_id) REFERENCES stages(id, teacher_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE subjects (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', UNIQUE KEY uq_subject_name (name)) ENGINE=InnoDB;
CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    student_number VARCHAR(40) NOT NULL,
    name VARCHAR(190) NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_teacher_number (teacher_id, student_number), UNIQUE KEY uq_student_id_teacher (id, teacher_id),
    INDEX idx_student_name (name), INDEX idx_student_class_status (class_id, status), INDEX idx_student_teacher_status (teacher_id, status),
    CONSTRAINT fk_student_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_student_class_owner FOREIGN KEY (class_id, teacher_id) REFERENCES classes(id, teacher_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE class_enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, academic_term_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active',
    enrolled_at DATE NULL, left_at DATE NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_enrollment_term_student (teacher_id, academic_term_id, student_id),
    UNIQUE KEY uq_enrollment_class_student (class_id, student_id), UNIQUE KEY uq_enrollment_id_class (id, class_id),
    INDEX idx_enrollment_class_status (class_id, status), INDEX idx_enrollment_student_history (student_id, academic_term_id),
    CONSTRAINT fk_enrollment_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrollment_term_owner FOREIGN KEY (academic_term_id, teacher_id) REFERENCES academic_terms(id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrollment_class_owner FOREIGN KEY (class_id, teacher_id, academic_term_id) REFERENCES classes(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_enrollment_student_owner FOREIGN KEY (student_id, teacher_id) REFERENCES students(id, teacher_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE template_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, sort_order INT NOT NULL DEFAULT 0, created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_template_group_owner_name (created_by, name), UNIQUE KEY uq_template_group_id_owner (id, created_by), INDEX idx_template_group_owner_order (created_by, sort_order, id),
    CONSTRAINT fk_template_group_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE table_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, group_id BIGINT UNSIGNED NOT NULL, name VARCHAR(190) NOT NULL, description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', current_version_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_status (status), INDEX idx_template_group_status (group_id, status),
    CONSTRAINT fk_template_admin FOREIGN KEY (created_by) REFERENCES admin_users(id),
    CONSTRAINT fk_template_group_owner FOREIGN KEY (group_id, created_by) REFERENCES template_groups(id, created_by) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE table_template_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_id BIGINT UNSIGNED NOT NULL, version_number INT UNSIGNED NOT NULL,
    settings_json JSON NULL, created_by BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_template_version (template_id, version_number), UNIQUE KEY uq_template_version_owner (id, created_by),
    CONSTRAINT fk_version_template FOREIGN KEY (template_id) REFERENCES table_templates(id) ON DELETE CASCADE,
    CONSTRAINT fk_version_admin FOREIGN KEY (created_by) REFERENCES admin_users(id)
) ENGINE=InnoDB;
ALTER TABLE table_templates ADD CONSTRAINT fk_template_current_version FOREIGN KEY (current_version_id) REFERENCES table_template_versions(id) ON DELETE SET NULL;

CREATE TABLE table_header_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_version_id BIGINT UNSIGNED NOT NULL, parent_id BIGINT UNSIGNED NULL,
    group_key VARCHAR(100) NOT NULL, name VARCHAR(190) NOT NULL, sort_order INT NOT NULL DEFAULT 0,
    text_direction VARCHAR(10) NOT NULL DEFAULT 'rtl', display_direction VARCHAR(12) NOT NULL DEFAULT 'horizontal',
    UNIQUE KEY uq_group_key (template_version_id, group_key), INDEX idx_group_order (template_version_id, parent_id, sort_order),
    CONSTRAINT fk_group_version FOREIGN KEY (template_version_id) REFERENCES table_template_versions(id) ON DELETE CASCADE,
    CONSTRAINT fk_group_parent FOREIGN KEY (parent_id) REFERENCES table_header_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE table_columns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_version_id BIGINT UNSIGNED NOT NULL, header_group_id BIGINT UNSIGNED NULL,
    column_key VARCHAR(100) NOT NULL, name VARCHAR(190) NOT NULL, header_label VARCHAR(190) NULL, type VARCHAR(40) NOT NULL,
    max_mark DECIMAL(10,2) NULL, step_value DECIMAL(10,2) NULL DEFAULT 0.25, width_mm DECIMAL(8,2) NOT NULL DEFAULT 15,
    sort_order INT NOT NULL DEFAULT 0, is_visible TINYINT(1) NOT NULL DEFAULT 1, is_calculated TINYINT(1) NOT NULL DEFAULT 0,
    text_direction VARCHAR(10) NOT NULL DEFAULT 'rtl', display_direction VARCHAR(12) NOT NULL DEFAULT 'horizontal', config_json JSON NULL,
    UNIQUE KEY uq_column_key (template_version_id, column_key), UNIQUE KEY uq_column_id_version (id, template_version_id), INDEX idx_column_order (template_version_id, sort_order),
    CONSTRAINT fk_column_version FOREIGN KEY (template_version_id) REFERENCES table_template_versions(id) ON DELETE CASCADE,
    CONSTRAINT fk_column_group FOREIGN KEY (header_group_id) REFERENCES table_header_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE table_formulas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, column_id BIGINT UNSIGNED NOT NULL UNIQUE, formula_type VARCHAR(30) NOT NULL,
    expression VARCHAR(1000) NOT NULL, missing_value_behavior VARCHAR(20) NOT NULL DEFAULT 'blank', percentage_base DECIMAL(10,2) NULL, decimal_places TINYINT UNSIGNED NOT NULL DEFAULT 2,
    divisor DECIMAL(10,4) NOT NULL DEFAULT 1, CONSTRAINT chk_formula_divisor CHECK (divisor > 0),
    CONSTRAINT fk_formula_column FOREIGN KEY (column_id) REFERENCES table_columns(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE table_formula_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, formula_id BIGINT UNSIGNED NOT NULL, source_column_id BIGINT UNSIGNED NOT NULL, sort_order INT NOT NULL DEFAULT 0, weight DECIMAL(10,4) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_formula_source (formula_id, source_column_id), CONSTRAINT fk_item_formula FOREIGN KEY (formula_id) REFERENCES table_formulas(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_source FOREIGN KEY (source_column_id) REFERENCES table_columns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assessment_schemes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL, subject_id BIGINT UNSIGNED NOT NULL, name VARCHAR(190) NOT NULL,
    description TEXT NULL, current_version_id BIGINT UNSIGNED NULL, status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_scheme_teacher_term_subject_name (teacher_id, academic_term_id, subject_id, name),
    UNIQUE KEY uq_scheme_id_owner_term (id, teacher_id, academic_term_id), INDEX idx_scheme_owner_status (teacher_id, academic_term_id, status),
    CONSTRAINT fk_scheme_teacher FOREIGN KEY (teacher_id) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_term_owner FOREIGN KEY (academic_term_id, teacher_id) REFERENCES academic_terms(id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE assessment_scheme_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_scheme_id BIGINT UNSIGNED NOT NULL, teacher_id BIGINT UNSIGNED NOT NULL,
    academic_term_id BIGINT UNSIGNED NOT NULL, subject_id BIGINT UNSIGNED NOT NULL, version_number INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published', created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, published_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_scheme_version_number (assessment_scheme_id, version_number),
    UNIQUE KEY uq_scheme_version_owner (id, teacher_id, academic_term_id),
    UNIQUE KEY uq_scheme_version_subject_owner (id, teacher_id, academic_term_id, subject_id),
    INDEX idx_scheme_version_status (assessment_scheme_id, status),
    CONSTRAINT fk_scheme_version_scheme_owner FOREIGN KEY (assessment_scheme_id, teacher_id, academic_term_id) REFERENCES assessment_schemes(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_version_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_scheme_version_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
ALTER TABLE assessment_schemes ADD CONSTRAINT fk_scheme_current_version FOREIGN KEY (current_version_id) REFERENCES assessment_scheme_versions(id) ON DELETE SET NULL;
CREATE TABLE assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_scheme_version_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL, academic_term_id BIGINT UNSIGNED NOT NULL, name VARCHAR(190) NOT NULL,
    short_name VARCHAR(80) NULL, sort_order INT NOT NULL DEFAULT 0, maximum_mark DECIMAL(10,2) NULL, weight DECIMAL(10,2) NULL,
    is_required TINYINT(1) NOT NULL DEFAULT 1, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assessment_version_name (assessment_scheme_version_id, name),
    UNIQUE KEY uq_assessment_id_version (id, assessment_scheme_version_id),
    UNIQUE KEY uq_assessment_id_context (id, assessment_scheme_version_id, teacher_id), INDEX idx_assessment_order (assessment_scheme_version_id, sort_order),
    CONSTRAINT fk_assessment_version_owner FOREIGN KEY (assessment_scheme_version_id, teacher_id, academic_term_id) REFERENCES assessment_scheme_versions(id, teacher_id, academic_term_id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE assessment_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id BIGINT UNSIGNED NOT NULL,
    assessment_scheme_version_id BIGINT UNSIGNED NOT NULL, teacher_id BIGINT UNSIGNED NOT NULL, template_version_id BIGINT UNSIGNED NOT NULL,
    label VARCHAR(190) NOT NULL, sort_order INT NOT NULL DEFAULT 0, is_required TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1, config_json JSON NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_assessment_template_version (assessment_id, template_version_id),
    UNIQUE KEY uq_assessment_template_id_context (id, assessment_id, template_version_id), INDEX idx_assessment_template_order (assessment_id, sort_order),
    CONSTRAINT fk_assessment_template_assessment_owner FOREIGN KEY (assessment_id, assessment_scheme_version_id, teacher_id) REFERENCES assessments(id, assessment_scheme_version_id, teacher_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assessment_template_version_owner FOREIGN KEY (template_version_id, teacher_id) REFERENCES table_template_versions(id, created_by) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE class_scheme_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, teacher_id BIGINT UNSIGNED NOT NULL, academic_term_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL, subject_id BIGINT UNSIGNED NOT NULL, assessment_scheme_version_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active', assigned_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_scheme_version (class_id, assessment_scheme_version_id),
    UNIQUE KEY uq_assignment_id_context (id, class_id, assessment_scheme_version_id),
    INDEX idx_assignment_lookup (teacher_id, academic_term_id, class_id, subject_id, status),
    CONSTRAINT fk_assignment_class_owner FOREIGN KEY (class_id, teacher_id, academic_term_id) REFERENCES classes(id, teacher_id, academic_term_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_scheme_owner FOREIGN KEY (assessment_scheme_version_id, teacher_id, academic_term_id, subject_id) REFERENCES assessment_scheme_versions(id, teacher_id, academic_term_id, subject_id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignment_actor FOREIGN KEY (assigned_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE class_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, class_scheme_assignment_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL, assessment_scheme_version_id BIGINT UNSIGNED NOT NULL, assessment_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', exam_date DATE NULL, opened_at TIMESTAMP NULL, opened_by BIGINT UNSIGNED NULL,
    locked_at TIMESTAMP NULL, locked_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_class_assessment (class_scheme_assignment_id, assessment_id),
    UNIQUE KEY uq_class_assessment_id_context (id, class_id, assessment_id), INDEX idx_class_assessment_status (class_id, status),
    INDEX idx_class_assessment_exam_date (class_id, exam_date),
    UNIQUE KEY uq_class_assessment_grade_context (id, assessment_id, class_id),
    CONSTRAINT fk_class_assessment_assignment FOREIGN KEY (class_scheme_assignment_id, class_id, assessment_scheme_version_id) REFERENCES class_scheme_assignments(id, class_id, assessment_scheme_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_assessment_definition FOREIGN KEY (assessment_id, assessment_scheme_version_id) REFERENCES assessments(id, assessment_scheme_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_assessment_opened_by FOREIGN KEY (opened_by) REFERENCES admin_users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_class_assessment_locked_by FOREIGN KEY (locked_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE grade_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, class_assessment_id BIGINT UNSIGNED NOT NULL,
    assessment_id BIGINT UNSIGNED NOT NULL, class_id BIGINT UNSIGNED NOT NULL, assessment_template_id BIGINT UNSIGNED NOT NULL,
    template_version_id BIGINT UNSIGNED NOT NULL, enrollment_id BIGINT UNSIGNED NOT NULL, column_id BIGINT UNSIGNED NOT NULL,
    numeric_value DECIMAL(12,4) NULL, text_value TEXT NULL, date_value DATE NULL, calculated_value DECIMAL(12,4) NULL,
    updated_by BIGINT UNSIGNED NOT NULL, revision INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grade_cell (class_assessment_id, assessment_template_id, enrollment_id, column_id),
    INDEX idx_grade_enrollment (enrollment_id, class_assessment_id), INDEX idx_grade_template (assessment_template_id, class_assessment_id),
    CONSTRAINT fk_grade_class_assessment FOREIGN KEY (class_assessment_id, assessment_id, class_id) REFERENCES class_assessments(id, assessment_id, class_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_assessment_template FOREIGN KEY (assessment_template_id, assessment_id, template_version_id) REFERENCES assessment_templates(id, assessment_id, template_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_enrollment_class FOREIGN KEY (enrollment_id, class_id) REFERENCES class_enrollments(id, class_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_column_version FOREIGN KEY (column_id, template_version_id) REFERENCES table_columns(id, template_version_id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_updated_by FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
CREATE TABLE grade_value_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, class_assessment_id BIGINT UNSIGNED NOT NULL,
    grade_value_id BIGINT UNSIGNED NULL, action VARCHAR(30) NOT NULL, old_value_json JSON NULL, new_value_json JSON NULL,
    actor_id BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_grade_audit_assessment (class_assessment_id, created_at), INDEX idx_grade_audit_value (grade_value_id, created_at),
    CONSTRAINT fk_grade_audit_assessment FOREIGN KEY (class_assessment_id) REFERENCES class_assessments(id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_audit_value FOREIGN KEY (grade_value_id) REFERENCES grade_values(id) ON DELETE RESTRICT,
    CONSTRAINT fk_grade_audit_actor FOREIGN KEY (actor_id) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TRIGGER trg_scheme_version_no_update BEFORE UPDATE ON assessment_scheme_versions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment scheme versions are immutable';
CREATE TRIGGER trg_scheme_version_no_delete BEFORE DELETE ON assessment_scheme_versions FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment scheme versions are immutable';
CREATE TRIGGER trg_assessment_no_update BEFORE UPDATE ON assessments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessments are immutable';
CREATE TRIGGER trg_assessment_no_delete BEFORE DELETE ON assessments FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessments are immutable';
CREATE TRIGGER trg_assessment_template_no_update BEFORE UPDATE ON assessment_templates FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessment templates are immutable';
CREATE TRIGGER trg_assessment_template_no_delete BEFORE DELETE ON assessment_templates FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessment templates are immutable';
CREATE TRIGGER trg_grade_audit_no_update BEFORE UPDATE ON grade_value_audits FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Grade audit history is immutable';
CREATE TRIGGER trg_grade_audit_no_delete BEFORE DELETE ON grade_value_audits FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Grade audit history is immutable';

CREATE TABLE reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_version_id BIGINT UNSIGNED NOT NULL, class_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL, academic_year_id BIGINT UNSIGNED NOT NULL, title VARCHAR(190) NOT NULL, semester VARCHAR(50) NULL,
    report_date DATE NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft', batch_token CHAR(32) NULL, created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, locked_at TIMESTAMP NULL,
    INDEX idx_report_lookup (class_id, academic_year_id, report_date), INDEX idx_report_batch_owner (batch_token, created_by),
    CONSTRAINT fk_report_version FOREIGN KEY (template_version_id) REFERENCES table_template_versions(id),
    CONSTRAINT fk_report_class FOREIGN KEY (class_id) REFERENCES classes(id), CONSTRAINT fk_report_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
    CONSTRAINT fk_report_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id), CONSTRAINT fk_report_admin FOREIGN KEY (created_by) REFERENCES admin_users(id)
) ENGINE=InnoDB;
CREATE TABLE report_students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, report_id BIGINT UNSIGNED NOT NULL, student_id BIGINT UNSIGNED NOT NULL,
    student_number_snapshot VARCHAR(40) NOT NULL, student_name_snapshot VARCHAR(190) NOT NULL, sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_report_student (report_id, student_id), CONSTRAINT fk_rs_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_rs_student FOREIGN KEY (student_id) REFERENCES students(id)
) ENGINE=InnoDB;
CREATE TABLE report_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, report_student_id BIGINT UNSIGNED NOT NULL, column_id BIGINT UNSIGNED NOT NULL,
    numeric_value DECIMAL(12,4) NULL, text_value TEXT NULL, date_value DATE NULL, calculated_value DECIMAL(12,4) NULL,
    updated_by BIGINT UNSIGNED NOT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_report_value (report_student_id, column_id), CONSTRAINT fk_value_student FOREIGN KEY (report_student_id) REFERENCES report_students(id) ON DELETE CASCADE,
    CONSTRAINT fk_value_column FOREIGN KEY (column_id) REFERENCES table_columns(id), CONSTRAINT fk_value_admin FOREIGN KEY (updated_by) REFERENCES admin_users(id)
) ENGINE=InnoDB;