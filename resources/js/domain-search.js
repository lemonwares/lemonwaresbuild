const normalizeDomain = (value) => {
    let domain = String(value || '')
        .trim()
        .toLowerCase()
        .replace(/^https?:\/\//, '')
        .replace(/^www\./, '')
        .split('/')[0]
        .split('?')[0]
        .replace(/\.$/, '');

    if (!domain) {
        return '';
    }

    if (!domain.includes('.') && /^[a-z0-9-]+$/i.test(domain)) {
        domain = `${domain}.com`;
    }

    if (!/^(?=.{1,253}$)(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i.test(domain)) {
        return '';
    }

    return domain;
};

const csrfHeaders = (token) => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': token,
});

const escapeHtml = (value) =>
    String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

const initDomainSearch = (root) => {
    const form = root.querySelector('[data-domain-form]');
    const input = root.querySelector('[data-domain-input]');
    const spinner = root.querySelector('[data-domain-spinner]');
    const submit = root.querySelector('[data-domain-submit]');
    const submitLabel = submit?.querySelector('[data-domain-submit-label]');
    const suggestionsWrap = root.querySelector('[data-domain-suggestions]');
    const resultWrap = root.querySelector('[data-domain-result]');
    const helpEl = root.querySelector('[data-domain-tab-help]');
    const helpRegister = root.querySelector('[data-domain-help-register]')?.textContent || '';
    const helpTransfer = root.querySelector('[data-domain-help-transfer]')?.textContent || '';
    const tabs = root.querySelectorAll('[data-domain-tab]');

    const checkUrl = root.dataset.checkUrl;
    const suggestUrl = root.dataset.suggestUrl;
    const quoteUrl = root.dataset.quoteUrl;
    const continueUrl = root.dataset.continueUrl;
    const csrf = root.dataset.csrf;

    let tab = root.dataset.initialTab === 'transfer' ? 'transfer' : 'register';
    let checkTimer = null;
    let suggestTimer = null;
    let checkRequestId = 0;
    let suggestRequestId = 0;
    let quoteRequestId = 0;
    let loadingCount = 0;

    const syncSubmitLabel = () => {
        if (!submitLabel) {
            return;
        }
        submitLabel.textContent =
            tab === 'transfer' ? root.dataset.msgTransferCta || 'Transfer' : root.dataset.msgSearchCta || 'Check availability';
    };

    const syncLoadingUi = () => {
        const isLoading = loadingCount > 0;
        spinner?.classList.toggle('hidden', !isLoading);
        input?.classList.toggle('is-loading', isLoading);
        submit?.classList.toggle('is-loading', isLoading);
        if (submit) {
            submit.disabled = isLoading;
        }
    };

    const setLoading = (active) => {
        loadingCount = active ? loadingCount + 1 : Math.max(0, loadingCount - 1);
        syncLoadingUi();
    };

    const setTab = (next) => {
        tab = next === 'transfer' ? 'transfer' : 'register';
        tabs.forEach((button) => {
            const active = button.getAttribute('data-domain-tab') === tab;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        if (helpEl) {
            helpEl.textContent = tab === 'transfer' ? helpTransfer : helpRegister;
        }
        syncSubmitLabel();

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        if (input?.value.trim()) {
            url.searchParams.set('q', input.value.trim());
        } else {
            url.searchParams.delete('q');
        }
        window.history.replaceState({}, '', url);

        if (input?.value.trim()) {
            runCheck();
        } else {
            clearResult();
            clearSuggestions();
        }
    };

    const clearSuggestions = () => {
        if (!suggestionsWrap) {
            return;
        }
        suggestionsWrap.innerHTML = '';
        suggestionsWrap.classList.add('hidden');
    };

    const clearResult = () => {
        if (!resultWrap) {
            return;
        }
        resultWrap.innerHTML = '';
        resultWrap.classList.add('hidden');
        resultWrap.classList.remove('is-ok', 'is-error');
    };

    const renderSuggestions = (items) => {
        if (!suggestionsWrap) {
            return;
        }

        suggestionsWrap.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            suggestionsWrap.classList.add('hidden');
            return;
        }

        const list = document.createElement('div');
        list.className = 'domain-search-chip-list';

        items.forEach((item) => {
            const domain = item.domain || item;
            const available = item.available !== false && item.status !== 'unavailable';
            const button = document.createElement('button');
            button.type = 'button';
            button.className = available
                ? 'domain-search-chip is-available'
                : 'domain-search-chip is-taken';
            const template = available
                ? root.dataset.msgSuggestionAvailable
                : root.dataset.msgSuggestionTaken;
            const statusLabel = escapeHtml(String(template || ':domain').replace(':domain', domain));
            const price = available && item.price_display
                ? `<span class="domain-search-chip-price">${escapeHtml(item.price_display)}</span>`
                : '';
            button.innerHTML = `<span class="domain-search-chip-label">${statusLabel}${price ? ` · ${price}` : ''}</span>`;
            button.addEventListener('click', () => {
                if (input) {
                    input.value = domain;
                }
                runCheck();
            });
            list.appendChild(button);
        });

        suggestionsWrap.appendChild(list);
        suggestionsWrap.classList.remove('hidden');
    };

    const continueHref = (domain, domainOption) => {
        const url = new URL(continueUrl, window.location.origin);
        url.searchParams.set('domain', domain);
        url.searchParams.set('domain_option', domainOption);
        return url.toString();
    };

    const showResult = ({ ok, title, message, domain, domainOption, priceDisplay, checkoutUrl }) => {
        if (!resultWrap) {
            return;
        }

        resultWrap.classList.remove('hidden', 'is-ok', 'is-error');
        resultWrap.classList.add(ok ? 'is-ok' : 'is-error');

        const safeTitle = escapeHtml(title);
        const safeMessage = escapeHtml(message || '');
        const safePrice = escapeHtml(priceDisplay || '');
        const isTransfer = domainOption === 'transfer';
        const statusIcon = ok
            ? `<span class="domain-search-result-icon is-ok" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>`
            : `<span class="domain-search-result-icon is-error" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></span>`;

        let html = `<div class="domain-search-result-card">`;
        html += `<div class="domain-search-result-head">${statusIcon}<div>`;
        html += `<p class="domain-search-result-title">${safeTitle}</p>`;
        if (domain) {
            html += `<p class="domain-search-result-domain">${escapeHtml(domain)}</p>`;
        }
        html += `</div></div>`;

        if (safeMessage) {
            html += `<p class="domain-search-result-message">${safeMessage}</p>`;
        }

        if (ok && safePrice) {
            html += `<div class="domain-search-result-price"><span>${escapeHtml(root.dataset.msgPrice)}</span><strong>${safePrice}</strong></div>`;
        }

        if (ok && domain) {
            const lede = isTransfer
                ? root.dataset.msgTransferContinueLede
                : root.dataset.msgContinueLede;
            const buyLabel = isTransfer
                ? root.dataset.msgBuyTransfer
                : root.dataset.msgBuyDomain;
            const addLabel = root.dataset.msgAddCart || 'Add to cart';
            const cartAddUrl = root.dataset.cartAddUrl || '';
            html += `<p class="domain-search-result-lede">${escapeHtml(lede || '')}</p>`;
            html += `<div class="domain-search-result-actions">`;
            if (cartAddUrl) {
                html += `<button type="button" class="domain-search-result-cta" data-add-cart data-domain="${escapeHtml(domain)}" data-option="${escapeHtml(domainOption)}">${escapeHtml(addLabel)}</button>`;
            }
            if (checkoutUrl) {
                html += `<a class="domain-search-result-cta${cartAddUrl ? ' is-secondary' : ''}" href="${escapeHtml(checkoutUrl)}" rel="noopener">${escapeHtml(buyLabel || '')}</a>`;
            }
            html += `<a class="domain-search-result-cta is-secondary" href="${continueHref(domain, domainOption)}">${escapeHtml(root.dataset.msgContinue)}</a>`;
            html += `</div>`;
        }

        html += `</div>`;
        resultWrap.innerHTML = html;
    };

    const fetchQuote = async (domain, domainOption) => {
        const requestId = ++quoteRequestId;
        setLoading(true);

        try {
            const response = await fetch(quoteUrl, {
                method: 'POST',
                headers: csrfHeaders(csrf),
                body: JSON.stringify({ domain, domain_option: domainOption }),
            });
            const quote = await response.json();
            if (requestId !== quoteRequestId) {
                return null;
            }
            if (!response.ok || !quote?.ok) {
                return null;
            }
            return quote;
        } catch {
            return null;
        } finally {
            // Always release — superseded requests must not leave the spinner stuck.
            setLoading(false);
        }
    };

    const fetchSuggestions = async (query) => {
        if (tab !== 'register' || !query || query.length < 2) {
            clearSuggestions();
            return;
        }

        const requestId = ++suggestRequestId;

        try {
            const response = await fetch(suggestUrl, {
                method: 'POST',
                headers: csrfHeaders(csrf),
                body: JSON.stringify({ query }),
            });
            const payload = await response.json();
            if (requestId !== suggestRequestId) {
                return;
            }
            renderSuggestions(payload?.suggestions || []);
        } catch {
            if (requestId === suggestRequestId) {
                clearSuggestions();
            }
        }
    };

    const runCheck = async () => {
        const raw = input?.value || '';
        const domain = normalizeDomain(raw);
        const domainOption = tab === 'transfer' ? 'transfer' : 'register';

        if (!domain) {
            clearResult();
            if (raw.trim()) {
                showResult({
                    ok: false,
                    title: root.dataset.msgInvalid,
                    message: '',
                });
            }
            return;
        }

        if (input && input.value !== domain) {
            input.value = domain;
        }

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        url.searchParams.set('q', domain);
        window.history.replaceState({}, '', url);

        const requestId = ++checkRequestId;
        setLoading(true);
        clearResult();

        try {
            const response = await fetch(checkUrl, {
                method: 'POST',
                headers: csrfHeaders(csrf),
                body: JSON.stringify({ domain, domain_option: domainOption }),
            });
            const payload = await response.json();
            if (requestId !== checkRequestId) {
                return;
            }

            if (payload?.ok) {
                const quote = await fetchQuote(domain, domainOption);
                if (requestId !== checkRequestId) {
                    return;
                }
                showResult({
                    ok: true,
                    title: domainOption === 'transfer'
                        ? root.dataset.msgTransferTitle
                        : root.dataset.msgAvailableTitle,
                    message: payload.message || '',
                    domain,
                    domainOption,
                    priceDisplay: quote?.display || null,
                    checkoutUrl: quote?.checkout_url || null,
                });
                if (domainOption === 'register') {
                    fetchSuggestions(domain.split('.')[0] || domain);
                } else {
                    clearSuggestions();
                }
                return;
            }

            const isTaken = domainOption === 'register' && payload?.status === 'unavailable';
            showResult({
                ok: false,
                title: isTaken
                    ? root.dataset.msgUnavailableTitle
                    : (payload?.message || root.dataset.msgCheckFailed),
                message: isTaken
                    ? root.dataset.msgUnavailableLede
                    : (payload?.message && !isTaken ? '' : (payload?.message || '')),
                domain,
            });

            if (domainOption === 'register' && isTaken) {
                fetchSuggestions(domain.split('.')[0] || domain);
            } else {
                clearSuggestions();
            }
        } catch {
            if (requestId === checkRequestId) {
                showResult({
                    ok: false,
                    title: root.dataset.msgInvalid,
                    message: '',
                });
            }
        } finally {
            // Always release — superseded checks must not leave the spinner stuck.
            setLoading(false);
        }
    };

    tabs.forEach((button) => {
        button.addEventListener('click', () => {
            setTab(button.getAttribute('data-domain-tab'));
        });
    });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        clearTimeout(checkTimer);
        clearTimeout(suggestTimer);
        runCheck();
    });

    input?.addEventListener('input', () => {
        clearTimeout(checkTimer);
        clearTimeout(suggestTimer);
        const value = input.value.trim();

        if (!value) {
            checkRequestId += 1;
            suggestRequestId += 1;
            quoteRequestId += 1;
            clearResult();
            clearSuggestions();
            const url = new URL(window.location.href);
            url.searchParams.delete('q');
            window.history.replaceState({}, '', url);
            return;
        }

        // Drop stale result UI while the query is incomplete or changing.
        if (!normalizeDomain(value)) {
            clearResult();
        }

        // Suggestions load quietly — they must not keep the input spinner spinning.
        suggestTimer = setTimeout(() => {
            if (tab === 'register') {
                fetchSuggestions(value);
            } else {
                clearSuggestions();
            }
        }, 350);

        checkTimer = setTimeout(() => {
            if (normalizeDomain(value)) {
                runCheck();
            }
        }, 700);
    });

    resultWrap?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-add-cart]');
        if (! button || ! resultWrap.contains(button)) {
            return;
        }

        const cartAddUrl = root.dataset.cartAddUrl;
        if (! cartAddUrl) {
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch(cartAddUrl, {
                method: 'POST',
                headers: csrfHeaders(csrf),
                body: JSON.stringify({
                    domain: button.dataset.domain,
                    option: button.dataset.option || 'register',
                    reg_period: 1,
                }),
            });
            const payload = await response.json();
            window.dispatchEvent(new CustomEvent('cart:updated', {
                detail: { count: payload?.count },
            }));
            window.dispatchEvent(new CustomEvent('domain-cart:updated', {
                detail: { count: payload?.count },
            }));

            if (response.ok && payload?.ok) {
                button.textContent = root.dataset.msgAddedCart || 'Added';
            } else {
                button.textContent = payload?.message || root.dataset.msgAddCart || 'Add to cart';
                button.disabled = false;
            }
        } catch {
            button.disabled = false;
        }
    });

    syncSubmitLabel();

    if (root.dataset.initialQ) {
        runCheck();
    }
};

document.querySelectorAll('[data-domain-search]').forEach(initDomainSearch);
