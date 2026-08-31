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
 * Accuracy is far below a pasted Word/Excel table. Always review the draft in the builder.
 */
final class PdfTableExtractor
{
    private const MIN_GAP = 2;

    private string $binary;

    public function __construct(?string $binary = null)
    {
        $this->binary = $binary ?: (getenv('PDFTOTEXT_PATH') ?: 'pdftotext');
    }

    /** Xpdf's pdftotext exits 99 on -v while poppler's exits 0, so only the banner is checked. */
    public function isAvailable(): bool
    {
        try {
            [, $output, $error] = $this->execute([$this->binary, '-v']);
            return stripos($output . $error, 'pdftotext') !== false;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @return list<list<array{text:string,colspan:int,rowspan:int,header:bool}>>
     */
    public function rows(string $path, int $page = 1, ?bool $rtl = null): array
    {
        $text = $this->text($path, $page);
        if (trim($text) === '') {
            throw new RuntimeException('لا يحتوي هذا الـPDF على نص قابل للقراءة. الأرجح أنه ممسوح ضوئيًا كصورة؛ استخدم لصق الجدول من Word أو Excel.');
        }
        $lines = $this->tableBlock($text);
        if (count($lines) < 2) {
            throw new RuntimeException('تعذر تمييز جدول في هذه الصفحة. جرّب رقم صفحة آخر أو استخدم لصق الجدول.');
        }
        $slots = $this->columnSlots($lines);
        if (count($slots) < 2) {
            throw new RuntimeException('تعذر تمييز أعمدة الجدول؛ المسافات بين الأعمدة غير واضحة في النص المستخرج.');
        }
        $rightToLeft = $rtl ?? $this->looksArabic($text);

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
        return $this->run([$this->binary, '-layout', '-nopgbrk', '-enc', 'UTF-8', '-f', (string) $page, '-l', (string) $page, $path, '-']);
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
        $selected = [];
        foreach ($counts as $index => $count) {
            if ($count === $best) $selected[] = $lines[$index];
        }
        return count($selected) >= 2 ? $selected : $lines;
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
