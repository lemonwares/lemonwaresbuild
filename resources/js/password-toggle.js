document.querySelectorAll('[data-password-toggle]').forEach((wrap) => {
    const input = wrap.querySelector('[data-password-input]');
    const button = wrap.querySelector('[data-password-toggle-btn]');
    const iconShow = wrap.querySelector('[data-password-icon-show]');
    const iconHide = wrap.querySelector('[data-password-icon-hide]');

    if (! input || ! button) {
        return;
    }

    const showLabel = wrap.getAttribute('data-show-label') || 'Show password';
    const hideLabel = wrap.getAttribute('data-hide-label') || 'Hide password';

    button.addEventListener('click', () => {
        const revealing = input.type === 'password';
        input.type = revealing ? 'text' : 'password';
        button.setAttribute('aria-pressed', revealing ? 'true' : 'false');
        button.setAttribute('aria-label', revealing ? hideLabel : showLabel);

        if (iconShow && iconHide) {
            iconShow.classList.toggle('hidden', revealing);
            iconHide.classList.toggle('hidden', ! revealing);
        }

        input.focus({ preventScroll: true });
    });
});
