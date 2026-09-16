document.querySelectorAll('[data-locale-switcher]').forEach((root) => {
    const trigger = root.querySelector('[data-locale-switcher-trigger]');
    const menu = root.querySelector('[data-locale-switcher-menu]');

    if (! trigger || ! menu) {
        return;
    }

    const setOpen = (open) => {
        root.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            menu.removeAttribute('hidden');
        } else {
            menu.setAttribute('hidden', '');
        }
    };

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(! root.classList.contains('is-open'));
    });

    document.addEventListener('click', (event) => {
        if (! root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });
});
