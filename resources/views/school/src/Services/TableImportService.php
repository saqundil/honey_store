<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;

/**
 * Turns a table pasted from Word/Excel, or a grid recovered from a PDF, into a
 * builder draft shaped exactly like window.BUILDER_DATA.
 *
 * The source only ever carries the header layout. Column types, maximum marks and
 * formulas are guessed from the header wording, so every draft is a starting point
 * that the teacher reviews in the builder before saving a version.
 */
final class TableImportService
{
    private const MAX_HEADER_ROWS = 4;

    /**
     * Header wording that decides the column type; the first match wins, so the specific
     * categories are tested before the identity ones — "رقم الطالب" must not be caught by
     * "الطالب", and "Notes" must not be caught by "no".
     */
    private const TYPE_HINTS = [
        'text' => ['ملاحظات', 'ملاحظة', 'التقدير', 'تقدير', 'notes', 'remarks', 'comment'],
        'date' => ['التاريخ', 'تاريخ', 'date'],
        'percentage' => ['النسبة', 'نسبة', 'المئوية', 'percent'],
        'calculated_average' => ['المعدل', 'معدل', 'المتوسط', 'متوسط', 'average', 'avg'],
        'calculated_total' => ['المجموع', 'مجموع', 'الاجمالي', 'الإجمالي', 'الكلي', 'المحصلة', 'النهائي', 'total'],
        'student_number' => ['رقم الطالب', 'الرقم', 'رقم', 'التسلسل', 'تسلسل', 'number', 'no'],
        'student_name' => ['اسم الطالب', 'الاسم', 'اسم', 'الطالب', 'الطالبة', 'name', 'student'],
    ];

    private const WIDTHS = [
        'student_number' => 10, 'student_name' => 48, 'date' => 20, 'text' => 25,
        'calculated_total' => 15, 'calculated_average' => 15, 'percentage' => 15, 'manual_mark' => 15,
    ];

    /** A rotated header exists to make its column narrow, so it gets a narrow default. */
    private const VERTICAL_WIDTH = 8.0;

    /** @var list<string> Human readable warnings about what had to be guessed. */
    private array $notes = [];

    /** True once every source column carried a width, so the source ratios can be trusted. */
    private bool $measuredWidths = false;

    /**
     * @return array{template_id:int,name:string,description:string,settings:array,groups:list<array>,columns:list<array>,notes:list<string>}
     */
    public function fromHtml(string $html, string $name = ''): array
    {
        return $this->fromRows($this->htmlRows($html), $name);
    }

