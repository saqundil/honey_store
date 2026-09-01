<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TemplateRepository;
use InvalidArgumentException;
use PDO;
use Throwable;

final class TemplateService
{
    private const TYPES = ['student_number','student_name','manual_mark','calculated_total','calculated_average','percentage','text','date','custom_formula'];

    public function __construct(private readonly PDO $db, private readonly TemplateRepository $templates, private readonly FormulaEngine $formulas = new FormulaEngine()) {}

    public function save(array $payload, int $adminId): int
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $columns = $payload['columns'] ?? [];
        if ($name === '' || !is_array($columns) || count($columns) < 2) throw new InvalidArgumentException('اسم القالب وعمودان على الأقل مطلوبان.');
        $keys = [];
        $identityTypes = ['student_number' => 0, 'student_name' => 0];
        foreach ($columns as &$column) {
            $key = (string) ($column['column_key'] ?? '');
            if (!preg_match('/^[a-z][a-z0-9_]{1,99}$/', $key) || isset($keys[$key])) throw new InvalidArgumentException('مفاتيح الأعمدة يجب أن تكون فريدة وبصيغة صحيحة.');
            if (!in_array($column['type'] ?? '', self::TYPES, true)) throw new InvalidArgumentException('نوع عمود غير مدعوم.');
            if (isset($identityTypes[$column['type']])) $identityTypes[$column['type']]++;
            if (in_array($column['type'], ['manual_mark','calculated_total','calculated_average','percentage','custom_formula'], true) && ($column['max_mark'] ?? '') !== '' && (float) $column['max_mark'] <= 0) throw new InvalidArgumentException('العلامة القصوى يجب أن تكون أكبر من صفر.');
            if (in_array($column['type'], ['calculated_total','calculated_average','percentage','custom_formula'], true) && (empty($column['formula']) || empty($column['formula']['sources']))) throw new InvalidArgumentException('كل عمود محسوب يحتاج معادلة وعمود مصدر واحدًا على الأقل.');
            if (!empty($column['formula']) && ($column['formula']['divisor'] ?? '') !== '' && (float) $column['formula']['divisor'] <= 0) throw new InvalidArgumentException('قاسم المعادلة يجب أن يكون أكبر من صفر.');
            $keys[$key] = true;
            $column['formula'] = $column['formula'] ?? null;
        }
        unset($column);
        if ($identityTypes['student_number'] !== 1 || $identityTypes['student_name'] !== 1) throw new InvalidArgumentException('يجب وجود عمود رقم طالب وعمود اسم طالب واحد من كل نوع.');
        $this->formulas->validate($columns);
        // قابل للاستدعاء داخل معاملة قائمة: معالج إنشاء الاختبار يبني
        // أقسامًا عدة ثم الاختبار في معاملة واحدة، فإما تنجح كلها أو لا شيء.
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) $this->db->beginTransaction();
        try {
            $templateId = (int) ($payload['template_id'] ?? 0);
            if ($templateId) {
                $existing = $this->templates->find($templateId);
                if (!$existing) {
                    throw new InvalidArgumentException('القالب غير موجود أو لا تملك صلاحية تعديله.');
                }
                $groupId = $this->resolveGroup($payload, (int) $existing['created_by'], (int) $existing['group_id']);
                $statement = $this->db->prepare('UPDATE table_templates SET group_id=?,name=?,description=? WHERE id=?');
                $statement->execute([$groupId, $name, trim((string) ($payload['description'] ?? '')), $templateId]);
            } else {
                $groupId = $this->resolveGroup($payload, $adminId);
                $statement = $this->db->prepare('INSERT INTO table_templates(group_id,name,description,created_by) VALUES(?,?,?,?)');
                $statement->execute([$groupId, $name, trim((string) ($payload['description'] ?? '')), $adminId]);
                $templateId = (int) $this->db->lastInsertId();
            }
            $statement = $this->db->prepare('SELECT COALESCE(MAX(version_number),0)+1 FROM table_template_versions WHERE template_id=? FOR UPDATE');
            $statement->execute([$templateId]);
            $versionNumber = (int) $statement->fetchColumn();
            $statement = $this->db->prepare('INSERT INTO table_template_versions(template_id,version_number,settings_json,created_by) VALUES(?,?,?,?)');
            $statement->execute([$templateId, $versionNumber, json_encode($payload['settings'] ?? [], JSON_UNESCAPED_UNICODE), $adminId]);
            $versionId = (int) $this->db->lastInsertId();

            $groupIds = [];
            $pending = array_values($payload['groups'] ?? []);
            while ($pending) {
                $progress = false;
                foreach ($pending as $index => $group) {
                    $parentKey = $group['parent_key'] ?? null;
                    if ($parentKey && !isset($groupIds[$parentKey])) continue;
                    $statement = $this->db->prepare('INSERT INTO table_header_groups(template_version_id,parent_id,group_key,name,sort_order,text_direction,display_direction) VALUES(?,?,?,?,?,?,?)');
                    $statement->execute([$versionId, $parentKey ? $groupIds[$parentKey] : null, $group['group_key'], trim($group['name']), (int) ($group['sort_order'] ?? 0), $group['text_direction'] ?? 'rtl', $group['display_direction'] ?? 'horizontal']);
                    $groupIds[$group['group_key']] = (int) $this->db->lastInsertId();
                    unset($pending[$index]); $progress = true;
                }
                if (!$progress) throw new InvalidArgumentException('علاقة مجموعات الرؤوس غير صالحة أو دائرية.');
            }

            $columnIds = [];
            $insertColumn = $this->db->prepare('INSERT INTO table_columns(template_version_id,header_group_id,column_key,name,header_label,type,max_mark,step_value,width_mm,sort_order,is_visible,is_calculated,text_direction,display_direction,config_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($columns as $column) {
                $groupKey = $column['header_group_key'] ?? null;
                $insertColumn->execute([$versionId, $groupKey ? ($groupIds[$groupKey] ?? null) : null, $column['column_key'], trim($column['name']), trim((string) ($column['header_label'] ?? '')), $column['type'], $column['max_mark'] !== '' ? $column['max_mark'] : null, $column['step_value'] ?? 0.25, $column['width_mm'] ?? 15, (int) ($column['sort_order'] ?? 0), !empty($column['is_visible']) ? 1 : 0, !empty($column['formula']) ? 1 : 0, $column['text_direction'] ?? 'rtl', $column['display_direction'] ?? 'horizontal', json_encode($column['config'] ?? [], JSON_UNESCAPED_UNICODE)]);
                $columnIds[$column['column_key']] = (int) $this->db->lastInsertId();
            }
            foreach ($columns as $column) {
                if (empty($column['formula'])) continue;
                $formula = $column['formula'];
                $expression = ($formula['type'] ?? 'SUM') . '(' . implode(',', $formula['sources'] ?? []) . ')';
                $statement = $this->db->prepare('INSERT INTO table_formulas(column_id,formula_type,expression,missing_value_behavior,percentage_base,divisor,decimal_places) VALUES(?,?,?,?,?,?,?)');
                $divisor = ($formula['divisor'] ?? '') !== '' ? (float) $formula['divisor'] : 1;
                $expression = $divisor > 1 || $divisor < 1 ? "({$expression})/{$divisor}" : $expression;
                $statement->execute([$columnIds[$column['column_key']], $formula['type'], $expression, $formula['missing'] ?? 'blank', $formula['base'] ?: null, $divisor, $formula['decimals'] ?? 2]);
                $formulaId = (int) $this->db->lastInsertId();
                $itemStatement = $this->db->prepare('INSERT INTO table_formula_items(formula_id,source_column_id,sort_order) VALUES(?,?,?)');
                foreach ($formula['sources'] ?? [] as $order => $source) $itemStatement->execute([$formulaId, $columnIds[$source], $order]);
            }
            $statement = $this->db->prepare('UPDATE table_templates SET current_version_id=? WHERE id=?');
            $statement->execute([$versionId, $templateId]);
            if ($ownsTransaction) $this->db->commit();
            return $templateId;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) $this->db->rollBack();
            throw $exception;
        }
    }

    private function resolveGroup(array $payload, int $ownerId, int $currentGroupId = 0): int
    {
        $groupId = (int) ($payload['group_id'] ?? 0);
        $groupName = trim((string) ($payload['group_name'] ?? ''));

        if ($groupId > 0 && $groupName === '') {
            $statement = $this->db->prepare('SELECT id FROM template_groups WHERE id=? AND created_by=?');
            $statement->execute([$groupId, $ownerId]);
            if ($statement->fetchColumn()) return $groupId;
            throw new InvalidArgumentException('مجموعة القالب غير موجودة أو لا تملك صلاحية استخدامها.');
        }

        if ($groupName === '') {
            if ($currentGroupId > 0) return $currentGroupId;
            throw new InvalidArgumentException('يجب اختيار مجموعة للقالب أو كتابة اسم مجموعة جديدة.');
        }
        if (mb_strlen($groupName) > 190) throw new InvalidArgumentException('اسم مجموعة القوالب أطول من الحد المسموح.');

        $statement = $this->db->prepare('SELECT id FROM template_groups WHERE created_by=? AND name=?');
        $statement->execute([$ownerId, $groupName]);
        $existingId = $statement->fetchColumn();
        if ($existingId) return (int) $existingId;

        $statement = $this->db->prepare('INSERT INTO template_groups(name,created_by) VALUES(?,?)');
        $statement->execute([$groupName, $ownerId]);
        return (int) $this->db->lastInsertId();
    }
}