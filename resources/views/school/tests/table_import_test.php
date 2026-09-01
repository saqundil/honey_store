<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Services\FormulaEngine;
use App\Services\PdfTableExtractor;
use App\Services\TableImportService;

$import = new TableImportService();
$formulas = new FormulaEngine();

/** Draft must always satisfy the rules TemplateService::save enforces. */
$assertSaveable = static function (array $draft): void {
    assert(count($draft['columns']) >= 2);
    $types = array_count_values(array_column($draft['columns'], 'type'));
    assert(($types['student_number'] ?? 0) === 1);
    assert(($types['student_name'] ?? 0) === 1);
    $keys = [];
    foreach ($draft['columns'] as $column) {
        assert((bool) preg_match('/^[a-z][a-z0-9_]{1,99}$/', $column['column_key']));
        assert(!isset($keys[$column['column_key']]));
        $keys[$column['column_key']] = true;
        if (in_array($column['type'], ['calculated_total', 'calculated_average', 'percentage', 'custom_formula'], true)) {
            assert(!empty($column['formula']['sources']));
        }
        if ($column['max_mark'] !== '') assert((float) $column['max_mark'] > 0);
    }
    $groupKeys = array_column($draft['groups'], 'group_key');
    foreach ($draft['groups'] as $group) {
        assert($group['parent_key'] === null || in_array($group['parent_key'], $groupKeys, true));
    }
    foreach ($draft['columns'] as $column) {
        assert($column['header_group_key'] === null || in_array($column['header_group_key'], $groupKeys, true));
    }
    (new FormulaEngine())->validate($draft['columns']);
};

// ترتيب تلميحات النوع: الأخص قبل الأعم، وإلا التقط «الطالب» عمودَ رقم الطالب و«no» كلمةَ Notes
$guessType = new ReflectionMethod(TableImportService::class, 'guessType');
$guessType->setAccessible(true);
$expected = [
    'رقم الطالب' => 'student_number', 'اسم الطالب' => 'student_name', 'الطالب' => 'student_name',
    'م' => 'student_number', 'No' => 'student_number', 'Notes' => 'text', 'Name' => 'student_name',
    'المجموع الكلي' => 'calculated_total', 'النسبة المئوية' => 'percentage', 'المعدل' => 'calculated_average',
    'تاريخ الاختبار' => 'date', 'اختبار قصير' => 'manual_mark', 'Homework' => 'manual_mark',
];
foreach ($expected as $label => $type) assert($guessType->invoke($import, $label) === $type, "{$label} => {$type}");

$bracketedMarks = $import->fromRows([[
    $import->cell('التسلسل'),
    $import->cell('اسم الطالب'),
    $import->cell('القراءة (10)'),
    $import->cell('المجموع (40)'),
    $import->cell('النسبة % (100)'),
]], 'رؤوس بعلامات');
assert(array_column($bracketedMarks['columns'], 'name') === ['التسلسل', 'اسم الطالب', 'القراءة', 'المجموع', 'النسبة %']);
assert(array_column($bracketedMarks['columns'], 'max_mark') === ['', '', '10', '40', '100']);

// جدول Word نموذجي: رؤوس مدمجة، صف علامات قصوى، ثم صفوف فارغة للتعبئة
$word = <<<'HTML'
<table>
  <tr><th rowspan="2">م</th><th rowspan="2">اسم الطالب</th><th colspan="3">التقويم الأول</th><th rowspan="2">المجموع</th></tr>
  <tr><th>المشاركة</th><th>الواجب</th><th>اختبار قصير</th></tr>
  <tr><td></td><td></td><td>10</td><td>10</td><td>20</td><td>40</td></tr>
  <tr><td>1</td><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
</table>
HTML;

$draft = $import->fromHtml($word, 'قالب التقويم الأول');
$assertSaveable($draft);
assert($draft['name'] === 'قالب التقويم الأول');
assert(count($draft['columns']) === 6);
assert(count($draft['groups']) === 1);
assert($draft['groups'][0]['name'] === 'التقويم الأول');
assert($draft['groups'][0]['parent_key'] === null);

$byKey = array_column($draft['columns'], null, 'column_key');
assert($byKey['student_number']['name'] === 'م');
assert($byKey['student_name']['name'] === 'اسم الطالب');

