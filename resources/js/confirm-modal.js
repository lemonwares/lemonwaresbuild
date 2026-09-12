document.querySelectorAll('[data-confirm-modal]').forEach((root) => {
    const open = root.querySelector('[data-confirm-open]');
    const dialog = root.querySelector('[data-confirm-dialog]');
    const cancel = root.querySelector('[data-confirm-cancel]');
    const submit = root.querySelector('[data-confirm-submit]');
    const form = root.querySelector('[data-confirm-form]');
    const spinner = root.querySelector('[data-confirm-spinner]');
    const label = root.querySelector('[data-confirm-label]');

    if (! open || ! dialog || ! cancel || ! submit || ! form) {
        return;
    }

    // Fixed + backdrop-blur headers make position:fixed relative to the header.
    if (dialog.parentElement !== document.body) {
        document.body.appendChild(dialog);
    }

    const isOpen = () => ! dialog.classList.contains('hidden');

    const show = () => {
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        cancel.focus();
    };

    const hide = () => {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        open.focus();
    };

    open.addEventListener('click', show);
    cancel.addEventListener('click', hide);

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            hide();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            hide();
        }
    });

    submit.addEventListener('click', () => {
        submit.disabled = true;
        submit.classList.add('opacity-80', 'cursor-not-allowed');
        spinner?.classList.remove('hidden');
        label?.classList.add('opacity-80');
        form.requestSubmit();
    });
});
