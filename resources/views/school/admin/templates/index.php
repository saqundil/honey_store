<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
$templates = (new App\Repositories\TemplateRepository(db(), current_user_id(), is_super_admin()))->all();
page_header('قوالب جداول التقييم', 'templates');
?>
<div class="section-head">
    <div>
        <p class="eyebrow">التصميمات</p>
        <h2>القوالب المحفوظة</h2>
    </div>
    <div class="quick-actions">
        <a class="button" href="<?= school_e(school_url('admin/templates/import.php')) ?>">استيراد من جدول أو PDF</a>
        <a class="button primary" href="<?= school_e(school_url('admin/templates/edit.php')) ?>">قالب جديد</a>
    </div>
</div>

<?php if ($templates): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">الاسم</th>
                    <th scope="col">الإصدار</th>
                    <th scope="col">الحالة</th>
                    <th scope="col">آخر تعديل</th>
                    <th scope="col"><span class="sr-only">إجراءات</span></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td>
                        <strong><?= school_e($template['name']) ?></strong>
                        <?php if ($template['description']): ?><small><?= school_e($template['description']) ?></small><?php endif; ?>
                    </td>
                    <td class="num">v<?= (int) $template['version_number'] ?></td>
                    <td><span class="status <?= school_e($template['status']) ?>"><?= $template['status'] === 'active' ? 'نشط' : 'معطل' ?></span></td>
                    <td class="num"><?= school_e($template['updated_at']) ?></td>
                    <td class="actions-cell">
                        <a href="<?= school_e(school_url('admin/templates/edit.php?id=' . $template['id'])) ?>">تعديل</a>
                        <a href="<?= school_e(school_url('admin/templates/preview.php?id=' . $template['id'])) ?>">معاينة</a>
                        <a href="<?= school_e(school_url('admin/reports/create.php?template=' . $template['id'])) ?>">استخدام</a>
                        <button class="link-button template-copy" data-id="<?= (int) $template['id'] ?>" data-name="<?= school_e($template['name']) ?>">نسخ</button>
                        <button class="link-button template-status" data-id="<?= (int) $template['id'] ?>" data-name="<?= school_e($template['name']) ?>"><?= $template['status'] === 'active' ? 'تعطيل' : 'تفعيل' ?></button>
                        <button class="link-button danger template-delete" data-id="<?= (int) $template['id'] ?>" data-name="<?= school_e($template['name']) ?>">حذف</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-state-mark" aria-hidden="true">＋</div>
        <h2>لا توجد قوالب بعد</h2>
        <p>القالب يحدّد أعمدة الجدول وطريقة حساب العلامات. ابدأ بقالب فارغ، أو استورد جدولًا جاهزًا من Word أو Excel أو PDF.</p>
        <div class="quick-actions">
            <a class="button" href="<?= school_e(school_url('admin/templates/import.php')) ?>">استيراد جدول</a>
            <a class="button primary" href="<?= school_e(school_url('admin/templates/edit.php')) ?>">إنشاء قالب</a>
        </div>
    </div>
<?php endif; ?>

<script>
window.APP = { baseUrl: <?= json_encode(school_url()) ?>, csrf: <?= json_encode(school_csrf_token()) ?> };

document.querySelectorAll('.template-copy, .template-status, .template-delete').forEach(button => {
    button.addEventListener('click', async () => {
        const action = button.classList.contains('template-copy') ? 'copy'
            : button.classList.contains('template-delete') ? 'delete'
            : 'status';
        const name = button.dataset.name || 'القالب';

        if (action === 'delete') {
            const ok = await UI.confirm({
                title: 'حذف القالب؟',
                message: `سيُخفى «${name}» من القوائم. التقارير المنشأة منه تبقى كما هي.`,
                confirmLabel: 'حذف',
                danger: true,
            });
            if (!ok) return;
        }

        button.disabled = true;
        try {
            const response = await fetch(`${APP.baseUrl}/api/templates/${action}.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
                body: JSON.stringify({ id: button.dataset.id }),
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'تعذّر تنفيذ العملية.');
            UI.toast({ copy: 'تم نسخ القالب.', delete: 'تم حذف القالب.', status: 'تم تغيير حالة القالب.' }[action], 'success', 1200);
            setTimeout(() => location.reload(), 600);
        } catch (error) {
            UI.toast(error.message, 'error');
            button.disabled = false;
        }
    });
});
</script>
<?php page_footer(); ?>