$marks = array_values(array_filter($draft['columns'], fn(array $column): bool => $column['type'] === 'manual_mark'));
assert(count($marks) === 3);
assert(array_column($marks, 'name') === ['المشاركة', 'الواجب', 'اختبار قصير']);
assert(array_column($marks, 'max_mark') === ['10', '10', '20']);
foreach ($marks as $mark) assert($mark['header_group_key'] === $draft['groups'][0]['group_key']);

$total = array_values(array_filter($draft['columns'], fn(array $column): bool => $column['type'] === 'calculated_total'))[0];
assert($total['name'] === 'المجموع');
assert($total['max_mark'] === '40');
assert($total['formula']['type'] === 'SUM');
assert($total['formula']['sources'] === array_column($marks, 'column_key'));
assert((float) $total['formula']['divisor'] === 1.0);

// المجموع يُحسب فعليًا من المصادر المستنتجة
$values = $formulas->calculate($draft['columns'], array_combine(array_column($marks, 'column_key'), [8, 9, 15]));
assert($values[$total['column_key']] === 32.0);

// وبقاسم 2 يصير الناتج نصف المجموع
$halved = $draft['columns'];
foreach ($halved as $index => $column) {
    if ($column['column_key'] === $total['column_key']) $halved[$index]['formula']['divisor'] = 2;
}
$values = $formulas->calculate($halved, array_combine(array_column($marks, 'column_key'), [8, 9, 15]));
assert($values[$total['column_key']] === 16.0);

// كشف تقييم المحادثة: مجموعتان × ستة أعمدة، والعلامة القصوى مكتوبة في نهاية كل رأس
$leafHeaders = ['الإلتزام بالوقت 1', 'سلامة اللغة 2', 'الثقة بالنفس وطلاقة الحديث 2', 'الالتزام بالموضوع وترتيب الأفكار 2', 'التلوين الصوتي 1', 'المجموع 8/2'];
$leafCells = '';
foreach ([1, 2] as $month) foreach ($leafHeaders as $header) $leafCells .= '<td>' . $header . '</td>';
$conversation = '<table>'
    . '<tr><td rowspan="2">م</td><td rowspan="2">اسم الطالب</td><td colspan="6">الشهر الأول1 1</td><td colspan="6">الشهر الأول2 2</td></tr>'
    . '<tr>' . $leafCells . '</tr>'
    . str_repeat('<tr>' . str_repeat('<td></td>', 14) . '</tr>', 3)
    . '</table>';

$draft = $import->fromHtml($conversation, 'تقييم المحادثة للشهر الثاني');
$assertSaveable($draft);
assert(count($draft['columns']) === 14); // صف المجموعات يغطي 14 عمودًا بأربع خلايا فقط، ويجب أن يُقرأ رأسًا
assert(count($draft['groups']) === 2);
assert(array_column($draft['groups'], 'name') === ['الشهر الأول1 1', 'الشهر الأول2 2']);
$marks = array_values(array_filter($draft['columns'], fn(array $column): bool => $column['type'] === 'manual_mark'));
assert(count($marks) === 10);
assert(array_column(array_slice($marks, 0, 5), 'max_mark') === ['1', '2', '2', '2', '1']);
assert(array_column(array_slice($marks, 0, 5), 'name') === ['الإلتزام بالوقت', 'سلامة اللغة', 'الثقة بالنفس وطلاقة الحديث', 'الالتزام بالموضوع وترتيب الأفكار', 'التلوين الصوتي']);
$totals = array_values(array_filter($draft['columns'], fn(array $column): bool => $column['type'] === 'calculated_total'));
assert(count($totals) === 2);
foreach ($totals as $index => $total) {
    // «8/2»: المكوّنات تجمع 8 ثم تُقسم على 2
    assert($total['max_mark'] === '4');
    assert((float) $total['formula']['divisor'] === 2.0);
    assert(count($total['formula']['sources']) === 5);
    assert($total['header_group_key'] === $draft['groups'][$index]['group_key']);
    foreach ($total['formula']['sources'] as $source) {
        assert(array_column($draft['columns'], null, 'column_key')[$source]['header_group_key'] === $total['header_group_key']);
    }
}

