<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GradebookRepository;
use App\Repositories\TemplateRepository;
use InvalidArgumentException;
use PDO;
use Throwable;

final class GradebookService
{
    public function __construct(private readonly PDO $db, private readonly GradebookRepository $repository, private readonly bool $canManageAll = false) {}

    public function assignScheme(int $classId, int $schemeVersionId, int $actorId): int
    {
        $statement = $this->db->prepare('SELECT c.id class_id,c.academic_term_id,sv.id version_id,sv.subject_id FROM classes c JOIN assessment_scheme_versions sv ON sv.teacher_id=c.teacher_id AND sv.academic_term_id=c.academic_term_id WHERE c.id=? AND sv.id=? AND c.teacher_id=?');
        $statement->execute([$classId, $schemeVersionId, $actorId]);
        $context = $statement->fetch();
        if (!$context) throw new InvalidArgumentException('الصف وإصدار المخطط لا ينتميان إلى السياق نفسه.');

        $started = !$this->db->inTransaction();
        $savepoint = $this->beginScope($started);
        try {
            $statement = $this->db->prepare('SELECT id FROM class_scheme_assignments WHERE class_id=? AND assessment_scheme_version_id=?');
            $statement->execute([$classId, $schemeVersionId]);
            $assignmentId = (int) $statement->fetchColumn();
            if (!$assignmentId) {
                $statement = $this->db->prepare("INSERT INTO class_scheme_assignments(teacher_id,academic_term_id,class_id,subject_id,assessment_scheme_version_id,status,assigned_by) VALUES(?,?,?,?,?,'active',?)");
                $statement->execute([$actorId, (int) $context['academic_term_id'], $classId, (int) $context['subject_id'], $schemeVersionId, $actorId]);
                $assignmentId = (int) $this->db->lastInsertId();
            }
            $statement = $this->db->prepare("UPDATE class_scheme_assignments SET status='inactive' WHERE class_id=? AND subject_id=? AND id<>? AND status='active'");
            $statement->execute([$classId, (int) $context['subject_id'], $assignmentId]);
            $this->db->prepare("UPDATE class_scheme_assignments SET status='active',assigned_by=? WHERE id=?")->execute([$actorId, $assignmentId]);
            $statement = $this->db->prepare("INSERT IGNORE INTO class_assessments(class_scheme_assignment_id,class_id,assessment_scheme_version_id,assessment_id,status) SELECT ?,?,?,id,'draft' FROM assessments WHERE assessment_scheme_version_id=? AND is_active=1");
            $statement->execute([$assignmentId, $classId, $schemeVersionId, $schemeVersionId]);
            $this->commitScope($started, $savepoint);
            return $assignmentId;
        } catch (Throwable $error) {
            $this->rollbackScope($started, $savepoint);
            throw $error;
        }
    }

    public function changeStatus(int $classAssessmentId, string $action, int $actorId): string
    {
        $context = $this->requireContext($classAssessmentId, $actorId);
        $transitions = [
            'open' => ['from' => ['draft'], 'to' => 'open'],
            'lock' => ['from' => ['draft', 'open'], 'to' => 'locked'],
            'reopen' => ['from' => ['locked'], 'to' => 'open'],
        ];
        $transition = $transitions[$action] ?? null;
        if (!$transition) throw new InvalidArgumentException('انتقال حالة الاختبار غير صالح.');
        $fields = match ($action) {
            'lock' => "status='locked',locked_at=CURRENT_TIMESTAMP,locked_by=?",
            'reopen' => "status='open',locked_at=NULL,locked_by=NULL,opened_at=COALESCE(opened_at,CURRENT_TIMESTAMP),opened_by=COALESCE(opened_by,?)",
            default => "status='open',opened_at=CURRENT_TIMESTAMP,opened_by=?",
        };
        $started = !$this->db->inTransaction();
        $savepoint = $this->beginScope($started);
        try {
            $currentStatus = $this->lockStatus($classAssessmentId);
            if (!in_array($currentStatus, $transition['from'], true)) throw new InvalidArgumentException('انتقال حالة الاختبار غير صالح.');
            $this->db->prepare("UPDATE class_assessments SET {$fields} WHERE id=?")->execute([$actorId, $classAssessmentId]);
            $this->audit($classAssessmentId, null, $action, ['status' => $currentStatus], ['status' => $transition['to']], $actorId);
            $this->commitScope($started, $savepoint);
            return $transition['to'];
        } catch (Throwable $error) {
            $this->rollbackScope($started, $savepoint);
            throw $error;
        }
    }

