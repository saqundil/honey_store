CREATE TRIGGER trg_scheme_version_no_update
BEFORE UPDATE ON assessment_scheme_versions
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment scheme versions are immutable';

CREATE TRIGGER trg_scheme_version_no_delete
BEFORE DELETE ON assessment_scheme_versions
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment scheme versions are immutable';

CREATE TRIGGER trg_assessment_no_update
BEFORE UPDATE ON assessments
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessments are immutable';

CREATE TRIGGER trg_assessment_no_delete
BEFORE DELETE ON assessments
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessments are immutable';

CREATE TRIGGER trg_assessment_template_no_update
BEFORE UPDATE ON assessment_templates
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessment templates are immutable';

CREATE TRIGGER trg_assessment_template_no_delete
BEFORE DELETE ON assessment_templates
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Published assessment templates are immutable';

CREATE TRIGGER trg_grade_audit_no_update
BEFORE UPDATE ON grade_value_audits
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Grade audit history is immutable';

CREATE TRIGGER trg_grade_audit_no_delete
BEFORE DELETE ON grade_value_audits
FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Grade audit history is immutable';
