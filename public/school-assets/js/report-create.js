(() => {
  'use strict';

  const classSelect = document.querySelector('#report-class');
  const searchInput = document.querySelector('#student-search');
  const templateSelect = document.querySelector('#report-template');
  const groupSummary = document.querySelector('[data-group-summary]');
  const studentRows = [...document.querySelectorAll('.student-row')];
  const countLabel = document.querySelector('#student-count');
  const summaryCount = document.querySelector('#summary-count');
  const summaryTemplate = document.querySelector('#summary-template');
  const emptyState = document.querySelector('#students-empty');
  const submitButton = document.querySelector('.create-report-button');
  const semesterInput = document.querySelector('#report-semester');

  if (templateSelect && templateSelect.dataset.requestedTemplate !== 'true') {
    const savedVersion = localStorage.getItem('report-template-version');
    if (savedVersion && [...templateSelect.options].some(option => option.value === savedVersion)) {
      templateSelect.value = savedVersion;
    }
  }

  const normalized = value => value.trim().toLocaleLowerCase('ar');
  const classRows = () => studentRows.filter(row => row.dataset.class === classSelect.value);
  const visibleRows = () => classRows().filter(row => !row.hidden);
  const selectedRows = () => classRows().filter(row => row.querySelector('input').checked);

  function updateSummary() {
    const count = selectedRows().length;
    countLabel.textContent = `${count} / 32`;
    summaryCount.textContent = count;
    summaryTemplate.textContent = groupSummary?.dataset.groupSummary
      || templateSelect?.options[templateSelect.selectedIndex]?.textContent
      || '';
    submitButton.disabled = count === 0;
  }

  function filterStudents() {
    const query = normalized(searchInput.value);
    let visibleCount = 0;
    studentRows.forEach(row => {
      const correctClass = row.dataset.class === classSelect.value;
      const matchesSearch = !query || normalized(row.dataset.name).includes(query);
      row.hidden = !correctClass || !matchesSearch;
      if (correctClass && matchesSearch) visibleCount += 1;
    });
    emptyState.hidden = visibleCount > 0;
    updateSummary();
  }

  function syncClass() {
    semesterInput.value = classSelect.selectedOptions[0]?.dataset.semester || '';
    studentRows.forEach(row => row.querySelector('input').checked = false);
    classRows().slice(0, 32).forEach(row => row.querySelector('input').checked = true);
    searchInput.value = '';
    filterStudents();
  }

  studentRows.forEach(row => row.querySelector('input').addEventListener('change', event => {
    if (event.target.checked && selectedRows().length > 32) {
      event.target.checked = false;
      UI.toast('الحد الأقصى للتقرير الواحد هو 32 طالبًا.','warn');
    }
    updateSummary();
  }));

  document.querySelector('#select-all-students').addEventListener('click', () => {
    visibleRows().slice(0, 32).forEach(row => row.querySelector('input').checked = true);
    updateSummary();
  });
  document.querySelector('#clear-students').addEventListener('click', () => {
    classRows().forEach(row => row.querySelector('input').checked = false);
    updateSummary();
  });
  classSelect.addEventListener('change', syncClass);
  searchInput.addEventListener('input', filterStudents);
  templateSelect?.addEventListener('change', () => {
    localStorage.setItem('report-template-version', templateSelect.value);
    updateSummary();
  });
  document.querySelector('#report-create-form').addEventListener('submit', event => {
    if (!selectedRows().length) {
      event.preventDefault();
      searchInput.focus();
    }
  });

  if (!classRows().length && studentRows.length) {
    classSelect.value = studentRows[0].dataset.class;
  }
  syncClass();
})();
