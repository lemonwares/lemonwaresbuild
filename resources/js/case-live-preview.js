/**
 * In-page live product preview for case studies (iframe overlay).
 * Close returns the visitor to the same scroll position on the LemonWares page.
 */
document.querySelectorAll('[data-case-live-preview]').forEach((root) => {
    const openBtns = root.querySelectorAll('[data-case-live-open]');
    const dialog = root.querySelector('[data-case-live-dialog]');
    const frame = root.querySelector('[data-case-live-frame]');
    const closeBtns = root.querySelectorAll('[data-case-live-close]');
    const loading = root.querySelector('[data-case-live-loading]');
    const blocked = root.querySelector('[data-case-live-blocked]');
    const url = root.getAttribute('data-preview-url') || '';

    if (! dialog || ! frame || ! url || openBtns.length === 0) {
        return;
    }

    if (dialog.parentElement !== document.body) {
        document.body.appendChild(dialog);
    }

    let lastFocus = null;
    let loadTimer = null;

    const setLoading = (on) => {
        if (loading) {
            loading.hidden = ! on;
        }
    };

    const setBlocked = (on) => {
        if (blocked) {
            blocked.hidden = ! on;
        }
    };

    const isOpen = () => dialog.classList.contains('is-open');

    const open = () => {
        if (isOpen()) {
            return;
        }

        lastFocus = document.activeElement;
        setBlocked(false);
        setLoading(true);

        // Set src only when opening so we don't load remote sites on every page view.
        if (frame.getAttribute('src') !== url) {
            frame.setAttribute('src', url);
        }

        dialog.hidden = false;
        dialog.classList.add('is-open');
        document.body.classList.add('case-live-preview-open');

        const closeBtn = dialog.querySelector('[data-case-live-close]');
        closeBtn?.focus();

        clearTimeout(loadTimer);
        // Safety: never leave the spinner up forever on slow or hung loads.
        loadTimer = window.setTimeout(() => {
            setLoading(false);
        }, 8000);
    };

    const close = () => {
        if (! isOpen()) {
            return;
        }

        clearTimeout(loadTimer);
        dialog.classList.remove('is-open');
        dialog.hidden = true;
        document.body.classList.remove('case-live-preview-open');
        setLoading(false);
        setBlocked(false);

        // Unload remote document to stop media/scripts while closed.
        frame.setAttribute('src', 'about:blank');

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    };

    frame.addEventListener('load', () => {
        if (frame.getAttribute('src') === 'about:blank' || ! isOpen()) {
            return;
        }

        clearTimeout(loadTimer);
        setLoading(false);

        // Cross-origin embeds throw here (expected). Same-origin blank means failure.
        try {
            const doc = frame.contentDocument;
            if (doc && (! doc.body || doc.body.childElementCount === 0)) {
                setBlocked(true);
            } else {
                setBlocked(false);
            }
        } catch {
            setBlocked(false);
        }
    });

    openBtns.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            open();
        });
    });

    closeBtns.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            close();
        });
    });

    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            close();
        }
    });
});
