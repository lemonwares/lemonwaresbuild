const modal = document.querySelector('[data-complete-profile-modal]');

if (modal) {
    const panel = modal.querySelector('[data-complete-profile-panel]');
    document.documentElement.classList.add('overflow-hidden');
    document.body.classList.add('overflow-hidden');

    // Non-skippable: block Escape and outside clicks.
    modal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            event.preventDefault();
            event.stopPropagation();
            panel?.querySelector('input, select, textarea, button')?.focus();
        }
    });

    // Focus first empty required field.
    const firstEmpty = [...modal.querySelectorAll('input[required], select[required]')]
        .find((field) => ! String(field.value || '').trim());
    (firstEmpty || modal.querySelector('input, select'))?.focus();
}
