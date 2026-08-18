const form = document.querySelector('[data-email-checkout]');

if (form) {
    const input = form.querySelector('[data-email-domain-input]');
    const suffixes = form.querySelectorAll('[data-email-domain-suffix]');

    if (input && suffixes.length) {
        const placeholder = input.dataset.domainPlaceholder ?? '@yourdomain.com';

        const normalizeDomain = (value) => {
            let domain = value.trim().toLowerCase();
            domain = domain.replace(/^https?:\/\//, '');
            domain = domain.split('/')[0] ?? '';
            domain = domain.replace(/:\d+$/, '').replace(/\.$/, '');

            return domain;
        };

        const updateSuffixes = () => {
            const domain = normalizeDomain(input.value);
            const label = domain ? `@${domain}` : placeholder;

            suffixes.forEach((suffix) => {
                suffix.textContent = label;
                suffix.title = label;
            });
        };

        input.addEventListener('input', updateSuffixes);
        updateSuffixes();
    }
}
