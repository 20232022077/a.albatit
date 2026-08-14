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
