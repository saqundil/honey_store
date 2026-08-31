-- 20260830_007_class_assessment_exam_date.sql
-- Adds an optional exam_date to class_assessments so the teacher's
-- inline "new exam" flow can record when the exam was given.
-- Nullable to preserve backwards compatibility with existing rows.

ALTER TABLE class_assessments
    ADD COLUMN exam_date DATE NULL AFTER status;

CREATE INDEX idx_class_assessment_exam_date ON class_assessments (class_id, exam_date);

INSERT INTO schema_migrations(migration) VALUES ('20260830_007_class_assessment_exam_date.sql');