// نفس الكشف كما تضعه Word في الحافظة: رؤوس مدوّرة وعروض أعمدة معلنة
$wordCell = static function (string $text, string $extra = '', bool $vertical = false, float $pt = 28.0): string {
    $flow = $vertical ? 'mso-layout-flow-alt:bottom-to-top' : '';
    return "<td width=" . (int) round($pt * 96 / 72) . " {$extra} valign=top style='width:{$pt}pt;border:solid windowtext 1.0pt;{$flow}'>"
        . "<p class=MsoNormal align=center dir=RTL><span lang=AR-JO>{$text}<o:p></o:p></span></p></td>";
};
$wordLeaves = '';
foreach ([1, 2] as $month) foreach ($leafHeaders as $header) $wordLeaves .= $wordCell($header, '', true, 22.0);
$wordBody = str_repeat(
    '<tr>' . $wordCell('&nbsp;', '', false, 18.0) . $wordCell('&nbsp;', '', false, 120.0) . str_repeat($wordCell('&nbsp;', '', false, 22.0), 12) . '</tr>',
    3
);
$wordHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office'><body dir=RTL>"
    . "<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0 dir=RTL style='border-collapse:collapse'>"
    . '<tr>' . $wordCell('م', 'rowspan=2', false, 18.0) . $wordCell('اسم الطالب', 'rowspan=2', false, 120.0)
    . $wordCell('الشهر الأول1 1', 'colspan=6', false, 132.0) . $wordCell('الشهر الأول2 2', 'colspan=6', false, 132.0) . '</tr>'
    . '<tr>' . $wordLeaves . '</tr>' . $wordBody . '</table></body></html>';

$draft = $import->fromHtml($wordHtml, 'تقييم المحادثة من Word');
$assertSaveable($draft);
assert(count($draft['columns']) === 14);
assert(array_column($draft['groups'], 'display_direction') === ['horizontal', 'horizontal']); // المجموعات أفقية في المصدر
assert(array_column(array_slice($draft['columns'], 0, 2), 'display_direction') === ['horizontal', 'horizontal']);
foreach (array_slice($draft['columns'], 2) as $column) assert($column['display_direction'] === 'vertical');
// العرض يُستخدم كنسبة، فالمهم أن تُنقل النسب لا القياس المطلق
$widths = array_column($draft['columns'], 'width_mm');
assert(round($widths[1] / array_sum($widths) * 100) === 30.0); // عمود الاسم يأخذ ثلث الجدول تقريبًا كما في المصدر
assert(count(array_unique(array_slice($widths, 2))) === 1);
assert($widths[2] < $widths[0] * 1.5 && $widths[2] < $widths[1] / 4);

// جدول بلا أي دوران أو عروض يبقى على الافتراضيات
$plain = '<table><tr><th>الرقم</th><th>اسم الطالب</th><th>الواجب</th></tr></table>';
$draft = $import->fromHtml($plain);
assert(array_column($draft['columns'], 'display_direction') === ['horizontal', 'horizontal', 'horizontal']);
assert(array_column($draft['columns'], 'width_mm') === [10.0, 48.0, 15.0]);

// النص المجرد لنفس الكشف: صفوف رأس غير متساوية، فيُرفض بدل بناء جدول خاطئ بصمت
try {
    $import->fromDelimitedText("م\tاسم الطالب\tالشهر الأول1 1\tالشهر الأول2 2\n\t\tالإلتزام بالوقت 1\tسلامة اللغة 2\tالثقة بالنفس\n وطلاقة الحديث 2\tالتلوين الصوتي 1\n");
    assert(false);
} catch (InvalidArgumentException $exception) {
    assert(str_contains($exception->getMessage(), 'غير متساوية'));
}

// «2/8» بالمقلوب ليست قسمة: القاسم يكون أصغر من المجموع دائمًا
$reversed = '<table><tr><th>م</th><th>اسم الطالب</th><th>الواجب 3</th><th>المشاركة 5</th><th>المجموع 2/8</th></tr></table>';
$draft = $import->fromHtml($reversed);
$assertSaveable($draft);
$reversedTotal = end($draft['columns']);
assert((float) $reversedTotal['formula']['divisor'] === 1.0);
assert($reversedTotal['max_mark'] === '8');

// مجموعات متداخلة: مستويان من الرؤوس فوق الأعمدة
$nested = <<<'HTML'
<table>
  <tr><td rowspan="3">الرقم</td><td rowspan="3">الاسم</td><td colspan="4">الفصل الأول</td></tr>
  <tr><td colspan="2">أعمال السنة</td><td colspan="2">الاختبارات</td></tr>
  <tr><td>الواجبات</td><td>المشاركة</td><td>النصفي</td><td>النهائي</td></tr>
</table>
HTML;