    /**
     * Create a new exam (assessment + template link + class_assessment instance) inline from
     * the teacher's "class exams" screen. Auto-bumps sort_order under the class's active
     * scheme_version so it doesn't collide with existing definitions.
     *
     * Returns the new class_assessment_id.
     */
    /**
     * يضمن أن الصف مرتبط بمخطط تقييم قبل إنشاء أول اختبار.
     *
     * المعلم لا يعرف أن «مخطط التقييم» موجود أصلًا؛ التعقيد يُنقل إلى النظام:
     *   مرتبط سلفًا            → يُعاد استخدامه (لا تُنشأ نسخة جديدة أبدًا)
     *   مخطط صالح واحد        → يُربط تلقائيًا
     *   أكثر من مخطط صالح     → تُعاد الخيارات بأسمائها ليختار المستخدم
     *   لا مخطط               → يُنشأ مخطط فارغ ويُربط، ثم يملؤه createExam
     *
     * الإصدارية محفوظة: كل ما يُنشأ هنا إصدار منشور جديد، ولا يُعدَّل إصدار قائم.
     *
     * @return array{status:string,version_id?:int,options?:array<int,array{id:int,label:string}>}
     */
    public function ensureSchemeForClass(int $classId, int $actorId, ?int $chosenVersionId = null): array
    {
        $statement = $this->db->prepare('SELECT id, teacher_id, academic_term_id, name FROM classes WHERE id=?');
        $statement->execute([$classId]);
        $class = $statement->fetch();
        if (!$class) {
            throw new InvalidArgumentException('الصف غير متاح.');
        }

        // الملكية: المخطط والفصل الدراسي مربوطان بمالك الصف في قيود قاعدة البيانات،
        // فأي ربط يجب أن يجري باسم المالك لا باسم فاعل آخر.
        $ownerId = (int) $class['teacher_id'];
        if ($ownerId !== $actorId && !$this->canManageAll) {
            throw new InvalidArgumentException('الصف غير متاح.');
        }

        $card = $this->repository->classCard($classId);
        if ($card && $card['active_scheme_version_id']) {
            return ['status' => 'ready', 'version_id' => (int) $card['active_scheme_version_id']];
        }

        $termId = (int) $class['academic_term_id'];

        if ($chosenVersionId) {
            if (!$this->schemeVersionFits($chosenVersionId, $ownerId, $termId)) {
                throw new InvalidArgumentException('نظام التقييم المختار غير متاح لهذا الصف.');
            }
            $this->assignScheme($classId, $chosenVersionId, $ownerId);
            return ['status' => 'ready', 'version_id' => $chosenVersionId];
        }

        $candidates = $this->currentSchemeVersions($ownerId, $termId);

        if (count($candidates) === 1) {
            $versionId = (int) $candidates[0]['id'];
            $this->assignScheme($classId, $versionId, $ownerId);
            return ['status' => 'ready', 'version_id' => $versionId];
        }

        if (count($candidates) > 1) {
            return ['status' => 'choose', 'options' => array_map(
                static fn(array $row): array => ['id' => (int) $row['id'], 'label' => (string) $row['label']],
                $candidates
            )];
        }

        $versionId = $this->createEmptyScheme($ownerId, $termId, $actorId);
        $this->assignScheme($classId, $versionId, $ownerId);
        return ['status' => 'ready', 'version_id' => $versionId];
    }

    /** الإصدارات الحالية فقط — لا تُعرض الإصدارات القديمة كخيارات. */
    private function currentSchemeVersions(int $ownerId, int $termId): array
    {
        $statement = $this->db->prepare(
            'SELECT sv.id, CONCAT(s.name, " · ", sub.name) AS label
               FROM assessment_scheme_versions sv
               JOIN assessment_schemes s ON s.id = sv.assessment_scheme_id AND s.current_version_id = sv.id
               JOIN subjects sub ON sub.id = sv.subject_id
              WHERE sv.teacher_id = ? AND sv.academic_term_id = ?
                AND sv.status = "published" AND s.status = "active"
              ORDER BY s.name'
        );
        $statement->execute([$ownerId, $termId]);
        return $statement->fetchAll();
    }

