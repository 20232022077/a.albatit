import './bootstrap';

document.querySelectorAll('[data-admin-shell]').forEach((shell) => {
    const sidebar = shell.querySelector('[data-sidebar]');
    const overlay = shell.querySelector('[data-sidebar-overlay]');
    const toggle = Array.from(shell.querySelectorAll('button')).find((button) => button.hasAttribute('@click'));
    const close = () => { sidebar.classList.add('translate-x-full'); overlay.classList.add('hidden'); };
    const open = () => { sidebar.classList.remove('translate-x-full'); overlay.classList.remove('hidden'); };
    toggle?.addEventListener('click', open);
    overlay?.addEventListener('click', close);
});