$draft = $import->fromHtml($nested);
$assertSaveable($draft);
assert(count($draft['groups']) === 3);
$groupsByName = array_column($draft['groups'], null, 'name');
assert($groupsByName['الفصل الأول']['parent_key'] === null);
assert($groupsByName['أعمال السنة']['parent_key'] === $groupsByName['الفصل الأول']['group_key']);
assert($groupsByName['الاختبارات']['parent_key'] === $groupsByName['الفصل الأول']['group_key']);
$columnsByName = array_column($draft['columns'], null, 'name');
assert($columnsByName['النصفي']['header_group_key'] === $groupsByName['الاختبارات']['group_key']);

// مصدر بلا عمودَي هوية: يُضافان تلقائيًا مع ملاحظة للمستخدم
$headless = '<table><tr><th>الواجب (5)</th><th>المشاركة من 5</th><th>النسبة</th></tr></table>';
$draft = $import->fromHtml($headless);
$assertSaveable($draft);
assert(count($draft['notes']) === 2);
assert($draft['columns'][0]['type'] === 'student_number');
assert($draft['columns'][1]['type'] === 'student_name');
$columnsByName = array_column($draft['columns'], null, 'name');
assert($columnsByName['الواجب']['max_mark'] === '5');
assert($columnsByName['المشاركة من 5']['max_mark'] === '5');
$percentage = $columnsByName['النسبة'];
assert($percentage['type'] === 'percentage');
assert($percentage['formula']['type'] === 'PERCENTAGE');
assert($percentage['formula']['base'] === '10');

// عمود محسوب بلا مصادر يُخفَّض إلى علامة يدوية بدل أن يكسر الحفظ
$lonely = '<table><tr><th>الرقم</th><th>اسم الطالب</th><th>المجموع</th></tr></table>';
$draft = $import->fromHtml($lonely);
$assertSaveable($draft);
assert(array_column($draft['columns'], 'type') === ['student_number', 'student_name', 'manual_mark']);
assert(count($draft['notes']) === 1);

// صف بيانات طالب لا يُقرأ كصف علامات قصوى
$withData = '<table><tr><th>الرقم</th><th>اسم الطالب</th><th>الواجب</th></tr><tr><td>1</td><td>أحمد</td><td>9</td></tr></table>';
$draft = $import->fromHtml($withData);
$assertSaveable($draft);
assert(array_column($draft['columns'], 'max_mark') === ['', '', '']);
assert($draft['notes'] === []);

// لصق نص من Excel بفواصل جدولة
$draft = $import->fromDelimitedText("الرقم\tاسم الطالب\tالواجب\tالمشاركة\tالمجموع");
$assertSaveable($draft);
assert(count($draft['columns']) === 5);
assert(end($draft['columns'])['formula']['sources'] === ['col_3', 'col_4']);

// محتوى بلا جدول يُرفض برسالة واضحة
try {
    $import->fromHtml('<p>لا يوجد جدول هنا</p>');
    assert(false);
} catch (InvalidArgumentException $exception) {
    assert(str_contains($exception->getMessage(), 'جدول'));
}

// مسار الـPDF: تخطيط pdftotext يعاد تركيبه إلى شبكة، والرأس العريض يصبح خلية ممتدة
$extractor = new PdfTableExtractor();
$layout = <<<'TEXT'
     No   Name                First Term                Total
                        Quiz    Homework    Exam
     1    Ahmad Ali           8         9        18         35
     2    Sami Nour           7        10        19         36
TEXT;

$lines = array_map(static fn(string $line): array => mb_str_split(rtrim($line)), explode("\n", $layout));
$columnSlots = new ReflectionMethod(PdfTableExtractor::class, 'columnSlots');
$columnSlots->setAccessible(true);
$splitLine = new ReflectionMethod(PdfTableExtractor::class, 'splitLine');
$splitLine->setAccessible(true);

$slots = $columnSlots->invoke($extractor, $lines);
assert(count($slots) === 6); // الحدود تُقرأ من صفوف البيانات، لا من الرأس الممتد
$rows = array_map(static fn(array $line): array => $splitLine->invoke($extractor, $line, $slots), $lines);
assert(array_column($rows[0], 'text') === ['No', 'Name', 'First Term', '', 'Total']);
assert(array_column($rows[0], 'colspan') === [1, 1, 2, 1, 1]);
assert(array_column($rows[1], 'text') === ['', '', 'Quiz', 'Homework', 'Exam', '']);
assert(array_column($rows[2], 'text') === ['1', 'Ahmad Ali', '8', '9', '18', '35']);

