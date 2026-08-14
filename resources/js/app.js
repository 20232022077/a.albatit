import './bootstrap';

/**
 * Public site header: hamburger toggle for the mobile nav menu.
 */
document.querySelectorAll('[data-mobile-menu-toggle]').forEach((toggle) => {
    const menu = document.querySelector('#' + toggle.getAttribute('aria-controls'));
    const iconOpen = toggle.querySelector('[data-menu-icon-open]');
    const iconClose = toggle.querySelector('[data-menu-icon-close]');
    if (!menu) return;

    const setOpen = (isOpen) => {
        menu.classList.toggle('hidden', !isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
        iconOpen?.classList.toggle('hidden', isOpen);
        iconClose?.classList.toggle('hidden', !isOpen);
    };

    toggle.addEventListener('click', () => setOpen(menu.classList.contains('hidden')));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !menu.classList.contains('hidden')) {
            setOpen(false);
            toggle.focus();
        }
    });
});

/**
 * Admin panel: off-canvas sidebar toggle for small/medium screens.
 */
document.querySelectorAll('[data-admin-shell]').forEach((shell) => {
    const sidebar = shell.querySelector('[data-sidebar]');
    const overlay = shell.querySelector('[data-sidebar-overlay]');
    const openButton = shell.querySelector('[data-sidebar-open]');
    const closeButton = shell.querySelector('[data-sidebar-close]');
    if (!sidebar || !overlay) return;

    const setOpen = (isOpen) => {
        sidebar.classList.toggle('translate-x-full', !isOpen);
        overlay.classList.toggle('hidden', !isOpen);
        openButton?.setAttribute('aria-expanded', String(isOpen));
    };

    openButton?.addEventListener('click', () => setOpen(true));
    closeButton?.addEventListener('click', () => setOpen(false));
    overlay.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !sidebar.classList.contains('translate-x-full')) {
            setOpen(false);
            openButton?.focus();
        }
    });
});

/**
 * data-confirm="message" on a <form> asks for confirmation before it
 * submits (used by destructive admin actions). Kept out of inline
 * onclick="" attributes so the CSP can run without 'unsafe-inline'.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.confirm === undefined) return;

    if (!window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
});

/**
 * data-autosubmit on a <select> or <input> submits its form on change
 * (used by list filter/sort controls and the "show trashed" checkbox).
 */
document.addEventListener('change', (event) => {
    const field = event.target;
    if ((field instanceof HTMLSelectElement || field instanceof HTMLInputElement) && field.dataset.autosubmit !== undefined) {
        field.form?.submit();
    }
});

/**
 * Admin biography editor: add/remove repeatable "section" rows (education,
 * work experience, etc.) from the <template> without a page reload.
 */
(() => {
    const container = document.getElementById('sections-container');
    const template = document.getElementById('section-template');
    const hint = document.getElementById('no-sections-hint');
    if (!container || !template) return;

    let index = container.querySelectorAll('.section-row').length;

    document.getElementById('add-section')?.addEventListener('click', () => {
        const clone = template.content.cloneNode(true);
        clone.querySelectorAll('[name*="__INDEX__"]').forEach((el) => {
            el.name = el.name.replace('__INDEX__', index);
        });
        container.appendChild(clone);
        index++;
        hint?.classList.add('hidden');
    });

    container.addEventListener('click', (event) => {
        if (event.target instanceof HTMLElement && event.target.classList.contains('remove-section')) {
            event.target.closest('.section-row')?.remove();
            if (!container.querySelector('.section-row')) {
                hint?.classList.remove('hidden');
            }
        }
    });
})();

/**
 * Global form-submit loading state: disables the submit button and swaps
 * its label so a slow request can't be double-submitted, and the user gets
 * immediate feedback that something is happening.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.noLoadingState !== undefined) return;

    const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
    if (!(submitter instanceof HTMLButtonElement) || submitter.disabled) return;

    submitter.dataset.originalLabel = submitter.innerHTML;
    submitter.disabled = true;
    submitter.setAttribute('aria-busy', 'true');
    submitter.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span>' + submitter.dataset.originalLabel + '</span></span>';
});
