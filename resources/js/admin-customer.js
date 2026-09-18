/**
 * Customer detail edit modal + clearable filter search.
 */
function initAdminCustomerUi() {
    document.querySelectorAll('[data-admin-edit-modal]').forEach((root) => {
        const openBtn = root.querySelector('[data-admin-edit-open]');
        const dialog = root.querySelector('[data-admin-edit-dialog]');
        const closeEls = root.querySelectorAll('[data-admin-edit-close]');

        if (! openBtn || ! dialog) {
            return;
        }

        if (dialog.parentElement !== document.body) {
            document.body.appendChild(dialog);
        }

        if (dialog.classList.contains('is-open') || ! dialog.hidden) {
            dialog.hidden = false;
            dialog.classList.add('is-open');
            document.body.classList.add('admin-modal-open');
        }

        const show = () => {
            dialog.hidden = false;
            dialog.classList.add('is-open');
            document.body.classList.add('admin-modal-open');
            const first = dialog.querySelector('input, select, textarea, button');
            first?.focus();
        };

        const hide = () => {
            dialog.hidden = true;
            dialog.classList.remove('is-open');
            document.body.classList.remove('admin-modal-open');
            openBtn.focus();
        };

        openBtn.addEventListener('click', show);
        closeEls.forEach((el) => el.addEventListener('click', hide));
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) hide();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && dialog.classList.contains('is-open')) {
                hide();
            }
        });
    });

    document.querySelectorAll('[data-admin-clearable-search]').forEach((form) => {
        const input = form.querySelector('input[name="q"]');
        const clearBtn = form.querySelector('[data-admin-search-clear]');
        if (! input || ! clearBtn) return;

        const sync = () => {
            clearBtn.hidden = input.value.trim() === '';
        };

        input.addEventListener('input', sync);
        sync();

        clearBtn.addEventListener('click', (event) => {
            event.preventDefault();
            input.value = '';
            sync();
            const source = form.querySelector('input[name="source"]')?.value;
            const url = new URL(form.action, window.location.origin);
            url.search = '';
            if (source) url.searchParams.set('source', source);
            window.location.href = url.toString();
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminCustomerUi);
} else {
    initAdminCustomerUi();
}