$draft = $import->fromRows($rows, 'من PDF');
$assertSaveable($draft);
assert(count($draft['groups']) === 1);
assert($draft['groups'][0]['name'] === 'First Term');
$columnsByName = array_column($draft['columns'], null, 'name');
assert($columnsByName['Quiz']['header_group_key'] === $draft['groups'][0]['group_key']);
assert($columnsByName['Total']['formula']['sources'] === ['quiz', 'homework', 'exam']);

// نموذج علامات فارغ: صفوف الطلاب تحمل الرقم والاسم فقط، فلا يجوز اختزال أعمدة العلامات إلى عمودين
$blankArabicLayout = <<<'TEXT'
    النهائية    الكتابي    الإملاء    التعبير    المحادثة    الاستماع            اسم الطالب    الرقم
                                                                                                                                                    آية زياد عيسى       1
                                                                                                                                                    بيان عمر محمود      2
                                                                                                                                                    بسمة مروان محمد     3
TEXT;
$blankArabicLines = array_map(static fn(string $line): array => mb_str_split(rtrim($line)), explode("\n", $blankArabicLayout));
$blankArabicSlots = $columnSlots->invoke($extractor, $blankArabicLines);
assert(count($blankArabicSlots) === 8, 'Blank Arabic grade sheet columns must come from its rich header row.');
$blankArabicRows = array_map(static fn(array $line): array => $splitLine->invoke($extractor, $line, $blankArabicSlots), $blankArabicLines);
assert(count($blankArabicRows[1]) === 8);

// من ملف PDF حقيقي، عندما يكون pdftotext متاحًا على الجهاز
if ($extractor->isAvailable()) {
    $extractedRows = $extractor->rows(__DIR__ . '/fixtures/grade-sheet.pdf', 1, false);
    $draft = $import->fromRows($extractedRows, 'كشف من PDF');
    $assertSaveable($draft);
    assert(array_column($draft['columns'], 'name') === ['No', 'Name', 'Quiz', 'Homework', 'Exam', 'Total']);
    assert(array_column($draft['columns'], 'type') === ['student_number', 'student_name', 'manual_mark', 'manual_mark', 'manual_mark', 'calculated_total']);
    assert(end($draft['columns'])['formula']['sources'] === ['quiz', 'homework', 'exam']);
    $bboxRows = new ReflectionMethod(PdfTableExtractor::class, 'bboxRows');
    $bboxDraft = $import->fromRows($bboxRows->invoke($extractor, __DIR__ . '/fixtures/grade-sheet.pdf', 1, false), 'كشف إحداثيات');
    $assertSaveable($bboxDraft);
    assert(array_column($bboxDraft['columns'], 'name') === ['No', 'Name', 'Quiz', 'Homework', 'Exam', 'Total']);
    echo "PDF extraction checked against tests/fixtures/grade-sheet.pdf.\n";
} else {
    echo "pdftotext غير متاح؛ تم تخطي اختبار استخراج PDF.\n";
}

if (class_exists(\Smalot\PdfParser\Parser::class)) {
    $fallback = new PdfTableExtractor('definitely-missing-pdftotext');
    $fallbackRows = $fallback->rows(__DIR__ . '/fixtures/grade-sheet.pdf', 1, false);
    assert(count($fallbackRows) === 5);
    assert(count($fallbackRows[0]) === 6);
    assert(array_column($fallbackRows[2], 'text') === ['1', 'Ahmad Ali', '8', '9', '18', '35']);
    $assertSaveable($import->fromRows($fallbackRows, 'كشف من محلل PHP'));
    echo "Pure-PHP PDF coordinate extraction checked.\n";
}

