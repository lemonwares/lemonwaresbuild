const form = document.querySelector('[data-email-checkout]');

if (form) {
    const domainInput = form.querySelector('[data-email-domain-input]');
    const suffixes = form.querySelectorAll('[data-email-domain-suffix]');
    const mailboxInputs = () => [...form.querySelectorAll('input[name="mailboxes[]"]')];

    if (domainInput && suffixes.length) {
        const placeholder = domainInput.dataset.domainPlaceholder ?? '@yourdomain.com';

        const normalizeDomain = (value) => {
            let domain = value.trim().toLowerCase();
            domain = domain.replace(/^https?:\/\//, '');
            domain = domain.split('/')[0] ?? '';
            domain = domain.replace(/:\d+$/, '').replace(/\.$/, '');

            return domain;
        };

        const updateSuffixes = () => {
            const domain = normalizeDomain(domainInput.value);
            const label = domain ? `@${domain}` : placeholder;

            suffixes.forEach((suffix) => {
                suffix.textContent = label;
                suffix.title = label;
            });
        };

        domainInput.addEventListener('input', updateSuffixes);
        updateSuffixes();
    }

    const isGuest = form.dataset.guestCheckout === '1';
    const statusUrl = form.dataset.accountStatusUrl;
    const businessSection = form.querySelector('[data-business-section]');
    const mailSetup = form.querySelector('[data-mail-setup]');
    const businessStepLabel = form.querySelector('[data-business-step-label]');
    const mailStepLabel = form.querySelector('[data-mail-step-label]');
    const welcomeBack = form.querySelector('[data-welcome-back]');
    const accountLede = form.querySelector('[data-account-lede]');
    const passwordHelp = form.querySelector('[data-password-help]');
    const passwordWrap = form.querySelector('[data-password-wrap]');
    const passwordInput = form.querySelector('[data-checkout-password]');
    const nameWrap = form.querySelector('[data-name-wrap]');
    const nameInput = form.querySelector('[data-checkout-name]');
    const emailInput = form.querySelector('[data-checkout-email]');
    const companyInput = form.querySelector('#company');
    const phoneInput = form.querySelector('#phone');
    const countryInput = form.querySelector('#billing_country');
    const submitButton = form.querySelector('[data-submit-button]');
    const continueHint = form.querySelector('[data-checkout-hint]');
    const csrfToken = form.querySelector('input[name="_token"]')?.value;
    const helpNew = form.dataset.passwordHelpNew || '';
    const helpExisting = form.dataset.passwordHelpExisting || '';

    let guestStatus = form.dataset.initialGuestStatus || 'pending';

    const filled = (el) => Boolean(el && String(el.value || '').trim());

    const setSectionVisible = (section, visible) => {
        if (! section) {
            return;
        }

        section.classList.toggle('hidden', ! visible);
        section.disabled = ! visible;
    };

    const setBusinessVisible = (visible) => {
        setSectionVisible(businessSection, visible);

        businessSection?.querySelectorAll('[data-business-required]').forEach((field) => {
            if (! visible) {
                field.required = false;

                return;
            }

            const wrap = field.closest('[data-business-field]');
            const fieldVisible = ! wrap || ! wrap.classList.contains('hidden');
            field.required = fieldVisible;
        });
    };

    const setMailVisible = (visible) => {
        setSectionVisible(mailSetup, visible);

        if (domainInput) {
            domainInput.required = visible;
        }

        mailboxInputs().forEach((input) => {
            input.required = visible;
        });
    };

    const setAccountExtrasVisible = ({ showName, showPassword }) => {
        if (nameWrap) {
            nameWrap.classList.toggle('hidden', ! showName);
        }

        if (nameInput) {
            nameInput.required = Boolean(showName);
        }

        if (passwordWrap) {
            passwordWrap.classList.toggle('hidden', ! showPassword);
        }

        if (passwordInput) {
            passwordInput.required = Boolean(showPassword);
        }
    };

    const needsBusinessForStatus = (status) => status === 'new' || status === 'existing_incomplete';

    const accountGatePassed = () => {
        if (! isGuest) {
            if (form.dataset.initialNeedsBusiness === '1') {
                return filled(companyInput) && filled(phoneInput) && filled(countryInput);
            }

            return true;
        }

        if (guestStatus === 'pending') {
            return false;
        }

        if (! filled(emailInput) || ! emailInput.checkValidity()) {
            return false;
        }

        if (! filled(passwordInput) || String(passwordInput.value).length < 8) {
            return false;
        }

        if (guestStatus === 'new' && ! filled(nameInput)) {
            return false;
        }

        if (needsBusinessForStatus(guestStatus)) {
            return filled(companyInput) && filled(phoneInput) && filled(countryInput);
        }

        return true;
    };

    const mailGatePassed = () => {
        if (! filled(domainInput)) {
            return false;
        }

        return mailboxInputs().every((input) => filled(input));
    };

    const formIsReady = () => accountGatePassed() && mailGatePassed();

    const syncSubmitButton = () => {
        if (! submitButton) {
            return;
        }

        const ready = formIsReady();
        submitButton.disabled = ! ready;
        submitButton.classList.toggle('opacity-50', ! ready);
        submitButton.classList.toggle('cursor-not-allowed', ! ready);
        submitButton.setAttribute('aria-disabled', ready ? 'false' : 'true');

        if (continueHint) {
            continueHint.classList.toggle('hidden', ready);
        }
    };

    const syncProgressiveSections = () => {
        if (isGuest) {
            const showBusiness = needsBusinessForStatus(guestStatus);
            setBusinessVisible(showBusiness);

            if (businessStepLabel) {
                businessStepLabel.textContent = '2.';
            }

            if (mailStepLabel) {
                mailStepLabel.textContent = showBusiness ? '3.' : '2.';
            }

            // Domain/mailboxes only after account (+ business when needed) is complete.
            setMailVisible(accountGatePassed());
        } else {
            const showBusiness = form.dataset.initialNeedsBusiness === '1';
            setBusinessVisible(showBusiness);
            setMailVisible(accountGatePassed());
        }

        syncSubmitButton();
    };

    const applyGuestStatus = (status) => {
        guestStatus = status;
        const existing = status === 'existing_complete' || status === 'existing_incomplete';

        if (welcomeBack) {
            welcomeBack.classList.toggle('hidden', ! existing);
        }

        if (accountLede) {
            accountLede.classList.toggle('hidden', existing);
        }

        if (passwordHelp) {
            passwordHelp.textContent = existing ? helpExisting : helpNew;
        }

        if (passwordInput) {
            passwordInput.autocomplete = existing ? 'current-password' : 'new-password';
        }

        setAccountExtrasVisible({
            showName: status === 'new',
            showPassword: status !== 'pending',
        });

        syncProgressiveSections();
    };

    form.addEventListener('input', syncProgressiveSections);
    form.addEventListener('change', syncProgressiveSections);

    if (isGuest && statusUrl && emailInput) {
        let debounceTimer = null;
        let lastEmail = '';
        let hasResolved = false;

        const resetToEmailOnly = () => {
            guestStatus = 'pending';
            setAccountExtrasVisible({ showName: false, showPassword: false });
            setBusinessVisible(false);
            setMailVisible(false);
            syncSubmitButton();
        };

        const initialStatus = form.dataset.initialGuestStatus || 'pending';
        if (initialStatus !== 'pending') {
            applyGuestStatus(initialStatus);
            hasResolved = true;
            lastEmail = emailInput.value.trim().toLowerCase();
        } else {
            resetToEmailOnly();
        }

        const lookupStatus = async () => {
            const email = emailInput.value.trim().toLowerCase();
            if (! email || ! email.includes('@')) {
                resetToEmailOnly();
                hasResolved = false;
                lastEmail = '';

                return;
            }

            if (email === lastEmail && hasResolved) {
                syncProgressiveSections();

                return;
            }

            lastEmail = email;

            try {
                const response = await fetch(statusUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ email }),
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    applyGuestStatus('new');
                    hasResolved = true;

                    return;
                }

                const data = await response.json();
                applyGuestStatus(data.status || 'new');
                hasResolved = true;
            } catch (_error) {
                applyGuestStatus('new');
                hasResolved = true;
            }
        };

        const scheduleLookup = () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(lookupStatus, 350);
        };

        emailInput.addEventListener('blur', lookupStatus);
        emailInput.addEventListener('change', lookupStatus);
        emailInput.addEventListener('input', scheduleLookup);

        if (emailInput.value.trim()) {
            lookupStatus();
        }
    } else {
        syncProgressiveSections();
    }

    // Block submit if criteria are not met (e.g. Enter key).
    form.addEventListener('submit', (event) => {
        if (! formIsReady()) {
            event.preventDefault();
            syncProgressiveSections();
        }
    });
}
