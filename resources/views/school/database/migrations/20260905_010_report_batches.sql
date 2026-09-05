ALTER TABLE reports
    ADD COLUMN batch_token CHAR(32) NULL AFTER status,
    ADD INDEX idx_report_batch_owner (batch_token, created_by);