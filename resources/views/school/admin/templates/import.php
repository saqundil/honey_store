<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

use App\Services\PdfTableExtractor;
use App\Services\TableImportService;
use App\Services\DocxTableExtractor;
use App\Services\TemplateService;
use App\Repositories\TemplateRepository;

const IMPORT_MAX_PASTE_BYTES = 4_000_000;
const IMPORT_MAX_PDF_BYTES = 8_000_000;
const IMPORT_MAX_DOCX_BYTES = 12_000_000;

$extractor = new PdfTableExtractor();
$pdfReady = $extractor->isAvailable();
$pdfWarning = $extractor->warning();
$docxExtractor = new DocxTableExtractor();
$docxReady = $docxExtractor->isAvailable();
$error = null;
$name = trim((string) ($_POST['name'] ?? ''));
$batchResult = $_SESSION['template_docx_result'] ?? null;
unset($_SESSION['template_docx_result']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $import = new TableImportService();
    try {
        if (isset($_POST['import_docx'])) {
            $file = $_FILES['docx'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('لم يصل الملف بشكل صحيح. اختر ملف Word بصيغة DOCX وأعد المحاولة.');
            }
            if (!$docxReady) throw new RuntimeException('امتداد ZIP المطلوب لقراءة ملفات Word غير مفعّل على الخادم.');
            if ($file['size'] > IMPORT_MAX_DOCX_BYTES) throw new RuntimeException('حجم ملف Word أكبر من 12MB.');
            if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'docx') {
                throw new RuntimeException('يجب أن يكون الملف بصيغة DOCX. احفظ ملفات DOC القديمة بصيغة DOCX أولًا.');
            }

            $tables = $docxExtractor->tables($file['tmp_name'], (string) $file['name']);
            $service = new TemplateService(db(), new TemplateRepository(db(), current_user_id(), is_super_admin()));
            $created = [];
            $failed = [];
            foreach ($tables as $index => $table) {
                $tableName = $table['name'];
                try {
                    $draft = $import->fromRows($table['rows'], $tableName);
                    $created[] = ['id' => $service->save($draft, current_user_id()), 'name' => $draft['name']];
                } catch (Throwable $exception) {
                    $failed[] = ['number' => $index + 1, 'name' => $tableName, 'message' => $exception->getMessage()];
                }
            }
            $_SESSION['template_docx_result'] = [
                'total' => count($tables),
                'created' => $created,
                'failed' => $failed,
            ];
            school_redirect('admin/templates/import.php?docx=done');
        } elseif (isset($_POST['import_pdf'])) {
            $file = $_FILES['pdf'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('لم يصل الملف بشكل صحيح. تأكد من اختيار ملف PDF وإعادة المحاولة.');
            }
            if ($file['size'] > IMPORT_MAX_PDF_BYTES) {
                throw new RuntimeException('حجم الملف أكبر من 8MB.');
            }
            if (file_get_contents($file['tmp_name'], false, null, 0, 5) !== '%PDF-') {
                throw new RuntimeException('الملف ليس PDF صالحًا.');
            }
            if (!$pdfReady) {
                throw new RuntimeException('لا يتوفر محرك لقراءة PDF على هذا الخادم. استخدم لصق الجدول من Word أو Excel.');
            }
            $direction = (string) ($_POST['direction'] ?? 'auto');
            $rows = $extractor->rows($file['tmp_name'], (int) ($_POST['page'] ?? 1), $direction === 'auto' ? null : $direction === 'rtl');
            $draft = $import->fromRows($rows, $name);
        } else {
            $html = (string) ($_POST['pasted_html'] ?? '');
            $text = (string) ($_POST['pasted_text'] ?? '');
            if (strlen($html) > IMPORT_MAX_PASTE_BYTES) {
                throw new RuntimeException('المحتوى الملصق كبير جدًا. الصق الرؤوس وصفًا أو صفين فقط، لا كشف الطلاب كاملًا.');
            }
            if (trim(strip_tags($html)) === '' && trim($text) === '') {
                throw new RuntimeException('الصق الجدول في المساحة المخصصة أولًا.');
            }
            $draft = stripos($html, '<table') !== false ? $import->fromHtml($html, $name) : $import->fromDelimitedText($text !== '' ? $text : strip_tags($html), $name);
        }
        $_SESSION['template_import'] = $draft;
        school_redirect('admin/templates/edit.php?import=1');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$initialMode = isset($_POST['import_docx']) ? 'word' : (isset($_POST['import_pdf']) ? 'pdf' : 'paste');
$importStylesheet = dirname(__DIR__, 2) . '/assets/css/template-import.css';
page_header('استيراد قالب', 'templates', ['assets/css/template-import.css?v=' . filemtime($importStylesheet)]);
?>
<div class="import-page">

    <header class="import-header">
        <a class="import-back" href="<?= school_e(school_url('admin/templates/index.php')) ?>">
            <span aria-hidden="true">→</span> القوالب
        </a>
        <h2>استيراد قالب من جدول جاهز</h2>
        <p>
            ارفع ملف Word لإنشاء قالب مستقل من كل جدول، أو الصق جدولًا واحدًا لبناء مسودة تراجعها في المحرر.
            تُقرأ الرؤوس ومجموعاتها، وتُخمّن أنواع الأعمدة وعلاماتها القصوى.
        </p>
    </header>

    <?php if ($error): ?><div class="alert error" role="alert"><?= school_e($error) ?></div><?php endif; ?>

    <?php if ($batchResult): ?>
        <section class="import-result" aria-live="polite">
            <div class="alert <?= $batchResult['failed'] ? 'error' : 'success' ?>">
                أُنشئ <?= count($batchResult['created']) ?> من أصل <?= (int) $batchResult['total'] ?> قالبًا من ملف Word.
            </div>
            <?php if ($batchResult['created']): ?>
                <div class="import-result-list">
                    <strong>القوالب المنشأة</strong>
                    <ul>
                        <?php foreach ($batchResult['created'] as $created): ?>
                            <li><a href="<?= school_e(school_url('admin/templates/edit.php?id=' . $created['id'])) ?>"><?= school_e($created['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($batchResult['failed']): ?>
                <div class="import-result-list failures">
                    <strong>جداول تحتاج مراجعة</strong>
                    <ul>
                        <?php foreach ($batchResult['failed'] as $failed): ?>
                            <li>الجدول <?= (int) $failed['number'] ?>، <?= school_e($failed['name']) ?>: <?= school_e($failed['message']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="import-form<?= $batchResult ? ' has-result' : '' ?>" data-initial-mode="<?= school_e($initialMode) ?>">
        <?= school_csrf_field() ?>

        <section class="import-card import-workspace">
            <div class="import-tabs" role="tablist" aria-label="طريقة استيراد القالب">
                <button type="button" class="import-tab" role="tab" id="tab-paste" aria-controls="panel-paste" data-import-mode="paste">نسخ ولصق</button>
                <button type="button" class="import-tab" role="tab" id="tab-word" aria-controls="panel-word" data-import-mode="word">ملف Word</button>
                <button type="button" class="import-tab" role="tab" id="tab-pdf" aria-controls="panel-pdf" data-import-mode="pdf">ملف PDF</button>
            </div>

            <div class="import-mode-panel" id="panel-paste" role="tabpanel" aria-labelledby="tab-paste" data-import-panel="paste">
                <header class="import-card-head">
                    <h3>الصق الجدول</h3>
                    <span class="badge best">الطريقة الموصى بها</span>
                </header>
                <p class="import-card-lede">انسخ الجدول نفسه من Word أو Excel والصقه هنا. يصل معه دمج خلايا الرؤوس فتُبنى المجموعات كما هي.</p>
                <div id="paste-target" class="paste-area" contenteditable="true" dir="rtl" role="textbox" aria-multiline="true" aria-label="مساحة لصق الجدول" data-placeholder="الصق الجدول هنا"></div>
                <input type="hidden" name="pasted_html" id="pasted-html">
                <input type="hidden" name="pasted_text" id="pasted-text">
                <div class="import-row">
                    <label class="import-name">اسم القالب<input name="name" value="<?= school_e($name) ?>" placeholder="يُملأ تلقائيًا عند تركه فارغًا"></label>
                    <div class="import-submit">
                        <span id="paste-state" class="hint">لم يُلصق شيء بعد</span>
                        <button class="button primary" type="submit" name="import_paste">بناء المسودة</button>
                    </div>
                </div>
            </div>

            <div class="import-mode-panel" id="panel-word" role="tabpanel" aria-labelledby="tab-word" data-import-panel="word" hidden>
                <header class="import-card-head">
                    <h3>ارفع ملف Word متعدد الجداول</h3>
                    <span class="badge best">إنشاء جماعي</span>
                </header>
                <p class="import-card-lede">يُنشأ قالب مستقل من كل جدول داخل ملف DOCX، وتُستخدم عناوين الجداول أسماءً لها تلقائيًا.</p>
                <div class="import-docx-fields">
                    <label class="file-drop" data-file-drop data-extension="docx" tabindex="0">
                        <input class="file-drop-input" type="file" name="docx" accept="application/vnd.openxmlformats-officedocument.wordprocessingml.document,.docx" <?= $docxReady ? '' : 'disabled' ?>>
                        <span class="file-drop-mark" aria-hidden="true">↑</span>
                        <strong>اسحب ملف Word وأفلته هنا</strong>
                        <span class="file-drop-help">أو اضغط لاختيار ملف DOCX</span>
                        <span class="file-drop-name" data-file-name>لم يتم اختيار ملف</span>
                    </label>
                </div>
                <?php if (!$docxReady): ?><p class="hint warn">امتداد ZIP غير مفعّل في PHP؛ فعّله لقراءة ملفات DOCX.</p><?php endif; ?>
                <div class="import-panel-foot">
                    <span class="hint">الحد الأقصى 12MB و100 جدول.</span>
                    <button class="button primary" type="submit" name="import_docx" <?= $docxReady ? '' : 'disabled' ?>>إنشاء القوالب</button>
                </div>
            </div>

            <div class="import-mode-panel" id="panel-pdf" role="tabpanel" aria-labelledby="tab-pdf" data-import-panel="pdf" hidden>
                <header class="import-card-head"><h3>استيراد جدول من PDF</h3><span class="badge">احتياطي</span></header>
                <p class="import-card-lede">ملفات PDF النصية فقط؛ تُستنتج الأعمدة من مواضع الكتابة وقد تحتاج النتيجة إلى مراجعة إضافية.</p>
                <?php if (!$pdfReady): ?>
                    <p class="hint warn">لا يتوفر محرك لقراءة PDF على هذا الخادم؛ استخدم النسخ واللصق.</p>
                <?php elseif ($pdfWarning !== ''): ?>
                    <p class="hint warn"><?= school_e($pdfWarning) ?></p>
                <?php endif; ?>
                <div class="import-pdf-fields">
                    <label class="file-drop" data-file-drop data-extension="pdf" tabindex="0">
                        <input class="file-drop-input" type="file" name="pdf" accept="application/pdf,.pdf" <?= $pdfReady ? '' : 'disabled' ?>>
                        <span class="file-drop-mark" aria-hidden="true">↑</span>
                        <strong>اسحب ملف PDF وأفلته هنا</strong>
                        <span class="file-drop-help">أو اضغط لاختيار الملف</span>
                        <span class="file-drop-name" data-file-name>لم يتم اختيار ملف</span>
                    </label>
                    <div class="import-pdf-settings">
                        <label>الصفحة<input type="number" name="page" value="1" min="1" step="1" <?= $pdfReady ? '' : 'disabled' ?>></label>
                        <label>اتجاه الأعمدة<select name="direction" <?= $pdfReady ? '' : 'disabled' ?>><option value="auto">تلقائي</option><option value="rtl">من اليمين لليسار</option><option value="ltr">من اليسار لليمين</option></select></label>
                    </div>
                </div>
                <div class="import-panel-foot">
                    <span class="hint">إن جاء ترتيب الأعمدة معكوسًا، بدّل الاتجاه وأعد المحاولة.</span>
                    <button class="button primary" type="submit" name="import_pdf" <?= $pdfReady ? '' : 'disabled' ?>>بناء المسودة</button>
                </div>
            </div>
        </section>
    </form>
</div>

<script>
(() => {
    const target = document.getElementById('paste-target');
    const html = document.getElementById('pasted-html');
    const text = document.getElementById('pasted-text');
    const state = document.getElementById('paste-state');
    const form = document.querySelector('.import-form');
    const tabs = [...document.querySelectorAll('[data-import-mode]')];
    const panels = [...document.querySelectorAll('[data-import-panel]')];

    const showMode = mode => {
        tabs.forEach(tab => {
            const selected = tab.dataset.importMode === mode;
            tab.classList.toggle('is-active', selected);
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.importPanel !== mode; });
    };

    tabs.forEach(tab => tab.addEventListener('click', () => showMode(tab.dataset.importMode)));
    showMode(form.dataset.initialMode || 'paste');

    document.querySelectorAll('[data-file-drop]').forEach(drop => {
        const input = drop.querySelector('.file-drop-input');
        const name = drop.querySelector('[data-file-name]');
        const extension = `.${drop.dataset.extension}`;

        const showFile = file => {
            const valid = !file || file.name.toLowerCase().endsWith(extension);
            drop.classList.toggle('has-file', Boolean(file) && valid);
            drop.classList.toggle('is-invalid', Boolean(file) && !valid);
            name.textContent = !file ? 'لم يتم اختيار ملف' : valid ? file.name : `يجب اختيار ملف ${extension.toUpperCase()}`;
        };

        input.addEventListener('change', () => showFile(input.files[0] || null));
        drop.addEventListener('keydown', event => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            input.click();
        });
        ['dragenter', 'dragover'].forEach(type => drop.addEventListener(type, event => {
            event.preventDefault();
            if (!input.disabled) drop.classList.add('is-dragging');
        }));
        ['dragleave', 'drop'].forEach(type => drop.addEventListener(type, event => {
            event.preventDefault();
            drop.classList.remove('is-dragging');
        }));
        drop.addEventListener('drop', event => {
            if (input.disabled) return;
            const file = event.dataTransfer.files[0];
            if (!file || !file.name.toLowerCase().endsWith(extension)) {
                showFile(file || null);
                return;
            }
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    /** تمييز العدد في العربية: مفرد، مثنى، جمع قلة (٣–١٠)، ثم تمييز مفرد منصوب. */
    const count = (n, one, two, few, many) =>
        n === 1 ? one
      : n === 2 ? two
      : (n % 100 >= 3 && n % 100 <= 10) ? `${n} ${few}`
      : `${n} ${many}`;

    const describe = () => {
        const table = target.querySelector('table');
        const rows = table ? table.querySelectorAll('tr').length : 0;

        // عدد الأعمدة الحقيقي = مجموع colspan في صف الرأس الأول،
        // لا عدد خلاياه: خلية «الشهر الأول» وحدها تغطي عمودين.
        const columns = table
            ? [...table.querySelector('tr').children]
                  .reduce((sum, cell) => sum + (parseInt(cell.getAttribute('colspan'), 10) || 1), 0)
            : 0;

        const words = target.innerText.trim();

        target.classList.toggle('has-content', rows > 0 || words !== '');
        state.classList.toggle('is-ready', rows > 0);

        state.textContent = rows
            ? `جدول من ${count(rows, 'صف واحد', 'صفين', 'صفوف', 'صفًا')}`
              + ` و${count(columns, 'عمود واحد', 'عمودين', 'أعمدة', 'عمودًا')} — جاهز`
            : (words ? 'نص بلا جدول؛ سيُقرأ سطرًا سطرًا' : 'لم يُلصق شيء بعد');
    };

    target.addEventListener('input', describe);
    target.addEventListener('paste', () => setTimeout(describe, 0));
    form.addEventListener('submit', () => {
        html.value = target.innerHTML;
        text.value = target.innerText;
    });
})();
</script>
<?php page_footer(); ?>