// كشف عربي كما يسلّمه pdftotext -bbox-layout: الكلمات بترتيب الرسم لا القراءة، أي معكوسة
// الأرقام تبقى بترتيبها، و«لا» رسمٌ واحد فيخرج حرفاه بترتيب القراءة: هنا يقع قلب الهجاء
$mirror = static function (string $text): string {
    $letters = mb_str_split($text);
    $mirrored = '';
    for ($index = count($letters) - 1; $index >= 0; $index--) {
        if ($index > 0 && $letters[$index - 1] === 'ل' && $letters[$index] === 'ا') {
            $mirrored .= 'لا';
            $index--;
            continue;
        }
        $mirrored .= $letters[$index];
    }
    return preg_replace_callback('/[\d\/.]+/', static fn(array $match): string => strrev($match[0]), $mirrored) ?? $mirrored;
};
$bboxBlock = static function (string $text, float $left, float $right, float $top, float $bottom, bool $visual = true) use ($mirror): string {
    $words = explode(' ', $text);
    if ($visual) $words = array_map($mirror, array_reverse($words));
    $box = 'xMin="' . $left . '" yMin="' . $top . '" xMax="' . $right . '" yMax="' . $bottom . '"';
    $inner = '';
    foreach ($words as $word) $inner .= '<word ' . $box . '>' . htmlspecialchars($word, ENT_XML1) . '</word>';
    return '<block ' . $box . '><line ' . $box . '>' . $inner . '</line></block>';
};
$conversationSheet = static function (bool $visual) use ($bboxBlock): string {
    $blocks = [
        // عنوان الصفحة فوق الجدول: ليس رأس عمود، ولا يجوز أن يلتحم بأول خلية
        $bboxBlock('مبحث اللغة العربية', 460, 560, 26, 38, $visual),
        $bboxBlock('الفصل الدراسي الأول', 460, 560, 40, 52, $visual),
        $bboxBlock('الصف الثامن', 460, 560, 54, 66, $visual),
        $bboxBlock('مجموعة مدارس الجامعة', 200, 340, 26, 42, $visual),
        $bboxBlock('تقييم المحادثة', 240, 320, 46, 62, $visual),
        $bboxBlock('عنوان صفحة قريب', 40, 100, 90, 104, $visual),
        $bboxBlock('م', 545, 560, 128, 142, $visual),
        $bboxBlock('اسم الطالب', 460, 530, 128, 142, $visual),
        $bboxBlock('الالتزام بالوقت 2', 390, 450, 112, 158, $visual),
        $bboxBlock('سلامة اللغة 2', 330, 388, 112, 158, $visual),
        $bboxBlock('الثقة بالنفس 2', 270, 328, 112, 158, $visual),
        $bboxBlock('ترتيب الأفكار 2', 210, 268, 112, 158, $visual),
        $bboxBlock('التلوين الصوتي 2', 150, 208, 112, 158, $visual),
        $bboxBlock('المجموع 10', 90, 148, 112, 158, $visual),
    ];
    foreach (['الما شادي اسعد عبدالهادي', 'جيانا علاء طلافحة', 'سناء عمر لطفي الفراج', 'كنزي سلمان أبو زمزم'] as $index => $student) {
        $top = 170 + $index * 16;
        $blocks[] = $bboxBlock((string) ($index + 1), 545, 560, $top, $top + 14, $visual);
        $blocks[] = $bboxBlock($student, 440, 535, $top, $top + 14, $visual);
    }
    return '<doc><page width="595" height="842">' . implode('', $blocks) . '</page></doc>';
};

$bboxBlocks = new ReflectionMethod(PdfTableExtractor::class, 'bboxBlocks');
$bboxGrid = new ReflectionMethod(PdfTableExtractor::class, 'bboxGrid');
// نص -layout للصفحة نفسها: هجاء صحيح تُصلَّح به الحروف التي يقلبها فكّ الرسم المتصل
$layoutSpelling = 'مبحث اللغة العربية الفصل الدراسي الأول الالتزام بالوقت سلامة اللغة الثقة بالنفس ترتيب الأفكار التلوين الصوتي المجموع جيانا علاء طلافحة سناء عمر لطفي الفراج';
$mirroredRows = $bboxGrid->invoke($extractor, $bboxBlocks->invoke($extractor, $conversationSheet(true), $layoutSpelling), true);
assert(array_column($mirroredRows[0], 'text') === [
    'م', 'اسم الطالب', 'الالتزام بالوقت 2', 'سلامة اللغة 2', 'الثقة بالنفس 2', 'ترتيب الأفكار 2', 'التلوين الصوتي 2', 'المجموع (10)',
], 'Right-to-left headers must come back in reading order, not mirrored.');
assert(array_column($mirroredRows[1], 'text') === ['1', 'الما شادي اسعد عبدالهادي', '', '', '', '', '', '']);
assert(array_column($mirroredRows[2], 'text')[1] === 'جيانا علاء طلافحة', 'A lam-alef ligature must be re-spelled from the -layout text.');
foreach ($mirroredRows[0] as $headerCell) {
    assert(!str_contains($headerCell['text'], 'مبحث') && !str_contains($headerCell['text'], 'مدارس'), 'Page headings sit above the table and are not columns.');
}

