document.querySelectorAll('[data-submit-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('[data-submit-button]').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-80', 'cursor-not-allowed');
            button.querySelector('[data-submit-spinner]')?.classList.remove('hidden');
            button.querySelector('[data-submit-label]')?.classList.add('hidden');
            button.querySelector('[data-submit-loading]')?.classList.remove('hidden');
        });
    });
});
