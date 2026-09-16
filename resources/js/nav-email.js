document.querySelectorAll('[data-nav-mega], [data-nav-email]').forEach((root) => {
    const trigger = root.querySelector(
        '[data-nav-mega-trigger], [data-nav-email-trigger], [data-nav-email-toggle], [data-nav-mega-toggle]',
    );
    const menu = root.querySelector('[data-nav-mega-menu], [data-nav-email-menu]');

    if (! trigger || ! menu) {
        return;
    }

    const isMobileToggle = trigger.hasAttribute('data-nav-email-toggle')
        || trigger.hasAttribute('data-nav-mega-toggle');

    const keepMenuInView = () => {
        if (isMobileToggle || menu.classList.contains('is-align-end')) {
            return;
        }

        menu.classList.remove('is-align-end');
        menu.style.left = '';
        menu.style.right = '';

        const rect = menu.getBoundingClientRect();
        const gutter = 16;

        if (rect.right > window.innerWidth - gutter) {
            menu.classList.add('is-align-end');
        }
    };

    const setOpen = (open) => {
        root.classList.toggle('is-open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (isMobileToggle) {
            if (open) {
                menu.removeAttribute('hidden');
            } else {
                menu.setAttribute('hidden', '');
            }
        } else if (open) {
            requestAnimationFrame(keepMenuInView);
        }
    };

    if (isMobileToggle) {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            setOpen(! root.classList.contains('is-open'));
        });
        return;
    }

    let closeTimer = null;

    const open = () => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        setOpen(true);
    };

    const scheduleClose = () => {
        closeTimer = setTimeout(() => setOpen(false), 120);
    };

    root.addEventListener('mouseenter', open);
    root.addEventListener('mouseleave', scheduleClose);
    root.addEventListener('focusin', open);
    root.addEventListener('focusout', (event) => {
        if (! root.contains(event.relatedTarget)) {
            scheduleClose();
        }
    });

    trigger.addEventListener('click', (event) => {
        if (window.matchMedia('(hover: none)').matches) {
            event.preventDefault();
            setOpen(! root.classList.contains('is-open'));
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    document.addEventListener('click', (event) => {
        if (! root.contains(event.target)) {
            setOpen(false);
        }
    });
});
