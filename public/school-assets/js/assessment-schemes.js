(() => {
    const config = window.ASSESSMENT_EDITOR;
    if (!config) return;

    const list = document.getElementById('assessment-list');
    const stateLabel = document.getElementById('save-state');
    const versionOptions = config.templateVersions.map(version => ({
        id: Number(version.id),
        label: `${version.name} · v${version.version_number}`,
    }));
    let nextKey = 1;
    const key = () => nextKey++;
    const flag = value => value === true || value === 1 || value === '1';
    const initialTemplate = () => ({key: key(), template_version_id: versionOptions[0]?.id || 0, label: versionOptions[0]?.label || '', is_required: true, is_active: true});
    const normalizeTemplate = template => ({key: key(), template_version_id: Number(template.template_version_id), label: template.label || '', is_required: flag(template.is_required), is_active: flag(template.is_active)});
    const normalizeAssessment = assessment => ({
        key: key(), name: assessment.name || '', short_name: assessment.short_name || '', maximum_mark: assessment.maximum_mark ?? '', weight: assessment.weight ?? '',
        is_required: assessment.is_required === undefined ? true : flag(assessment.is_required), is_active: assessment.is_active === undefined ? true : flag(assessment.is_active),
        templates: (assessment.templates || []).map(normalizeTemplate),
    });
    let assessments = config.initial.map(normalizeAssessment);
    if (!assessments.length && versionOptions.length) assessments.push(normalizeAssessment({name: '', templates: [initialTemplate()]}));

    const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
    const optionHtml = selected => versionOptions.map(option => `<option value="${option.id}" ${option.id === Number(selected) ? 'selected' : ''}>${escapeHtml(option.label)}</option>`).join('');
    const checked = value => value ? 'checked' : '';

    function render() {
        list.innerHTML = assessments.map((assessment, assessmentIndex) => `<section class="assessment-block" data-key="${assessment.key}">
            <header class="assessment-head"><span class="order-number">${assessmentIndex + 1}</span><div><strong>${escapeHtml(assessment.name || 'اختبار جديد')}</strong><small>${assessment.templates.length} قالب</small></div><div class="order-actions">
                <button type="button" data-action="assessment-up" title="تحريك لأعلى" aria-label="تحريك الاختبار لأعلى">↑</button><button type="button" data-action="assessment-down" title="تحريك لأسفل" aria-label="تحريك الاختبار لأسفل">↓</button><button type="button" data-action="assessment-remove" title="حذف" aria-label="حذف الاختبار">×</button>
            </div></header>
            <div class="assessment-fields">
                <label>اسم الاختبار<input data-field="name" value="${escapeHtml(assessment.name)}" required></label>
                <label>الاسم المختصر<input data-field="short_name" value="${escapeHtml(assessment.short_name)}"></label>
                <label>العلامة القصوى<input data-field="maximum_mark" type="number" min="0" step="0.01" value="${escapeHtml(assessment.maximum_mark)}"></label>
                <label>الوزن<input data-field="weight" type="number" min="0" step="0.01" value="${escapeHtml(assessment.weight)}"></label>
                <label class="check"><input data-field="is_required" type="checkbox" ${checked(assessment.is_required)}> مطلوب</label>
                <label class="check"><input data-field="is_active" type="checkbox" ${checked(assessment.is_active)}> نشط</label>
            </div>
            <div class="template-heading"><strong>قوالب الاختبار</strong><button type="button" class="link-button" data-action="template-add">إضافة قالب</button></div>
            <div class="template-assignments">${assessment.templates.map((template, templateIndex) => `<div class="template-assignment" data-template-key="${template.key}">
                <span class="template-number">${templateIndex + 1}</span>
                <label>إصدار القالب<select data-template-field="template_version_id">${optionHtml(template.template_version_id)}</select></label>
                <label>العنوان داخل الاختبار<input data-template-field="label" value="${escapeHtml(template.label)}" required></label>
                <label class="check"><input data-template-field="is_required" type="checkbox" ${checked(template.is_required)}> مطلوب</label>
                <label class="check"><input data-template-field="is_active" type="checkbox" ${checked(template.is_active)}> نشط</label>
                <div class="order-actions"><button type="button" data-action="template-up" title="تحريك لأعلى" aria-label="تحريك القالب لأعلى">↑</button><button type="button" data-action="template-down" title="تحريك لأسفل" aria-label="تحريك القالب لأسفل">↓</button><button type="button" data-action="template-remove" title="حذف" aria-label="حذف القالب">×</button></div>
            </div>`).join('')}</div>
        </section>`).join('');
        if (!assessments.length) list.innerHTML = '<div class="empty">أضف اختبارًا لبدء بناء المخطط.</div>';
    }

    function move(items, index, offset) {
        const target = index + offset;
        if (target < 0 || target >= items.length) return;
        [items[index], items[target]] = [items[target], items[index]];
        render();
    }

    list.addEventListener('input', event => {
        const block = event.target.closest('.assessment-block');
        if (!block) return;
        const assessment = assessments.find(item => item.key === Number(block.dataset.key));
        const assignment = event.target.closest('.template-assignment');
        if (assignment) {
            const template = assessment.templates.find(item => item.key === Number(assignment.dataset.templateKey));
            const field = event.target.dataset.templateField;
            if (field) template[field] = event.target.type === 'checkbox' ? event.target.checked : field === 'template_version_id' ? Number(event.target.value) : event.target.value;
        } else {
            const field = event.target.dataset.field;
            if (field) assessment[field] = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
        }
        if (event.target.dataset.field === 'name') block.querySelector('.assessment-head strong').textContent = event.target.value || 'اختبار جديد';
    });

    list.addEventListener('click', event => {
        const button = event.target.closest('[data-action]');
        if (!button) return;
        const block = button.closest('.assessment-block');
        const assessmentIndex = assessments.findIndex(item => item.key === Number(block.dataset.key));
        const assessment = assessments[assessmentIndex];
        const assignment = button.closest('.template-assignment');
        const templateIndex = assignment ? assessment.templates.findIndex(item => item.key === Number(assignment.dataset.templateKey)) : -1;
        const actions = {
            'assessment-up': () => move(assessments, assessmentIndex, -1),
            'assessment-down': () => move(assessments, assessmentIndex, 1),
            'assessment-remove': () => { if (confirm('حذف الاختبار من الإصدار الجديد؟')) { assessments.splice(assessmentIndex, 1); render(); } },
            'template-add': () => { assessment.templates.push(initialTemplate()); render(); },
            'template-up': () => move(assessment.templates, templateIndex, -1),
            'template-down': () => move(assessment.templates, templateIndex, 1),
            'template-remove': () => { assessment.templates.splice(templateIndex, 1); render(); },
        };
        actions[button.dataset.action]?.();
    });

    document.getElementById('add-assessment').addEventListener('click', () => {
        assessments.push(normalizeAssessment({name: '', templates: versionOptions.length ? [initialTemplate()] : []}));
        render();
    });

    document.getElementById('publish-scheme').addEventListener('click', async event => {
        const button = event.currentTarget;
        button.disabled = true;
        stateLabel.textContent = 'جارٍ النشر...';
        try {
            const response = await fetch(`${config.baseUrl}/api/assessments/save.php`, {
                method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': config.csrf},
                body: JSON.stringify({id: config.schemeId, name: document.getElementById('scheme-name').value, description: document.getElementById('scheme-description').value, academic_term_id: Number(document.getElementById('scheme-term').value), subject_id: Number(document.getElementById('scheme-subject').value), assessments}),
            });
            const result = await response.json();
            if (!result.ok) throw new Error(result.message || 'تعذر النشر.');
            location.href = `${config.baseUrl}/admin/assessments/index.php`;
        } catch (error) {
            stateLabel.textContent = error.message;
            stateLabel.classList.add('error-text');
            button.disabled = false;
        }
    });

    render();
})();
