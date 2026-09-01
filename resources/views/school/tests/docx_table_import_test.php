<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

use App\Services\DocxTableExtractor;
use App\Services\TableImportService;

if (!class_exists(ZipArchive::class)) {
    echo "docx_table_import_test: skipped (ZipArchive unavailable)\n";
    exit(0);
}

$documentXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p><w:r><w:t>قالب القراءة</w:t></w:r></w:p>
    <w:tbl>
      <w:tr>
        <w:tc><w:tcPr><w:vMerge w:val="restart"/><w:tcW w:w="600" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>م</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:vMerge w:val="restart"/><w:tcW w:w="2400" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>اسم الطالب</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:gridSpan w:val="2"/><w:tcW w:w="1800" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>التقويم</w:t></w:r></w:p></w:tc>
      </w:tr>
      <w:tr>
        <w:tc><w:tcPr><w:vMerge/><w:tcW w:w="600" w:type="dxa"/></w:tcPr><w:p/></w:tc>
        <w:tc><w:tcPr><w:vMerge/><w:tcW w:w="2400" w:type="dxa"/></w:tcPr><w:p/></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="900" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>القراءة 10</w:t></w:r></w:p></w:tc>
        <w:tc><w:tcPr><w:tcW w:w="900" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>المجموع 10</w:t></w:r></w:p></w:tc>
      </w:tr>
    </w:tbl>
    <w:p><w:r><w:t>قالب الكتابة</w:t></w:r></w:p>
    <w:tbl>
      <w:tr>
        <w:tc><w:p><w:r><w:t>الرقم</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>الاسم</w:t></w:r></w:p></w:tc>
        <w:tc><w:p><w:r><w:t>الكتابة (20)</w:t></w:r></w:p></w:tc>
      </w:tr>
    </w:tbl>
  </w:body>
</w:document>
XML;

$path = tempnam(sys_get_temp_dir(), 'docx-import-');
assert($path !== false);
$zip = new ZipArchive();
assert($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
$zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
$zip->addFromString('word/document.xml', $documentXml);
$zip->close();

try {
    $tables = (new DocxTableExtractor())->tables($path, 'اختبارات.docx');
    assert(count($tables) === 2);
    assert(array_column($tables, 'name') === ['قالب القراءة', 'قالب الكتابة']);

    $importer = new TableImportService();
    $first = $importer->fromRows($tables[0]['rows'], $tables[0]['name']);
    assert($first['name'] === 'قالب القراءة');
    assert(array_column($first['columns'], 'name') === ['م', 'اسم الطالب', 'القراءة', 'المجموع']);
    assert(count($first['groups']) === 1);
    assert($first['groups'][0]['name'] === 'التقويم');
    assert(array_column($first['columns'], 'max_mark') === ['', '', '10', '10']);

    $second = $importer->fromRows($tables[1]['rows'], $tables[1]['name']);
    assert($second['name'] === 'قالب الكتابة');
    assert(array_column($second['columns'], 'type') === ['student_number', 'student_name', 'manual_mark']);
    assert($second['columns'][2]['max_mark'] === '20');
} finally {
    @unlink($path);
}

echo "docx_table_import_test: OK\n";