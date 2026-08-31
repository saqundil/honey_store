/**
 * exam-wizard.js — معالج إنشاء الاختبار.
 *
 * الحالة كلها في الذاكرة حتى الضغطة الأخيرة؛ عندها تُرسل دفعة واحدة
 * إلى api/gradebook/create_exam.php الذي يبني الأقسام والاختبار في
 * معاملة واحدة. لا يظهر للمعلم قالب ولا إصدار ولا مخطط.
 */
(() => {
    'use strict';

    const app = window.APP;
    const config = window.WIZARD;
    if (!app || !config) return;

    const $ = selector => document.querySelector(selector);
    const $$ = selector => [...document.querySelectorAll(selector)];

    /** @type {{name:string, date:string, sections:Array}} */
    const state = { name: '', date: '', sections: [] };
    let step = 1;
    let editingIndex = -1;

    /* ================================================================
     * التنقّل بين الخطوات
     * ============================================================== */

    function showStep(next) {
        step = next;
        $$('.wizard-panel').forEach(panel => panel.classList.toggle('is-active', Number(panel.dataset.step) === step));
        $$('.wizard-steps li').forEach(tab => {
            const index = Number(tab.dataset.stepTab);
            tab.classList.toggle('is-done', index < step);
            tab.classList.toggle('is-current', index === step);
            if (index === step) tab.setAttribute('aria-current', 'step');
            else tab.removeAttribute('aria-current');
        });
        clearErrors();
        if (step === 2) renderSections();
        if (step === 3) renderReview();
        window.scrollTo({ top: 0, behavior: 'auto' });
    }

    const clearErrors = () => $$('.wizard-error').forEach(box => { box.hidden = true; box.textContent = ''; });

    function fail(message) {
        const box = $(`.wizard-panel[data-step="${step}"] .wizard-error`);
        if (!box) return;
        box.textContent = message;
        box.hidden = false;
    }

    function validateStep() {
        if (step === 1) {
            state.name = $('#exam-name').value.trim();
            state.date = $('#exam-date').value;
            if (!state.name) { fail('اسم الاختبار مطلوب.'); $('#exam-name').focus(); return false; }
        }
        if (step === 2 && !state.sections.length) {
            fail('أضف قسمًا واحدًا على الأقل.');
            return false;
        }
        return true;
    }

    $$('[data-next]').forEach(button => button.addEventListener('click', () => {
        if (validateStep()) showStep(step + 1);
    }));
    $$('[data-back]').forEach(button => button.addEventListener('click', () => showStep(step - 1)));

    /* ================================================================
     * الأقسام
     * ============================================================== */

    const sectionTotal = section => section.reusedName
        ? null
        : section.columns.reduce((sum, column) => sum + (column.type === 'manual_mark' ? Number(column.max_mark || 0) : 0), 0);

    function renderSections() {
        const list = $('#section-list');
        if (!state.sections.length) {
            list.innerHTML = `<div class="section-empty">
                <h3>لا توجد أقسام لهذا الاختبار بعد</h3>
                <p>أضف أول قسم مثل: محادثة أو كتابة أو قراءة.</p>
            </div>`;
        } else {
            list.innerHTML = state.sections.map((section, index) => {
                const total = sectionTotal(section);
                const detail = section.reusedName
                    ? 'قسم جاهز'
                    : `${section.columns.length} ${section.columns.length === 1 ? 'عمود' : 'أعمدة'}`;
                return `<article class="section-card">
                    <div class="section-card-main">
                        <strong>${escapeHtml(section.name)}</strong>
                        <small>${detail}${total !== null ? ` · ${total} درجة` : ''}</small>
                    </div>
                    <div class="section-card-actions">
                        ${section.reusedName ? '' : `<button type="button" class="tbtn tbtn-sm" data-edit-section="${index}">تعديل</button>`}
                        <button type="button" class="tbtn tbtn-sm tbtn-danger" data-remove-section="${index}">حذف</button>
                    </div>
                </article>`;
            }).join('');
        }
        list.insertAdjacentHTML('beforeend',
            '<button type="button" class="tbtn tbtn-primary add-section" id="add-section">＋ إضافة قسم</button>');

        $('#add-section').addEventListener('click', () => openSection(-1));
        $$('[data-edit-section]').forEach(b => b.addEventListener('click', () => openSection(Number(b.dataset.editSection))));
        $$('[data-remove-section]').forEach(b => b.addEventListener('click', () => {
            state.sections.splice(Number(b.dataset.removeSection), 1);
            renderSections();
        }));
    }

    /* ================================================================
     * حوار القسم
     * ============================================================== */

    const dialog = $('#section-dialog');
    let draftColumns = [];

    function openSection(index) {
        editingIndex = index;
        const editing = index >= 0 ? state.sections[index] : null;

        $('#section-dialog-title').textContent = editing ? 'تعديل القسم' : 'إضافة قسم';
        $('#section-name').value = editing ? editing.name : '';
        draftColumns = editing ? structuredClone(editing.columns) : [{ name: '', type: 'manual_mark', max_mark: '5' }];

        // التعديل لا يعرض خيار إعادة الاستخدام — القسم قائم بالفعل
        const modeBox = document.querySelector('.section-mode');
        modeBox.hidden = !!editing || !config.reusable.length;
        document.querySelector('input[name="section-mode"][value="new"]').checked = true;
        setMode('new');

        renderColumns();
        sectionError('');
        dialog.hidden = false;
        document.body.style.overflow = 'hidden';
        $('#section-name').focus();
    }

    function closeSection() {
        dialog.hidden = true;
        document.body.style.overflow = '';
    }

    function setMode(mode) {
        $$('.section-form').forEach(form => { form.hidden = form.dataset.mode !== mode; });
    }

    $$('input[name="section-mode"]').forEach(radio =>
        radio.addEventListener('change', () => setMode(radio.value)));
    $$('[data-close-section]').forEach(b => b.addEventListener('click', closeSection));
    dialog.addEventListener('mousedown', event => { if (event.target === dialog) closeSection(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !dialog.hidden) closeSection();
    });

    function sectionError(message) {
        const box = document.querySelector('.section-error');
        box.textContent = message;
        box.hidden = !message;
    }

    /* ================================================================
     * أعمدة القسم — الحقول الأساسية فقط
     * ============================================================== */

    function renderColumns() {
        const list = $('#column-list');
        list.innerHTML = draftColumns.map((column, index) => `
            <div class="column-row" draggable="true" data-index="${index}">
                <span class="column-grip" aria-hidden="true">☰</span>
                <label class="sr-only" for="col-name-${index}">اسم العمود</label>
                <input id="col-name-${index}" type="text" value="${escapeHtml(column.name)}"
                       placeholder="مثال: السؤال ${index + 1}" data-column-name="${index}" autocomplete="off">
                <label class="sr-only" for="col-type-${index}">نوع الإدخال</label>
                <select id="col-type-${index}" data-column-type="${index}">
                    <option value="manual_mark" ${column.type === 'manual_mark' ? 'selected' : ''}>علامة</option>
                    <option value="calculated_total" ${column.type === 'calculated_total' ? 'selected' : ''}>مجموع تلقائي</option>
                    <option value="text" ${column.type === 'text' ? 'selected' : ''}>نص</option>
                </select>
                <label class="sr-only" for="col-mark-${index}">العلامة القصوى</label>
                <input id="col-mark-${index}" type="number" min="0" step="0.25" value="${escapeHtml(column.max_mark ?? '')}"
                       placeholder="الدرجة" data-column-mark="${index}"
                       ${column.type === 'manual_mark' ? '' : 'disabled'}>
                <button type="button" class="column-remove" data-column-remove="${index}" aria-label="حذف العمود">×</button>
            </div>`).join('');

        $$('[data-column-name]').forEach(input => input.addEventListener('input', () => {
            draftColumns[Number(input.dataset.columnName)].name = input.value;
        }));
        $$('[data-column-type]').forEach(select => select.addEventListener('change', () => {
            const column = draftColumns[Number(select.dataset.columnType)];
            column.type = select.value;
            if (column.type !== 'manual_mark') column.max_mark = '';
            renderColumns();
            updateTotal();
        }));
        $$('[data-column-mark]').forEach(input => input.addEventListener('input', () => {
            draftColumns[Number(input.dataset.columnMark)].max_mark = input.value;
            updateTotal();
        }));
        $$('[data-column-remove]').forEach(button => button.addEventListener('click', () => {
            draftColumns.splice(Number(button.dataset.columnRemove), 1);
            renderColumns();
            updateTotal();
        }));

        bindColumnDrag();
        updateTotal();
    }

    /** إعادة الترتيب بالسحب — المعلم لا يرى sort_order إطلاقًا. */
    function bindColumnDrag() {
        let from = -1;
        $$('.column-row').forEach(row => {
            row.addEventListener('dragstart', () => { from = Number(row.dataset.index); row.classList.add('dragging'); });
            row.addEventListener('dragend', () => row.classList.remove('dragging'));
            row.addEventListener('dragover', event => event.preventDefault());
            row.addEventListener('drop', event => {
                event.preventDefault();
                const to = Number(row.dataset.index);
                if (from < 0 || from === to) return;
                draftColumns.splice(to, 0, draftColumns.splice(from, 1)[0]);
                renderColumns();
            });
        });
    }

    const updateTotal = () => {
        const total = draftColumns.reduce((sum, c) => sum + (c.type === 'manual_mark' ? Number(c.max_mark || 0) : 0), 0);
        $('#section-total').textContent = `${total} درجة`;
    };

    $('#add-column').addEventListener('click', () => {
        draftColumns.push({ name: '', type: 'manual_mark', max_mark: '5' });
        renderColumns();
        $(`[data-column-name="${draftColumns.length - 1}"]`)?.focus();
    });

    $('#save-section').addEventListener('click', () => {
        const mode = document.querySelector('input[name="section-mode"]:checked')?.value || 'new';

        if (mode === 'existing' && editingIndex < 0) {
            const select = $('#section-existing');
            const name = select.options[select.selectedIndex]?.textContent.trim() || 'قسم';
            if (state.sections.some(s => s.template_version_id === Number(select.value))) {
                sectionError('هذا القسم مضاف بالفعل إلى هذا الاختبار.');
                return;
            }
            state.sections.push({ name, template_version_id: Number(select.value), reusedName: name, columns: [] });
            closeSection();
            renderSections();
            return;
        }

        const name = $('#section-name').value.trim();
        if (!name) { sectionError('اسم القسم مطلوب.'); return; }
        const columns = draftColumns.filter(column => column.name.trim() !== '');
        if (!columns.length) { sectionError('أضف عمودًا واحدًا على الأقل باسم.'); return; }

        const bad = columns.find(c => c.type === 'manual_mark' && !(Number(c.max_mark) > 0));
        if (bad) { sectionError(`العمود «${bad.name}» يحتاج درجة أكبر من صفر.`); return; }
        if (columns.every(c => c.type !== 'manual_mark')) {
            sectionError('القسم يحتاج عمود علامة واحدًا على الأقل.');
            return;
        }

        const section = { name, columns, template_version_id: null, reusedName: null };
        if (editingIndex >= 0) state.sections[editingIndex] = section;
        else state.sections.push(section);

        closeSection();
        renderSections();
    });

    /* ================================================================
     * المراجعة والإنشاء
     * ============================================================== */

    function renderReview() {
        const grand = state.sections.reduce((sum, s) => sum + (sectionTotal(s) || 0), 0);
        $('#review').innerHTML = `
            <div class="review-head">
                <strong>${escapeHtml(state.name)}</strong>
                <span>${escapeHtml(config.class_name)}${state.date ? ' · ' + escapeHtml(state.date) : ''}</span>
            </div>
            <div class="review-sections">
                ${state.sections.map(section => {
                    const total = sectionTotal(section);
                    return `<div class="review-section">
                        <span class="review-tick" aria-hidden="true">✓</span>
                        <div>
                            <strong>${escapeHtml(section.name)}</strong>
                            <small>${section.reusedName
                                ? 'قسم جاهز يُعاد استخدامه'
                                : `${section.columns.length} ${section.columns.length === 1 ? 'عمود' : 'أعمدة'} · ${total} درجة`}</small>
                        </div>
                    </div>`;
                }).join('')}
            </div>
            <div class="review-total">
                <span>مجموع درجات الأقسام الجديدة</span>
                <strong>${grand} درجة</strong>
            </div>`;
    }

    $('#create-exam').addEventListener('click', async () => {
        const button = $('#create-exam');
        button.disabled = true;
        button.classList.add('is-loading');
        clearErrors();

        const payload = {
            class_id: config.class_id,
            name: state.name,
            exam_date: state.date || null,
            sections: state.sections.map(section => section.template_version_id
                ? { name: section.name, template_version_id: section.template_version_id }
                : { name: section.name, columns: section.columns }),
        };

        try {
            let result = await send(payload);

            // أكثر من نظام تقييم صالح: نسأل بالاسم لا بالمعرّف
            if (result.needs_scheme) {
                const chosen = await chooseScheme(result.options || []);
                if (!chosen) { reset(button); return; }
                payload.scheme_version_id = chosen;
                result = await send(payload);
            }

            if (!result.ok) throw new Error(result.message || 'تعذر إنشاء الاختبار.');
            window.location.href = result.entry_url;
        } catch (error) {
            fail(error.message || 'تعذر إنشاء الاختبار.');
            reset(button);
        }
    });

    const reset = button => { button.disabled = false; button.classList.remove('is-loading'); };

    async function send(payload) {
        const response = await fetch(`${app.baseUrl}/api/gradebook/create_exam.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': app.csrf },
            body: JSON.stringify(payload),
        });
        return response.json();
    }

    function chooseScheme(options) {
        return new Promise(resolve => {
            const scrim = document.createElement('div');
            scrim.className = 't-modal-scrim';
            const box = document.createElement('div');
            box.className = 't-modal';
            box.setAttribute('role', 'dialog');
            box.setAttribute('aria-modal', 'true');
            box.setAttribute('aria-label', 'اختر نظام التقييم');

            const title = document.createElement('h3');
            title.textContent = 'اختر نظام التقييم';
            const lead = document.createElement('p');
            lead.textContent = 'أي نظام تريد استخدامه لهذا الصف؟';
            box.append(title, lead);

            const list = document.createElement('div');
            list.className = 'scheme-choices';
            options.forEach((option, index) => {
                const row = document.createElement('label');
                row.className = 'scheme-choice';
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'wizard-scheme';
                radio.value = String(option.id);
                if (!index) radio.checked = true;
                const text = document.createElement('span');
                text.textContent = option.label;
                row.append(radio, text);
                list.appendChild(row);
            });
            box.appendChild(list);

            const actions = document.createElement('div');
            actions.className = 't-modal-actions';
            const go = document.createElement('button');
            go.type = 'button';
            go.className = 'tbtn tbtn-primary';
            go.textContent = 'متابعة';
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'tbtn';
            cancel.textContent = 'إلغاء';
            actions.append(go, cancel);
            box.appendChild(actions);
            scrim.appendChild(box);
            document.body.appendChild(scrim);
            go.focus();

            const finish = value => { scrim.remove(); resolve(value); };
            go.addEventListener('click', () => finish(Number(list.querySelector('input:checked')?.value) || null));
            cancel.addEventListener('click', () => finish(null));
        });
    }

    const escapeHtml = value => String(value ?? '')
        .replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));

    showStep(1);
})();
