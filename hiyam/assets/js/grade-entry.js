(() => {
    'use strict';
    const data = window.GRADEBOOK_DATA;
    if (!data) return;

    const indicator = document.getElementById('autosave-state');
    const templates = new Map(data.templates.map(template => [Number(template.id), template]));
    const timers = new Map();
    const pending = new Set();
    const revisions = data.revisions || {};

    function calculateRow(row, template) {
        const values = {};
        row.querySelectorAll('[data-column-key]').forEach(cell => {
            const input = cell.querySelector('input');
            if (input) values[cell.dataset.columnKey] = input.value === '' ? null : input.type === 'number' ? Number(input.value) : input.value;
        });
        const calculated = template.columns.filter(column => column.formula);
        let changed = true;
        let guard = 0;
        while (changed && guard++ <= calculated.length) {
            changed = false;
            calculated.forEach(column => {
                if (values[column.column_key] !== undefined && values[column.column_key] !== null) return;
                const sources = column.formula.sources.map(source => values[source]);
                if (sources.some(value => value === undefined || value === null || value === '')) return;
                const sum = sources.reduce((total, value) => total + Number(value), 0);
                const result = column.formula.type === 'AVERAGE' ? sum / sources.length : column.formula.type === 'PERCENTAGE' ? sum / Number(column.formula.base || 1) * 100 : sum;
                values[column.column_key] = Number(result.toFixed(Number(column.formula.decimals ?? 2)));
                changed = true;
            });
        }
        calculated.forEach(column => {
            const output = row.querySelector(`[data-column-key="${CSS.escape(column.column_key)}"] .cell-value`);
            if (output) output.textContent = values[column.column_key] ?? '';
        });
    }

    function rowsFor(panel, templateId) {
        return [...panel.querySelectorAll('tbody tr[data-student-id]')].map(row => ({
            enrollment_id: Number(row.dataset.studentId),
            values: Object.fromEntries([...row.querySelectorAll('[data-column-key]')].map(cell => [cell.dataset.columnKey, cell.querySelector('input')?.value ?? null])),
            revisions: revisions[templateId]?.[row.dataset.studentId] || {},
        }));
    }

    async function save(templateId) {
        const panel = document.querySelector(`[data-template-panel="${templateId}"]`);
        if (!panel || data.locked) return;
        pending.add(templateId);
        indicator.textContent = 'جارٍ الحفظ...';
        try {
            const response = await fetch(`${APP.baseUrl}/api/gradebook/values.php`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf},
                body: JSON.stringify({class_assessment_id: data.id, assessment_template_id: templateId, rows: rowsFor(panel, templateId)}),
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'تعذر الحفظ.');
            revisions[templateId] ||= {};
            result.saved.forEach(cell => {
                revisions[templateId][cell.enrollment_id] ||= {};
                revisions[templateId][cell.enrollment_id][cell.column_key] = cell.revision;
            });
            pending.delete(templateId);
            indicator.textContent = pending.size ? 'جارٍ حفظ تغييرات أخرى...' : `تم الحفظ تلقائيًا ${result.saved_at}`;
            indicator.classList.remove('error-text');
            const status = document.getElementById('assessment-status');
            if (status?.textContent.trim() === 'draft') {
                status.textContent = 'open';
                status.className = 'status open';
            }
        } catch (error) {
            pending.delete(templateId);
            indicator.textContent = error.message;
            indicator.classList.add('error-text');
        }
    }

    document.querySelectorAll('[data-template-panel]').forEach(panel => {
        const templateId = Number(panel.dataset.templatePanel);
        const template = templates.get(templateId);
        panel.querySelectorAll('tbody tr').forEach(row => calculateRow(row, template));
        panel.addEventListener('input', event => {
            const input = event.target.closest('input');
            if (!input) return;
            if (input.type === 'number') {
                const value = input.value;
                const valid = value === '' || Number(value) >= Number(input.min) && Number(value) <= Number(input.max) && Math.abs(Number(value) / Number(input.step || 0.25) - Math.round(Number(value) / Number(input.step || 0.25))) < 0.00001;
                input.classList.toggle('invalid', !valid);
                if (!valid) { indicator.textContent = 'صحح العلامة غير الصالحة قبل الحفظ'; return; }
            }
            calculateRow(input.closest('tr'), template);
            indicator.textContent = 'تغييرات غير محفوظة';
            clearTimeout(timers.get(templateId));
            timers.set(templateId, setTimeout(() => save(templateId), 700));
        });
    });

    document.querySelectorAll('[data-template-tab]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-template-tab]').forEach(item => item.classList.toggle('active', item === tab));
        document.querySelectorAll('[data-template-panel]').forEach(panel => panel.classList.toggle('active', panel.dataset.templatePanel === tab.dataset.templateTab));
    }));

    document.querySelector('[data-status-action]')?.addEventListener('click', async event => {
        const action = event.currentTarget.dataset.statusAction;
        if (action === 'lock' && !confirm('سيصبح الاختبار للقراءة فقط. هل تريد قفله؟')) return;
        event.currentTarget.disabled = true;
        try {
            const response = await fetch(`${APP.baseUrl}/api/gradebook/status.php`, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf},
                body: JSON.stringify({class_assessment_id: data.id, action}),
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'تعذر تغيير الحالة.');
            location.reload();
        } catch (error) {
            indicator.textContent = error.message;
            indicator.classList.add('error-text');
            event.currentTarget.disabled = false;
        }
    });
})();
