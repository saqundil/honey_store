/* ============================================================
   teacher-class.js — تصفية قائمة اختبارات الصف.
   إنشاء الاختبار انتقل إلى معالج مستقل (exam-wizard.js).
   ============================================================ */

(() => {
    'use strict';
    const app = window.APP;
    const data = window.CLASS_DATA;
    if (!app || !data) return;

    /* ---------- filter chips ---------- */
    const cards = [...document.querySelectorAll('.exam-card')];
    const filters = [...document.querySelectorAll('.exam-filter')];
    let activeFilter = 'all';

    function applyFilter(status){
        activeFilter = status;
        filters.forEach(f => f.setAttribute('aria-pressed', f.dataset.filter === status ? 'true' : 'false'));
        let visible = 0;
        cards.forEach(card => {
            const match = status === 'all' || card.dataset.status === status;
            card.classList.toggle('is-hidden', !match);
            if (match) visible += 1;
        });
        const emptyNote = document.querySelector('.exam-empty-filter');
        if (emptyNote) emptyNote.hidden = visible !== 0;
    }
    filters.forEach(f => f.addEventListener('click', () => applyFilter(f.dataset.filter)));

})();
