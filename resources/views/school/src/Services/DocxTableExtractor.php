<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

final class DocxTableExtractor
{
    private const MAX_DOCUMENT_XML_BYTES = 16_000_000;
    private const MAX_TABLES = 100;

    public function isAvailable(): bool
    {
        return class_exists(ZipArchive::class) && class_exists(DOMDocument::class);
    }

    /**
     * @return list<array{name:string,rows:list<list<array{text:string,colspan:int,rowspan:int,header:bool,vertical:bool,width_mm:float|null}>>}>
     */
    public function tables(string $path, string $baseName = ''): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('امتداد ZIP المطلوب لقراءة ملفات Word غير مفعّل على الخادم.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('تعذر فتح ملف Word. تأكد من أنه ملف DOCX صالح.');
        }

        try {
            $entry = $zip->statName('word/document.xml');
            if (!$entry || (int) ($entry['size'] ?? 0) > self::MAX_DOCUMENT_XML_BYTES) {
                throw new RuntimeException('ملف Word غير صالح أو محتواه أكبر من الحد المسموح.');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }

        if (!is_string($xml) || $xml === '') {
            throw new RuntimeException('لم يُعثر على مستند Word داخل الملف.');
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) throw new RuntimeException('تعذر قراءة محتوى ملف Word.');

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $nodes = $xpath->query('//w:body/w:tbl');
        if (!$nodes || $nodes->length === 0) throw new RuntimeException('لا يحتوي ملف Word على أي جدول.');
        if ($nodes->length > self::MAX_TABLES) throw new RuntimeException('يحتوي الملف على أكثر من 100 جدول؛ قسّمه إلى ملفين ثم أعد المحاولة.');

        $fallback = trim(pathinfo($baseName, PATHINFO_FILENAME)) ?: 'قالب Word';
        $tables = [];
        foreach ($nodes as $index => $table) {
            if (!$table instanceof DOMElement) continue;
            $tables[] = [
                'name' => $this->tableName($table, $xpath, $fallback, $index + 1),
                'rows' => $this->tableRows($table, $xpath),
            ];
        }
        return $tables;
    }

    private function tableName(DOMElement $table, DOMXPath $xpath, string $fallback, int $number): string
    {
        for ($node = $table->previousSibling; $node; $node = $node->previousSibling) {
            if (!$node instanceof DOMElement || $node->localName !== 'p') continue;
            $name = $this->text($node, $xpath);
            if ($name !== '') return mb_substr($name, 0, 180);
        }
        return $fallback . ' - جدول ' . $number;
    }

    /** @return list<list<array{text:string,colspan:int,rowspan:int,header:bool,vertical:bool,width_mm:float|null}>> */
    private function tableRows(DOMElement $table, DOMXPath $xpath): array
    {
        $rows = [];
        $verticalMerges = [];
        foreach ($xpath->query('./w:tr', $table) ?: [] as $rowNode) {
            if (!$rowNode instanceof DOMElement) continue;
            $row = [];
            $column = 0;
            $continuedColumns = [];
            foreach ($xpath->query('./w:tc', $rowNode) ?: [] as $cellNode) {
                if (!$cellNode instanceof DOMElement) continue;
                $spanNode = $xpath->query('./w:tcPr/w:gridSpan', $cellNode)?->item(0);
                $colspan = max(1, (int) ($spanNode instanceof DOMElement ? $this->attribute($spanNode, 'val') : 1));
                $mergeNode = $xpath->query('./w:tcPr/w:vMerge', $cellNode)?->item(0);
                $mergeValue = $mergeNode instanceof DOMElement ? $this->attribute($mergeNode, 'val') : null;
                $isContinuation = $mergeNode instanceof DOMElement && $mergeValue !== 'restart';

                if ($isContinuation && isset($verticalMerges[$column])) {
                    [$originRow, $originCell] = $verticalMerges[$column];
                    $rows[$originRow][$originCell]['rowspan']++;
                    for ($offset = 0; $offset < $colspan; $offset++) $continuedColumns[$column + $offset] = true;
                    $column += $colspan;
                    continue;
                }

                $widthNode = $xpath->query('./w:tcPr/w:tcW', $cellNode)?->item(0);
                $widthType = $widthNode instanceof DOMElement ? $this->attribute($widthNode, 'type') : '';
                $width = $widthNode instanceof DOMElement ? (float) $this->attribute($widthNode, 'w') : 0.0;
                $directionNode = $xpath->query('./w:tcPr/w:textDirection', $cellNode)?->item(0);
                $direction = $directionNode instanceof DOMElement ? $this->attribute($directionNode, 'val') : '';
                $row[] = [
                    'text' => $this->text($cellNode, $xpath),
                    'colspan' => $colspan,
                    'rowspan' => 1,
                    'header' => false,
                    'vertical' => in_array($direction, ['tbRl', 'btLr', 'tbRlV'], true),
                    'width_mm' => $widthType === 'dxa' && $width > 0 ? $width * 25.4 / 1440 : null,
                ];
                $cellIndex = array_key_last($row);
                for ($offset = 0; $offset < $colspan; $offset++) {
                    $currentColumn = $column + $offset;
                    unset($verticalMerges[$currentColumn]);
                    if ($mergeValue === 'restart') $verticalMerges[$currentColumn] = [count($rows), $cellIndex];
                }
                $column += $colspan;
            }
            foreach (array_keys($verticalMerges) as $activeColumn) {
                if ($activeColumn < $column && !isset($continuedColumns[$activeColumn])) {
                    $origin = $verticalMerges[$activeColumn];
                    if ($origin[0] < count($rows)) unset($verticalMerges[$activeColumn]);
                }
            }
            if ($row) $rows[] = $row;
        }
        if (!$rows) throw new RuntimeException('أحد جداول Word فارغ أو غير قابل للقراءة.');
        return $rows;
    }

    private function text(DOMElement $node, DOMXPath $xpath): string
    {
        $paragraphs = [];
        foreach ($xpath->query('descendant-or-self::w:p', $node) ?: [] as $paragraph) {
            $parts = [];
            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) ?: [] as $part) {
                $parts[] = $part->localName === 't' ? $part->textContent : ' ';
            }
            $text = trim(implode('', $parts));
            if ($text !== '') $paragraphs[] = $text;
        }
        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $paragraphs)));
    }

    private function attribute(DOMElement $element, string $name): string
    {
        return $element->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', $name)
            ?: $element->getAttribute('w:' . $name);
    }
}