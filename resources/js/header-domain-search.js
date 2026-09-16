document.querySelectorAll('[data-header-domain-search]').forEach((form) => {
    const submit = form.querySelector('[data-header-domain-submit]');
    const input = form.querySelector('[data-header-domain-input]');
    const clearBtn = form.querySelector('[data-header-domain-clear]');

    const syncClearVisibility = () => {
        if (! clearBtn || ! input) {
            return;
        }

        clearBtn.classList.toggle('hidden', String(input.value || '').trim() === '');
    };

    input?.addEventListener('input', syncClearVisibility);
    syncClearVisibility();

    clearBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        if (input) {
            input.value = '';
            input.focus();
        }
        syncClearVisibility();

        const url = new URL(window.location.href);
        if (url.searchParams.has('q')) {
            url.searchParams.delete('q');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
        }
    });

    if (! submit) {
        return;
    }

    form.addEventListener('submit', (event) => {
        if (submit.classList.contains('is-loading')) {
            event.preventDefault();
            return;
        }

        event.preventDefault();

        const query = String(input?.value || '').trim();
        const loadingLabel = submit.getAttribute('data-loading-label') || '';

        submit.classList.add('is-loading');
        submit.setAttribute('disabled', 'disabled');
        submit.setAttribute('aria-busy', 'true');
        if (loadingLabel) {
            submit.setAttribute('aria-label', loadingLabel);
        }

        const action = new URL(form.getAttribute('action') || '/domain', window.location.origin);
        action.searchParams.set('tab', 'register');
        if (query) {
            action.searchParams.set('q', query);
        } else {
            action.searchParams.delete('q');
        }

        window.setTimeout(() => {
            window.location.assign(action.toString());
        }, 320);
    });
});

(() => {
    const modal = document.querySelector('[data-domain-search-modal]');
    const openers = document.querySelectorAll('[data-domain-search-open]');

    if (! modal || openers.length === 0) {
        return;
    }

    if (modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

    const panel = modal.querySelector('[data-domain-search-panel]');
    const input = modal.querySelector('[data-header-domain-input]');
    const dismissers = modal.querySelectorAll('[data-domain-search-dismiss]');

    const isOpen = () => modal.classList.contains('is-open');

    const setExpanded = (expanded) => {
        openers.forEach((opener) => {
            opener.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    };

    const open = () => {
        if (isOpen()) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('domain-search-modal-locked');
        setExpanded(true);

        window.requestAnimationFrame(() => {
            input?.focus();
            input?.select?.();
        });
    };

    const close = () => {
        if (! isOpen()) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('domain-search-modal-locked');
        setExpanded(false);
        openers[0]?.focus();
    };

    openers.forEach((opener) => {
        opener.addEventListener('click', (event) => {
            event.preventDefault();
            open();
        });
    });

    dismissers.forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            close();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            close();
        }
    });

    panel?.addEventListener('click', (event) => {
        event.stopPropagation();
    });
})();
