import './bootstrap';

/**
 * Scroll-reveal for public page sections (see [data-reveal-scope] in
 * layouts/public.blade.php and the matching CSS in app.css). A <section>
 * that's already on screen at load time is left completely alone — only
 * sections still below the fold get hidden-then-revealed, so there's never
 * a flash of content disappearing or popping in unstyled.
 */
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.08, rootMargin: '0px 0px -80px 0px' }
    );

    document.querySelectorAll('[data-reveal-scope] section').forEach((el) => {
        const rect = el.getBoundingClientRect();
        const alreadyVisible = rect.top < window.innerHeight && rect.bottom > 0;
        if (alreadyVisible) return;

        el.classList.add('reveal-pending');
        requestAnimationFrame(() => el.classList.add('reveal-init'));
        observer.observe(el);
    });
}

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

/**
 * Admin book form: if the admin doesn't pick a cover image themselves,
 * render the first page of the selected PDF to a canvas and use that as
 * the cover file — done entirely client-side (pdf.js loaded on demand) so
 * the server needs no PDF-rendering dependency (Imagick/Ghostscript aren't
 * installed). A cover the admin picks by hand always takes priority and is
 * never overwritten by this.
 */
/**
 * Share button (see partials/share-button.blade.php): opens the OS-native
 * share sheet where available, otherwise a small menu of share links.
 * Delegated at the document level since a single card grid can render many
 * of these at once. The menu is moved to a direct child of <body> the
 * first time it opens — every card it can appear inside already has both
 * `overflow-hidden` and a `hover:-translate-y-2` transform (the transform
 * becomes active while the card is hovered to click the button at all,
 * which creates a new containing block for `position: fixed` descendants),
 * so leaving the menu nested inside the card would clip or mis-position
 * it. Positioned from the trigger's own on-screen coordinates instead.
 */
(() => {
    const shareLinkBuilders = {
        whatsapp: (title, url) => `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`,
        facebook: (title, url) => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        x: (title, url) => `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`,
        telegram: (title, url) => `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
        email: (title, url) => `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`,
    };

    const closeAllShareMenus = () => {
        document.querySelectorAll('[data-share-menu]').forEach((menu) => menu.classList.add('hidden'));
        document.querySelectorAll('[data-share-trigger]').forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
    };

    const positionShareMenu = (menu, trigger) => {
        if (menu.parentElement !== document.body) {
            document.body.appendChild(menu);
        }

        const rect = trigger.getBoundingClientRect();
        const menuWidth = menu.offsetWidth || 192;
        const menuHeight = menu.offsetHeight || 260;
        const margin = 8;

        let left = rect.right - menuWidth;
        left = Math.max(margin, Math.min(left, window.innerWidth - menuWidth - margin));

        let top = rect.bottom + margin;
        if (top + menuHeight > window.innerHeight - margin && rect.top - menuHeight - margin > 0) {
            top = rect.top - menuHeight - margin;
        }

        menu.style.left = `${left}px`;
        menu.style.top = `${top}px`;
    };

    const copyShareLink = (button, url) => {
        const label = button.querySelector('[data-share-copy-label]');
        const original = label?.textContent;

        const onCopied = () => {
            if (!label) return;
            label.textContent = 'تم نسخ الرابط ✓';
            setTimeout(() => {
                label.textContent = original;
            }, 1500);
        };

        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(url).then(onCopied).catch(() => {});
            return;
        }

        const temp = document.createElement('textarea');
        temp.value = url;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.select();
        try {
            document.execCommand('copy');
            onCopied();
        } catch {
            // Clipboard access denied/unsupported — the option simply does nothing.
        }
        temp.remove();
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-share-trigger]');
        if (trigger) {
            event.preventDefault();
            event.stopPropagation();

            const title = trigger.dataset.shareTitle || document.title;
            const url = trigger.dataset.shareUrl || window.location.href;

            if (navigator.share) {
                navigator.share({ title, url }).catch(() => {});
                return;
            }

            const menu = document.getElementById(trigger.dataset.shareTarget);
            if (!menu) return;

            const isOpen = !menu.classList.contains('hidden');
            closeAllShareMenus();
            if (isOpen) return;

            menu.classList.remove('hidden');
            positionShareMenu(menu, trigger);
            trigger.setAttribute('aria-expanded', 'true');
            return;
        }

        const option = event.target.closest('[data-share-option]');
        if (option) {
            event.preventDefault();
            event.stopPropagation();

            const menu = option.closest('[data-share-menu]');
            const title = menu?.dataset.shareTitle || document.title;
            const url = menu?.dataset.shareUrl || window.location.href;
            const platform = option.dataset.shareOption;

            if (platform === 'copy') {
                copyShareLink(option, url);
                return; // keep the menu open briefly so the "copied" feedback is visible
            }

            const build = shareLinkBuilders[platform];
            if (build) {
                window.open(build(title, url), '_blank', 'noopener');
            }
            closeAllShareMenus();
            return;
        }

        if (!event.target.closest('[data-share-menu]')) {
            closeAllShareMenus();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAllShareMenus();
    });
})();

(() => {
    const scope = document.querySelector('[data-book-cover-source]');
    if (!scope) return;

    const pdfInput = scope.querySelector('[data-pdf-input]');
    const coverInput = scope.querySelector('[data-cover-input]');
    const preview = scope.querySelector('[data-cover-preview]');
    const autoNote = scope.querySelector('[data-cover-auto-note]');
    if (!pdfInput || !coverInput || !preview) return;

    let coverManuallyChosen = coverInput.files.length > 0;

    const showPreview = (blobOrFile, isAuto) => {
        preview.src = URL.createObjectURL(blobOrFile);
        preview.classList.remove('hidden');
        autoNote?.classList.toggle('hidden', !isAuto);
    };

    coverInput.addEventListener('change', () => {
        if (coverInput.files.length === 0) return;
        coverManuallyChosen = true;
        showPreview(coverInput.files[0], false);
    });

    pdfInput.addEventListener('change', async () => {
        if (coverManuallyChosen || pdfInput.files.length === 0) return;

        try {
            const [{ getDocument, GlobalWorkerOptions }, workerSrc] = await Promise.all([
                import('pdfjs-dist'),
                import('pdfjs-dist/build/pdf.worker.min.mjs?url').then((m) => m.default),
            ]);
            GlobalWorkerOptions.workerSrc = workerSrc;

            const data = await pdfInput.files[0].arrayBuffer();
            const pdf = await getDocument({ data }).promise;
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({ scale: 1.5 });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob((result) => (result ? resolve(result) : reject(new Error('toBlob failed'))), 'image/jpeg', 0.85);
            });

            const generatedCover = new File([blob], 'auto-cover.jpg', { type: 'image/jpeg' });
            const transfer = new DataTransfer();
            transfer.items.add(generatedCover);
            coverInput.files = transfer.files;

            showPreview(blob, true);
        } catch {
            // PDF rendering isn't guaranteed on every browser/file; the admin
            // can always attach a cover manually, and cover_image is optional.
        }
    });
})();

