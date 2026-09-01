<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Services\FormulaEngine;
use App\Services\HeaderLayoutCalculator;
use App\Services\TableRenderer;

$columns = [
    ['column_key' => 'name', 'name' => 'الطالب', 'header_label' => '', 'type' => 'student_name', 'sort_order' => 1, 'is_visible' => 1, 'header_group_id' => null],
    ['column_key' => 'a', 'name' => 'أ', 'header_label' => '', 'type' => 'manual_mark', 'max_mark' => '4.00', 'sort_order' => 2, 'is_visible' => 1, 'header_group_id' => 10],
    ['column_key' => 'b', 'name' => 'ب', 'header_label' => '', 'type' => 'manual_mark', 'max_mark' => '2.50', 'sort_order' => 3, 'is_visible' => 1, 'header_group_id' => 10],
    ['column_key' => 'total', 'name' => 'المجموع', 'header_label' => '', 'type' => 'calculated_total', 'max_mark' => '6.25', 'sort_order' => 4, 'is_visible' => 1, 'header_group_id' => 10, 'formula' => ['type' => 'SUM', 'sources' => ['a', 'b']]],
];
$groups = [['id' => 10, 'parent_id' => null, 'name' => 'الشهر', 'sort_order' => 1]];
$layout = (new HeaderLayoutCalculator())->calculate($groups, $columns);
assert($layout['rows'][0][0]['label'] === 'الطالب');
assert($layout['rows'][0][1]['colspan'] === 3);
$values = (new FormulaEngine())->calculate($columns, ['a' => 1.25, 'b' => 2.5]);
assert($values['total'] === 3.75);

$nestedGroups = [
    ['id' => 1, 'parent_id' => null, 'name' => 'الفصل', 'sort_order' => 1],
    ['id' => 2, 'parent_id' => 1, 'name' => 'الشهر', 'sort_order' => 1],
];
$nestedColumns = [['column_key' => 'mark', 'name' => 'علامة', 'header_label' => '', 'type' => 'manual_mark', 'sort_order' => 1, 'is_visible' => 1, 'header_group_id' => 2]];
$nestedLayout = (new HeaderLayoutCalculator())->calculate($nestedGroups, $nestedColumns);
assert($nestedLayout['depth'] === 3);
assert($nestedLayout['rows'][0][0]['colspan'] === 1);

$repeatedColumns = array_map(
    static fn(int $index): array => [
        'column_key' => 'behavior_' . $index, 'name' => 'الالتزام بقوانين المعلم', 'header_label' => '',
        'type' => 'manual_mark', 'max_mark' => '1.00', 'sort_order' => $index,
        'is_visible' => 1, 'header_group_id' => null, 'display_direction' => 'horizontal',
    ],
    [1, 2, 3]
);
$repeatedLayout = (new HeaderLayoutCalculator())->calculate([], $repeatedColumns);
assert(count($repeatedLayout['columns']) === 3);
assert(count($repeatedLayout['rows'][0]) === 1);
assert($repeatedLayout['rows'][0][0]['colspan'] === 3);

$cycleDetected = false;
try {
    (new FormulaEngine())->validate([
        ['column_key' => 'first', 'formula' => ['sources' => ['second']]],
        ['column_key' => 'second', 'formula' => ['sources' => ['first']]],
    ]);
} catch (InvalidArgumentException) {
    $cycleDetected = true;
}
assert($cycleDetected);

$rendered = (new TableRenderer())->render(
    ['groups' => $groups, 'columns' => $columns],
    [['id' => 7, 'student_number' => 'S-7', 'name' => 'طالب اختبار']],
    [7 => ['a' => 1, 'b' => 2, 'total' => 3]],
    true,
    32
);
assert(str_contains($rendered, 'colspan="3"'));
assert(str_contains($rendered, 'data-column-key="a"'));
assert(str_contains($rendered, 'طالب اختبار'));
assert(substr_count($rendered, 'class="mark-input"') === 2);
assert(!str_contains($rendered, 'mm">'));
assert(substr_count($rendered, '<col style="width:') === count($columns));
assert(str_contains($rendered, '<small class="header-mark" dir="ltr">(4)</small>'));
assert(str_contains($rendered, '<small class="header-mark" dir="ltr">(2.5)</small>'));
assert(str_contains($rendered, '<small class="header-mark" dir="ltr">(6.25)</small>'));
assert(!str_contains($rendered, '>(4.00)</small>'));
assert(str_contains($rendered, 'class="column total-column"'));
assert(str_contains($rendered, '<td class="total-column" data-column-key="total">'));
assert(substr_count($rendered, '<tr data-student-id=') === 1);
assert(substr_count($rendered, '<tr class="empty-report-row"') === 31);

$numberedRows = (new TableRenderer())->render(
    ['groups' => [], 'columns' => [[
        'column_key' => 'number', 'name' => 'الرقم', 'header_label' => '', 'type' => 'student_number',
        'max_mark' => null, 'sort_order' => 1, 'is_visible' => 1, 'header_group_id' => null,
    ]]],
    [],
    [],
    false,
    32
);
assert(substr_count($numberedRows, '<tr class="empty-report-row"') === 32);
assert(str_contains($numberedRows, '<span class="cell-value">32</span>'));

$studentRows = (new TableRenderer())->render(
    ['groups' => [], 'columns' => [[
        'column_key' => 'number', 'name' => 'الرقم', 'header_label' => '', 'type' => 'student_number',
        'max_mark' => null, 'sort_order' => 1, 'is_visible' => 1, 'header_group_id' => null,
    ]]],
    [['id' => 9, 'student_number' => '6A-005', 'name' => 'طالب اختبار']]
);
assert(str_contains($studentRows, '<span class="cell-value">1</span>'));
assert(!str_contains($studentRows, '6A-005'));

echo "Service tests passed.\n";