$repeatedHeaderRows = new ReflectionMethod(PdfTableExtractor::class, 'repeatedHeaderRows');
$identity = [
    ['text' => 'م', 'colspan' => 1, 'rowspan' => 1, 'header' => true],
    ['text' => 'اسم الطالب', 'colspan' => 1, 'rowspan' => 1, 'header' => true],
];
$conversationHeaders = array_map(
    static fn(string $text): array => ['text' => $text, 'colspan' => 1, 'rowspan' => 1, 'header' => true],
    ['الالتزام بالوقت 2', 'سلامة اللغة 2', 'الثقة بالنفس وطلاقة الحديث 2', 'الالتزام بالموضوع وترتيب الأفكار 2', 'التلوين الصوتي 2', 'المجموع (10)']
);
$groupedRows = $repeatedHeaderRows->invoke($extractor, [...$identity, ...$conversationHeaders, ...$conversationHeaders]);
assert(count($groupedRows) === 2);
assert(array_column(array_slice($groupedRows[0], 2), 'text') === ['المجموعة 1', 'المجموعة 2']);
assert(array_column(array_slice($groupedRows[0], 2), 'colspan') === [6, 6]);
$groupedDraft = $import->fromRows($groupedRows, 'تقييم المحادثة بمجموعتين');
assert(count($groupedDraft['groups']) === 2);
assert(count(array_filter($groupedDraft['columns'], fn(array $column): bool => $column['type'] === 'manual_mark')) === 10);
assert(count(array_filter($groupedDraft['columns'], fn(array $column): bool => $column['type'] === 'calculated_total')) === 2);

$shortTestsColumns = new ReflectionMethod(PdfTableExtractor::class, 'shortTestsColumns');
$shortTestsBlocks = [
    ['text' => 'االختبارات القصرية', 'x' => 300.0, 'y' => 80.0, 'top' => 70.0, 'bottom' => 90.0],
];
$shortTestsIdentity = [
    ['x' => 470.0, 'text' => 'اسم الطالبة'],
    ['x' => 560.0, 'text' => 'الرقم'],
];
$completed = $shortTestsColumns->invoke($extractor, $shortTestsBlocks, $shortTestsIdentity, true);
assert(array_column($completed, 'text') === [
    'ملاحظات', 'المجموع (30)', 'الاختبار 3 (10)', 'الاختبار 2 (10)', 'الاختبار 1 (10)', 'اسم الطالبة', 'الرقم',
]);
assert($shortTestsColumns->invoke($extractor, $shortTestsBlocks, $shortTestsIdentity, false) === $shortTestsIdentity);

// الصفحة نفسها حين يسلّمها المحرك بترتيب القراءة: تُقرأ كما هي، ولا تُعكس مرة ثانية
$logicalRows = $bboxGrid->invoke($extractor, $bboxBlocks->invoke($extractor, $conversationSheet(false), $layoutSpelling), true);
assert(array_column($logicalRows[0], 'text') === array_column($mirroredRows[0], 'text'), 'Already-logical text must not be reversed.');

$visualOrder = new ReflectionMethod(PdfTableExtractor::class, 'visualOrder');
assert($visualOrder->invoke($extractor, 'بلاطلا مسا ةيبرعلا ةغللا ثحبم') === true);
assert($visualOrder->invoke($extractor, 'اسم الطالب مبحث اللغة العربية') === false);
assert($visualOrder->invoke($extractor, 'No Name Quiz Homework Total') === false);

$logicalRtl = new ReflectionMethod(PdfTableExtractor::class, 'logicalRtl');
assert($logicalRtl->invoke($extractor, '10 Name بلاطلا مسا') === 'اسم الطالب Name 10');

// إعداد المحرك: مسار لا يعمل يجب أن يُقال صراحةً بدل الفشل عند أول رفع
$missingEngine = new PdfTableExtractor("definitely-missing-pdftotext");
assert($missingEngine->engine()["available"] === false);
assert($missingEngine->engine()["bbox"] === false);
assert(str_contains($missingEngine->warning(), "PDFTOTEXT_PATH"));
if ($extractor->engine()["available"]) {
    assert($extractor->engine()["name"] !== "", "A working pdftotext must name its engine.");
}

