/* ─────────────────────────────────────────────────────────
   Mobile navigation — white slide-down overlay
───────────────────────────────────────────────────────── */

const syncHeaderHeight = () => {
    const bar = document.querySelector('[data-site-header-bar]');
    if (!bar) return;
    const h = Math.ceil(bar.getBoundingClientRect().height);
    document.documentElement.style.setProperty('--site-header-height', `${h}px`);
};

syncHeaderHeight();
window.addEventListener('resize', syncHeaderHeight, { passive: true });

document.querySelectorAll('[data-site-header]').forEach((root) => {
    const toggle = root.querySelector('[data-mobile-nav-toggle]');
    const panel  = root.querySelector('[data-mobile-nav]');
    if (!toggle || !panel) return;

    // Portal panel to <body> so fixed header blur never clips it
    if (panel.parentElement !== document.body) document.body.appendChild(panel);

    const openLabel  = toggle.dataset.openLabel  || 'Open menu';
    const closeLabel = toggle.dataset.closeLabel || 'Close menu';
    let closing = false;

    const setOpen = (open) => {
        if (open) {
            closing = false;
            syncHeaderHeight();
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', closeLabel);
            panel.setAttribute('aria-hidden', 'false');
            document.body.classList.add('mobile-nav-locked');
            panel.classList.remove('is-open');
            void panel.offsetWidth; // force reflow
            requestAnimationFrame(() => panel.classList.add('is-open'));
            return;
        }

        if (toggle.getAttribute('aria-expanded') !== 'true' || closing) return;
        closing = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', openLabel);
        document.body.classList.remove('mobile-nav-locked');
        panel.classList.remove('is-open');

        const done = (e) => {
            if (e && e.target !== panel) return;
            if (e && e.propertyName && e.propertyName !== 'transform') return;
            panel.setAttribute('aria-hidden', 'true');
            closing = false;
            panel.removeEventListener('transitionend', done);
        };
        panel.addEventListener('transitionend', done);
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    // Close when any link inside is clicked
    panel.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });

    // Reset on desktop resize
    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            panel.classList.remove('is-open');
            panel.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', openLabel);
            document.body.classList.remove('mobile-nav-locked');
            closing = false;
        }
    }, { passive: true });
});
