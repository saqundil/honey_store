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
        <a class="button" href="<?= school_e(school_url('admin/templates/import.php')) ?>">استيراد Word أو جدول</a>
        <a class="button primary" href="<?= school_e(school_url('admin/templates/edit.php')) ?>">قالب جديد</a>
    </div>
</div>

<?php if ($templates): ?>
    <?php $templateCountsByGroup = array_count_values(array_map(static fn(array $template): int => (int) $template['group_id'], $templates)); ?>
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
            <?php $currentTemplateGroup = null; foreach ($templates as $template): ?>
                <?php if ($currentTemplateGroup !== (int) $template['group_id']): $currentTemplateGroup = (int) $template['group_id']; ?>
                    <tr class="template-group-row" data-template-group="<?= $currentTemplateGroup ?>">
                        <th colspan="5" scope="rowgroup">
                            <div class="template-group-heading">
                                <button type="button" class="template-group-toggle" aria-expanded="true" aria-label="طي مجموعة <?= school_e($template['group_name']) ?>">
                                    <span class="template-group-chevron" aria-hidden="true">⌄</span>
                                    <span><?= school_e($template['group_name']) ?></span>
                                    <small><?= (int) $templateCountsByGroup[$currentTemplateGroup] ?> قالب</small>
                                </button>
                                <div class="template-group-actions">
                                    <button type="button" class="template-group-move" data-direction="up" title="نقل المجموعة إلى أعلى" aria-label="نقل مجموعة <?= school_e($template['group_name']) ?> إلى أعلى">↑</button>
                                    <button type="button" class="template-group-move" data-direction="down" title="نقل المجموعة إلى أسفل" aria-label="نقل مجموعة <?= school_e($template['group_name']) ?> إلى أسفل">↓</button>
                                    <a href="<?= school_e(school_url('admin/reports/create.php?group=' . $currentTemplateGroup)) ?>">استخدام المجموعة</a>
                                    <a target="_blank" rel="noopener" href="<?= school_e(school_url('admin/reports/create.php?group=' . $currentTemplateGroup . '&after=print')) ?>">طباعة المجموعة</a>
                                </div>
                            </div>
                        </th>
                    </tr>
                <?php endif; ?>
                <tr class="template-item-row" data-parent-group="<?= (int) $template['group_id'] ?>">
                    <td>
                        <div class="template-name" data-template-name="<?= (int) $template['id'] ?>">
                            <div class="template-name-display">
                                <strong><?= school_e($template['name']) ?></strong>
                                <?php if ($template['description']): ?><small><?= school_e($template['description']) ?></small><?php endif; ?>
                            </div>
                            <form class="template-rename-form" hidden>
                                <input type="text" maxlength="190" required value="<?= school_e($template['name']) ?>" aria-label="اسم القالب">
                                <button type="submit">حفظ</button>
                                <button type="button" data-cancel-rename>إلغاء</button>
                            </form>
                        </div>
                    </td>
                    <td class="num">v<?= (int) $template['version_number'] ?></td>
                    <td><span class="status <?= school_e($template['status']) ?>"><?= $template['status'] === 'active' ? 'نشط' : 'معطل' ?></span></td>
                    <td class="num"><?= school_e($template['updated_at']) ?></td>
                    <td class="actions-cell">
                        <button class="link-button template-rename" data-id="<?= (int) $template['id'] ?>">تسمية</button>
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
        <p>القالب يحدّد أعمدة الجدول وطريقة حساب العلامات. ابدأ بقالب فارغ، أو ارفع ملف Word متعدد الجداول، أو استورد جدولًا جاهزًا.</p>
        <div class="quick-actions">
            <a class="button" href="<?= school_e(school_url('admin/templates/import.php')) ?>">استيراد جدول</a>
            <a class="button primary" href="<?= school_e(school_url('admin/templates/edit.php')) ?>">إنشاء قالب</a>
        </div>
    </div>
<?php endif; ?>

<script>
window.APP = { baseUrl: <?= json_encode(school_url()) ?>, csrf: <?= json_encode(school_csrf_token()) ?> };

const collapsedGroups = new Set(JSON.parse(localStorage.getItem('collapsed-template-groups') || '[]').map(String));

const groupRows = () => [...document.querySelectorAll('.template-group-row')];
const childRows = groupId => [...document.querySelectorAll(`[data-parent-group="${groupId}"]`)];