    private function schemeVersionFits(int $versionId, int $ownerId, int $termId): bool
    {
        $statement = $this->db->prepare(
            'SELECT 1 FROM assessment_scheme_versions
              WHERE id=? AND teacher_id=? AND academic_term_id=? AND status="published"'
        );
        $statement->execute([$versionId, $ownerId, $termId]);
        return (bool) $statement->fetchColumn();
    }

    /**
     * مخطط فارغ: إصدار منشور بلا اختبارات، يملؤه createExam اختبارًا اختبارًا.
     * يُعاد استخدام المخطط إن كان له الاسم نفسه بدل إنشاء نسخة مكررة.
     */
    private function createEmptyScheme(int $ownerId, int $termId, int $actorId): int
    {
        $subjectId = $this->defaultSubjectId();

        $statement = $this->db->prepare('SELECT name FROM subjects WHERE id=?');
        $statement->execute([$subjectId]);
        $name = 'نظام تقييم ' . (string) $statement->fetchColumn();

        $started = !$this->db->inTransaction();
        $savepoint = $this->beginScope($started);
        try {
            $this->db->prepare(
                'INSERT INTO assessment_schemes(teacher_id,academic_term_id,subject_id,name)
                 VALUES(?,?,?,?)
                 ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), status="active"'
            )->execute([$ownerId, $termId, $subjectId, $name]);
            $schemeId = (int) $this->db->lastInsertId();

            $statement = $this->db->prepare(
                'SELECT COALESCE(MAX(version_number),0)+1 FROM assessment_scheme_versions WHERE assessment_scheme_id=?'
            );
            $statement->execute([$schemeId]);
            $versionNumber = (int) $statement->fetchColumn();

            $this->db->prepare(
                'INSERT INTO assessment_scheme_versions(assessment_scheme_id,teacher_id,academic_term_id,subject_id,version_number,status,created_by,published_at)
                 VALUES(?,?,?,?,?,"published",?,CURRENT_TIMESTAMP)'
            )->execute([$schemeId, $ownerId, $termId, $subjectId, $versionNumber, $actorId]);
            $versionId = (int) $this->db->lastInsertId();

            $this->db->prepare('UPDATE assessment_schemes SET current_version_id=? WHERE id=?')
                ->execute([$versionId, $schemeId]);

            $this->commitScope($started, $savepoint);
            return $versionId;
        } catch (Throwable $error) {
            $this->rollbackScope($started, $savepoint);
            throw $error;
        }
    }

    /** جدول المواد عام ومشترك؛ نستعمل الوحيدة إن وُجدت، وإلا نزرع مادة افتراضية. */
    private function defaultSubjectId(): int
    {
        $rows = $this->db->query('SELECT id FROM subjects WHERE status="active" ORDER BY id')->fetchAll();
        if (count($rows) === 1) {
            return (int) $rows[0]['id'];
        }
        if ($rows) {
            return (int) $rows[0]['id'];
        }
        $this->db->prepare(
            'INSERT INTO subjects(name) VALUES(?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), status="active"'
        )->execute(['المادة الأساسية']);
        return (int) $this->db->lastInsertId();
    }

    public function createExam(int $classId, string $name, ?string $examDate, int $templateVersionId, int $actorId, array $sections = []): int
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 190) {
            throw new InvalidArgumentException('عنوان الاختبار مطلوب.');
        }
        if ($examDate !== null && $examDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDate)) {
            throw new InvalidArgumentException('صيغة التاريخ غير صالحة.');
        }
        $class = $this->repository->classCard($classId);
        if (!$class || (!$this->canManageAll && !$this->classOwnedByActor($classId, $actorId))) {
            throw new InvalidArgumentException('الصف غير متاح.');
        }
        if (!$class['active_assignment_id'] || !$class['active_scheme_version_id']) {
            throw new InvalidArgumentException('الصف يحتاج إعدادًا أوليًا قبل إنشاء اختبار.');
        }
        // القسم = صف في assessment_templates (label + إصدار جدول).
        // بلا أقسام صريحة نبقى على السلوك القديم: قسم واحد بالجدول المُمرَّر.
        if (!$sections) {
            $sections = [['template_version_id' => $templateVersionId, 'label' => 'الجدول الرئيسي']];
        }

        $seen = [];
        foreach ($sections as $index => $section) {
            $versionId = (int) ($section['template_version_id'] ?? 0);
            $label = trim((string) ($section['label'] ?? ''));
            if ($versionId < 1) throw new InvalidArgumentException('كل قسم يحتاج جدول علامات.');
            if ($label === '') throw new InvalidArgumentException('كل قسم يحتاج اسمًا.');
            if (isset($seen[$versionId])) {
                throw new InvalidArgumentException("لا يمكن استخدام الجدول نفسه في قسمين. غيّر «{$label}».");
            }
            $seen[$versionId] = true;

            $statement = $this->db->prepare('SELECT 1 FROM table_template_versions WHERE id=? AND created_by=?');
            $statement->execute([$versionId, $actorId]);
            if (!$statement->fetchColumn() && !$this->canManageAll) {
                throw new InvalidArgumentException("القسم «{$label}» غير متاح.");
            }
            $sections[$index] = ['template_version_id' => $versionId, 'label' => $label];
        }

        $started = !$this->db->inTransaction();
        $savepoint = $this->beginScope($started);
        try {
            // Duplicate name guard within the version — assessments has UNIQUE(version_id, name)
            $statement = $this->db->prepare('SELECT 1 FROM assessments WHERE assessment_scheme_version_id=? AND name=?');
            $statement->execute([(int) $class['active_scheme_version_id'], $name]);
            if ($statement->fetchColumn()) {
                throw new InvalidArgumentException('يوجد اختبار بهذا العنوان في الصف. اختر عنوانًا مختلفًا.');
            }
            $nextSort = (int) $this->db->query(
                'SELECT COALESCE(MAX(sort_order),0)+1 FROM assessments WHERE assessment_scheme_version_id=' .
                (int) $class['active_scheme_version_id']
            )->fetchColumn();

            $this->db->prepare('INSERT INTO assessments(assessment_scheme_version_id,teacher_id,academic_term_id,name,short_name,sort_order,maximum_mark,weight,is_required,is_active) VALUES(?,?,?,?,?,?,?,?,?,?)')
                ->execute([(int) $class['active_scheme_version_id'], $actorId, (int) $class['academic_term_id'], $name, null, $nextSort, null, 1.0, 1, 1]);
            $assessmentId = (int) $this->db->lastInsertId();

            $insertSection = $this->db->prepare('INSERT INTO assessment_templates(assessment_id,assessment_scheme_version_id,teacher_id,template_version_id,label,sort_order,is_required,is_active,config_json) VALUES(?,?,?,?,?,?,?,?,?)');
            foreach (array_values($sections) as $order => $section) {
                $insertSection->execute([$assessmentId, (int) $class['active_scheme_version_id'], $actorId, $section['template_version_id'], $section['label'], $order, 1, 1, '{}']);
            }

            $this->db->prepare("INSERT INTO class_assessments(class_scheme_assignment_id,class_id,assessment_scheme_version_id,assessment_id,status,exam_date) VALUES(?,?,?,?, 'draft', ?)")
                ->execute([(int) $class['active_assignment_id'], $classId, (int) $class['active_scheme_version_id'], $assessmentId, $examDate ?: null]);
            $classAssessmentId = (int) $this->db->lastInsertId();

            $this->commitScope($started, $savepoint);
            return $classAssessmentId;
        } catch (Throwable $error) {
            $this->rollbackScope($started, $savepoint);
            throw $error;
        }
    }

    private function classOwnedByActor(int $classId, int $actorId): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM classes WHERE id=? AND teacher_id=?');
        $statement->execute([$classId, $actorId]);
        return (bool) $statement->fetchColumn();
    }

    public function saveValues(int $classAssessmentId, int $assessmentTemplateId, array $rows, int $actorId): array
    {
        $context = $this->requireContext($classAssessmentId, $actorId);
        $statement = $this->db->prepare('SELECT * FROM assessment_templates WHERE id=? AND assessment_id=? AND is_active=1');
        $statement->execute([$assessmentTemplateId, (int) $context['assessment_id']]);
        $assignment = $statement->fetch();
        if (!$assignment) throw new InvalidArgumentException('قالب الاختبار غير متاح.');

        $template = (new TemplateRepository($this->db, $actorId, $this->canManageAll))->configuration((int) $assignment['template_version_id']);
        if (!$template) throw new InvalidArgumentException('إصدار القالب غير متاح.');
        $columns = array_column($template['columns'], null, 'column_key');
        $enrollments = array_column($this->repository->enrollments($classAssessmentId), null, 'id');
        $engine = new FormulaEngine();
        $saved = [];

        $started = !$this->db->inTransaction();
        $savepoint = $this->beginScope($started);
        try {
            $currentStatus = $this->lockStatus($classAssessmentId);
            if ($currentStatus === 'locked') throw new InvalidArgumentException('الاختبار مقفل ولا يقبل تعديلات.');
            $find = $this->db->prepare('SELECT * FROM grade_values WHERE class_assessment_id=? AND assessment_template_id=? AND enrollment_id=? AND column_id=? FOR UPDATE');
            $insert = $this->db->prepare('INSERT INTO grade_values(class_assessment_id,assessment_id,class_id,assessment_template_id,template_version_id,enrollment_id,column_id,numeric_value,text_value,date_value,calculated_value,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
            $update = $this->db->prepare('UPDATE grade_values SET numeric_value=?,text_value=?,date_value=?,calculated_value=?,updated_by=?,revision=revision+1 WHERE id=?');
            foreach ($rows as $row) {
                $enrollmentId = (int) ($row['enrollment_id'] ?? 0);
                if (!isset($enrollments[$enrollmentId])) throw new InvalidArgumentException('الطالب غير مسجل في صف الاختبار.');
                $expectedRevisions = is_array($row['revisions'] ?? null) ? $row['revisions'] : [];
                $values = $this->validateValues($columns, is_array($row['values'] ?? null) ? $row['values'] : []);
                $values = $engine->calculate($template['columns'], $values);
                foreach ($columns as $key => $column) {
                    if (in_array($column['type'], ['student_number', 'student_name'], true) || !array_key_exists($key, $values)) continue;
                    $parts = $this->valueParts($column, $values[$key]);
                    $find->execute([$classAssessmentId, $assessmentTemplateId, $enrollmentId, (int) $column['id']]);
                    $old = $find->fetch();
                    $expectedRevision = (int) ($expectedRevisions[$key] ?? 0);
                    $actualRevision = $old ? (int) $old['revision'] : 0;
                    if ($expectedRevision !== $actualRevision) throw new \DomainException('تعارض حفظ: تغيرت علامة الطالب في جلسة أخرى. أعد تحميل الصفحة قبل المتابعة.');
                    $oldParts = $old ? array_intersect_key($old, array_flip(['numeric_value', 'text_value', 'date_value', 'calculated_value'])) : null;
                    if ($old && $this->sameValue($oldParts, $parts)) {
                        $saved[] = ['enrollment_id' => $enrollmentId, 'column_key' => $key, 'id' => (int) $old['id'], 'revision' => (int) $old['revision']];
                        continue;
                    }
                    if ($old) {
                        $update->execute([...array_values($parts), $actorId, (int) $old['id']]);
                        $gradeValueId = (int) $old['id'];
                        $revision = (int) $old['revision'] + 1;
                    } else {
                        $insert->execute([$classAssessmentId, (int) $context['assessment_id'], (int) $context['class_id'], $assessmentTemplateId, (int) $assignment['template_version_id'], $enrollmentId, (int) $column['id'], ...array_values($parts), $actorId]);
                        $gradeValueId = (int) $this->db->lastInsertId();
                        $revision = 1;
                    }
                    $this->audit($classAssessmentId, $gradeValueId, $old ? 'update' : 'create', $oldParts, $parts, $actorId);
                    $saved[] = ['enrollment_id' => $enrollmentId, 'column_key' => $key, 'id' => $gradeValueId, 'revision' => $revision];
                }
            }
            if ($currentStatus === 'draft') {
                $this->db->prepare("UPDATE class_assessments SET status='open',opened_at=CURRENT_TIMESTAMP,opened_by=? WHERE id=?")->execute([$actorId, $classAssessmentId]);
                $this->audit($classAssessmentId, null, 'open', ['status' => 'draft'], ['status' => 'open'], $actorId);
            }
            $this->commitScope($started, $savepoint);
            return $saved;
        } catch (Throwable $error) {
            $this->rollbackScope($started, $savepoint);
            throw $error;
        }
    }

    private function validateValues(array $columns, array $input): array
    {
        $values = [];
        foreach ($input as $key => $value) {
            $column = $columns[$key] ?? null;
            if (!$column) throw new InvalidArgumentException('عمود العلامة غير معروف.');
            if ($column['is_calculated']) continue;
            if ($column['type'] === 'manual_mark') {
                if ($value === '' || $value === null) {$values[$key] = null; continue;}
                if (!is_numeric($value)) throw new InvalidArgumentException('العلامة المدخلة غير رقمية.');
                $number = (float) $value;
                $step = max(0.0001, (float) ($column['step_value'] ?: 0.25));
                if ($number < 0 || $number > (float) $column['max_mark'] || abs(($number / $step) - round($number / $step)) > 0.00001) throw new InvalidArgumentException('علامة خارج المجال أو لا تطابق خطوة الإدخال في ' . $column['name']);
                $values[$key] = $number;
            } elseif ($column['type'] === 'text') {
                $text = trim((string) $value);
                if (mb_strlen($text) > 2000) throw new InvalidArgumentException('النص أطول من الحد المسموح.');
                $values[$key] = $text;
            } elseif ($column['type'] === 'date') {
                $date = trim((string) $value);
                if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new InvalidArgumentException('صيغة التاريخ غير صالحة.');
                $values[$key] = $date;
            }
        }
        return $values;
    }

    private function valueParts(array $column, mixed $value): array
    {
        return [
            'numeric_value' => $column['type'] === 'manual_mark' && $value !== null ? $value : null,
            'text_value' => $column['type'] === 'text' ? $value : null,
            'date_value' => $column['type'] === 'date' && $value !== '' ? $value : null,
            'calculated_value' => $column['is_calculated'] && $value !== null ? $value : null,
        ];
    }

    private function sameValue(?array $old, array $new): bool
    {
        if ($old === null) return false;
        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;
            if ($oldValue === null && $value === null) continue;
            if (in_array($key, ['numeric_value', 'calculated_value'], true) && is_numeric($oldValue) && is_numeric($value)) {
                if (abs((float) $oldValue - (float) $value) > 0.000001) return false;
                continue;
            }
            if ((string) $oldValue !== (string) $value) return false;
        }
        return true;
    }

    private function requireContext(int $classAssessmentId, int $actorId): array
    {
        $context = $this->repository->context($classAssessmentId);
        if (!$context || (!$this->canManageAll && (int) $context['teacher_id'] !== $actorId)) throw new InvalidArgumentException('اختبار الصف غير متاح.');
        return $context;
    }

    private function lockStatus(int $classAssessmentId): string
    {
        $statement = $this->db->prepare('SELECT status FROM class_assessments WHERE id=? FOR UPDATE');
        $statement->execute([$classAssessmentId]);
        $status = $statement->fetchColumn();
        if (!is_string($status)) throw new InvalidArgumentException('اختبار الصف غير متاح.');
        return $status;
    }

    private function beginScope(bool $started): string
    {
        if ($started) {
            $this->db->beginTransaction();
            return '';
        }
        $savepoint = 'gradebook_' . bin2hex(random_bytes(6));
        $this->db->exec("SAVEPOINT {$savepoint}");
        return $savepoint;
    }

    private function commitScope(bool $started, string $savepoint): void
    {
        if ($started) $this->db->commit();
        else $this->db->exec("RELEASE SAVEPOINT {$savepoint}");
    }

    private function rollbackScope(bool $started, string $savepoint): void
    {
        if ($started && $this->db->inTransaction()) $this->db->rollBack();
        elseif (!$started && $this->db->inTransaction()) $this->db->exec("ROLLBACK TO SAVEPOINT {$savepoint}");
    }

    private function audit(int $classAssessmentId, ?int $gradeValueId, string $action, ?array $old, ?array $new, int $actorId): void
    {
        $statement = $this->db->prepare('INSERT INTO grade_value_audits(class_assessment_id,grade_value_id,action,old_value_json,new_value_json,actor_id) VALUES(?,?,?,?,?,?)');
        $statement->execute([$classAssessmentId, $gradeValueId, $action, $old === null ? null : json_encode($old, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $new === null ? null : json_encode($new, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $actorId]);
    }
}
