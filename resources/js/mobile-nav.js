const syncSiteHeaderHeight = () => {
    const bar =
        document.querySelector('[data-site-header-bar]') ||
        document.querySelector('.site-header-fixed');

    if (! bar) {
        return 76;
    }

    const height = Math.ceil(bar.getBoundingClientRect().height);
    document.documentElement.style.setProperty('--site-header-height', `${height}px`);

    return height;
};

document.querySelectorAll('[data-site-header-spacer]').forEach((spacer) => {
    // Height is driven by --site-header-height on :root
    spacer.style.height = 'var(--site-header-height, 4.75rem)';
});

syncSiteHeaderHeight();
window.addEventListener('resize', syncSiteHeaderHeight);

document.querySelectorAll('[data-site-header]').forEach((header) => {
    const toggle = header.querySelector('[data-mobile-nav-toggle]');
    const panel = header.querySelector('[data-mobile-nav]');
    const openIcon = header.querySelector('[data-mobile-nav-open-icon]');
    const closeIcon = header.querySelector('[data-mobile-nav-close-icon]');

    if (! toggle || ! panel) {
        return;
    }

    // Workaround: keep the fullscreen menu outside the fixed/blurred header
    // so position:fixed is always relative to the viewport.
    if (panel.parentElement !== document.body) {
        document.body.appendChild(panel);
    }

    const openLabel = toggle.getAttribute('data-open-label') || toggle.getAttribute('aria-label') || 'Open menu';
    const closeLabel = toggle.getAttribute('data-close-label') || 'Close menu';
    let closing = false;

    const setOpen = (open) => {
        if (open) {
            closing = false;
            syncSiteHeaderHeight();
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', closeLabel);
            panel.setAttribute('aria-hidden', 'false');
            openIcon?.classList.add('hidden');
            closeIcon?.classList.remove('hidden');
            document.body.classList.add('mobile-nav-locked');

            panel.classList.remove('is-open');
            void panel.offsetWidth;
            requestAnimationFrame(() => {
                panel.classList.add('is-open');
            });

            return;
        }

        if (toggle.getAttribute('aria-expanded') !== 'true' || closing) {
            return;
        }

        closing = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', openLabel);
        openIcon?.classList.remove('hidden');
        closeIcon?.classList.add('hidden');
        panel.classList.remove('is-open');
        document.body.classList.remove('mobile-nav-locked');

        const finishClose = (event) => {
            if (event && event.target !== panel) {
                return;
            }

            if (event && event.propertyName && event.propertyName !== 'transform') {
                return;
            }

            panel.setAttribute('aria-hidden', 'true');
            closing = false;
            panel.removeEventListener('transitionend', finishClose);
        };

        panel.addEventListener('transitionend', finishClose);
    };

    toggle.addEventListener('click', () => {
        const isOpen = toggle.getAttribute('aria-expanded') === 'true';
        setOpen(! isOpen);
    });

    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 768px)').matches) {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', openLabel);
            openIcon?.classList.remove('hidden');
            closeIcon?.classList.add('hidden');
            document.body.classList.remove('mobile-nav-locked');
            closing = false;
        }
    });
});
