ALTER TABLE template_groups
    ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER name,
    ADD INDEX idx_template_group_owner_order (created_by, sort_order, id);

UPDATE template_groups
SET sort_order = id * 10;