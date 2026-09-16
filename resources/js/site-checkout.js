const form = document.querySelector('[data-site-checkout]');

if (form) {
    const isGuest = form.dataset.guestCheckout === '1';
    const statusUrl = form.dataset.accountStatusUrl;
    const loginUrl = form.dataset.loginUrl || '/login?redirect=/checkout';

    const emailInput = form.querySelector('[data-checkout-email]');
    const nameWrap = form.querySelector('[data-name-wrap]');
    const nameInput = form.querySelector('[data-checkout-name]');
    const passwordWrap = form.querySelector('[data-password-wrap]');
    const passwordConfirmWrap = form.querySelector('[data-password-confirm-wrap]');
    const passwordInput = form.querySelector('[data-checkout-password]');
    const passwordConfirmInput = form.querySelector('[data-checkout-password-confirm]');
    const existingBanner = form.querySelector('[data-existing-account]');
    const accountLede = form.querySelector('[data-account-lede]');
    const newAccountFields = form.querySelector('[data-new-account-fields]');
    const billingSection = form.querySelector('[data-billing-section]');
    const shippingSection = form.querySelector('[data-shipping-section]');
    const detailsSection = form.querySelector('[data-mail-setup]');
    const shippingSame = form.querySelector('[data-shipping-same]');
    const shippingFields = form.querySelector('[data-shipping-fields]');
    const submitButtons = [...form.querySelectorAll('[data-submit-button]')];
    const continueHints = [...form.querySelectorAll('[data-checkout-hint]')];
    const signInLink = form.querySelector('[data-checkout-sign-in]');
    const csrfToken = form.querySelector('input[name="_token"]')?.value;

    let guestStatus = form.dataset.initialGuestStatus || 'pending';

    const filled = (el) => Boolean(el && String(el.value || '').trim());

    const setVisible = (el, visible) => {
        if (! el) {
            return;
        }
        el.classList.toggle('hidden', ! visible);
    };

    const setSectionEnabled = (section, enabled) => {
        if (! section) {
            return;
        }
        section.classList.toggle('hidden', ! enabled);
        section.disabled = ! enabled;
    };

    const syncShippingFields = () => {
        const same = ! shippingSame || shippingSame.checked;
        setVisible(shippingFields, ! same);

        form.querySelectorAll('[data-shipping-required]').forEach((field) => {
            field.required = ! same;
            if (same) {
                field.value = field.tagName === 'SELECT' ? field.value : field.value;
            }
        });
    };

    const syncGuestAccountUi = () => {
        if (! isGuest) {
            return;
        }

        const isExisting = guestStatus === 'existing';
        const isNew = guestStatus === 'new';

        setVisible(existingBanner, isExisting);
        setVisible(accountLede, ! isExisting);
        setVisible(nameWrap, isNew || guestStatus === 'pending');
        setVisible(passwordWrap, isNew);
        setVisible(passwordConfirmWrap, isNew);

        if (nameInput) {
            nameInput.required = isNew;
        }
        if (passwordInput) {
            passwordInput.required = isNew;
            if (! isNew) {
                passwordInput.value = '';
            }
        }
        if (passwordConfirmInput) {
            passwordConfirmInput.required = isNew;
            if (! isNew) {
                passwordConfirmInput.value = '';
            }
        }

        // Prefill sign-in link with the typed email.
        if (signInLink && emailInput) {
            const url = new URL(loginUrl, window.location.origin);
            const email = String(emailInput.value || '').trim();
            if (email) {
                url.searchParams.set('email', email);
            }
            signInLink.setAttribute('href', url.toString());
        }

        setSectionEnabled(billingSection, ! isExisting);
        setSectionEnabled(shippingSection, ! isExisting);
        setSectionEnabled(detailsSection, ! isExisting);

        if (newAccountFields) {
            // Keep email visible even for existing so they can change it.
            newAccountFields.classList.toggle('opacity-100', true);
        }
    };

    const billingReady = () => {
        if (isGuest && guestStatus === 'existing') {
            return false;
        }

        if (isGuest) {
            if (guestStatus !== 'new') {
                return false;
            }
            if (! filled(emailInput) || ! emailInput.checkValidity()) {
                return false;
            }
            if (! filled(nameInput)) {
                return false;
            }
            if (! filled(passwordInput) || String(passwordInput.value).length < 8) {
                return false;
            }
            if (! filled(passwordConfirmInput) || passwordConfirmInput.value !== passwordInput.value) {
                return false;
            }
        } else {
            const authName = form.querySelector('#name');
            if (authName && ! filled(authName)) {
                return false;
            }
        }

        const billingOk = [...form.querySelectorAll('[data-billing-required]')].every((field) => {
            if (field.closest('[disabled]')) {
                return true;
            }
            return filled(field) && (field.checkValidity ? field.checkValidity() : true);
        });

        if (! billingOk) {
            return false;
        }

        const same = ! shippingSame || shippingSame.checked;
        if (! same) {
            const shippingOk = [...form.querySelectorAll('[data-shipping-required]')].every((field) => filled(field));
            if (! shippingOk) {
                return false;
            }
        }

        const eppInputs = [...form.querySelectorAll('[data-domain-epp]')];
        if (eppInputs.length && ! eppInputs.every((input) => filled(input))) {
            return false;
        }

        return true;
    };

    const syncSubmit = () => {
        const ready = billingReady();

        submitButtons.forEach((button) => {
            button.disabled = ! ready;
            button.classList.toggle('opacity-50', ! ready);
            button.classList.toggle('cursor-not-allowed', ! ready);
            button.setAttribute('aria-disabled', ready ? 'false' : 'true');
        });

        continueHints.forEach((hint) => {
            hint.classList.toggle('hidden', ready);
        });
    };

    const applyStatus = (status) => {
        guestStatus = status;
        syncGuestAccountUi();
        syncShippingFields();
        syncSubmit();
    };

    shippingSame?.addEventListener('change', () => {
        syncShippingFields();
        syncSubmit();
    });

    form.addEventListener('input', syncSubmit);
    form.addEventListener('change', syncSubmit);

    if (isGuest && statusUrl && emailInput) {
        let debounceTimer = null;
        let lastEmail = '';

        const lookup = async () => {
            const email = String(emailInput.value || '').trim().toLowerCase();
            if (! email || ! email.includes('@')) {
                applyStatus('pending');
                return;
            }

            if (email === lastEmail) {
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
                    },
                    body: JSON.stringify({ email }),
                });

                if (! response.ok) {
                    applyStatus('pending');
                    return;
                }

                const data = await response.json();
                applyStatus(data.status || 'pending');
            } catch {
                applyStatus('pending');
            }
        };

        emailInput.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(lookup, 350);
        });

        emailInput.addEventListener('blur', lookup);

        if (guestStatus !== 'pending') {
            applyStatus(guestStatus);
        } else {
            applyStatus('pending');
        }
    } else {
        syncShippingFields();
        syncSubmit();
    }
}
