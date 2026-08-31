<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Services\TableRenderer;

/**
 * معاينة المحرر تُبنى في المتصفح لتبقى فورية، وجدول الطباعة يُبنى في PHP.
 * هذا الاختبار يحرس بقاءهما مخرِجَين للترميز نفسه، وإلا اختلفت المعاينة عن الورقة المطبوعة.
 */
$run = static function (array $command, ?string $input = null): array {
    $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $process = @proc_open($command, $descriptors, $pipes);
    if (!is_resource($process)) return [-1, '', 'تعذر التشغيل'];
    if ($input !== null) fwrite($pipes[0], $input);
    fclose($pipes[0]);
    $output = (string) stream_get_contents($pipes[1]);
    $error = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return [proc_close($process), $output, $error];
};

[$status] = $run(['node', '--version']);
if ($status !== 0) {
    echo "Node غير متاح؛ تم تخطي اختبار تطابق المعاينة مع الطباعة.\n";
    return;
}

$groups = [
    ['id' => 1, 'group_key' => 'term_one', 'name' => 'الشهر الأول', 'parent_id' => null, 'parent_key' => null, 'sort_order' => 1, 'text_direction' => 'rtl', 'display_direction' => 'horizontal'],
    ['id' => 2, 'group_key' => 'term_two', 'name' => 'الشهر الثاني', 'parent_id' => null, 'parent_key' => null, 'sort_order' => 2, 'text_direction' => 'rtl', 'display_direction' => 'vertical'],
];
$columns = [
    ['id' => 1, 'column_key' => 'student_number', 'name' => 'م', 'header_label' => '', 'type' => 'student_number', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 8, 'sort_order' => 1, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => null, 'header_group_key' => null, 'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null],
    ['id' => 2, 'column_key' => 'student_name', 'name' => 'اسم الطالب', 'header_label' => '', 'type' => 'student_name', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 42.3, 'sort_order' => 2, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => null, 'header_group_key' => null, 'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null],
    ['id' => 3, 'column_key' => 'commitment', 'name' => 'الإلتزام بالوقت', 'header_label' => '', 'type' => 'manual_mark', 'max_mark' => '1', 'step_value' => 0.25, 'width_mm' => 7.8, 'sort_order' => 3, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => 1, 'header_group_key' => 'term_one', 'text_direction' => 'rtl', 'display_direction' => 'vertical', 'formula' => null],
    ['id' => 4, 'column_key' => 'language', 'name' => 'سلامة اللغة', 'header_label' => 'سلامة اللغة العربية', 'type' => 'manual_mark', 'max_mark' => '2.5', 'step_value' => 0.25, 'width_mm' => 7.8, 'sort_order' => 4, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => 1, 'header_group_key' => 'term_one', 'text_direction' => 'rtl', 'display_direction' => 'vertical', 'formula' => null],
    ['id' => 5, 'column_key' => 'sum_one', 'name' => 'المجموع', 'header_label' => '', 'type' => 'calculated_total', 'max_mark' => '1.75', 'step_value' => 0.25, 'width_mm' => 7.8, 'sort_order' => 5, 'is_visible' => true, 'is_calculated' => 1, 'header_group_id' => 1, 'header_group_key' => 'term_one', 'text_direction' => 'rtl', 'display_direction' => 'vertical', 'formula' => ['type' => 'SUM', 'sources' => ['commitment', 'language'], 'missing' => 'blank', 'base' => '', 'divisor' => 2, 'decimals' => 2]],
    ['id' => 6, 'column_key' => 'exam', 'name' => 'الاختبار', 'header_label' => '', 'type' => 'manual_mark', 'max_mark' => '10', 'step_value' => 0.25, 'width_mm' => 9, 'sort_order' => 6, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => 2, 'header_group_key' => 'term_two', 'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null],
    ['id' => 7, 'column_key' => 'hidden_note', 'name' => 'ملاحظة مخفية', 'header_label' => '', 'type' => 'text', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 20, 'sort_order' => 7, 'is_visible' => false, 'is_calculated' => 0, 'header_group_id' => 2, 'header_group_key' => 'term_two', 'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null],
];

$builderData = ['template_id' => 0, 'name' => 'قالب المطابقة', 'description' => '', 'settings' => [], 'groups' => $groups, 'columns' => $columns];
$dataFile = sys_get_temp_dir() . '/builder-data-' . bin2hex(random_bytes(6)) . '.json';
file_put_contents($dataFile, json_encode($builderData, JSON_UNESCAPED_UNICODE));

try {
    [$status, $javascript, $error] = $run(['node', __DIR__ . '/fixtures/render-preview.js', $dataFile, dirname(__DIR__) . '/assets/js/table-builder.js']);
    assert($status === 0, "node: {$error}");
} finally {
    @unlink($dataFile);
}

// نفس عينة المعاينة: كل علامة يدوية بقيمة علامتها القصوى، والمحسوب مشتق منها
$visible = array_values(array_filter($columns, static fn(array $column): bool => $column['is_visible']));
$sample = [];
foreach ($visible as $column) {
    if ($column['type'] === 'manual_mark') $sample[$column['column_key']] = (float) ($column['max_mark'] ?: 1);
}
$sample = (new App\Services\FormulaEngine())->calculate($columns, $sample);
$students = [
    ['id' => 1, 'name' => 'أحمد محمد'],
    ['id' => 2, 'name' => 'سارة خالد'],
    ['id' => 3, 'name' => 'ليان عمر'],
];
$php = (new TableRenderer())->render(['groups' => $groups, 'columns' => $columns], $students, [1 => $sample, 2 => $sample, 3 => $sample], false);

$widthAgnostic = static fn(string $html): string => (string) preg_replace('/width:[\d.]+%/', 'width:N%', $html);
assert(str_contains($javascript, '<table class="dynamic-report-table">'));
assert($widthAgnostic($php) === $widthAgnostic($javascript), "المعاينة تختلف عن الطباعة:\nPHP: {$php}\nJS : {$javascript}");

// وأن العناصر التي تحمل المظهر موجودة فعلًا في المخرج المشترك
foreach (['vertical-header', 'total-column', 'header-label', 'header-mark', 'cell-value', 'rowspan="2"', 'colspan="3"'] as $marker) {
    assert(str_contains($php, $marker), $marker);
}
assert(!str_contains($php, 'ملاحظة مخفية')); // العمود المخفي لا يُطبع ولا يُعاين
assert(str_contains($php, '<span class="cell-value">1.75</span>'), 'القاسم يجب أن يُطبَّق في الطباعة');
assert(str_contains($javascript, '<span class="cell-value">1.75</span>'), 'والقاسم نفسه في المعاينة');

// ورقة الطباعة ومعاينتها تشتركان في نفس ملف الأنماط ونفس الترويسة
$sheet = (string) preg_replace('~/\*.*?\*/~s', '', (string) file_get_contents(dirname(__DIR__) . '/assets/css/report-sheet.css'));
foreach (['.report-sheet{', '.dynamic-report-table{', '.vertical-header{', 'writing-mode:vertical-rl'] as $rule) {
    assert(str_contains($sheet, $rule), $rule);
}
assert(!str_contains($sheet, 'body{') && !str_contains($sheet, '@page'), 'report-sheet.css يجب أن يخلو من قواعد الصفحة ليُحمَّل داخل لوحة التحكم');
foreach (['admin/templates/edit.php', 'admin/templates/preview.php', 'reports/print.php', 'gradebook/print.php'] as $page) {
    assert(str_contains((string) file_get_contents(dirname(__DIR__) . '/' . $page), 'report-sheet.css'), $page);
}
foreach (['admin/templates/edit.php', 'admin/templates/preview.php'] as $page) {
    $source = (string) file_get_contents(dirname(__DIR__) . '/' . $page);
    assert(str_contains($source, 'report-sheet report-sheet--dense'), $page);
    assert(str_contains($source, 'includes/report-header.php'), $page);
}

/**
 * حالة القالب الجديد: عمودان بلا أي مجموعة رؤوس.
 * كانت خارج التغطية، وفيها اختلف عمق الرأس بين اللغتين
 * (max(1, ...depths) + 1 في JS مقابل max(1, depth + 1) في PHP)
 * فأنتجت المعاينة rowspan="2" وصفَّ رأس فارغًا لا وجود لهما في الطباعة.
 */
$plainColumns = [
    ['id' => 1, 'column_key' => 'student_number', 'name' => 'الرقم', 'header_label' => '', 'type' => 'student_number', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 10, 'sort_order' => 1, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => null, 'header_group_key' => null, 'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null],
    ['id' => 2, 'column_key' => 'student_name', 'name' => 'اسم الطالب', 'header_label' => '', 'type' => 'student_name', 'max_mark' => '', 'step_value' => 0.25, 'width_mm' => 48, 'sort_order' => 2, 'is_visible' => true, 'is_calculated' => 0, 'header_group_id' => null, 'header_group_key' => null, 'text_direction' => 'rtl', 'display_direction' => 'horizontal', 'formula' => null],
];

$plainFile = sys_get_temp_dir() . '/builder-plain-' . bin2hex(random_bytes(6)) . '.json';
file_put_contents($plainFile, json_encode(
    ['template_id' => 0, 'name' => 'قالب بلا مجموعات', 'description' => '', 'settings' => [], 'groups' => [], 'columns' => $plainColumns],
    JSON_UNESCAPED_UNICODE
));

try {
    [$status, $plainJs, $error] = $run(['node', __DIR__ . '/fixtures/render-preview.js', $plainFile, dirname(__DIR__) . '/assets/js/table-builder.js']);
    assert($status === 0, "node: {$error}");
} finally {
    @unlink($plainFile);
}

$plainPhp = (new TableRenderer())->render(['groups' => [], 'columns' => $plainColumns], $students, [], false);
assert($widthAgnostic($plainPhp) === $widthAgnostic($plainJs), 'قالب بلا مجموعات: المعاينة تختلف عن الطباعة');
assert(!str_contains($plainJs, '<tr></tr>'), 'لا يجوز أن يصدر صف رأس فارغ حين لا توجد مجموعات');
assert(substr_count($plainPhp, '<tr>') === substr_count($plainJs, '<tr>'), 'عدد صفوف الجدول يجب أن يتطابق');

echo "Preview matches the printed sheet.\n";
