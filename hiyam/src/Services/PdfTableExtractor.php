<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Recovers a table grid from a text PDF.
 *
 * A PDF stores glyphs at coordinates, never a table, so the structure is rebuilt from
 * the fixed-width rendering of `pdftotext -layout`: columns are the vertical bands that
 * are blank on every line, and a header run that straddles several bands becomes a
 * spanning cell, which is what turns into a header group upstream.
 *
 * Arabic sheets take the coordinate route instead — their headers are stacked vertically in
 * narrow cells, which the fixed-width rendering shreds — and the `-layout` text stays on as
 * the reference for words whose letters a ligature glyph leaves swapped.
 *
 * Accuracy is far below a pasted Word/Excel table. Always review the draft in the builder.
 */
final class PdfTableExtractor
{
    private const MIN_GAP = 2;
    private const BLOCK_GAP = '__PDF_BLOCK_GAP__';

    private string $binary;
    private bool $configured;
    /** @var array{path:string,name:string,available:bool,bbox:bool}|null */
    private ?array $engine = null;
    /** @var array{path:string,name:string,available:bool,bbox:bool}|null */
    private ?array $bboxEngine = null;

    public function __construct(?string $binary = null)
    {
        $configured = $binary ?: self::setting('PDFTOTEXT_PATH');
        $this->configured = $configured !== '';
        $this->binary = $configured ?: 'pdftotext';
    }

