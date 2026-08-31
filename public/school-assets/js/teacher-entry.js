/* ============================================================
   teacher-entry.js — شبكة إدخال العلامات
   ------------------------------------------------------------
   يعتمد على:
     window.GRADEBOOK_DATA = { id, locked, revisions, templates:[{id,columns:[]}] }
     window.APP           = { baseUrl, csrf }
   الـHTML يحتوي على:
     .entry-grid-wrap > <table class="dynamic-report-table">
     td[data-column-key], tbody tr[data-student-id]
     input.mark-input | input.value-input
     .entry-save[data-state]  •  .entry-progress-fill  •  .entry-progress-text
   ============================================================ */

(() => {
    'use strict';
    const data = window.GRADEBOOK_DATA;
    const app  = window.APP;
    if (!data || !app) return;

    const saveEl     = document.querySelector('.entry-save');
    const saveText   = saveEl?.querySelector('.entry-save-text');
    const progressFill = document.querySelector('.entry-progress-fill');
    const progressText = document.querySelector('.entry-progress-text');
    const statusPill = document.querySelector('.entry-status');

    const templates  = new Map(data.templates.map(t => [Number(t.id), t]));
    const revisions  = data.revisions || {};
    const timers     = new Map();  // template id -> debounce timer
    const pending    = new Set();  // template ids currently saving
    let   locked     = !!data.locked;
    let   activeCell = null;

    const SAVE_DEBOUNCE_MS = 650;

    /* ---------- helpers ---------- */
    const $  = (sel, root=document) => root.querySelector(sel);
    const $$ = (sel, root=document) => [...root.querySelectorAll(sel)];

    function setSaveState(state, message){
        if (!saveEl) return;
        saveEl.dataset.state = state;
        if (saveText) saveText.textContent = message ?? saveText.textContent;
    }

    function updateStatusPill(next){
        if (!statusPill) return;
        statusPill.dataset.status = next;
        const labels = { draft:'مسودة', open:'مفتوح', locked:'مقفل' };
        statusPill.textContent = labels[next] ?? next;
    }

    /* ---------- formula recompute on client (visual only) ---------- */
    function calculateRow(row, template){
        const values = {};
        $$('td[data-column-key] input', row).forEach(input => {
            const key = input.closest('[data-column-key]').dataset.columnKey;
            values[key] = input.value === '' ? null
                        : input.type === 'number' ? Number(input.value)
                        : input.value;
        });
        const calc = template.columns.filter(c => c.formula);
        let changed = true, guard = 0;
        while (changed && guard++ <= calc.length + 1) {
            changed = false;
            for (const col of calc) {
                if (values[col.column_key] !== undefined && values[col.column_key] !== null) continue;
                const sources = col.formula.sources.map(s => values[s]);
                const missing = sources.some(v => v === undefined || v === null || v === '');
                if (missing && (col.formula.missing ?? 'blank') !== 'zero') continue;
                const nums = sources.map(v => Number(v ?? 0));
                const sum  = nums.reduce((a,b) => a+b, 0);
                const result = col.formula.type === 'AVERAGE'
                    ? (nums.length ? sum / nums.length : 0)
                    : col.formula.type === 'PERCENTAGE'
                        ? (Number(col.formula.base) > 0 ? sum / Number(col.formula.base) * 100 : 0)
                        : sum;
                const decimals = Number(col.formula.decimals ?? 2);
                values[col.column_key] = Number(result.toFixed(decimals));
                changed = true;
            }
        }
        calc.forEach(col => {
            const out = row.querySelector(`[data-column-key="${CSS.escape(col.column_key)}"] .cell-value`);
            if (out) out.textContent = values[col.column_key] ?? '';
        });
    }

    /* ---------- validation ---------- */
    function validateInput(input){
        if (input.type !== 'number') return { valid:true };
        const v = input.value;
        if (v === '') return { valid:true };
        const num  = Number(v);
        const min  = Number(input.min);
        const max  = Number(input.max);
        const step = Number(input.step || 0.25);
        if (Number.isNaN(num)) return { valid:false, reason:'غير رقمي' };
        if (num < min || num > max) return { valid:false, reason:`القيمة خارج المجال 0–${max}` };
        const q = num / step;
        if (Math.abs(q - Math.round(q)) > 0.00001)
            return { valid:false, reason:`خطوة الإدخال ${step}` };
        return { valid:true };
    }

    function markCell(input){
        const td = input.closest('td[data-column-key]');
        if (!td) return true;
        const res = validateInput(input);
        input.classList.toggle('invalid', !res.valid);
        input.setAttribute('aria-invalid', res.valid ? 'false' : 'true');
        td.classList.toggle('has-invalid', !res.valid);
        if (!res.valid) td.dataset.invalidMsg = res.reason;
        else delete td.dataset.invalidMsg;
        return res.valid;
    }

    /* ---------- crosshair (row + column highlight) ---------- */
    function clearCrosshair(root){
        $$('tr.row-active', root).forEach(r => r.classList.remove('row-active'));
        $$('td.col-active', root).forEach(td => td.classList.remove('col-active'));
        $$('th.col-active-head', root).forEach(th => th.classList.remove('col-active-head'));
    }
    function paintCrosshair(cell){
        const root = cell.closest('.entry-grid-wrap');
        if (!root) return;
        clearCrosshair(root);
        cell.closest('tr')?.classList.add('row-active');
        const key = cell.dataset.columnKey;
        if (!key) return;
        $$(`tbody td[data-column-key="${CSS.escape(key)}"]`, root)
            .forEach(td => td.classList.add('col-active'));
        // Approximate header highlight: find leaf th whose <span> label matches column position
        // We rely on column index instead of key (headers may span).
        const table = root.querySelector('table');
        const colIndex = [...cell.parentNode.children].indexOf(cell);
        if (table && colIndex >= 0) {
            const lastHeaderRow = table.tHead?.rows[table.tHead.rows.length - 1];
            const th = lastHeaderRow?.cells[colIndex];
            th?.classList.add('col-active-head');
        }
    }

    /* ---------- keyboard navigation ---------- */
    function moveFocus(from, direction){
        const td   = from.closest('td[data-column-key]');
        const tr   = td?.parentNode;
        const root = td?.closest('.entry-grid-wrap');
        if (!td || !tr || !root) return;
        const rows = $$('tbody tr[data-student-id]', root);
        const rIdx = rows.indexOf(tr);
        const cIdx = [...tr.children].indexOf(td);
        let target = null;
        if (direction === 'down' || direction === 'up') {
            let step = direction === 'down' ? 1 : -1;
            let i = rIdx + step;
            while (i >= 0 && i < rows.length) {
                const cell = rows[i].children[cIdx];
                const inp = cell?.querySelector('input:not([type="hidden"])');
                if (inp && !inp.readOnly && !inp.disabled) { target = inp; break; }
                i += step;
            }
        } else if (direction === 'next' || direction === 'prev') {
            // In RTL, Tab moves *visually* to the next cell = higher column index in DOM.
            // We use DOM order regardless of writing direction (predictable in the grid).
            let step = direction === 'next' ? 1 : -1;
            let r = rIdx, c = cIdx + step;
            while (r >= 0 && r < rows.length) {
                if (c < 0) { r -= 1; if (r < 0) break; c = rows[r].children.length - 1; continue; }
                if (c >= rows[r].children.length) { r += 1; if (r >= rows.length) break; c = 0; continue; }
                const cell = rows[r].children[c];
                const inp = cell?.querySelector('input:not([type="hidden"])');
                if (inp && !inp.readOnly && !inp.disabled) { target = inp; break; }
                c += step;
            }
        }
        if (target) {
            target.focus();
            target.select?.();
        }
    }

    /* ---------- progress meter ---------- */
    function refreshProgress(){
        const active = document.querySelector('.entry-panel.is-active');
        if (!active) return;
        const inputs = $$('td input.mark-input, td input.value-input', active);
        if (!inputs.length) { if (progressText) progressText.textContent = '—'; return; }
        const filled = inputs.filter(i => i.value !== '' && i.value !== null).length;
        const pct = Math.round((filled / inputs.length) * 100);
        if (progressFill) progressFill.style.width = pct + '%';
        if (progressText) progressText.textContent = `${filled}/${inputs.length}`;
    }

    /* ---------- save (debounced per template) ---------- */
    function collectRows(panel){
        const templateId = Number(panel.dataset.templatePanel);
        return $$('tbody tr[data-student-id]', panel).map(tr => {
            const values = {};
            $$('td[data-column-key] input', tr).forEach(input => {
                const key = input.closest('[data-column-key]').dataset.columnKey;
                values[key] = input.value === '' ? null : input.value;
            });
            return {
                enrollment_id: Number(tr.dataset.studentId),
                values,
                revisions: revisions[templateId]?.[tr.dataset.studentId] || {},
            };
        });
    }

    async function saveTemplate(templateId){
        const panel = document.querySelector(`[data-template-panel="${templateId}"]`);
        if (!panel || locked) return;
        // Skip if any invalid cell exists in this template
        if (panel.querySelector('input.invalid')) {
            setSaveState('dirty', 'صحّح القيم غير الصالحة قبل الحفظ');
            return;
        }
        pending.add(templateId);
        setSaveState('saving', 'يُحفظ…');
        try {
            const response = await fetch(`${app.baseUrl}/api/gradebook/values.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': app.csrf },
                body: JSON.stringify({
                    class_assessment_id: data.id,
                    assessment_template_id: templateId,
                    rows: collectRows(panel),
                }),
            });
            let result;
            try { result = await response.json(); }
            catch (_) { throw new Error('استجابة الخادم غير صالحة.'); }

            if (response.status === 409 || result?.conflict) {
                pending.delete(templateId);
                openConflictModal(result?.message);
                return;
            }
            if (!response.ok || !result?.ok) throw new Error(result?.message || 'تعذر الحفظ.');

            revisions[templateId] ||= {};
            (result.saved || []).forEach(cell => {
                revisions[templateId][cell.enrollment_id] ||= {};
                revisions[templateId][cell.enrollment_id][cell.column_key] = cell.revision;
            });
            pending.delete(templateId);
            if (pending.size === 0) setSaveState('saved', `حُفظ ${result.saved_at ?? ''}`.trim());
            // Draft → open transition after first save
            if (statusPill && statusPill.dataset.status === 'draft') updateStatusPill('open');
        } catch (err) {
            pending.delete(templateId);
            setSaveState('error', err.message || 'فشل الحفظ');
        }
    }

    function scheduleSave(templateId){
        if (locked) return;
        setSaveState('dirty', 'تغييرات غير محفوظة');
        clearTimeout(timers.get(templateId));
        timers.set(templateId, setTimeout(() => saveTemplate(templateId), SAVE_DEBOUNCE_MS));
    }

    /* ---------- 409 conflict modal ---------- */
    function openConflictModal(message){
        // Guard: one modal at a time
        if (document.querySelector('.t-modal-scrim')) return;
        const scrim = document.createElement('div');
        scrim.className = 't-modal-scrim';
        scrim.innerHTML = `
            <div class="t-modal t-modal-conflict" role="alertdialog" aria-modal="true" aria-labelledby="t-conflict-t">
                <h3 id="t-conflict-t">عُدّل الاختبار من جلسة أخرى</h3>
                <p>${message ? escapeHtml(message) : 'شخص آخر (أو نافذة أخرى) عدّل علامات هذا الاختبار بعد أن فتحته. لتفادي الكتابة فوق تعديلاته سنعيد تحميل القيم الآن.'}</p>
                <div class="t-modal-actions">
                    <button type="button" class="tbtn tbtn-primary" data-conflict-reload>إعادة تحميل القيم</button>
                </div>
            </div>`;
        document.body.appendChild(scrim);
        scrim.querySelector('[data-conflict-reload]')?.addEventListener('click', () => location.reload());
        // Focus trap: focus reload button
        scrim.querySelector('button')?.focus();
    }
    function escapeHtml(s){
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    /* ---------- lock / reopen ---------- */
    async function changeStatus(action){
        const btn = document.querySelector(`[data-status-action="${action}"]`);
        if (btn) btn.disabled = true;
        try {
            const response = await fetch(`${app.baseUrl}/api/gradebook/status.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': app.csrf },
                body: JSON.stringify({ class_assessment_id: data.id, action }),
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'تعذر تغيير الحالة.');
            location.reload();
        } catch (err) {
            setSaveState('error', err.message || 'تعذر تغيير الحالة.');
            if (btn) btn.disabled = false;
        }
    }

    /* ---------- enrich cells: aria labels + mobile data-label ---------- */
    function enrichPanel(panel){
        const table = panel.querySelector('table');
        if (!table) return;
        // Build per-column header text using the *last* header row (leaf headers)
        const headerRows = table.tHead ? [...table.tHead.rows] : [];
        const leaf = headerRows[headerRows.length - 1];
        if (!leaf) return;
        const headers = [...leaf.cells].map(th => (th.textContent || '').replace(/\s+/g,' ').trim());

        $$('tbody tr[data-student-id]', panel).forEach(tr => {
            const nameCell = tr.children[1];
            const studentName = nameCell ? (nameCell.textContent || '').trim() : '';
            [...tr.children].forEach((td, idx) => {
                const label = headers[idx] || '';
                td.setAttribute('data-label', label);
                const input = td.querySelector('input');
                if (input) {
                    input.setAttribute('aria-label', `${label} — ${studentName}`);
                }
            });
        });
    }

    /* ---------- init ---------- */
    function initPanel(panel){
        const templateId = Number(panel.dataset.templatePanel);
        const template = templates.get(templateId);
        if (!template) return;
        enrichPanel(panel);
        $$('tbody tr[data-student-id]', panel).forEach(row => calculateRow(row, template));

        panel.addEventListener('input', event => {
            const input = event.target.closest('input');
            if (!input || locked) return;
            const ok = markCell(input);
            calculateRow(input.closest('tr'), template);
            if (!ok) { setSaveState('dirty', 'تغييرات غير محفوظة'); return; }
            refreshProgress();
            scheduleSave(templateId);
        });

        panel.addEventListener('focusin', event => {
            const input = event.target.closest('input');
            if (!input) return;
            const cell = input.closest('td[data-column-key]');
            if (cell) { activeCell = cell; paintCrosshair(cell); }
        });

        panel.addEventListener('keydown', event => {
            const input = event.target.closest('input');
            if (!input || locked) return;
            const key = event.key;
            if (key === 'Enter' || key === 'ArrowDown') {
                event.preventDefault();
                moveFocus(input, 'down');
            } else if (key === 'ArrowUp') {
                event.preventDefault();
                moveFocus(input, 'up');
            } else if (key === 'Tab') {
                // In RTL, browser default Tab moves to previous DOM cell (visually next-to-right).
                // Override so Tab moves to next DOM cell (visually to the left / next column).
                event.preventDefault();
                moveFocus(input, event.shiftKey ? 'prev' : 'next');
            } else if (key === 'ArrowRight' || key === 'ArrowLeft') {
                // Only override when caret would leave the input at an edge (empty or at end)
                const inputElement = input;
                const atStart = inputElement.selectionStart === 0 && inputElement.selectionEnd === 0;
                const atEnd   = inputElement.selectionStart === inputElement.value.length
                             && inputElement.selectionEnd === inputElement.value.length;
                // In RTL: ArrowRight = previous column (higher DOM index in start-inline sense)
                // We map based on visual direction of the document.
                const isRtl = document.documentElement.dir === 'rtl';
                if (key === 'ArrowRight' && atStart) {
                    event.preventDefault();
                    moveFocus(input, isRtl ? 'prev' : 'next');
                } else if (key === 'ArrowLeft' && atEnd) {
                    event.preventDefault();
                    moveFocus(input, isRtl ? 'next' : 'prev');
                }
            }
        });

        // pre-validate any existing values
        $$('td input', panel).forEach(markCell);
    }

    // Init all panels
    $$('.entry-panel').forEach(initPanel);

    // Template tabs (aria-selected pattern)
    $$('.entry-tabs [data-template-tab]').forEach(tab => {
        tab.addEventListener('click', () => {
            const id = tab.dataset.templateTab;
            $$('.entry-tabs [data-template-tab]').forEach(t =>
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false')
            );
            $$('.entry-panel').forEach(p =>
                p.classList.toggle('is-active', p.dataset.templatePanel === id)
            );
            refreshProgress();
        });
    });

    // Save retry
    saveEl?.querySelector('.entry-save-retry')?.addEventListener('click', () => {
        // Retry all pending templates
        templates.forEach((_, id) => saveTemplate(id));
    });

    // Status actions — use delegation so multiple buttons (e.g. inline + notice) all work
    document.addEventListener('click', event => {
        const btn = event.target.closest('[data-status-action]');
        if (!btn) return;
        const action = btn.dataset.statusAction;
        if (action === 'lock') {
            if (confirm('سيصبح الاختبار مقفلًا للقراءة فقط. متابعة؟')) changeStatus('lock');
        } else if (action === 'reopen') {
            changeStatus('reopen');
        }
    });

    // Flush on unload if dirty
    window.addEventListener('beforeunload', event => {
        if (saveEl?.dataset.state === 'dirty' || saveEl?.dataset.state === 'saving') {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    // First paint
    refreshProgress();
    if (!locked) setSaveState('saved', 'جاهز');
})();
