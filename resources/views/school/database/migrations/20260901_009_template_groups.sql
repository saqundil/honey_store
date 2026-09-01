CREATE TABLE template_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_template_group_owner_name (created_by, name),
    UNIQUE KEY uq_template_group_id_owner (id, created_by),
    CONSTRAINT fk_template_group_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE table_templates
    ADD COLUMN group_id BIGINT UNSIGNED NULL AFTER id;

INSERT INTO template_groups(name, created_by)
    SELECT 'قوالب عامة', created_by FROM table_templates GROUP BY created_by;

UPDATE table_templates t
    JOIN template_groups g ON g.created_by=t.created_by AND g.name='قوالب عامة'
    SET t.group_id=g.id;

ALTER TABLE table_templates
    MODIFY group_id BIGINT UNSIGNED NOT NULL,
    ADD INDEX idx_template_group_status (group_id, status),
    ADD CONSTRAINT fk_template_group_owner FOREIGN KEY (group_id, created_by) REFERENCES template_groups(id, created_by) ON DELETE RESTRICT;