    /**
     * Tab or multi-space separated text, the shape Excel puts on the clipboard.
     */
    public function fromDelimitedText(string $text, string $name = ''): array
    {
        $rows = [];
        foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
            if (trim($line) === '') continue;
            $parts = str_contains($line, "\t") ? explode("\t", $line) : (preg_split('/ {2,}|\s*[|;]\s*/u', trim($line)) ?: []);
            $rows[] = array_map(fn(string $cell): array => $this->cell($cell), $parts);
        }
        if (!$rows) throw new InvalidArgumentException('لم يُعثر على صفوف في النص الملصق.');
        $this->rejectRaggedText($rows);
        return $this->fromRows($rows, $name);
    }

    /**
     * Plain text carries no merges, so a header row that spans columns arrives short and a header
     * cell that wrapped onto two lines arrives as an extra row. Both silently produce a wrong
     * table, so uneven header rows are refused instead of guessed at.
     *
     * @param list<list<array{text:string,colspan:int,rowspan:int,header:bool}>> $rows
     */
    private function rejectRaggedText(array $rows): void
    {
        $counts = [];
        foreach (array_slice($rows, 0, 3) as $row) {
            while ($row && end($row)['text'] === '') array_pop($row);
            if (count($row) >= 2) $counts[] = count($row);
        }
        if (count(array_unique($counts)) <= 1) return;
        throw new InvalidArgumentException('صفوف الرأس في النص الملصق غير متساوية الأعمدة، فلا يمكن معرفة أي رأس يغطي أي أعمدة. هذا يحدث عندما يحتوي الجدول على خلايا رؤوس مدمجة أو نصًا ملتفًا على سطرين. انسخ الجدول نفسه من Word أو Excel والصقه هنا بدل نسخ نصه.');
    }

    /**
     * @param list<list<array{text:string,colspan:int,rowspan:int,header:bool}>> $rows
     */
    public function fromRows(array $rows, string $name = ''): array
    {
        $this->notes = [];
        [$grid, $cells, $width, $height] = $this->layout($rows);
        if ($width < 1 || $height < 1) throw new InvalidArgumentException('لم يُعثر على جدول صالح في المحتوى.');

        $headerRows = $this->headerRowCount($grid, $cells, $width, $height);
        [$groups, $columns] = $this->readHeader($grid, $cells, $width, $headerRows);
        $columns = $this->guessTypes($columns);
        $columns = $this->applyTrailingMarks($columns);
        $columns = $this->applyMaxMarkRow($grid, $cells, $columns, $width, $headerRows);
        $columns = $this->assignKeys($columns);
        $this->measuredWidths = $this->widthsUsable($columns);
        $columns = $this->attachFormulas($columns);
        $groups = $this->pruneGroups($groups, $columns);
        $rotated = count(array_filter($columns, static fn(array $column): bool => $column['display_direction'] === 'vertical'));
        if ($rotated) $this->notes[] = "نُقل دوران النص من المصدر إلى {$rotated} عمودًا كرأس عمودي.";
        if ($this->measuredWidths) $this->notes[] = 'قُرئت عروض الأعمدة من المصدر؛ العرض يُستخدم كنسبة من عرض الجدول لا كقياس مطلق.';

        return [
            'template_id' => 0,
            'name' => trim($name) !== '' ? trim($name) : 'قالب مستورد ' . date('Y-m-d'),
            'description' => '',
            'settings' => [],
            'groups' => array_values($groups),
            'columns' => array_values($columns),
            // ملاحظات متطابقة الصياغة لا تضيف شيئًا: عمودان بالاسم نفسه
            // ينتجان السطر نفسه حرفيًا، فيُعرض مرتين بلا فائدة.
            'notes' => array_values(array_unique($this->notes)),
        ];
    }

    /** @return array{text:string,colspan:int,rowspan:int,header:bool,vertical:bool,width_mm:float|null} */
    public function cell(string $text, int $colspan = 1, int $rowspan = 1, bool $header = false, bool $vertical = false, ?float $widthMm = null): array
    {
        return [
            'text' => $this->normalize($text), 'colspan' => max(1, $colspan), 'rowspan' => max(1, $rowspan),
            'header' => $header, 'vertical' => $vertical, 'width_mm' => $widthMm,
        ];
    }

    // ---------------------------------------------------------------- parsing

    /** @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>> */
    private function htmlRows(string $html): array
    {
        if (stripos($html, '<table') === false) {
            throw new InvalidArgumentException('المحتوى الملصق لا يحتوي على جدول. انسخ الجدول نفسه من Word أو Excel.');
        }
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $table = $xpath->query('(//table)[1]')->item(0);
        if (!$table instanceof DOMElement) throw new InvalidArgumentException('تعذر قراءة الجدول من المحتوى الملصق.');

        $rows = [];
        foreach ($xpath->query('.//tr', $table) as $tr) {
            if (!$tr instanceof DOMElement) continue;
            if ($xpath->query('ancestor::table', $tr)->item(0) !== $table) continue; // تجاهل الجداول المتداخلة
            $row = [];
            foreach ($tr->childNodes as $node) {
                if (!$node instanceof DOMElement) continue;
                $tag = strtolower($node->nodeName);
                if ($tag !== 'td' && $tag !== 'th') continue;
                $row[] = $this->cell(
                    $this->textOf($node),
                    (int) ($node->getAttribute('colspan') ?: 1),
                    (int) ($node->getAttribute('rowspan') ?: 1),
                    $tag === 'th',
                    $this->isRotated($node, $xpath),
                    $this->widthOf($node, $xpath)
                );
            }
            if ($row) $rows[] = $row;
        }
        if (!$rows) throw new InvalidArgumentException('الجدول الملصق فارغ.');
        return $rows;
    }

    private function textOf(DOMNode $node): string
    {
        return $this->normalize($node->textContent ?? '');
    }

    /**
     * Rotated header text. Word writes `mso-layout-flow-alt` on the cell or on the paragraph
     * inside it, Excel and LibreOffice write the standard `writing-mode` or a rotation, and the
     * project already renders any of them through `display_direction = vertical`.
     */
    private function isRotated(DOMElement $cell, DOMXPath $xpath): bool
    {
        return (bool) preg_match(
            '/mso-layout-flow-alt\s*:\s*(?:bottom-to-top|top-to-bottom)|layout-flow\s*:\s*vertical|writing-mode\s*:\s*(?:tb-rl|vertical-[rl]l)|mso-rotate\s*:\s*(?:90|270|-90)|rotate\s*\(\s*-?(?:90|270)deg/i',
            $this->styleOf($cell, $xpath)
        );
    }

    /**
     * Word states each column's width on its cells. Only ratios matter downstream, but mixing a
     * measured column with a defaulted one would distort them, so a width is kept only when every
     * cell of the table offers one in an absolute unit.
     */
    private function widthOf(DOMElement $cell, DOMXPath $xpath): ?float
    {
        $units = ['mm' => 1.0, 'cm' => 10.0, 'pt' => 25.4 / 72, 'px' => 25.4 / 96, 'in' => 25.4, 'pc' => 25.4 / 6];
        if (preg_match('/(?:^|[;{\s])width\s*:\s*([\d.]+)\s*(mm|cm|pt|px|in|pc)/i', $this->styleOf($cell, $xpath, false), $match)) {
            return (float) $match[1] * $units[strtolower($match[2])];
        }
        $attribute = trim($cell->getAttribute('width'));
        if ($attribute !== '' && preg_match('/^[\d.]+$/', $attribute)) return (float) $attribute * $units['px'];
        return null;
    }

    private function styleOf(DOMElement $cell, DOMXPath $xpath, bool $withDescendants = true): string
    {
        $style = $cell->getAttribute('style');
        if (!$withDescendants) return $style;
        foreach ($xpath->query('.//*[@style]', $cell) as $descendant) {
            if ($descendant instanceof DOMElement) $style .= ';' . $descendant->getAttribute('style');
        }
        return $style;
    }

    /**
     * Expands colspan/rowspan into an occupancy grid so every leaf column can be
     * walked from the top header row down to its own label.
     *
     * @return array{0:array<int,array<int,int>>,1:list<array>,2:int,3:int}
     */
    private function layout(array $rows): array
    {
        $grid = [];
        $cells = [];
        foreach (array_values($rows) as $rowIndex => $row) {
            $column = 0;
            foreach ($row as $source) {
                while (isset($grid[$rowIndex][$column])) $column++;
                $id = count($cells);
                $cells[$id] = $source + ['row' => $rowIndex, 'column' => $column];
                for ($r = 0; $r < $source['rowspan']; $r++) {
                    for ($c = 0; $c < $source['colspan']; $c++) $grid[$rowIndex + $r][$column + $c] = $id;
                }
                $column += $source['colspan'];
            }
        }
        $height = $grid ? max(array_keys($grid)) + 1 : 0;
        $width = 0;
        foreach ($grid as $row) $width = max($width, $row ? max(array_keys($row)) + 1 : 0);
        return [$grid, $cells, $width, $height];
    }

    private function headerRowCount(array $grid, array $cells, int $width, int $height): int
    {
        $count = 0;
        for ($row = 0; $row < min($height, self::MAX_HEADER_ROWS); $row++) {
            if (!$this->isHeaderRow($grid, $cells, $width, $row)) break;
            $count++;
        }
        return max(1, $count);
    }

    private function isHeaderRow(array $grid, array $cells, int $width, int $row): bool
    {
        $covered = 0; // أعمدة مغطاة بنص، لا عدد خلايا: خلية مجموعة واحدة قد تغطي نصف الجدول
        $numeric = 0;
        $seen = [];
        for ($column = 0; $column < $width; $column++) {
            $id = $grid[$row][$column] ?? null;
            if ($id === null) continue;
            $cell = $cells[$id];
            if ($cell['header']) return true;
            if ($cell['text'] === '') continue;
            $covered++;
            if (isset($seen[$id])) continue;
            $seen[$id] = true;
            if ($this->isNumeric($cell['text'])) $numeric++;
        }
        return $covered > 0 && $numeric === 0 && $covered >= max(1, (int) floor($width / 2));
    }

    /**
     * @return array{0:array<string,array>,1:list<array>}
     */
    private function readHeader(array $grid, array $cells, int $width, int $headerRows): array
    {
        $groups = [];
        $columns = [];
        for ($column = 0; $column < $width; $column++) {
            $chain = [];
            for ($row = 0; $row < $headerRows; $row++) {
                $id = $grid[$row][$column] ?? null;
                if ($id === null) continue;
                if ($chain && end($chain) === $id) continue;
                $chain[] = $id;
            }
            if (!$chain) { $columns[] = ['label' => '', 'group' => null, 'vertical' => false, 'width_mm' => null]; continue; }

            $leafId = array_pop($chain);
            $parentKey = null;
            $prefix = [];
            foreach ($chain as $ancestorId) {
                $ancestor = $cells[$ancestorId];
                if ($ancestor['text'] === '') continue;
                if ($ancestor['colspan'] < 2) { $prefix[] = $ancestor['text']; continue; }
                $key = 'grp_' . $ancestorId;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'group_key' => $key, 'name' => $ancestor['text'], 'parent_key' => $parentKey,
                        'sort_order' => count($groups) + 1, 'text_direction' => 'rtl',
                        'display_direction' => !empty($ancestor['vertical']) ? 'vertical' : 'horizontal',
                    ];
                }
                $parentKey = $key;
            }
            $leaf = $cells[$leafId];
            $label = trim(implode(' ', array_merge($prefix, [$leaf['text']])));
            // خلية ممتدة تغطي أعمدة عدة، فعرضها ليس عرض عمود واحد
            $columns[] = [
                'label' => $label, 'group' => $parentKey, 'vertical' => !empty($leaf['vertical']),
                'width_mm' => $leaf['colspan'] === 1 ? ($leaf['width_mm'] ?? null) : null,
            ];
        }
        return [$groups, $columns];
    }

    // ------------------------------------------------------------- guessing

    private function guessTypes(array $raw): array
    {
        $columns = [];
        foreach ($raw as $index => $item) {
            $label = $item['label'] !== '' ? $item['label'] : 'عمود ' . ($index + 1);
            $maxMark = $this->maxMarkFromLabel($label);
            $columns[] = [
                'label' => $maxMark !== '' ? $this->withoutMaxMark($label) : $label,
                'type' => $this->guessType($label),
                'header_group_key' => $item['group'],
                'max_mark' => $maxMark,
                'source_column' => $index,
                'vertical' => $item['vertical'] ?? false,
                'width_mm' => $item['width_mm'] ?? null,
            ];
        }
        return $this->enforceIdentityColumns($columns);
    }

    private function guessType(string $label): string
    {
        $haystack = mb_strtolower($this->stripDiacritics($label), 'UTF-8');
        if ($haystack === '' || $haystack === 'م' || $haystack === '#') return 'student_number';
        foreach (self::TYPE_HINTS as $type => $hints) {
            foreach ($hints as $hint) {
                // اللاتيني يُطابق ككلمة كاملة حتى لا تلتقط "no" كلمة "Notes"
                $matched = preg_match('/^[a-z]+$/', $hint)
                    ? preg_match('/\b' . $hint . '\b/', $haystack) === 1
                    : mb_strpos($haystack, mb_strtolower($hint, 'UTF-8')) !== false;
                if ($matched) return $type;
            }
        }
        return 'manual_mark';
    }

    /** Only a bracketed or "من 10" number is a maximum mark; "اختبار 2" is a name. */
    private function maxMarkFromLabel(string $label): string
    {
        $text = $this->digits($label);
        if (preg_match('/[\(\[\{]\s*(\d+(?:\.\d+)?)\s*[\)\]\}]/u', $text, $match)) return $match[1];
        if ($this->ratioInLabel($text)) return ''; // «المجموع 8/2» نسبة قسمة لا علامة قصوى
        if (preg_match('/(?:من|\/|out of)\s*(\d+(?:\.\d+)?)/ui', $text, $match)) return $match[1];
        return '';
    }

    private function withoutMaxMark(string $label): string
    {
        $clean = preg_replace('/\s*[\(\[\{]\s*[\d\x{0660}-\x{0669}\x{06F0}-\x{06F9}]+(?:[.,\x{066B}][\d\x{0660}-\x{0669}\x{06F0}-\x{06F9}]+)?\s*[\)\]\}]\s*/u', ' ', $label);
        return trim(preg_replace('/\s+/u', ' ', $clean ?? $label));
    }

    /**
     * "المجموع 8/2" means the components add up to 8 and the result is then halved. Returns
     * [total, divisor] so a calculated column can carry the division the header asks for.
     *
     * @return array{0:float,1:float}|null
     */
    private function ratioInLabel(string $label): ?array
    {
        if (!preg_match('/(?:^|\s)(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)(?:\s|$)/u', $this->digits($label), $match)) return null;
        [$total, $divisor] = [(float) $match[1], (float) $match[2]];
        // القاسم أصغر من المجموع دائمًا؛ العكس يعني «2 من 8» فلا قسمة فيه
        return $divisor > 0 && $total > 0 && $divisor < $total ? [$total, $divisor] : null;
    }

    /**
     * Arabic grade sheets usually write the mark at the end of the header — "الإلتزام بالوقت 1".
     * A single such header could be a name ("اختبار 2"), so the pattern is only trusted when most
     * of the mark columns share it; then the number becomes the maximum and leaves the name.
     */
    private function applyTrailingMarks(array $columns): array
    {
        $trailing = [];
        $candidates = 0;
        foreach ($columns as $index => $column) {
            if (!in_array($column['type'], ['manual_mark', 'calculated_total', 'calculated_average', 'percentage'], true)) continue;
            $candidates++;
            if ($column['max_mark'] !== '') continue;
            if (preg_match('/^(.*\S)\s+([\d\x{0660}-\x{0669}\x{06F0}-\x{06F9}]+(?:[.,\x{066B}][\d\x{0660}-\x{0669}\x{06F0}-\x{06F9}]+)?)$/u', $column['label'], $match)) {
                $trailing[$index] = [$match[1], $this->digits($match[2])];
            }
        }
        if (count($trailing) < 2 || count($trailing) < $candidates / 2) return $columns;
        foreach ($trailing as $index => [$label, $mark]) {
            if ((float) $mark <= 0) continue;
            $columns[$index]['label'] = $label;
            $columns[$index]['max_mark'] = $mark;
        }
        $this->notes[] = 'قُرئ الرقم في نهاية كل رأس عمود كعلامة قصوى وأُزيل من اسم العمود.';
        return $columns;
    }

    private function enforceIdentityColumns(array $columns): array
    {
        foreach (['student_number', 'student_name'] as $identity) {
            $found = array_keys(array_column($columns, 'type'), $identity, true);
            foreach (array_slice($found, 1) as $extra) {
                $columns[$extra]['type'] = 'text';
                $columns[$extra]['max_mark'] = '';
            }
            if ($found) continue;
            $label = $identity === 'student_number' ? 'الرقم' : 'اسم الطالب';
            array_unshift($columns, ['label' => $label, 'type' => $identity, 'header_group_key' => null, 'max_mark' => '', 'source_column' => null, 'vertical' => false, 'width_mm' => null]);
            $this->notes[] = "لم يُعثر على عمود «{$label}» في المصدر، فأُضيف تلقائيًا في بداية الجدول.";
        }
        // رقم الطالب أولًا ثم اسمه، كما يبدأ به المحرر
        $rank = fn(array $column): int => match ($column['type']) { 'student_number' => 0, 'student_name' => 1, default => 2 };
        $ordered = $columns;
        usort($ordered, fn(array $first, array $second): int => $rank($first) <=> $rank($second));
        return array_values($ordered);
    }

    /**
     * A row of bare numbers right under the header is the maximum-marks row, not data.
     */
    private function applyMaxMarkRow(array $grid, array $cells, array $columns, int $width, int $headerRows): array
    {
        if (!isset($grid[$headerRows])) return $columns;
        $bySource = [];
        foreach ($columns as $index => $column) {
            if ($column['source_column'] !== null) $bySource[$column['source_column']] = $index;
        }
        $values = [];
        for ($column = 0; $column < $width; $column++) {
            $id = $grid[$headerRows][$column] ?? null;
            $text = $id === null ? '' : $this->digits($cells[$id]['text']);
            if ($text === '') continue;
            if (!$this->isNumeric($text)) return $columns;
            $index = $bySource[$column] ?? null;
            $target = $index === null ? null : $columns[$index];
            if ($target && in_array($target['type'], ['student_number', 'student_name'], true)) return $columns; // صف بيانات طالب
            if ($index !== null) $values[$index] = $text;
        }
        if (!$values) return $columns;
        foreach ($values as $index => $value) {
            if (!isset($columns[$index]) || (float) $value <= 0) continue;
            if (in_array($columns[$index]['type'], ['text', 'date'], true)) continue;
            $columns[$index]['max_mark'] = $value;
        }
        $this->notes[] = 'قُرئ الصف الذي يلي الرؤوس كصف علامات قصوى.';
        return $columns;
    }

    private function assignKeys(array $columns): array
    {
        $used = [];
        foreach ($columns as $index => $column) {
            if ($column['type'] !== 'student_number' && $column['type'] !== 'student_name') continue;
            $columns[$index]['column_key'] = $column['type'];
            $used[$column['type']] = true;
        }
        foreach ($columns as $index => $column) {
            if (isset($column['column_key'])) continue;
            $slug = trim((string) preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($column['label'], 'UTF-8')), '_');
            $key = preg_match('/^[a-z][a-z0-9_]{1,99}$/', $slug) && !isset($used[$slug]) ? $slug : 'col_' . ($index + 1);
            while (isset($used[$key])) $key .= '_' . ($index + 1);
            $used[$key] = true;
            $columns[$index]['column_key'] = $key;
        }
        return $columns;
    }

    /**
     * A calculated column sums the mark columns of its own header group, or the run
     * of mark columns before it. Without sources it is demoted so the draft stays saveable.
     */
    private function attachFormulas(array $columns): array
    {
        $calculated = ['calculated_total' => 'SUM', 'calculated_average' => 'AVERAGE', 'percentage' => 'PERCENTAGE'];
        $marks = array_column($columns, 'max_mark', 'column_key');
        $result = [];
        foreach ($columns as $index => $column) {
            $built = [
                'column_key' => $column['column_key'], 'name' => $column['label'], 'header_label' => '',
                'type' => $column['type'], 'max_mark' => $column['max_mark'], 'step_value' => 0.25,
                'width_mm' => $this->widthFor($column), 'sort_order' => $index + 1, 'is_visible' => true,
                'header_group_key' => $column['header_group_key'], 'text_direction' => 'rtl',
                'display_direction' => $column['vertical'] ? 'vertical' : 'horizontal',
            ];
            if (!isset($calculated[$column['type']])) { $result[] = $built; continue; }

            $sources = $this->formulaSources($columns, $index);
            if (!$sources) {
                $built['type'] = 'manual_mark';
                $built['width_mm'] = $this->widthFor(['type' => 'manual_mark'] + $column);
                $this->notes[] = "العمود «{$column['label']}» بدا محسوبًا لكن لم تُعثر له أعمدة مصدر، فحُوِّل إلى علامة يدوية.";
                $result[] = $built;
                continue;
            }
            $sourceMarks = array_map(fn(string $key): string => (string) ($marks[$key] ?? ''), $sources);
            $sourceTotal = in_array('', $sourceMarks, true) ? null : array_sum(array_map('floatval', $sourceMarks));
            $base = $column['type'] === 'percentage' ? (string) $sourceTotal : '';
            // «المجموع 8/2»: المكوّنات تجمع 8 ثم تُقسم على 2، فالقصوى 4 والقاسم 2
            $ratio = $this->ratioInLabel($column['label']);
            $divisor = $ratio && (($sourceTotal === null) || abs($ratio[0] - $sourceTotal) < 0.001) ? $ratio[1] : 1.0;
            // مجموع مصادر معروفة أوثق من رقم مقروء من نص الرأس
            if ($column['type'] === 'calculated_total' && $sourceTotal !== null) $built['max_mark'] = $this->number($sourceTotal / $divisor);
            $built['formula'] = ['type' => $calculated[$column['type']], 'sources' => $sources, 'missing' => 'blank', 'base' => $base, 'divisor' => $divisor, 'decimals' => 2];
            if ($divisor > 1) $this->notes[] = "قُرئ رأس «{$column['label']}» على أنه مجموع مقسوم على {$this->number($divisor)}.";
            $result[] = $built;
        }
        return $result;
    }

    /** @return list<string> */
    private function formulaSources(array $columns, int $index): array
    {
        $group = $columns[$index]['header_group_key'];
        $inGroup = [];
        $run = [];
        for ($cursor = 0; $cursor < $index; $cursor++) {
            $candidate = $columns[$cursor];
            if ($candidate['type'] !== 'manual_mark') {
                if (!in_array($candidate['type'], ['text', 'date'], true)) $run = [];
                continue;
            }
            $run[] = $candidate['column_key'];
            if ($group !== null && $candidate['header_group_key'] === $group) $inGroup[] = $candidate['column_key'];
        }
        if ($inGroup) return $inGroup;
        if ($run) return $run;
        $all = [];
        foreach ($columns as $cursor => $candidate) {
            if ($cursor !== $index && $candidate['type'] === 'manual_mark') $all[] = $candidate['column_key'];
        }
        return $all;
    }

    /** Column widths are used proportionally downstream, so a partial set would skew every column. */
    private function widthsUsable(array $columns): bool
    {
        $measured = 0;
        foreach ($columns as $column) {
            if ($column['source_column'] === null) continue; // عمود هوية أُضيف تلقائيًا، لا مصدر له
            if (($column['width_mm'] ?? null) === null || $column['width_mm'] <= 0) return false;
            $measured++;
        }
        return $measured > 0;
    }

    private function widthFor(array $column): float
    {
        if ($this->measuredWidths && ($column['width_mm'] ?? null) > 0) {
            return round(max(6.0, min(120.0, (float) $column['width_mm'])), 1);
        }
        if (!empty($column['vertical'])) return self::VERTICAL_WIDTH;
        return (float) (self::WIDTHS[$column['type']] ?? 15);
    }

    private function pruneGroups(array $groups, array $columns): array
    {
        $used = array_filter(array_column($columns, 'header_group_key'));
        do {
            $changed = false;
            foreach ($groups as $key => $group) {
                if (in_array($key, $used, true)) continue;
                if (in_array($key, array_column($groups, 'parent_key'), true)) continue;
                unset($groups[$key]);
                $changed = true;
            }
        } while ($changed);
        foreach ($groups as $key => $group) {
            if ($group['parent_key'] !== null && !isset($groups[$group['parent_key']])) $groups[$key]['parent_key'] = null;
        }
        return $groups;
    }

    // -------------------------------------------------------------- helpers

    private function normalize(string $text): string
    {
        $text = str_replace(["\xC2\xA0", "\xE2\x80\x8F", "\xE2\x80\x8E"], ' ', $text);
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** Arabic-Indic and Persian digits to ASCII so numbers are comparable. */
    private function digits(string $text): string
    {
        return strtr($text, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٫' => '.',
        ]);
    }

    private function stripDiacritics(string $text): string
    {
        return (string) preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', $text);
    }

    /** رقم بأقصر صورة: 4 لا 4.00 */
    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function isNumeric(string $text): bool
    {
        return (bool) preg_match('/^\d+(?:[.,]\d+)?$/', str_replace(' ', '', $this->digits($text)));
    }
}
