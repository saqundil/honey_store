<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

use App\Services\PdfTableExtractor;
use App\Services\TableImportService;

const IMPORT_MAX_PASTE_BYTES = 4_000_000;
const IMPORT_MAX_PDF_BYTES = 8_000_000;

$extractor = new PdfTableExtractor();
$pdfReady = $extractor->isAvailable();
$error = null;
$name = trim((string) ($_POST['name'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $import = new TableImportService();
    try {
        if (isset($_POST['import_pdf'])) {
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
                throw new RuntimeException('أداة pdftotext غير متوفرة على هذا الخادم، فلا يمكن قراءة الـPDF. استخدم لصق الجدول من Word أو Excel، أو اضبط PDFTOTEXT_PATH.');
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

page_header('استيراد قالب', 'templates', ['assets/css/template-import.css']);
?>
<div class="import-page">

    <header class="import-header">
        <a class="import-back" href="<?= school_e(school_url('admin/templates/index.php')) ?>">
            <span aria-hidden="true">→</span> القوالب
        </a>
        <h2>استيراد قالب من جدول جاهز</h2>
        <p>
            يُقرأ <strong>رأس الجدول فقط</strong> فتُبنى منه مسودة: الأعمدة، ومجموعات الرؤوس،
            وتخمين لأنواعها وعلاماتها القصوى. تُفتح المسودة في المحرر للمراجعة،
            ولا يُحفظ إصدار قبل ضغطك «حفظ الإصدار».
        </p>
    </header>

    <?php if ($error): ?><div class="alert error" role="alert"><?= school_e($error) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="import-form">
        <?= school_csrf_field() ?>

        <section class="import-card">
            <header class="import-card-head">
                <h3>الصق الجدول</h3>
                <span class="badge best">الطريقة الموصى بها</span>
            </header>

            <p class="import-card-lede">
                انسخ الجدول نفسه من Word أو Excel — لا صورة عنه — والصقه في المساحة أدناه.
                يصل معه دمج خلايا الرؤوس، فتُبنى المجموعات كما هي.
            </p>

            <div
                id="paste-target"
                class="paste-area"
                contenteditable="true"
                dir="rtl"
                role="textbox"
                aria-multiline="true"
                aria-label="مساحة لصق الجدول"
                data-placeholder="الصق الجدول هنا"
            ></div>
            <input type="hidden" name="pasted_html" id="pasted-html">
            <input type="hidden" name="pasted_text" id="pasted-text">

            <div class="import-row">
                <label class="import-name">
                    اسم القالب
                    <input name="name" value="<?= school_e($name) ?>" placeholder="يُملأ تلقائيًا عند تركه فارغًا">
                </label>
                <div class="import-submit">
                    <span id="paste-state" class="hint">لم يُلصق شيء بعد</span>
                    <button class="button primary" type="submit" name="import_paste">بناء المسودة</button>
                </div>
            </div>
        </section>

        <details class="import-fallback" <?= $error && isset($_POST['import_pdf']) ? 'open' : '' ?>>
            <summary>
                <span class="import-fallback-title">لا يتوفّر الجدول الأصلي؟ استورد من ملف PDF</span>
                <span class="badge">احتياطي</span>
            </summary>

            <div class="import-fallback-body">
                <p>
                    للـPDF النصي فقط. الـPDF لا يخزّن جدولًا بل نصًا بإحداثيات، فتُستنتج الأعمدة من
                    مواضع الكتابة؛ توقّع مراجعة أكثر، وقد لا تظهر مجموعات الرؤوس.
                    الملف الممسوح ضوئيًا (صورة) غير مدعوم.
                </p>

                <?php if (!$pdfReady): ?>
                    <p class="hint warn">
                        أداة <code>pdftotext</code> غير متوفرة على هذا الخادم. ثبّت poppler أو Xpdf،
                        أو اضبط <code>PDFTOTEXT_PATH</code> على مسارها، أو استخدم اللصق.
                    </p>
                <?php endif; ?>

                <div class="import-pdf-fields">
                    <label>ملف PDF<input type="file" name="pdf" accept="application/pdf,.pdf" <?= $pdfReady ? '' : 'disabled' ?>></label>
                    <label>الصفحة<input type="number" name="page" value="1" min="1" step="1" <?= $pdfReady ? '' : 'disabled' ?>></label>
                    <label>اتجاه الأعمدة<select name="direction" <?= $pdfReady ? '' : 'disabled' ?>>
                        <option value="auto">تلقائي</option>
                        <option value="rtl">من اليمين لليسار</option>
                        <option value="ltr">من اليسار لليمين</option>
                    </select></label>
                </div>

                <div class="import-submit">
                    <span class="hint">إن جاء ترتيب الأعمدة معكوسًا، بدّل الاتجاه وأعد المحاولة.</span>
                    <button class="button" type="submit" name="import_pdf" <?= $pdfReady ? '' : 'disabled' ?>>بناء المسودة من PDF</button>
                </div>
            </div>
        </details>
    </form>
</div>

<script>
(() => {
    const target = document.getElementById('paste-target');
    const html = document.getElementById('pasted-html');
    const text = document.getElementById('pasted-text');
    const state = document.getElementById('paste-state');

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
    document.querySelector('.import-form').addEventListener('submit', () => {
        html.value = target.innerHTML;
        text.value = target.innerText;
    });
})();
</script>
<?php page_footer(); ?>
