const formPanel = document.getElementById('contact-form');

if (formPanel) {
    const params = new URLSearchParams(window.location.search);
    const shouldFocusForm =
        window.location.hash === '#contact-form' || params.has('subject');

    if (shouldFocusForm) {
        const scrollToForm = () => {
            formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            const subject = formPanel.querySelector('#contact-subject');
            if (subject instanceof HTMLInputElement && subject.value.trim() !== '') {
                subject.focus({ preventScroll: true });
            }
        };

        if (document.readyState === 'complete') {
            requestAnimationFrame(scrollToForm);
        } else {
            window.addEventListener('load', () => requestAnimationFrame(scrollToForm), { once: true });
        }
    }
}

document.querySelectorAll('[data-contact-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        form.querySelectorAll('[data-contact-button]').forEach((button) => {
            button.disabled = true;
            button.classList.add('opacity-80', 'cursor-not-allowed');
            button.querySelector('[data-contact-spinner]')?.classList.remove('hidden');
            button.querySelector('[data-contact-label]')?.classList.add('hidden');
            button.querySelector('[data-contact-loading]')?.classList.remove('hidden');
        });
    });
});
