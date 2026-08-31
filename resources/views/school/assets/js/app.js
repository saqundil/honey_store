/**
 * app.js — طبقة الواجهة المشتركة.
 * تُحمَّل في كل شاشة. لا تعتمد على أي مكتبة خارجية.
 *
 * تقدّم:
 *   - إبقاء عنصر التنقّل النشط ظاهرًا في الشريط القابل للتمرير
 *   - إشعارات toast تحلّ محل alert()
 *   - نافذة تأكيد تحلّ محل confirm()
 *   - تحويل الجداول إلى بطاقات على الشاشات الضيقة (data-label)
 */
(() => {
    'use strict';

    /* ------------------------------------------------------------------
     * 1. شريط التنقّل: إبقاء الصفحة الحالية ظاهرة
     *    الشريط يمرّر أفقيًا على الشاشات الضيقة، فقد يقع العنصر النشط
     *    خارج الرؤية. نعتمد scrollIntoView مع block:'nearest' لأن حساب
     *    scrollLeft يدويًا غير موثوق في RTL، و'nearest' يمنع أي إزاحة رأسية.
     * ---------------------------------------------------------------- */
    const navBar = document.querySelector('.appbar-nav');
    const current = navBar?.querySelector('.nav-link.active');

    if (navBar && current && navBar.scrollWidth > navBar.clientWidth) {
        current.scrollIntoView({ inline: 'center', block: 'nearest' });
    }

    /* ------------------------------------------------------------------
     * 2. إشعارات Toast
     * ---------------------------------------------------------------- */
    const ICONS = { success: '✓', error: '!', warn: '!', info: 'i' };

    let stack = null;
    const getStack = () => {
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            stack.setAttribute('role', 'status');
            stack.setAttribute('aria-live', 'polite');
            document.body.appendChild(stack);
        }
        return stack;
    };

    /**
     * @param {string} message نص الإشعار
     * @param {'success'|'error'|'warn'|'info'} kind
     * @param {number} duration بالمللي ثانية؛ 0 يعني بلا إخفاء تلقائي
     */
    function toast(message, kind = 'info', duration = 4500) {
        const node = document.createElement('div');
        node.className = 'toast';
        node.dataset.kind = kind;

        const icon = document.createElement('span');
        icon.className = 'toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = ICONS[kind] || ICONS.info;

        const body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', 'إغلاق الإشعار');
        close.textContent = '×';

        node.append(icon, body, close);
        getStack().appendChild(node);

        const dismiss = () => {
            node.classList.add('is-leaving');
            node.addEventListener('animationend', () => node.remove(), { once: true });
        };

        close.addEventListener('click', dismiss);
        if (duration > 0) setTimeout(dismiss, duration);

        return dismiss;
    }

    /* ------------------------------------------------------------------
     * 3. نافذة تأكيد — بديل confirm() بمظهر المنتج
     * ---------------------------------------------------------------- */
    function confirmDialog({ title, message = '', confirmLabel = 'تأكيد', cancelLabel = 'إلغاء', danger = false }) {
        return new Promise(resolve => {
            const previous = document.activeElement;

            const scrimEl = document.createElement('div');
            scrimEl.className = 't-modal-scrim';

            const modal = document.createElement('div');
            modal.className = 't-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');

            const heading = document.createElement('h3');
            heading.textContent = title;
            modal.appendChild(heading);
            modal.setAttribute('aria-label', title);

            if (message) {
                const paragraph = document.createElement('p');
                paragraph.textContent = message;
                modal.appendChild(paragraph);
            }

            const actions = document.createElement('div');
            actions.className = 't-modal-actions';

            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'button';
            cancel.textContent = cancelLabel;

            const accept = document.createElement('button');
            accept.type = 'button';
            accept.className = danger ? 'button danger' : 'button primary';
            accept.textContent = confirmLabel;

            actions.append(accept, cancel);
            modal.appendChild(actions);
            scrimEl.appendChild(modal);
            document.body.appendChild(scrimEl);
            document.body.style.overflow = 'hidden';

            accept.focus({ preventScroll: true });

            const finish = result => {
                document.body.style.overflow = '';
                scrimEl.remove();
                previous instanceof HTMLElement && previous.focus({ preventScroll: true });
                resolve(result);
            };

            accept.addEventListener('click', () => finish(true));
            cancel.addEventListener('click', () => finish(false));
            scrimEl.addEventListener('mousedown', event => {
                if (event.target === scrimEl) finish(false);
            });

            // حصر التركيز داخل النافذة
            modal.addEventListener('keydown', event => {
                if (event.key === 'Escape') { finish(false); return; }
                if (event.key !== 'Tab') return;
                const focusables = [accept, cancel];
                const index = focusables.indexOf(document.activeElement);
                event.preventDefault();
                const next = event.shiftKey ? index - 1 : index + 1;
                focusables[(next + focusables.length) % focusables.length].focus();
            });
        });
    }

    /* ------------------------------------------------------------------
     * 4. جداول قابلة للتكديس على الجوال
     *    ينسخ نص كل <th> إلى data-label في خلايا عموده.
     * ---------------------------------------------------------------- */
    function labelTableCells(root = document) {
        root.querySelectorAll('table.data-table').forEach(table => {
            const headings = [...table.querySelectorAll('thead th')].map(th => th.textContent.trim());
            if (!headings.length) return;

            table.classList.add('is-stacked');
            table.querySelectorAll('tbody tr').forEach(row => {
                [...row.children].forEach((cell, index) => {
                    if (cell.hasAttribute('data-label')) return;
                    const label = headings[index];
                    if (label) cell.setAttribute('data-label', label);
                });
            });
        });
    }

    labelTableCells();

    /* ------------------------------------------------------------------
     * 5. تصدير الواجهة العامة
     * ---------------------------------------------------------------- */
    window.UI = { toast, confirm: confirmDialog, labelTableCells };
})();