$plain = new ReflectionMethod(PdfTableExtractor::class, 'plain');
assert($plain->invoke($extractor, "\u{202B}اسم الطالب\u{202C}") === 'اسم الطالب');
$assertSaveable($import->fromRows($mirroredRows, "كشف المحادثة"));
echo "Mirrored Arabic bbox extraction checked.\n";

$tableBlock = new ReflectionMethod(PdfTableExtractor::class, 'tableBlock');
$pageWithHeading = "نموذج قراءة وكتابة تجريبي    مبحث اللغة العربية\n"
    . "السادس أ                      الفصل الدراسي\n"
    . "2026/2027                    بيانات سابقة\n__PDF_BLOCK_GAP__\n"
    . "التسلسل  اسم الطالب    القراءة  الكتابة\n"
    . "1         أحمد          8        9\n"
    . "2         سارة          7        10\n"
    . "3         ليان          9        8\n";
$detectedBlock = $tableBlock->invoke($extractor, $pageWithHeading);
assert(!str_contains(implode('', $detectedBlock[0]), 'نموذج'));
assert(str_contains(implode('', $detectedBlock[0]), 'التسلسل'));

$canonicalHeader = new ReflectionMethod(PdfTableExtractor::class, 'canonicalHeader');
assert($canonicalHeader->invoke($extractor, 'لتسلسل2027') === 'التسلسل');
assert($canonicalHeader->invoke($extractor, 'سم الطالبا') === 'اسم الطالب');
assert($canonicalHeader->invoke($extractor, 'لقراءة (10)ا') === 'القراءة (10)');
assert($canonicalHeader->invoke($extractor, 'لنسبة % (100)ا') === 'النسبة % (100)');

// المسودة تمر فعليًا عبر TemplateService::save، وهي البوابة التي تحرس القالب
try {
    $pdo = db();
} catch (Throwable) {
    $pdo = null;
    echo "قاعدة البيانات غير متاحة؛ تم تخطي اختبار حفظ المسودة.\n";
}
if ($pdo) {
    $draft = $import->fromHtml($conversation, 'قالب استيراد للاختبار ' . bin2hex(random_bytes(4)));
    unset($draft['notes']);
    $adminId = (int) $pdo->query('SELECT id FROM admin_users ORDER BY id LIMIT 1')->fetchColumn();
    $service = new App\Services\TemplateService($pdo, new App\Repositories\TemplateRepository($pdo, $adminId, true));
    $templateId = $service->save($draft, $adminId);
    try {
        $saved = (new App\Repositories\TemplateRepository($pdo, $adminId, true))->currentConfiguration($templateId);
        assert($saved !== null);
        assert(count($saved['columns']) === count($draft['columns']));
        assert(count($saved['groups']) === count($draft['groups']));
        $savedTotal = array_values(array_filter($saved['columns'], static fn(array $column): bool => $column['type'] === 'calculated_total'))[0];
        assert((int) $savedTotal['is_calculated'] === 1);
        // القاسم يعبر قاعدة البيانات ويعمل: 1+2+2+2+1 = 8 ثم ÷2 = 4
        assert((float) $savedTotal['max_mark'] === 4.0);
        assert((float) $savedTotal['formula']['divisor'] === 2.0);
        $expression = $pdo->query('SELECT expression FROM table_formulas f JOIN table_columns c ON c.id=f.column_id WHERE c.template_version_id=' . (int) $saved['id'] . " AND c.type='calculated_total' LIMIT 1")->fetchColumn();
        assert(str_contains((string) $expression, '/2'), (string) $expression); // التعبير المخزَّن يوثّق القسمة
        $marksBySource = array_fill_keys($savedTotal['formula']['sources'], 0);
        foreach (array_keys($marksBySource) as $index => $key) $marksBySource[$key] = [1, 2, 2, 2, 1][$index];
        $computed = (new FormulaEngine())->calculate($saved['columns'], $marksBySource);
        assert($computed[$savedTotal['column_key']] === 4.0);
    } finally {
        $pdo->prepare('UPDATE table_templates SET current_version_id=NULL WHERE id=?')->execute([$templateId]);
        $pdo->prepare('DELETE FROM table_templates WHERE id=?')->execute([$templateId]);
    }
    echo "Draft saved and removed through TemplateService.\n";
}

echo "Table import tests passed.\n";
