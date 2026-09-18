/**
 * Show a spinner on navigation / action controls while the next page loads.
 * Use on <a> or <button> with data-action-loading (optional data-loading-label).
 */
const applyLoadingVisual = (el) => {
    el.classList.add('is-loading');
    el.setAttribute('aria-busy', 'true');

    const loadingLabel = el.getAttribute('data-loading-label');
    if (loadingLabel) {
        const label = el.querySelector('[data-action-label]');
        const loading = el.querySelector('[data-action-loading-label]');
        if (label) {
            label.classList.add('hidden');
        }
        if (loading) {
            loading.classList.remove('hidden');
            loading.textContent = loadingLabel;
        }
    }

    el.querySelector('[data-action-spinner]')?.classList.remove('hidden');
};

const setLoading = (el) => {
    if (el.classList.contains('is-loading') || el.getAttribute('aria-disabled') === 'true') {
        return false;
    }

    applyLoadingVisual(el);

    const isSubmitButton = el.tagName === 'BUTTON'
        && String(el.getAttribute('type') || 'submit').toLowerCase() === 'submit';

    if (el.tagName === 'A') {
        el.setAttribute('aria-disabled', 'true');
    } else if (isSubmitButton) {
        // Defer disable so the browser can still submit the form (formaction/formmethod included).
        window.setTimeout(() => {
            el.disabled = true;
        }, 0);
    } else {
        el.disabled = true;
    }

    return true;
};

document.querySelectorAll('[data-action-loading]').forEach((el) => {
    el.addEventListener('click', (event) => {
        if (el.tagName === 'A') {
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || el.target === '_blank') {
                return;
            }

            if (el.getAttribute('aria-disabled') === 'true') {
                event.preventDefault();
                return;
            }

            if (! setLoading(el)) {
                event.preventDefault();
            }

            return;
        }

        if (el.getAttribute('aria-disabled') === 'true' || el.disabled) {
            event.preventDefault();
            return;
        }

        if (! setLoading(el)) {
            event.preventDefault();
        }
    });
});