function setGroupCollapsed(groupRow, collapsed) {
    const groupId = groupRow.dataset.templateGroup;
    const toggle = groupRow.querySelector('.template-group-toggle');
    childRows(groupId).forEach(row => row.hidden = collapsed);
    groupRow.classList.toggle('is-collapsed', collapsed);
    toggle.setAttribute('aria-expanded', String(!collapsed));
    toggle.setAttribute('aria-label', `${collapsed ? 'فتح' : 'طي'} مجموعة ${toggle.querySelector('span:nth-child(2)').textContent.trim()}`);
    if (collapsed) collapsedGroups.add(groupId);
    else collapsedGroups.delete(groupId);
    localStorage.setItem('collapsed-template-groups', JSON.stringify([...collapsedGroups]));
}

function refreshGroupMoveButtons() {
    const rows = groupRows();
    rows.forEach((row, index) => {
        row.querySelector('[data-direction="up"]').disabled = index === 0;
        row.querySelector('[data-direction="down"]').disabled = index === rows.length - 1;
    });
}

function groupBlock(groupRow) {
    return [groupRow, ...childRows(groupRow.dataset.templateGroup)];
}

async function persistGroupOrder() {
    const response = await fetch(`${APP.baseUrl}/api/templates/group-order.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
        body: JSON.stringify({ group_ids: groupRows().map(row => row.dataset.templateGroup) }),
    });
    const result = await response.json();
    if (!result.ok) throw new Error(result.message || 'تعذّر حفظ ترتيب المجموعات.');
}

groupRows().forEach(row => {
    setGroupCollapsed(row, collapsedGroups.has(row.dataset.templateGroup));
    row.querySelector('.template-group-toggle').addEventListener('click', () => {
        setGroupCollapsed(row, row.querySelector('.template-group-toggle').getAttribute('aria-expanded') === 'true');
    });
    row.querySelectorAll('.template-group-move').forEach(button => button.addEventListener('click', async () => {
        const rows = groupRows();
        const index = rows.indexOf(row);
        const target = button.dataset.direction === 'up' ? rows[index - 1] : rows[index + 1];
        if (!target) return;
        const tbody = row.parentElement;
        const moving = groupBlock(row);
        if (button.dataset.direction === 'up') {
            moving.forEach(node => tbody.insertBefore(node, target));
        } else {
            const anchor = groupBlock(target).at(-1).nextSibling;
            moving.forEach(node => tbody.insertBefore(node, anchor));
        }
        refreshGroupMoveButtons();
        button.disabled = true;
        try {
            await persistGroupOrder();
            UI.toast('تم تغيير مكان المجموعة.', 'success', 1000);
        } catch (error) {
            UI.toast(error.message, 'error');
            setTimeout(() => location.reload(), 600);
        }
    }));
});
refreshGroupMoveButtons();

document.querySelectorAll('.template-rename').forEach(button => {
    button.addEventListener('click', () => {
        const container = document.querySelector(`[data-template-name="${button.dataset.id}"]`);
        const form = container.querySelector('.template-rename-form');
        container.querySelector('.template-name-display').hidden = true;
        form.hidden = false;
        form.querySelector('input').focus();
        form.querySelector('input').select();
    });
});

document.querySelectorAll('.template-rename-form').forEach(form => {
    const container = form.closest('.template-name');
    const display = container.querySelector('.template-name-display');
    const label = display.querySelector('strong');
    const input = form.querySelector('input');
    const close = () => {
        input.value = label.textContent.trim();
        form.hidden = true;
        display.hidden = false;
    };

    form.querySelector('[data-cancel-rename]').addEventListener('click', close);
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const name = input.value.trim();
        if (!name) {
            input.focus();
            return;
        }

        const controls = form.querySelectorAll('input, button');
        controls.forEach(control => control.disabled = true);
        try {
            const response = await fetch(`${APP.baseUrl}/api/templates/rename.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
                body: JSON.stringify({ id: container.dataset.templateName, name }),
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'تعذّر تعديل اسم القالب.');
            label.textContent = result.name;
            input.value = result.name;
            document.querySelectorAll(`[data-name][data-id="${container.dataset.templateName}"]`).forEach(action => action.dataset.name = result.name);
            form.hidden = true;
            display.hidden = false;
            UI.toast('تم تعديل اسم القالب.', 'success', 1200);
        } catch (error) {
            UI.toast(error.message, 'error');
        } finally {
            controls.forEach(control => control.disabled = false);
        }
    });
});

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
