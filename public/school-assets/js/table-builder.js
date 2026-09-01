/**
 * table-builder.js — محرر جدول العلامات.
 *
 * المعاينة هي واجهة التحرير: النقر على رأس عمود أو مجموعة داخل الجدول
 * يفتح إعداداته في اللوحة الجانبية، وكل تعديل ينعكس على الجدول فورًا
 * من حالة محلية (draft) دون أي اتصال بالخادم حتى يضغط «حفظ».
 *
 * المعلم لا يرى: قالبًا، إصدارًا، مخططًا، ولا مفتاح عمود.
 *
 * قيدان يحكمان أي تعديل هنا:
 *  1) مخرَج renderPreview مقيَّد بـtests/preview_parity_test.php ليطابق
 *     App\Services\TableRenderer حرفًا بحرف. لا تغيّر ترميز الجدول؛
 *     الربط التفاعلي يتم بعد الإسناد عبر setAttribute/classList فقط.
 *  2) الاختبار ينفّذ الملف تحت DOM وهمي لا يعرف إلا
 *     querySelector / querySelectorAll (تعيد []) / createElement،
 *     و window بلا addEventListener. فاحرس أي استدعاء خارج ذلك.
 */
(() => {
  'use strict';

  const state = structuredClone(window.BUILDER_DATA);

  const TYPES = {
    manual_mark: 'علامة',
    calculated_total: 'مجموع تلقائي',
    calculated_average: 'متوسط',
    percentage: 'نسبة مئوية',
    text: 'نص',
    date: 'تاريخ',
  };
  const CALCULATED = new Set(['calculated_total', 'calculated_average', 'percentage', 'custom_formula']);
  const IDENTITY = new Set(['student_number', 'student_name']);
  const SCORED = new Set(['manual_mark']);

  const $ = selector => document.querySelector(selector);
  const $$ = selector => document.querySelectorAll(selector);
  const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  // الاسم الذي تستعمله كتلة المعاينة المقيَّدة — لا تُعدَّل الكتلة نفسها
  const escapeHtml = esc;

  /* ================================================================
   * المفتاح الداخلي — يولّده النظام ولا يظهر للمعلم
   * ============================================================== */

  function uniqueKey(name) {
    const latin = String(name || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    const base = latin.length > 1 && /^[a-z]/.test(latin) ? latin : 'col';
    const taken = new Set(state.columns.map(c => c.column_key));
    if (!taken.has(base)) return base;
    let n = 2;
    while (taken.has(base + '_' + n)) n += 1;
    return base + '_' + n;
  }

  const newGroupKey = () => 'grp_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 5);

  /* ================================================================
   * التحديد وحالة الحفظ
   * ============================================================== */

  /** @type {{kind:'template'|'group'|'column', key:string|null}} */
  let selection = { kind: 'template', key: null };
  let isDirty = false;

  const findGroup = key => state.groups.find(g => g.group_key === key) || null;
  const findColumn = key => state.columns.find(c => c.column_key === key) || null;

  const setSaveState = (text, saved) => {
    const node = $('#save-state');
    node.classList.toggle('saved', !!saved);
    node.innerHTML = '<i></i> ' + text;
  };
  const dirty = () => { isDirty = true; setSaveState('توجد تغييرات غير محفوظة', false); };

  window.addEventListener?.('beforeunload', event => {
    if (!isDirty) return;
    event.preventDefault();
    event.returnValue = '';
  });

  /* ================================================================
   * قراءة الحالة
   * ============================================================== */

  const identityColumns = () => state.columns.filter(c => IDENTITY.has(c.type));
  const rootGroups = () => state.groups.filter(g =>
    !g.parent_key || !state.groups.some(o => o.group_key === g.parent_key));

  const columnsOf = key => state.columns
    .filter(c => (c.header_group_key || null) === key && !IDENTITY.has(c.type))
    .sort((a, b) => Number(a.sort_order) - Number(b.sort_order));

  const topLevelItems = () => [
    ...rootGroups().map(group => ({
      kind: 'group', item: group,
      order: columnsOf(group.group_key)[0]?.sort_order ?? group.sort_order ?? Number.MAX_SAFE_INTEGER,
    })),
    ...columnsOf(null).map(column => ({ kind: 'column', item: column, order: column.sort_order })),
  ].sort((a, b) => Number(a.order) - Number(b.order));

  const sumOf = list => list.reduce((s, c) => s + (SCORED.has(c.type) ? Number(c.max_mark || 0) : 0), 0);
  const grandTotal = () => sumOf(state.columns);

  /** يرتب كتل المستوى الأعلى، بما فيها الأعمدة الواقعة بين المجموعات. */
  function applyTopLevelOrder(items) {
    const ordered = [...identityColumns()];
    let order = ordered.length + 1;
    ordered.forEach((column, index) => { column.sort_order = index + 1; });
    items.forEach(entry => {
      if (entry.kind === 'group') {
        entry.item.sort_order = order;
        const columns = columnsOf(entry.item.group_key);
        columns.forEach(column => { column.sort_order = order++; ordered.push(column); });
        if (!columns.length) order += 1;
      } else {
        entry.item.sort_order = order++;
        ordered.push(entry.item);
      }
    });
    state.columns = ordered;
  }

  const resequence = () => applyTopLevelOrder(topLevelItems());

  /* ================================================================
   * قائمة الترتيب — النقر يحدد، والسحب من المقبض وحده
   * ============================================================== */

  function structureRow(item, kind) {
    const key = kind === 'group' ? item.group_key : item.column_key;
    const active = selection.kind === kind && selection.key === key;
    const detail = kind === 'group'
      ? sumOf(columnsOf(key)) + ' درجة'
      : (SCORED.has(item.type) ? (item.max_mark ?? 0) + ' درجة' : TYPES[item.type] || item.type);
    return '<div class="tb-row ' + (kind === 'group' ? 'is-group ' : '') + (active ? 'is-active' : '') + '"'
      + ' data-kind="' + kind + '" data-key="' + esc(key) + '">'
      + '<span class="tb-grip" draggable="true" title="اسحب لإعادة الترتيب" aria-hidden="true">&#9776;</span>'
      + '<button type="button" class="tb-row-main" data-select="' + kind + ':' + esc(key) + '">'
      + '<strong>' + esc(item.name) + '</strong><small>' + esc(detail) + '</small></button>'
      + '<button type="button" class="tb-row-del" data-del="' + kind + ':' + esc(key) + '" aria-label="حذف ' + esc(item.name) + '">&times;</button>'
      + '</div>';
  }

  function renderStructure() {
    let html = '';
    topLevelItems().forEach(entry => {
      if (entry.kind === 'column') {
        html += structureRow(entry.item, 'column');
        return;
      }
      const group = entry.item;
      const inside = columnsOf(group.group_key);
      html += structureRow(group, 'group')
        + '<div class="tb-nest" data-drop-group="' + esc(group.group_key) + '">'
        + (inside.length ? inside.map(c => structureRow(c, 'column')).join('')
                         : '<p class="tb-nest-empty">اسحب عمودًا إلى هذه المجموعة</p>')
        + '<button type="button" class="tb-add-inline" data-add-col="' + esc(group.group_key) + '">&#65291; عمود في هذه المجموعة</button>'
        + '</div>';
    });

    const loose = columnsOf(null);
    html += '<div class="tb-root-drop" data-drop-group="">'
      + '<p class="tb-root-empty">اسحب عمودًا إلى هنا لإخراجه من مجموعته</p>'
      + '</div>';

    if (!state.groups.length && !loose.length) {
      html = '<div class="tb-empty"><h3>الجدول فارغ</h3>'
        + '<p>أضف مجموعة أعمدة (مثل: الشهر الأول)، أو عمودًا مستقلًا خارج المجموعات.</p></div>';
    }

    $('#structure').innerHTML = html;
    $('#grand-total').textContent = String(grandTotal());
    bindStructure();
  }

  function bindStructure() {
    $$('[data-select]').forEach(b => b.onclick = () => {
      const parts = b.dataset.select.split(':');
      select(parts[0], parts[1]);
    });
    $$('[data-del]').forEach(b => b.onclick = () => {
      const parts = b.dataset.del.split(':');
      if (parts[0] === 'group') removeGroup(parts[1]);
      else removeColumn(parts[1]);
    });
    $$('[data-add-col]').forEach(b => b.onclick = () => addColumn(b.dataset.addCol || null));
    bindDrag();
  }

  /** السحب من المقبض وحده، فلا يتعارض مع النقر للتحرير. */
  function bindDrag() {
    let dragged = null;

    $$('.tb-row').forEach(row => {
      const grip = row.querySelector('.tb-grip');
      if (grip) {
        grip.ondragstart = event => {
          dragged = { kind: row.dataset.kind, key: row.dataset.key };
          row.classList.add('dragging');
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('text/plain', row.dataset.key);
        };
        grip.ondragend = () => {
          row.classList.remove('dragging');
          $$('.tb-row').forEach(r => r.classList.remove('drop-over'));
          dragged = null;
        };
      }
      row.ondragover = event => {
        if (!dragged) return;
        const topLevelReorder = isTopLevel(dragged.kind, dragged.key)
          && isTopLevel(row.dataset.kind, row.dataset.key);
        if (!topLevelReorder && dragged.kind !== row.dataset.kind) return;
        event.preventDefault();
        row.classList.add('drop-over');
      };
      row.ondragleave = () => row.classList.remove('drop-over');
      row.ondrop = event => {
        event.preventDefault();
        event.stopPropagation();
        row.classList.remove('drop-over');
        if (!dragged) return;
        if (isTopLevel(dragged.kind, dragged.key) && isTopLevel(row.dataset.kind, row.dataset.key)) {
          reorderTopLevel(dragged.kind, dragged.key, row.dataset.kind, row.dataset.key);
        } else if (dragged.kind === 'column' && row.dataset.kind === 'column') {
          reorderColumns(dragged.key, row.dataset.key);
        }
      };
    });

    $$('[data-drop-group]').forEach(zone => {
      zone.ondragover = event => {
        if (!dragged || dragged.kind !== 'column') return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        zone.classList.add('drop-over');
      };
      zone.ondragleave = event => {
        if (!zone.contains(event.relatedTarget)) zone.classList.remove('drop-over');
      };
      zone.ondrop = event => {
        event.preventDefault();
        event.stopPropagation();
        zone.classList.remove('drop-over');
        if (!dragged || dragged.kind !== 'column') return;
        moveColumnToGroup(dragged.key, zone.dataset.dropGroup || null);
      };
    });
  }

  function isTopLevel(kind, key) {
    if (kind === 'group') {
      const group = findGroup(key);
      return !!group && (!group.parent_key || !findGroup(group.parent_key));
    }
    const column = findColumn(key);
    return !!column && !column.header_group_key && !IDENTITY.has(column.type);
  }

  function reorderTopLevel(fromKind, fromKey, toKind, toKey) {
    if (fromKind === toKind && fromKey === toKey) return;
    const items = topLevelItems();
    const from = items.findIndex(entry => entry.kind === fromKind
      && (fromKind === 'group' ? entry.item.group_key : entry.item.column_key) === fromKey);
    const to = items.findIndex(entry => entry.kind === toKind
      && (toKind === 'group' ? entry.item.group_key : entry.item.column_key) === toKey);
    if (from < 0 || to < 0) return;
    items.splice(to, 0, items.splice(from, 1)[0]);
    applyTopLevelOrder(items);
    dirty();
    renderAll();
  }

  function reorderColumns(fromKey, toKey) {
    if (fromKey === toKey) return;
    const moving = findColumn(fromKey);
    const target = findColumn(toKey);
    if (!moving || !target) return;
    // الإفلات على عمود في مجموعة أخرى ينقله إليها أيضًا
    moving.header_group_key = target.header_group_key || null;
    state.columns.splice(state.columns.indexOf(moving), 1);
    state.columns.splice(state.columns.indexOf(target), 0, moving);
    state.columns.forEach((c, i) => c.sort_order = i + 1);
    dirty();
    renderAll();
  }

  function moveColumnToGroup(columnKey, groupKey) {
    const moving = findColumn(columnKey);
    if (!moving || (groupKey && !findGroup(groupKey))) return;
    if ((moving.header_group_key || null) === groupKey) return;
    const destination = columnsOf(groupKey);
    moving.header_group_key = groupKey;
    moving.sort_order = destination.length
      ? Math.max(...destination.map(column => Number(column.sort_order))) + 1
      : state.columns.length + 1;
    resequence();
    dirty();
    renderAll();
  }

  /* ================================================================
   * لوحة الإعدادات — تتبع التحديد ولا تحجب المعاينة
   * ============================================================== */

  function select(kind, key) {
    selection = { kind, key: key || null };
    renderStructure();
    renderPanel();
    highlight();
  }

  const field = (label, input, hint) =>
    '<label class="tb-field"><span class="tb-field-label">' + esc(label) + '</span>' + input
    + (hint ? '<small>' + esc(hint) + '</small>' : '') + '</label>';

  function panelTemplate() {
    const groupOptions = (window.TEMPLATE_GROUPS || []).map(name => '<option value="' + esc(name) + '"></option>').join('');
    return '<header class="tb-panel-head"><small>إعدادات الجدول</small><strong>معلومات عامة</strong></header>'
      + '<div class="tb-panel-body">'
      + field('مجموعة القوالب', '<input id="f-template-group" type="text" list="template-group-options" maxlength="190" required value="' + esc(state.group_name || '') + '"><datalist id="template-group-options">' + groupOptions + '</datalist>', 'اختر مجموعة موجودة أو اكتب اسم مجموعة جديدة.')
      + field('اسم الجدول', '<input id="f-name" type="text" maxlength="190" value="' + esc(state.name) + '">')
      + field('وصف اختياري', '<input id="f-desc" type="text" maxlength="255" value="' + esc(state.description || '') + '">')
      + '<p class="tb-panel-hint">اضغط على أي عنوان داخل الجدول لتعديل ذلك العمود أو تلك المجموعة.</p>'
      + '</div>';
  }

  function panelGroup(group) {
    return '<header class="tb-panel-head"><small>إعدادات المجموعة</small><strong>' + esc(group.name) + '</strong></header>'
      + '<div class="tb-panel-body">'
      + field('اسم المجموعة', '<input id="f-gname" type="text" maxlength="190" value="' + esc(group.name) + '">')
      + '<details class="tb-adv"><summary>خيارات متقدمة</summary><div class="tb-adv-body">'
      + field('اتجاه العنوان',
          '<select id="f-gdir">'
          + '<option value="horizontal"' + (group.display_direction !== 'vertical' ? ' selected' : '') + '>أفقي</option>'
          + '<option value="vertical"' + (group.display_direction === 'vertical' ? ' selected' : '') + '>عمودي</option>'
          + '</select>', 'العمودي يوفّر عرضًا في الجداول الضيقة.')
      + '</div></details>'
      + '<button type="button" class="button danger block" data-del="group:' + esc(group.group_key) + '">حذف المجموعة</button>'
      + '</div>';
  }

  function panelColumn(column) {
    const calc = CALCULATED.has(column.type);
    const typeOptions = Object.keys(TYPES)
      .map(value => '<option value="' + value + '"' + (value === column.type ? ' selected' : '') + '>' + TYPES[value] + '</option>')
      .join('');

    const markField = (SCORED.has(column.type) || calc)
      ? field('العلامة القصوى', '<input id="f-mark" type="number" min="0" step="0.25" value="' + esc(column.max_mark ?? '') + '">')
      : '';

    let sources = '';
    if (calc) {
      const chosen = new Set((column.formula && column.formula.sources) || []);
      const usable = state.columns.filter(c =>
        !IDENTITY.has(c.type) && c.type !== 'text' && c.type !== 'date' && c.column_key !== column.column_key);
      sources = '<div class="tb-field"><span class="tb-field-label">يُحسب من</span><div class="tb-sources">'
        + (usable.length
            ? usable.map(c => '<label class="tb-source"><input type="checkbox" data-source value="' + esc(c.column_key) + '"'
                + (chosen.has(c.column_key) ? ' checked' : '') + '><span>' + esc(c.name) + '</span></label>').join('')
            : '<p class="tb-panel-hint">أضف أعمدة علامات أولًا.</p>')
        + '</div></div>';
    }

    const formulaAdvanced = calc
      ? field('المنازل العشرية', '<input id="f-decimals" type="number" min="0" max="4" value="' + esc((column.formula && column.formula.decimals) ?? 2) + '">')
        + field('قسمة الناتج على',
            '<input id="f-divisor" type="number" min="0.0001" step="0.25" value="' + esc((column.formula && column.formula.divisor) ?? 1) + '">',
            'يُقسم الناتج على هذا الرقم بعد الحساب. مثال: مجموع شهرين ÷ 2 لمعدّلهما. اتركه 1 لعدم القسمة.')
        + (column.type === 'percentage'
            ? field('العلامة الكاملة للنسبة',
                '<input id="f-base" type="number" min="0" value="' + esc((column.formula && column.formula.base) ?? '') + '">',
                'العلامة التي تُنسب إليها النتيجة: النسبة = المجموع ÷ هذه العلامة × 100.')
            : '')
      : '';

    return '<header class="tb-panel-head"><small>إعدادات العمود</small><strong>' + esc(column.name) + '</strong></header>'
      + '<div class="tb-panel-body">'
      + field('اسم العمود', '<input id="f-cname" type="text" maxlength="190" value="' + esc(column.name) + '">')
      + markField
      + field('نوع الإدخال', '<select id="f-ctype">' + typeOptions + '</select>')
      + sources
      + '<details class="tb-adv"><summary>خيارات متقدمة</summary><div class="tb-adv-body">'
      + field('مقدار الزيادة', '<input id="f-step" type="number" min="0.01" step="0.05" value="' + esc(column.step_value ?? 0.25) + '">',
          'أصغر مقدار يمكن إدخاله عند رصد العلامة. مثال: 0.25 يسمح بـ 0.25 و0.5 و0.75.')
      + field('عرض العمود', '<input id="f-width" type="number" min="6" value="' + esc(column.width_mm ?? 15) + '">',
          'عرضه النسبي في الجدول المطبوع.')
      + field('اتجاه العنوان',
          '<select id="f-cdir">'
          + '<option value="horizontal"' + (column.display_direction !== 'vertical' ? ' selected' : '') + '>أفقي</option>'
          + '<option value="vertical"' + (column.display_direction === 'vertical' ? ' selected' : '') + '>عمودي</option>'
          + '</select>')
      + field('عنوان مختلف في الرأس', '<input id="f-header" type="text" maxlength="190" value="' + esc(column.header_label || '') + '">',
          'يُطبع بدل اسم العمود عند تعبئته.')
      + formulaAdvanced
      + '<label class="tb-check"><input id="f-visible" type="checkbox"' + (column.is_visible ? ' checked' : '') + '> إظهار العمود في الجدول</label>'
      + '</div></details>'
      + '<button type="button" class="button danger block" data-del="column:' + esc(column.column_key) + '">حذف العمود</button>'
      + '</div>';
  }

  function renderPanel() {
    const panel = $('#panel');
    if (selection.kind === 'group') {
      const group = findGroup(selection.key);
      if (!group) { selection = { kind: 'template', key: null }; renderPanel(); return; }
      panel.innerHTML = panelGroup(group);
    } else if (selection.kind === 'column') {
      const column = findColumn(selection.key);
      if (!column) { selection = { kind: 'template', key: null }; renderPanel(); return; }
      panel.innerHTML = panelColumn(column);
    } else {
      panel.innerHTML = panelTemplate();
    }
    bindPanel();
  }

  /** كل تغيير يكتب في الحالة المحلية ثم يعيد رسم المعاينة فورًا. */
  function bindPanel() {
    const live = (selector, apply) => {
      const input = $(selector);
      if (!input) return;
      const run = () => { apply(input); dirty(); renderPreviewAndBind(); refreshTotals(); };
      input.oninput = run;
      if (input.tagName === 'SELECT') input.onchange = run;
    };

    live('#f-name', i => { state.name = i.value; });
    live('#f-desc', i => { state.description = i.value; });
    live('#f-template-group', i => {
      state.group_id = 0;
      state.group_name = i.value;
      $('.report-heading-title span').textContent = i.value || 'مجموعة القوالب';
    });

    const group = selection.kind === 'group' ? findGroup(selection.key) : null;
    if (group) {
      live('#f-gname', i => { group.name = i.value; $('.tb-panel-head strong').textContent = i.value; });
      live('#f-gdir', i => { group.display_direction = i.value; });
    }

    const column = selection.kind === 'column' ? findColumn(selection.key) : null;
    if (column) {
      live('#f-cname', i => { column.name = i.value; $('.tb-panel-head strong').textContent = i.value; });
      live('#f-mark', i => { column.max_mark = i.value; });
      live('#f-step', i => { column.step_value = i.value; });
      live('#f-width', i => { column.width_mm = i.value; });
      live('#f-cdir', i => { column.display_direction = i.value; });
      live('#f-header', i => { column.header_label = i.value; });
      live('#f-decimals', i => { ensureFormula(column).decimals = i.value; });
      live('#f-divisor', i => { ensureFormula(column).divisor = i.value; });
      live('#f-base', i => { ensureFormula(column).base = i.value; });

      const visible = $('#f-visible');
      if (visible) visible.onchange = () => {
        column.is_visible = visible.checked;
        dirty();
        renderPreviewAndBind();
      };

      const type = $('#f-ctype');
      if (type) type.onchange = () => {
        column.type = type.value;
        if (CALCULATED.has(column.type)) ensureFormula(column);
        else delete column.formula;
        dirty();
        renderAll();
      };

      $$('[data-source]').forEach(box => box.onchange = () => {
        const picked = [];
        $$('[data-source]').forEach(other => { if (other.checked) picked.push(other.value); });
        ensureFormula(column).sources = picked;
        dirty();
        renderPreviewAndBind();
      });
    }

    $$('[data-del]').forEach(b => b.onclick = () => {
      const parts = b.dataset.del.split(':');
      if (parts[0] === 'group') removeGroup(parts[1]);
      else removeColumn(parts[1]);
    });
  }

  function ensureFormula(column) {
    if (!column.formula) {
      column.formula = {
        type: 'SUM', sources: [], missing: 'blank', base: null, divisor: 1, decimals: 2,
      };
    }
    column.formula.type = column.type === 'calculated_average' ? 'AVERAGE'
      : column.type === 'percentage' ? 'PERCENTAGE' : 'SUM';
    return column.formula;
  }

  const refreshTotals = () => {
    $('#grand-total').textContent = String(grandTotal());
    $$('.tb-row').forEach(row => {
      const item = row.dataset.kind === 'group' ? findGroup(row.dataset.key) : findColumn(row.dataset.key);
      if (!item) return;
      const strong = row.querySelector('strong');
      if (strong) strong.textContent = item.name;
      const small = row.querySelector('small');
      if (!small) return;
      small.textContent = row.dataset.kind === 'group'
        ? sumOf(columnsOf(row.dataset.key)) + ' درجة'
        : (SCORED.has(item.type) ? (item.max_mark ?? 0) + ' درجة' : (TYPES[item.type] || item.type));
    });
  };

  /* ================================================================
   * الإضافة والحذف
   * ============================================================== */

  function addGroup() {
    const group = {
      group_key: newGroupKey(), name: 'مجموعة جديدة', parent_key: null,
      sort_order: state.groups.length + 1, text_direction: 'rtl', display_direction: 'horizontal',
    };
    state.groups.push(group);
    resequence();
    dirty();
    select('group', group.group_key);
    renderPreviewAndBind();
    focusFirst();
  }

  /** groupKey = null يعني عمودًا مستقلًا خارج المجموعات. */
  function addColumn(groupKey) {
    const column = {
      column_key: uniqueKey('col'), name: 'عمود جديد', header_label: '', type: 'manual_mark',
      max_mark: 5, step_value: 0.25, width_mm: 15, sort_order: state.columns.length + 1,
      is_visible: true, header_group_key: groupKey || null,
      text_direction: 'rtl', display_direction: 'horizontal', formula: null,
    };
    state.columns.push(column);
    resequence();
    dirty();
    select('column', column.column_key);
    renderPreviewAndBind();
    focusFirst();
  }

  const focusFirst = () => {
    const input = $('#panel input[type="text"]');
    if (input) { input.focus?.(); input.select?.(); }
  };

  async function removeGroup(key) {
    const group = findGroup(key);
    if (!group) return;
    const inside = columnsOf(key).length;
    const ok = await confirmAction('حذف مجموعة «' + group.name + '»؟',
      inside ? 'أعمدتها (' + inside + ') لن تُحذف، وستنتقل إلى خارج المجموعات.' : 'المجموعة فارغة.');
    if (!ok) return;
    state.columns.forEach(c => { if (c.header_group_key === key) c.header_group_key = null; });
    state.groups.forEach(g => { if (g.parent_key === key) g.parent_key = null; });
    state.groups.splice(state.groups.indexOf(group), 1);
    resequence();
    dirty();
    select('template', null);
    renderPreviewAndBind();
  }

  async function removeColumn(key) {
    const column = findColumn(key);
    if (!column) return;
    const dependents = state.columns.filter(c => c.formula && (c.formula.sources || []).includes(key));
    const ok = await confirmAction('حذف العمود «' + column.name + '»؟',
      dependents.length
        ? 'يُحسب منه: ' + dependents.map(c => c.name).join('، ') + '. ستفقد ارتباطها به.'
        : 'سيُزال من هذا الجدول. الاختبارات المحفوظة سابقًا لا تتأثر.');
    if (!ok) return;
    state.columns.forEach(c => {
      if (c.formula && c.formula.sources) c.formula.sources = c.formula.sources.filter(k => k !== key);
    });
    state.columns.splice(state.columns.indexOf(column), 1);
    resequence();
    dirty();
    select('template', null);
    renderPreviewAndBind();
  }

  async function confirmAction(title, message) {
    if (window.UI && window.UI.confirm) {
      return window.UI.confirm({ title: title, message: message, confirmLabel: 'حذف', danger: true });
    }
    return confirm(title + '\n' + message);
  }

  /* ================================================================
   * المعاينة — مقيَّدة بالتطابق مع TableRenderer. لا تغيّر الترميز.
   * ============================================================== */

  function headerRows(){const roots=state.groups.filter(group=>!group.parent_key),children=key=>state.groups.filter(group=>group.parent_key===key),depth=group=>1+Math.max(0,...children(group.group_key).map(depth)),maxDepth=Math.max(1,(roots.length?Math.max(...roots.map(depth)):0)+1),rows=Array.from({length:maxDepth},()=>[]),ids=group=>[group.group_key,...children(group.group_key).flatMap(ids)],order=group=>Math.min(...state.columns.filter(column=>column.is_visible&&ids(group).includes(column.header_group_key)).map(column=>Number(column.sort_order)),Number.MAX_SAFE_INTEGER),cell=(column,rowspan)=>({kind:'column',ref:column.column_key,label:column.header_label||column.name,max:column.max_mark,colspan:1,rowspan,vertical:column.display_direction==='vertical',total:column.type==='calculated_total'});const walk=(group,level)=>{const columns=state.columns.filter(column=>column.is_visible&&ids(group).includes(column.header_group_key));if(!columns.length)return;rows[level].push({kind:'group',ref:group.group_key,label:group.name,colspan:columns.length,rowspan:1,vertical:group.display_direction==='vertical',total:false});[...children(group.group_key).map(item=>({kind:'group',item,order:order(item)})),...state.columns.filter(column=>column.is_visible&&column.header_group_key===group.group_key).map(item=>({kind:'column',item,order:Number(item.sort_order)}))].sort((a,b)=>a.order-b.order).forEach(entry=>entry.kind==='group'?walk(entry.item,level+1):rows[level+1].push(cell(entry.item,maxDepth-level-1)));};[...roots.map(item=>({kind:'group',item,order:order(item)})),...state.columns.filter(column=>column.is_visible&&!column.header_group_key).map(item=>({kind:'column',item,order:Number(item.sort_order)}))].sort((a,b)=>a.order-b.order).forEach(entry=>entry.kind==='group'?walk(entry.item,0):rows[0].push(cell(entry.item,maxDepth)));return rows;}

  function mergeEquivalentHeaders(rows){return rows.map(row=>row.reduce((merged,header)=>{const previous=merged.at(-1),equivalent=header.kind==='column'&&previous?.kind==='column'&&header.label===previous.label&&header.max===previous.max&&header.rowspan===previous.rowspan&&header.vertical===previous.vertical&&header.total===previous.total;if(equivalent)previous.colspan+=header.colspan;else merged.push({...header});return merged;},[]));}

  function calculate(values){let changed=true,guard=0;while(changed&&guard++<state.columns.length){changed=false;state.columns.forEach(column=>{if(!column.formula||values[column.column_key]!=null)return;const sources=column.formula.sources.map(key=>values[key]);if(!sources.length||sources.some(value=>value==null))return;const sum=sources.reduce((total,value)=>total+Number(value),0);const raw=column.formula.type==='AVERAGE'?sum/sources.length:column.formula.type==='PERCENTAGE'?sum/Number(column.formula.base||1)*100:sum;const divisor=Number(column.formula.divisor)>0?Number(column.formula.divisor):1;values[column.column_key]=Number((raw/divisor).toFixed(Number(column.formula.decimals??2)));changed=true;});}return values;}

  const formatMark = value => String(Number(value)).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');

  // نفس ترميز TableRenderer::render حرفيًا، ليطابق ما يُطبع
  function renderPreview(){
    const columns=state.columns.filter(column=>column.is_visible);
    const total=columns.reduce((sum,column)=>sum+Math.max(.1,Number(column.width_mm||15)),0)||1;
    const sample={};
    columns.filter(column=>column.type==='manual_mark').forEach(column=>sample[column.column_key]=Number(column.max_mark||1));
    calculate(sample);
    const heading=$('#preview-sheet .report-heading-title strong');
    if(heading)heading.textContent=state.name||'جدول العلامات';
    if(!columns.length){$('#live-preview').innerHTML='<p class="tb-preview-empty">أضف عمودًا لبدء المعاينة</p>';return;}
    const colgroup=columns.map(column=>`<col style="width:${formatMark(Math.max(.1,Number(column.width_mm||15))/total*100)}%">`).join('');
    const thead=mergeEquivalentHeaders(headerRows()).map(row=>`<tr>${row.map(header=>{
      const classes=[header.kind,header.vertical?'vertical-header':'',header.total?'total-column':''].filter(Boolean).join(' ');
      const mark=header.max!==''&&header.max!=null?`<small class="header-mark" dir="ltr">(${escapeHtml(formatMark(header.max))})</small>`:'';
      return `<th class="${classes}" colspan="${header.colspan}" rowspan="${header.rowspan}"><span><span class="header-label">${escapeHtml(header.label)}</span>${mark}</span></th>`;
    }).join('')}</tr>`).join('');
    const names=['أحمد محمد','سارة خالد','ليان عمر'];
    const tbody=names.map((name,row)=>`<tr data-student-id="${row+1}">${columns.map(column=>{
      const value=column.type==='student_number'?row+1:column.type==='student_name'?name:CALCULATED.has(column.type)||column.type==='manual_mark'?formatMark(Number(sample[column.column_key]??0).toFixed(2)):'';
      return `<td${column.type==='calculated_total'?' class="total-column"':''} data-column-key="${escapeHtml(column.column_key)}"><span class="cell-value">${escapeHtml(value)}</span></td>`;
    }).join('')}</tr>`).join('');
    $('#live-preview').innerHTML=`<table class="dynamic-report-table"><colgroup>${colgroup}</colgroup><thead>${thead}</thead><tbody>${tbody}</tbody></table>`;
  }

  /* ================================================================
   * جعل المعاينة تفاعلية — بعد الإسناد، فلا يتغيّر الترميز المقارَن
   * ============================================================== */

  function bindPreview() {
    const stage = $('#live-preview');
    const rows = stage.querySelectorAll('thead tr');
    if (!rows.length) return;
    let draggedPreviewItem = null;

    const clearPreviewDrag = () => {
      stage.querySelectorAll('.is-dragging, .is-drop-target').forEach(node => {
        node.classList.remove('is-dragging');
        node.classList.remove('is-drop-target');
      });
      draggedPreviewItem = null;
    };

    const matrix = headerRows() || [];
    matrix.forEach((cells, rowIndex) => {
      const ths = rows[rowIndex] ? rows[rowIndex].children : [];
      cells.forEach((cell, cellIndex) => {
        const th = ths[cellIndex];
        if (!th || !cell.ref) return;
        th.setAttribute('data-pick', cell.kind + ':' + cell.ref);
        th.setAttribute('role', 'button');
        th.setAttribute('tabindex', '0');
        const column = cell.kind === 'column' ? findColumn(cell.ref) : null;
        const movableColumn = column && !IDENTITY.has(column.type);
        const movableGroup = cell.kind === 'group' && isTopLevel('group', cell.ref);
        const movable = movableColumn || movableGroup;
        th.setAttribute('title', movableGroup ? 'اسحب لتغيير مكان المجموعة أو أفلت عمودًا داخلها'
          : movableColumn ? 'اسحب لتغيير مكان العمود' : 'تعديل العمود');
        th.classList.add('is-pickable');
        th.onclick = () => select(cell.kind, cell.ref);
        th.onkeydown = event => {
          if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); select(cell.kind, cell.ref); }
        };
        if (movable) {
          th.setAttribute('draggable', 'true');
          th.ondragstart = event => {
            draggedPreviewItem = { kind: cell.kind, key: cell.ref };
            th.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', cell.ref);
          };
          th.ondragend = clearPreviewDrag;
        }
        if (cell.kind === 'group' || (column && !IDENTITY.has(column.type))) {
          th.ondragover = event => {
            if (!draggedPreviewItem
              || (draggedPreviewItem.kind === cell.kind && draggedPreviewItem.key === cell.ref)) return;
            const canDropGroup = draggedPreviewItem.kind === 'group'
              && isTopLevel(cell.kind, cell.ref);
            const canDropColumn = draggedPreviewItem.kind === 'column';
            if (!canDropGroup && !canDropColumn) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'move';
            th.classList.add('is-drop-target');
          };
          th.ondragleave = () => th.classList.remove('is-drop-target');
          th.ondrop = event => {
            event.preventDefault();
            const moving = draggedPreviewItem;
            clearPreviewDrag();
            if (!moving || (moving.kind === cell.kind && moving.key === cell.ref)) return;
            if (moving.kind === 'group') {
              reorderTopLevel('group', moving.key, cell.kind, cell.ref);
            } else if (cell.kind === 'group') {
              moveColumnToGroup(moving.key, cell.ref);
            } else {
              reorderColumns(moving.key, cell.ref);
            }
          };
        }
      });
    });

    highlight();
  }

  /** يُظهر داخل الجدول أي عنصر يجري تعديله الآن. */
  function highlight() {
    const stage = $('#live-preview');
    stage.querySelectorAll('.is-picked').forEach(node => node.classList.remove('is-picked'));
    if (!selection.key) return;

    const th = stage.querySelector('[data-pick="' + selection.kind + ':' + cssEscape(selection.key) + '"]');
    if (th) th.classList.add('is-picked');

    if (selection.kind === 'column') {
      stage.querySelectorAll('[data-column-key]').forEach(cell => {
        if (cell.dataset.columnKey === selection.key) cell.classList.add('is-picked');
      });
    }
  }

  const cssEscape = value => (window.CSS && window.CSS.escape) ? window.CSS.escape(value) : String(value);

  const renderPreviewAndBind = () => { renderPreview(); bindPreview(); };

  /* ================================================================
   * الربط العام والحفظ
   * ============================================================== */

  const renderAll = () => { renderStructure(); renderPanel(); renderPreviewAndBind(); };

  $('#add-group').onclick = addGroup;
  $('#add-root-column').onclick = () => addColumn(null);

  async function save() {
    resequence();
    const buttons = [$('#save-template'), $('#save-template-2')];
    buttons.forEach(b => { b.disabled = true; b.classList.add('is-loading'); });
    setSaveState('جارٍ الحفظ…', false);
    try {
      const response = await fetch(APP.baseUrl + '/api/templates/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': APP.csrf },
        body: JSON.stringify(state),
      });
      const data = await response.json();
      if (!data.ok) throw new Error(data.message);
      isDirty = false;
      setSaveState('تم الحفظ', true);
      if (window.UI) window.UI.toast('حُفظ جدول العلامات.', 'success', 2500);
      if (!state.template_id) location.href = APP.baseUrl + '/admin/templates/edit.php?id=' + data.id;
    } catch (error) {
      // التعديلات المحلية تبقى كما هي والمعاينة لا تتأثر
      setSaveState('تعذّر الحفظ — تغييراتك محفوظة محليًا', false);
      if (window.UI) {
        window.UI.toast((error.message || 'تعذّر حفظ الجدول.') + ' تغييراتك لم تُفقد؛ أعد المحاولة.', 'error', 6000);
      }
    } finally {
      buttons.forEach(b => { b.disabled = false; b.classList.remove('is-loading'); });
    }
  }

  $('#save-template').onclick = save;
  $('#save-template-2').onclick = save;

  function fitMobilePreview() {
    const viewport = $('#preview-viewport');
    const sheet = $('#preview-sheet');
    if (!viewport || !sheet || !window.matchMedia?.('(max-width: 560px)').matches) {
      sheet?.style?.removeProperty?.('--preview-scale');
      return;
    }
    const sheetWidth = sheet.offsetWidth;
    if (sheetWidth) sheet.style.setProperty?.('--preview-scale', String(viewport.clientWidth / sheetWidth));
  }

  if (window.ResizeObserver) {
    const previewViewport = $('#preview-viewport');
    if (previewViewport) new ResizeObserver(fitMobilePreview).observe(previewViewport);
  } else {
    window.addEventListener?.('resize', fitMobilePreview);
  }

  renderAll();
  fitMobilePreview();
})();