    /**
     * PDFTOTEXT_PATH يُثبّت المحرك بدل ترك ترتيب PATH يختاره: النسخ تتفاوت في فكّ الحروف
     * المتصلة العربية. تُقرأ من بيئة العملية، ومن .env عبر Laravel، ومن SetEnv في Apache.
     */
    private static function setting(string $key): string
    {
        foreach ([$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)] as $value) {
            if (is_string($value) && trim($value) !== '') return trim($value);
        }
        return '';
    }

    public function isAvailable(): bool
    {
        return $this->hasBinary() || class_exists(\Smalot\PdfParser\Parser::class);
    }

    /**
     * كلا المحركين يطبع اسمه على -v ويخرج بالرمز 0، فالبانر وحده هو الفيصل؛ ومن يجهل
     * -bbox-layout يردّ بصفحة الاستعمال، وبها يُعرف أن مسار الإحداثيات غير متاح عنده.
     *
     * @return array{path:string,name:string,available:bool,bbox:bool}
     */
    public function engine(): array
    {
        return $this->engine ??= $this->probe($this->binary);
    }

    /**
     * قراءة الإحداثيات حكرٌ على poppler، بينما قد يكون محرك النص المختار Xpdf لأنه أضبط في فكّ
     * الحروف المتصلة؛ فيجوز تعيين PDFTOTEXT_BBOX_PATH لمحرك ثانٍ يتولى الإحداثيات وحدها،
     * ويبقى هجاء الكلمات مأخوذًا من نص المحرك الأول.
     *
     * @return array{path:string,name:string,available:bool,bbox:bool}
     */
    public function coordinateEngine(): array
    {
        if ($this->bboxEngine !== null) return $this->bboxEngine;

        $configured = self::setting('PDFTOTEXT_BBOX_PATH');
        if ($configured === '') return $this->bboxEngine = $this->engine();

        return $this->bboxEngine = $this->probe($configured);
    }

    /** @return array{path:string,name:string,available:bool,bbox:bool} */
    private function probe(string $binary): array
    {
        $banner = '';
        try {
            [, $output, $error] = $this->execute([$binary, '-bbox-layout', '-v']);
            $banner = $output . $error;
        } catch (\Throwable) {
        }
        $available = stripos($banner, 'pdftotext') !== false;
        $version = preg_match('/version\s+([\d.]+)/i', $banner, $match) ? ' ' . $match[1] : '';

        return [
            'path' => $binary,
            'name' => $available ? (stripos($banner, 'poppler') !== false ? 'Poppler' : 'Xpdf') . $version : '',
            'available' => $available,
            'bbox' => $available && stripos($banner, 'Usage:') === false,
        ];
    }

    /** ما يحتاج المستخدم معرفته عن محرك القراءة قبل أن يرفع ملفًا، أو '' إن كان كل شيء مهيأً. */
    public function warning(): string
    {
        $engine = $this->engine();
        if (!$engine['available']) {
            return $this->configured
                ? "المسار المضبوط في PDFTOTEXT_PATH لا يعمل: {$engine['path']}"
                : '';
        }
        $coordinates = $this->coordinateEngine();
        if (!$coordinates['available'] && $coordinates['path'] !== $engine['path']) {
            return "المسار المضبوط في PDFTOTEXT_BBOX_PATH لا يعمل: {$coordinates['path']}";
        }
        if (!$coordinates['bbox'] && !class_exists(\Smalot\PdfParser\Parser::class)) {
            return "محرك القراءة الحالي ({$engine['name']}) لا يقرأ الكشوف شبه الفارغة؛ عيّن PDFTOTEXT_BBOX_PATH لنسخة poppler، أو ثبّت حزمة smalot/pdfparser.";
        }
        return '';
    }

    private function hasBinary(): bool
    {
        return $this->engine()['available'];
    }

    /**
     * @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>>
     */
    public function rows(string $path, int $page = 1, ?bool $rtl = null): array
    {
        if (!$this->hasBinary() && class_exists(\Smalot\PdfParser\Parser::class)) {
            return $this->coordinateRows($this->phpPage($path, $page), $rtl);
        }

        $text = $this->text($path, $page);
        if (trim($text) === '') {
            throw new RuntimeException('لا يحتوي هذا الـPDF على نص قابل للقراءة. الأرجح أنه ممسوح ضوئيًا كصورة؛ استخدم لصق الجدول من Word أو Excel.');
        }
        // الرؤوس العربية تُكدَّس رأسيًا في خلايا ضيقة فتتفتّت في نصّ العرض الثابت؛ لذلك
        // يجب اختيار الإحداثيات قبل محاولة tableBlock، لا بوصفها إنقاذًا بعد فشلها فقط.
        $rightToLeft = $rtl ?? $this->looksArabic($text);
        if ($rightToLeft) {
            if ($this->coordinateEngine()['bbox']) return $this->bboxRows($path, $page, true, $text);
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    return $this->coordinateRows($this->phpPage($path, $page), true, $text);
                } catch (\Throwable) {
                }
            }
        }

        $lines = $this->tableBlock($text);
        if (count($lines) < 2) {
            // كشف فارغ: -layout لا يترك أسطرًا كافية، فيُعاد البناء من الإحداثيات
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    return $this->coordinateRows($this->phpPage($path, $page), $rtl, $text);
                } catch (\Throwable) {
                }
            }
            if (!$this->coordinateEngine()['bbox']) {
                throw new RuntimeException('تعذر تمييز جدول في هذه الصفحة؛ محرك القراءة الحالي لا يقرأ إحداثيات الكلمات. استخدم لصق الجدول من Word أو Excel.');
            }
            return $this->bboxRows($path, $page, $rtl ?? $this->looksArabic($text), $text);
        }
        $slots = $this->columnSlots($lines);
        if (count($slots) < 2) {
            throw new RuntimeException('تعذر تمييز أعمدة الجدول؛ المسافات بين الأعمدة غير واضحة في النص المستخرج.');
        }
        $rows = [];
        foreach ($lines as $line) {
            $row = $this->splitLine($line, $slots);
            if (!$row) continue;
            $rows[] = $rightToLeft ? array_reverse($row) : $row;
        }
        return $rows;
    }

    public function text(string $path, int $page = 1): string
    {
        $page = max(1, $page);
        if ($this->hasBinary()) {
            $outputPath = tempnam(sys_get_temp_dir(), 'pdf-text-');
            if ($outputPath === false) throw new RuntimeException('تعذر إنشاء ملف مؤقت لقراءة PDF.');
            $inputPath = $path;
            $temporaryInput = null;
            try {
                if (preg_match('/[^\x20-\x7E]/', $path)) {
                    $temporaryInput = tempnam(sys_get_temp_dir(), 'pdf-input-');
                    if ($temporaryInput === false || !copy($path, $temporaryInput)) {
                        throw new RuntimeException('تعذر تجهيز اسم ملف PDF العربي للقراءة.');
                    }
                    $inputPath = $temporaryInput;
                }
                $this->run([$this->binary, '-layout', '-nopgbrk', '-enc', 'UTF-8', '-f', (string) $page, '-l', (string) $page, $inputPath, $outputPath]);
                return $this->plain((string) file_get_contents($outputPath));
            } finally {
                if ($temporaryInput !== null) @unlink($temporaryInput);
                @unlink($outputPath);
            }
        }

        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new RuntimeException('لا يتوفر محرك لقراءة PDF على هذا الخادم. استخدم لصق الجدول من Word أو Excel.');
        }

        try {
            return $this->coordinateLayout($this->phpPage($path, $page));
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RuntimeException('تعذر قراءة نص PDF بواسطة محلل PHP. قد يكون الملف مشفرًا أو ممسوحًا ضوئيًا.', 0, $exception);
        }
    }

    /**
     * `-bbox-layout` reports where every word sits, but unlike `-layout` it never reorders a
     * right-to-left run, so Arabic arrives mirrored and must be turned back before anything
     * downstream reads it. `$layoutText` is the same page as `-layout` already spelled it, and
     * is used to repair the letter pairs a ligature glyph leaves swapped by the reversal.
     *
     * @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>>
     */
    private function bboxRows(string $path, int $page, bool $rtl, string $layoutText = ''): array
    {
        $xml = $this->run([$this->coordinateEngine()['path'], '-bbox-layout', '-enc', 'UTF-8', '-f', (string) $page, '-l', (string) $page, $path, '-']);

        return $this->bboxGrid($this->bboxBlocks($xml, $layoutText), $rtl);
    }

    /**
     * @return list<array{text:string,x:float,y:float,top:float,bottom:float}>
     */
    private function bboxBlocks(string $xml, string $layoutText = ''): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) throw new RuntimeException('تعذر قراءة إحداثيات النص من ملف PDF.');

        $xpath = new \DOMXPath($document);
        $blocks = [];
        foreach ($xpath->query('//*[local-name()="block"]') ?: [] as $block) {
            if (!$block instanceof \DOMElement) continue;
            $words = [];
            foreach ($xpath->query('.//*[local-name()="word"]', $block) ?: [] as $word) {
                $value = trim($this->plain($word->textContent ?? ''));
                if ($value !== '') $words[] = $value;
            }
            if (!$words) continue;
            $blocks[] = [
                'text' => implode(' ', $words),
                'x' => ((float) $block->getAttribute('xMin') + (float) $block->getAttribute('xMax')) / 2,
                'y' => ((float) $block->getAttribute('yMin') + (float) $block->getAttribute('yMax')) / 2,
                'top' => (float) $block->getAttribute('yMin'),
                'bottom' => (float) $block->getAttribute('yMax'),
            ];
        }
        return $this->readingOrder($blocks, $layoutText);
    }

    /**
     * @param list<array{text:string,x:float,y:float,top:float,bottom:float}> $blocks
     * @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>>
     */
    private function bboxGrid(array $blocks, bool $rtl): array
    {
        usort($blocks, static fn(array $left, array $right): int => $left['y'] <=> $right['y']);
        $sequence = $this->numberSequence($blocks);
        if (count($sequence) < 3) {
            throw new RuntimeException('تعذر تمييز صفوف الجدول من إحداثيات PDF. تأكد أن الصفحة تحتوي أرقام طلاب متسلسلة.');
        }

        $firstY = $sequence[0]['y'];
        $rowGaps = [];
        for ($index = 1; $index < count($sequence); $index++) $rowGaps[] = $sequence[$index]['y'] - $sequence[$index - 1]['y'];
        sort($rowGaps);
        $rowGap = $rowGaps[(int) floor(count($rowGaps) / 2)] ?? 20.0;
        $headerBlocks = array_values(array_filter($blocks, static fn(array $block): bool =>
            $block['y'] < $firstY - 2 && $block['y'] >= $firstY - max(180.0, $rowGap * 6)
        ));
        $headerBlocks = $this->headerBand($headerBlocks, $rowGap);
        $columns = $this->bboxColumns($headerBlocks, $rowGap);
        $columns = $this->shortTestsColumns($blocks, $columns, $rtl);
        if (count($columns) < 2) throw new RuntimeException('تعذر تمييز أعمدة الجدول من إحداثيات PDF.');

        $header = [];
        foreach ($columns as $column) {
            $text = $rtl ? $this->canonicalHeader($column['text']) : $column['text'];
            $header[] = $this->bboxCell($text, true);
        }
        $header = $rtl ? array_reverse($header) : $header;
        $rows = $this->repeatedHeaderRows($header) ?? [$header];
        foreach ($sequence as $number) {
            $cells = array_fill(0, count($columns), '');
            foreach ($blocks as $block) {
                if (abs($block['y'] - $number['y']) > max(5.0, $rowGap * 0.3)) continue;
                $nearest = 0;
                $distance = INF;
                foreach ($columns as $index => $column) {
                    $candidate = abs($column['x'] - $block['x']);
                    if ($candidate < $distance) { $distance = $candidate; $nearest = $index; }
                }
                $cells[$nearest] = trim($cells[$nearest] . ' ' . $block['text']);
            }
            $row = array_map(fn(string $text): array => $this->bboxCell($text), $cells);
            $rows[] = $rtl ? array_reverse($row) : $row;
        }
        return $rows;
    }

    /** @return list<array{x:float,text:string}> */
    private function shortTestsColumns(array $blocks, array $columns, bool $rtl): array
    {
        if (!$rtl || count($columns) !== 2) return $columns;
        $pageText = preg_replace('/[\sـ\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]+/u', '', implode(' ', array_column($blocks, 'text'))) ?? '';
        $hasTests = str_contains($pageText, 'اختبارات') || str_contains($pageText, 'االختبارات');
        if (!$hasTests || (!str_contains($pageText, 'قصير') && !str_contains($pageText, 'قصر'))) return $columns;

        $byText = [];
        foreach ($columns as $column) $byText[$this->canonicalHeader($column['text'])] = $column;
        $number = $byText['الرقم'] ?? $columns[array_key_last($columns)];
        $student = $byText['اسم الطالبة'] ?? $byText['اسم الطالب'] ?? $columns[0];
        $studentX = (float) $student['x'];
        $numberX = (float) $number['x'];
        $step = max(24.0, abs($numberX - $studentX));

        return [
            ['x' => $studentX - $step * 5, 'text' => 'ملاحظات'],
            ['x' => $studentX - $step * 4, 'text' => 'المجموع (30)'],
            ['x' => $studentX - $step * 3, 'text' => 'الاختبار 3 (10)'],
            ['x' => $studentX - $step * 2, 'text' => 'الاختبار 2 (10)'],
            ['x' => $studentX - $step, 'text' => 'الاختبار 1 (10)'],
            ['x' => $studentX, 'text' => 'اسم الطالبة'],
            ['x' => $numberX, 'text' => 'الرقم'],
        ];
    }

    /** @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>>|null */
    private function repeatedHeaderRows(array $header): ?array
    {
        if (count($header) < 8) return null;
        $identity = array_slice($header, 0, 2);
        $repeated = array_slice($header, 2);
        if (count($repeated) % 2 !== 0) return null;

        $size = intdiv(count($repeated), 2);
        $first = array_slice($repeated, 0, $size);
        $second = array_slice($repeated, $size);
        if (array_column($first, 'text') !== array_column($second, 'text')) return null;
        if (!array_filter($first, fn(array $cell): bool => str_contains($cell['text'], 'المجموع'))) return null;

        foreach ($identity as &$cell) $cell['rowspan'] = 2;
        unset($cell);
        $firstGroup = $this->bboxCell('المجموعة 1', true);
        $firstGroup['colspan'] = $size;
        $secondGroup = $this->bboxCell('المجموعة 2', true);
        $secondGroup['colspan'] = $size;

        return [[...$identity, $firstGroup, $secondGroup], [...$first, ...$second]];
    }

    /** @return list<array{text:string,x:float,y:float,top:float,bottom:float}> */
    private function numberSequence(array $blocks): array
    {
        $best = [];
        foreach ($blocks as $start => $block) {
            if ($this->sequenceNumber($block['text']) !== 1) continue;
            $sequence = [$block];
            $expected = 2;
            for ($index = $start + 1; $index < count($blocks); $index++) {
                if ($this->sequenceNumber($blocks[$index]['text']) !== $expected) continue;
                if (abs($blocks[$index]['x'] - $block['x']) > 18 || $blocks[$index]['y'] <= end($sequence)['y']) continue;
                $sequence[] = $blocks[$index];
                $expected++;
            }
            if (count($sequence) > count($best)) $best = $sequence;
        }
        return $best;
    }

    private function sequenceNumber(string $text): ?int
    {
        $text = strtr(trim($text), ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        return preg_match('/^\.?\s*(\d+)\s*\.?$/u', $text, $match) ? (int) $match[1] : null;
    }

    /**
     * Start at the cells reaching furthest down toward the first student, then walk upward only
     * through overlapping table cells. A nearby page title is separated by a real vertical gap.
     *
     * @return list<array{text:string,x:float,y:float,top:float,bottom:float}>
     */
    private function headerBand(array $blocks, float $rowGap): array
    {
        if (!$blocks) return [];
        $deepest = max(array_column($blocks, 'bottom'));
        $selected = array_values(array_filter($blocks, static fn(array $block): bool =>
            $block['bottom'] >= $deepest - max(6.0, $rowGap * 0.35)
        ));

        $changed = true;
        while ($changed) {
            $changed = false;
            $bandTop = min(array_column($selected, 'top'));
            $minimumX = min(array_column($selected, 'x'));
            $maximumX = max(array_column($selected, 'x'));
            foreach ($blocks as $block) {
                if (in_array($block, $selected, true)) continue;
                $overlaps = $block['bottom'] >= $bandTop && $block['top'] <= $deepest;
                $isAdjacentRow = false;
                if ($block['bottom'] >= $bandTop - max(4.0, $rowGap * 0.6)) {
                    foreach ($blocks as $peer) {
                        if (abs($peer['y'] - $block['y']) <= 3
                            && $peer['x'] >= $minimumX - 12 && $peer['x'] <= $maximumX + 12) {
                            $isAdjacentRow = true;
                            break;
                        }
                    }
                }
                if (!$overlaps && !$isAdjacentRow) continue;
                $selected[] = $block;
                $changed = true;
            }
        }
        return $selected;
    }

    /** @return list<array{x:float,text:string}> */
    private function bboxColumns(array $blocks, float $rowGap): array
    {
        $blocks = array_values(array_filter($blocks, fn(array $block): bool => !$this->isPageHeading($block['text'])));
        if (!$blocks) return [];
        $deepest = max(array_column($blocks, 'bottom'));
        $leaves = array_values(array_filter($blocks, static fn(array $block): bool =>
            $block['bottom'] >= $deepest - max(6.0, $rowGap * 0.6)
        ));
        $minimum = min(array_column($leaves, 'x'));
        $maximum = max(array_column($leaves, 'x'));
        $candidates = $leaves;
        foreach ($blocks as $block) {
            if ($block['bottom'] >= $deepest - max(6.0, $rowGap * 0.35)) continue;
            if ($block['x'] < $minimum - 12 || $block['x'] > $maximum + 12) $candidates[] = $block;
        }
        usort($candidates, static fn(array $left, array $right): int => $left['x'] <=> $right['x']);
        $columns = [];
        foreach ($candidates as $block) {
            $last = array_key_last($columns);
            if ($last !== null && abs($columns[$last]['x'] - $block['x']) <= 16) {
                $columns[$last]['text'] = trim($columns[$last]['text'] . ' ' . $block['text']);
                $columns[$last]['x'] = ($columns[$last]['x'] + $block['x']) / 2;
            } else {
                $columns[] = ['x' => $block['x'], 'text' => $block['text']];
            }
        }
        return $columns;
    }

    /** Page metadata must never become a grade-table column. */
    private function isPageHeading(string $text): bool
    {
        $plain = preg_replace('/[\sـ]+/u', '', $this->plain($text)) ?? $text;
        if ($plain === '') return true;
        $letters = preg_replace('/[^\p{Arabic}A-Za-z0-9]+/u', '', $plain) ?? $plain;
        if ((str_contains($letters, 'مجموعة') && str_contains($letters, 'مدارس'))
            || (str_contains($letters, 'مدارس') && str_contains($letters, 'الجامعة'))) return true;
        foreach (['مبحث', 'الفصلالدراسي', 'الصفالسادس', 'الصفالسابع', 'الصفالثامن', 'الصفالتاسع', 'قالبمستورد'] as $heading) {
            if (str_contains($letters, $heading)) return true;
        }
        if ((str_contains($letters, 'تقييم') || str_contains($letters, 'التقييم') || str_contains($letters, 'اختبارات'))
            && !str_contains($letters, 'العلامة')) return true;
        return preg_match('/20\d{2}[\/-]?20?\d{2}/u', $letters) === 1;
    }

    /** @return array{text:string,colspan:int,rowspan:int,header:bool} */
    private function bboxCell(string $text, bool $header = false): array
    {
        return ['text' => trim($text), 'colspan' => 1, 'rowspan' => 1, 'header' => $header];
    }

    private function phpPage(string $path, int $page): \Smalot\PdfParser\Page
    {
        $pages = (new \Smalot\PdfParser\Parser())->parseFile($path)->getPages();
        if (!isset($pages[$page - 1])) {
            throw new RuntimeException("صفحة PDF رقم {$page} غير موجودة.");
        }

        return $pages[$page - 1];
    }

    /** @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>> */
    private function coordinateRows(\Smalot\PdfParser\Page $page, ?bool $rtl, string $reference = ''): array
    {
        $lines = $this->coordinateLines($page);
        if (!$lines) throw new RuntimeException('لا يحتوي هذا الـPDF على نص قابل للقراءة.');

        $gaps = [];
        for ($index = 1; $index < count($lines); $index++) {
            $gaps[] = abs($lines[$index - 1]['y'] - $lines[$index]['y']);
        }
        sort($gaps);
        $medianGap = $gaps ? $gaps[(int) floor(count($gaps) / 2)] : 0;
        $blocks = [[]];
        foreach ($lines as $index => $line) {
            if ($index > 0 && $medianGap > 0 && abs($lines[$index - 1]['y'] - $line['y']) > max(18, $medianGap * 1.6)) {
                $blocks[] = [];
            }
            $blocks[array_key_last($blocks)][] = $line;
        }
        usort($blocks, static fn(array $left, array $right): int => count($right) <=> count($left));
        $table = $blocks[0] ?? [];
        if (count($table) < 2) throw new RuntimeException('تعذر تمييز جدول في هذه الصفحة.');

        $threshold = 15.0;
        $richest = [];
        foreach ($table as $line) {
            $runs = $this->coordinateRuns($line['items'], $threshold);
            if (count($runs) > count($richest)) $richest = $runs;
        }
        if (count($richest) < 2) throw new RuntimeException('تعذر تمييز أعمدة الجدول من إحداثيات النص.');

        $centers = array_map(static fn(array $run): float => ($run['from'] + $run['to']) / 2, $richest);
        $boundaries = [];
        for ($index = 1; $index < count($centers); $index++) {
            $boundaries[] = ($centers[$index - 1] + $centers[$index]) / 2;
        }
        $pageText = '';
        foreach ($table as $line) {
            foreach ($line['items'] as $item) $pageText .= ' ' . $item['text'];
        }
        $rightToLeft = $rtl ?? $this->looksArabic($pageText);
        $visual = $this->visualOrder($pageText);
        $spellings = $visual ? $this->spellings($reference) : [];

        $rows = [];
        foreach ($table as $lineIndex => $line) {
            $cells = array_fill(0, count($centers), '');
            foreach ($line['items'] as $item) {
                $slot = 0;
                while (isset($boundaries[$slot]) && $item['x'] > $boundaries[$slot]) $slot++;
                $cells[$slot] .= $item['text'];
            }
            $row = array_map(fn(string $text): array => [
                'text' => $visual ? $this->respell($this->logicalRtl($text), $spellings) : trim($text),
                'colspan' => 1,
                'rowspan' => 1,
                'header' => $lineIndex === 0,
            ], $cells);
            $rows[] = $rightToLeft ? array_reverse($row) : $row;
        }

        if ($rightToLeft && $rows) {
            foreach ($rows[0] as &$cell) $cell['text'] = $this->canonicalHeader($cell['text']);
            unset($cell);
            foreach (array_slice($rows, 1, null, true) as $index => $row) {
                if (!$visual) continue; // الرقم يسبق الاسم أصلًا في نص منطقي، فلا شيء يُفصل
                $identity = trim(($row[0]['text'] ?? '').($row[1]['text'] ?? ''));
                if (preg_match('/^(.*?)(\d+)$/u', $identity, $match)) {
                    $rows[$index][0]['text'] = $match[2];
                    $rows[$index][1]['text'] = trim($match[1]);
                }
            }
        }

        return $rows;
    }

    /** @return list<array{y:float,items:list<array{x:float,text:string}>}> */
    private function coordinateLines(\Smalot\PdfParser\Page $page): array
    {
        $lines = [];
        foreach ($page->getDataTm() as $entry) {
            $matrix = $entry[0] ?? null;
            $text = (string) ($entry[1] ?? '');
            if (!is_array($matrix) || count($matrix) < 6 || $text === '') continue;
            $y = (float) $matrix[5];
            $lineIndex = null;
            foreach ($lines as $index => $line) {
                if (abs($line['y'] - $y) <= 5) { $lineIndex = $index; break; }
            }
            if ($lineIndex === null) {
                $lineIndex = count($lines);
                $lines[] = ['y' => $y, 'items' => []];
            }
            $lines[$lineIndex]['items'][] = ['x' => (float) $matrix[4], 'text' => $text];
        }
        usort($lines, static fn(array $left, array $right): int => $right['y'] <=> $left['y']);
        foreach ($lines as &$line) usort($line['items'], static fn(array $left, array $right): int => $left['x'] <=> $right['x']);
        unset($line);
        return $lines;
    }

    /** @return list<array{from:float,to:float,text:string}> */
    private function coordinateRuns(array $items, float $threshold): array
    {
        $runs = [];
        foreach ($items as $item) {
            $last = array_key_last($runs);
            if ($last === null || $item['x'] - $runs[$last]['to'] > $threshold) {
                $runs[] = ['from' => $item['x'], 'to' => $item['x'], 'text' => $item['text']];
            } else {
                $runs[$last]['to'] = $item['x'];
                $runs[$last]['text'] .= $item['text'];
            }
        }
        return $runs;
    }

    private function logicalRtl(string $text): string
    {
        // الأرقام والكلمات اللاتينية تُرسم من اليسار لليمين، فالعكس الشامل هو ما يقلبها وحدها
        $logical = implode('', array_reverse(mb_str_split(trim($text))));
        $logical = preg_replace_callback('/[A-Za-z0-9\/.]+/', static fn(array $match): string => strrev($match[0]), $logical) ?? $logical;
        return strtr($logical, ['(' => ')', ')' => '(']);
    }

    /**
     * Turns painted order into reading order, and only when it really is painted order:
     * `-layout` hands back Arabic already reordered while the bbox modes and the raw text
     * matrices do not, so reversing on the strength of the script alone mirrors every cell
     * of a sheet that arrived correct. Arabic decides it — ة and ى can only close a word and
     * the article ال can only open one, so the direction that yields more of them wins.
     *
     * @param list<array{text:string,...}> $blocks
     * @return list<array{text:string,...}>
     */
    private function readingOrder(array $blocks, string $reference = ''): array
    {
        if (!$blocks || !$this->visualOrder(implode(' ', array_column($blocks, 'text')))) return $blocks;

        $spellings = $this->spellings($reference);
        foreach ($blocks as $index => $block) {
            $blocks[$index]['text'] = $this->respell($this->logicalRtl($block['text']), $spellings);
        }
        return $blocks;
    }

    private function visualOrder(string $text): bool
    {
        $reversed = implode('', array_reverse(mb_str_split($text)));
        return $this->readingScore($reversed) > $this->readingScore($text);
    }

    private function readingScore(string $text): int
    {
        $score = 0;
        foreach (preg_split('/\s+/u', $text) ?: [] as $word) {
            if (mb_strlen($word) < 3 || !preg_match('/^[\x{0621}-\x{064A}]+$/u', $word)) continue;
            $first = mb_substr($word, 0, 1);
            if (str_starts_with($word, 'ال')) $score++;
            if ($first === 'ة' || $first === 'ى') $score--;
            if (str_ends_with($word, 'ة') || str_ends_with($word, 'ى')) $score++;
            if (str_ends_with($word, 'لا')) $score--;
        }
        return $score;
    }

    /**
     * A glyph that carries a ligature maps back to two letters in reading order, so reversing
     * the run leaves that pair — لا, لج, مج and their kin — the wrong way round: «سلامة» comes
     * out «سالمة». The same words spelled correctly by `-layout` are indexed by their letters,
     * which the swap preserves, and any word that misses is re-spelled from its twin there.
     *
     * @return array<string,string>
     */
    private function spellings(string $reference): array
    {
        $spellings = [];
        // كلمات المرجع تُلتقط بالنمط نفسه الذي يُصلَّح به، وإلا فاتت كلمة لصقها -layout برقم
        preg_match_all('/[\x{0621}-\x{064A}]{3,}/u', $this->plain($reference), $matches);
        foreach ($matches[0] as $word) {
            $key = $this->letterKey($word);
            $spellings[$key] = isset($spellings[$key]) && $spellings[$key] !== $word ? '' : $word;
        }
        return array_filter($spellings);
    }

    /** @param array<string,string> $spellings */
    private function respell(string $text, array $spellings): string
    {
        if (!$spellings) return $text;

        return preg_replace_callback('/[\x{0621}-\x{064A}]{3,}/u', function (array $match) use ($spellings): string {
            $key = $this->letterKey($match[0]);
            return isset($spellings[$key]) ? $spellings[$key] : $match[0];
        }, $text) ?? $text;
    }

    private function letterKey(string $word): string
    {
        $letters = mb_str_split($word);
        sort($letters);
        return implode('', $letters);
    }

    /** Poppler wraps every RTL run in bidi controls, which would otherwise reach the column keys. */
    private function plain(string $text): string
    {
        return preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', $text) ?? $text;
    }

    private function canonicalHeader(string $text): string
    {
        $compact = preg_replace('/[\sـ\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]+/u', '', $text) ?? $text;
        $mark = preg_match('/\(?\s*(\d+)\s*\)?/u', $compact, $match) ? " ({$match[1]})" : '';
        $trailingMark = isset($match[1]) ? ' '.$match[1] : '';

        return match (true) {
            str_contains($compact, 'تسلسل') => 'التسلسل',
            str_contains($compact, 'رقم') => 'الرقم',
            str_contains($compact, 'اسم') && str_contains($compact, 'طالبة') => 'اسم الطالبة',
            str_contains($compact, 'سمالطالب') || (str_contains($compact, 'اسم') && str_contains($compact, 'طالب')) => 'اسم الطالب',
            str_contains($compact, 'التزام') && str_contains($compact, 'وقت') => 'الالتزام بالوقت'.$trailingMark,
            str_contains($compact, 'سلامة') && str_contains($compact, 'لغة') => 'سلامة اللغة'.$trailingMark,
            str_contains($compact, 'ثقة') && str_contains($compact, 'نفس') && str_contains($compact, 'طلاقة') => 'الثقة بالنفس وطلاقة الحديث'.$trailingMark,
            str_contains($compact, 'التزام') && str_contains($compact, 'موضو') && str_contains($compact, 'ترتيب') => 'الالتزام بالموضوع وترتيب الأفكار'.$trailingMark,
            str_contains($compact, 'تلوين') && str_contains($compact, 'صوت') => 'التلوين الصوتي'.$trailingMark,
            str_contains($compact, 'استماع') => 'الاستماع'.$mark,
            str_contains($compact, 'محادثة') => 'المحادثة'.$mark,
            str_contains($compact, 'تعبير') => 'التعبير'.$mark,
            str_contains($compact, 'اختبار') && str_contains($compact, 'كتابي') => 'الاختبار الكتابي'.$mark,
            str_contains($compact, 'علامة') && str_contains($compact, 'نهائية') => 'العلامة النهائية'.$mark,
            str_contains($compact, 'قراءة') => 'القراءة'.$mark,
            str_contains($compact, 'كتابة') => 'الكتابة'.$mark,
            str_contains($compact, 'قواعد') => 'القواعد'.$mark,
            str_contains($compact, 'ملاء') => 'الإملاء'.$mark,
            str_contains($compact, 'مجموع') => 'المجموع'.$mark,
            str_contains($compact, 'نسبة') => 'النسبة %'.$mark,
            default => preg_replace('/\b20\d{2}\b/u', '', trim($text)) ?? trim($text),
        };
    }

    private function coordinateLayout(\Smalot\PdfParser\Page $page): string
    {
        $items = [];
        foreach ($page->getDataTm() as $entry) {
            $matrix = $entry[0] ?? null;
            $text = trim((string) ($entry[1] ?? ''));
            if (!is_array($matrix) || count($matrix) < 6 || $text === '') continue;

            $items[] = [
                'x' => (float) $matrix[4],
                'y' => (float) $matrix[5],
                'text' => preg_replace('/\s+/u', ' ', $text) ?: $text,
            ];
        }
        if (!$items) return $page->getText();
        $items = $this->readingOrder($items);

        usort($items, static fn(array $left, array $right): int =>
            abs($left['y'] - $right['y']) <= 2
                ? $left['x'] <=> $right['x']
                : $right['y'] <=> $left['y']
        );

        $minimumX = min(array_column($items, 'x'));
        $lines = [];
        foreach ($items as $item) {
            $lineKey = null;
            foreach (array_keys($lines) as $y) {
                if (abs((float) $y - $item['y']) <= 2) {
                    $lineKey = $y;
                    break;
                }
            }
            $lineKey ??= (string) $item['y'];
            $lines[$lineKey] ??= ['y' => $item['y'], 'items' => []];
            $lines[$lineKey]['items'][] = $item;
        }

        $verticalGaps = [];
        $lineValues = array_values($lines);
        for ($index = 1; $index < count($lineValues); $index++) {
            $verticalGaps[] = abs($lineValues[$index - 1]['y'] - $lineValues[$index]['y']);
        }
        sort($verticalGaps);
        $medianGap = $verticalGaps ? $verticalGaps[(int) floor(count($verticalGaps) / 2)] : 0;

        $rendered = [];
        $previousY = null;
        foreach ($lineValues as $lineData) {
            if ($previousY !== null && $medianGap > 0 && abs($previousY - $lineData['y']) > max(18, $medianGap * 1.6)) {
                $rendered[] = self::BLOCK_GAP;
            }
            $previousY = $lineData['y'];
            $line = $lineData['items'];
            usort($line, static fn(array $left, array $right): int => $left['x'] <=> $right['x']);
            $text = '';
            foreach ($line as $item) {
                $column = max(0, (int) round(($item['x'] - $minimumX) / 6));
                $text .= str_repeat(' ', max(0, $column - mb_strlen($text)));
                $text .= $item['text'];
            }
            $rendered[] = rtrim($text);
        }

        return implode("\n", $rendered);
    }

    // ---------------------------------------------------------------- layout

    /**
     * The largest run of consecutive lines that look like table rows: at least two
     * text runs separated by a real gap.
     *
     * @return list<list<string>> lines as character arrays
     */
    private function tableBlock(string $text): array
    {
        $best = [];
        $current = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = rtrim(str_replace("\t", '    ', $line));
            if (trim($line) === '') continue; // pdftotext يترك أسطرًا فارغة بين صفوف الجدول
            if (count(preg_split('/ {' . self::MIN_GAP . ',}/u', trim($line)) ?: []) >= 2) {
                $current[] = mb_str_split($line);
                continue;
            }
            if (count($current) > count($best)) $best = $current;
            $current = [];
        }
        return count($current) > count($best) ? $current : $best;
    }

    /**
     * @param list<list<string>> $lines
     * @return list<array{0:int,1:int}> inclusive character ranges, left to right
     */
    private function columnSlots(array $lines): array
    {
        $reference = $this->boundaryLines($lines);
        $width = 0;
        foreach ($reference as $line) $width = max($width, count($line));
        $blank = array_fill(0, $width, true);
        foreach ($reference as $line) {
            foreach ($line as $position => $character) {
                if ($character !== ' ') $blank[$position] = false;
            }
        }
        return $this->widen($this->segments($blank, $width));
    }

    /**
     * Body cells are narrower than their column, so each band is stretched to the midpoint
     * between its neighbours. Without this a wide header word falls into the gap between
     * two narrow number columns and is lost.
     *
     * @param list<array{0:int,1:int}> $slots
     * @return list<array{0:int,1:int}>
     */
    private function widen(array $slots): array
    {
        $last = count($slots) - 1;
        $widened = [];
        foreach ($slots as $index => [$start, $end]) {
            $widened[] = [
                $index === 0 ? 0 : (int) floor(($slots[$index - 1][1] + $start) / 2) + 1,
                $index === $last ? PHP_INT_MAX : (int) floor(($end + $slots[$index + 1][0]) / 2),
            ];
        }
        return $widened;
    }

    /**
     * Boundaries come from the body rows, never from the header: a header cell that spans
     * several columns fills the gap between them and would weld them into one band. Body
     * rows all carry the same number of runs, so the most repeated run count identifies them.
     *
     * @param list<list<string>> $lines
     * @return list<list<string>>
     */
    private function boundaryLines(array $lines): array
    {
        $counts = [];
        foreach ($lines as $index => $line) $counts[$index] = count($this->lineRuns($line));
        $frequency = array_count_values($counts);
        $best = 0;
        foreach ($frequency as $runCount => $howOften) {
            if ($howOften > ($frequency[$best] ?? 0) || ($howOften === ($frequency[$best] ?? 0) && $runCount > $best)) $best = $runCount;
        }
        $richest = max($counts ?: [0]);
        if ($best <= 2 && $richest > $best) $best = $richest;
        $selected = [];
        foreach ($counts as $index => $count) {
            if ($count === $best) $selected[] = $lines[$index];
        }
        return $selected ?: $lines;
    }

    /**
     * @param list<string> $line
     * @return list<array{0:int,1:int}>
     */
    private function lineRuns(array $line): array
    {
        $blank = [];
        $count = count($line);
        for ($position = 0; $position < $count; $position++) $blank[$position] = $line[$position] === ' ';
        return $this->segments($blank, $count);
    }

    /**
     * Maximal runs of filled positions; a blank stretch shorter than MIN_GAP does not split one.
     *
     * @param array<int,bool> $blank
     * @return list<array{0:int,1:int}>
     */
    private function segments(array $blank, int $width): array
    {
        $segments = [];
        $position = 0;
        while ($position < $width) {
            if ($blank[$position]) { $position++; continue; }
            $start = $position;
            $end = $position;
            while ($position < $width) {
                if (!$blank[$position]) { $end = $position++; continue; }
                $gap = 0;
                while ($position + $gap < $width && $blank[$position + $gap]) $gap++;
                if ($gap >= self::MIN_GAP || $position + $gap >= $width) break;
                $position += $gap;
            }
            $segments[] = [$start, $end];
        }
        return $segments;
    }

    /**
     * @param list<string> $line
     * @param list<array{0:int,1:int}> $slots
     * @return list<array{text:string,colspan:int,rowspan:int,header:bool}>
     */
    private function splitLine(array $line, array $slots): array
    {
        $runs = $this->lineRuns($line);
        if (!$runs) return [];

        // كل مقطع نصي يحجز خانات الأعمدة التي يتقاطع معها؛ فيصبح الرأس الممتد خلية بعرض عدة أعمدة
        $owner = array_fill(0, count($slots), null);
        $texts = [];
        foreach ($runs as $index => [$from, $to]) {
            $texts[$index] = trim(implode('', array_slice($line, $from, $to - $from + 1)));
            $taken = false;
            foreach ($this->claimedSlots($slots, $from, $to) as $slot) {
                if ($owner[$slot] !== null) continue;
                $owner[$slot] = $index;
                $taken = true;
            }
            if (!$taken) $this->attachToNearest($owner, $texts, $slots, $index, $from);
        }
        $this->expandCenteredHeaders($owner, $texts);

        $cells = [];
        for ($slot = 0; $slot < count($slots); $slot++) {
            $index = $owner[$slot];
            if ($index === null) { $cells[] = $this->emptyCell(); continue; }
            $span = 1;
            while (($owner[$slot + $span] ?? null) === $index) $span++;
            $cells[] = ['text' => $texts[$index], 'colspan' => $span, 'rowspan' => 1, 'header' => false];
            $slot += $span - 1;
        }
        return $cells;
    }

    /** pdftotext may center a group title inside one slot and leave its covered neighbours blank. */
    private function expandCenteredHeaders(array &$owner, array $texts): void
    {
        foreach ($texts as $text) {
            if (preg_match('/^\d+(?:[.\/]\d+)*$/u', $text)) return;
        }
        foreach ($texts as $index => $text) {
            if (!preg_match('/\s/u', $text)) continue;
            $owned = array_keys($owner, $index, true);
            if (count($owned) !== 1) continue;
            $slot = $owned[0];
            if ($slot > 0 && $owner[$slot - 1] === null) $owner[$slot - 1] = $index;
            if (isset($owner[$slot + 1]) && $owner[$slot + 1] === null) $owner[$slot + 1] = $index;
        }
    }

    /**
     * The columns a text run covers. A band it barely grazes is dropped, so a header that
     * merely leans into its neighbour is not mistaken for a spanning cell.
     *
     * @param list<array{0:int,1:int}> $slots
     * @return list<int>
     */
    private function claimedSlots(array $slots, int $from, int $to): array
    {
        $overlaps = [];
        foreach ($slots as $slot => [$start, $end]) {
            $overlap = min($to, $end) - max($from, $start) + 1;
            if ($overlap > 0) $overlaps[$slot] = $overlap;
        }
        if (!$overlaps) return [];
        $threshold = max(1, (int) ceil(($to - $from + 1) * 0.3));
        $claimed = array_keys(array_filter($overlaps, static fn(int $overlap): bool => $overlap >= $threshold));
        return range(min($claimed), max($claimed));
    }

    /** Text that lands in a gap, or on slots another run already took, joins its closest neighbour. */
    private function attachToNearest(array $owner, array &$texts, array $slots, int $index, int $from): void
    {
        $nearest = null;
        $distance = PHP_INT_MAX;
        foreach ($slots as $slot => [$slotStart, $slotEnd]) {
            if ($owner[$slot] === null) continue;
            $gap = min(abs($from - $slotStart), abs($from - $slotEnd));
            if ($gap >= $distance) continue;
            $distance = $gap;
            $nearest = $owner[$slot];
        }
        if ($nearest === null) return;
        $texts[$nearest] = trim($texts[$nearest] . ' ' . $texts[$index]);
    }

    /** @return array{text:string,colspan:int,rowspan:int,header:bool} */
    private function emptyCell(): array
    {
        return ['text' => '', 'colspan' => 1, 'rowspan' => 1, 'header' => false];
    }

    private function looksArabic(string $text): bool
    {
        return preg_match_all('/[\x{0600}-\x{06FF}]/u', $text) > preg_match_all('/[A-Za-z]/', $text);
    }

    /** @param list<string> $command */
    private function run(array $command): string
    {
        [$status, $output, $error] = $this->execute($command);
        if ($status !== 0 && trim($output) === '') {
            throw new RuntimeException('فشل استخراج النص من الملف: ' . trim($error !== '' ? $error : "رمز الخروج {$status}"));
        }
        return $output;
    }

    /**
     * @param list<string> $command
     * @return array{0:int,1:string,2:string}
     */
    private function execute(array $command): array
    {
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = @proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('تعذر تشغيل pdftotext على هذا الخادم.');
        }
        $output = (string) stream_get_contents($pipes[1]);
        $error = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output, $error];
    }
}
