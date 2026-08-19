@extends('layouts.app')

@section('title', 'Start Hosting Order — ' . config('site.short_name'))
@section('meta_description', 'Tell Lemonwares what hosting plan you need, then continue to secure WHMCS checkout.')
@section('focus_flow', '1')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.2/build/css/intlTelInput.css">

    <x-layout.page-hero
        :eyebrow="__('hosting.step_2')"
        :title="__('hosting.billing_details_title')"
        :lede="__('hosting.billing_details_lede')"
        cta-href="#page-content"
        cta-label="Continue"
    />

    <x-layout.page-content>
        <div id="hosting-intake-form" class="mx-auto max-w-6xl scroll-mt-28">
            @if (session('hosting_feedback'))
                <p @class([
                    'mb-5 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => (session('hosting_feedback.type') === 'success'),
                    'border border-sky-200 bg-sky-50 text-sky-800' => (session('hosting_feedback.type') === 'info'),
                    'border border-rose/20 bg-rose/5 text-rose' => (session('hosting_feedback.type') === 'error'),
                ])>{{ session('hosting_feedback.message') }}</p>
            @endif

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
                <form method="POST" action="{{ route('hosting.intake.submit') }}" class="order-2 space-y-5 rounded-3xl border border-border bg-white p-6 sm:p-8 lg:order-1" data-hosting-intake-form>
                @csrf
                <input type="hidden" name="plan" value="{{ old('plan', $selectedPlan) }}">
                <input type="hidden" name="spec" value="{{ old('spec', $selectedSpec ?? '') }}">
                <input type="hidden" name="billing_cycle" value="{{ old('billing_cycle', $selectedBillingCycle ?? 'monthly') }}">

                @if (($selectedPlan ?? '') !== 'vps')
                    <div class="rounded-2xl border border-border bg-blush-soft p-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('hosting.domain_section_title') }}</p>
                        <p class="mt-1 text-sm text-on-blush/75">{{ __('hosting.domain_section_help') }}</p>

                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="domain" class="mb-2 block text-sm font-semibold text-black">{{ __('hosting.domain_label') }} <span class="text-rose">*</span></label>
                                <div class="hosting-domain-field relative">
                                    <input
                                        id="domain"
                                        name="domain"
                                        type="text"
                                        value="{{ old('domain') }}"
                                        placeholder="example.com"
                                        class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3 transition-[padding]"
                                        required
                                        autocomplete="off"
                                        spellcheck="false"
                                        data-hosting-input
                                        data-hosting-domain-input
                                    />
                                    <span
                                        class="hidden"
                                        data-hosting-domain-spinner
                                        aria-hidden="true"
                                    >
                                        <span class="size-4 animate-spin rounded-full border-2 border-on-blush/20 border-t-rose"></span>
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-sm text-on-blush/65"
                                    data-hosting-domain-prompt
                                    data-hosting-domain-prompt-default="{{ __('hosting.domain_check_prompt') }}"
                                >{{ __('hosting.domain_check_prompt') }}</p>
                                <div class="mt-3 hidden flex-wrap gap-2" data-hosting-domain-suggestions></div>
                                <p class="mt-2 hidden text-sm font-semibold text-black" data-hosting-domain-status aria-live="polite"></p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="domain_option" class="mb-2 block text-sm font-semibold text-black">{{ __('hosting.domain_option_label') }} <span class="text-rose">*</span></label>
                                <select
                                    id="domain_option"
                                    name="domain_option"
                                    class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                                    required
                                    data-hosting-input
                                    data-hosting-domain-option
                                >
                                    <option value="register" @selected(old('domain_option', 'register') === 'register')>{{ __('hosting.domain_option_register') }}</option>
                                    <option value="owndomain" @selected(old('domain_option') === 'owndomain')>{{ __('hosting.domain_option_owndomain') }}</option>
                                    <option value="transfer" @selected(old('domain_option') === 'transfer')>{{ __('hosting.domain_option_transfer') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="full_name" class="mb-2 block text-sm font-semibold text-black">Full Name <span class="text-rose">*</span></label>
                        <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" required data-hosting-input />
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-black">Email <span class="text-rose">*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" required data-hosting-input />
                    </div>

                    <div>
                        <label for="billing_country" class="mb-2 block text-sm font-semibold text-black">Country <span class="text-rose">*</span></label>
                        <div class="mb-2 hidden" data-country-search-wrap>
                            <input
                                id="country_search"
                                type="text"
                                placeholder="Search country..."
                                class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                                data-country-search
                            />
                            <div class="mt-2 max-h-56 overflow-auto rounded-xl border border-border bg-white" data-country-results></div>
                        </div>
                        <select
                            id="billing_country"
                            name="billing_country"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            required
                            data-hosting-input
                            data-billing-country
                            data-old-country="{{ old('billing_country', 'NG') }}"
                        >
                            <option value="">Select a country</option>
                        </select>
                    </div>

                    <div>
                        <label for="phone" class="mb-2 block text-sm font-semibold text-black">Phone Number <span class="text-rose">*</span></label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone') }}"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            required
                            maxlength="16"
                            pattern="^\+?[0-9]{7,15}$"
                            title="Enter a valid phone number (7 to 15 digits, optional +)."
                            data-hosting-input
                        />
                    </div>

                    <div>
                        <label for="company" class="mb-2 block text-sm font-semibold text-black">Company (optional)</label>
                        <input id="company" name="company" type="text" value="{{ old('company') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-hosting-input />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="billing_address_line_1" class="mb-2 block text-sm font-semibold text-black">Billing Address Line 1 <span class="text-rose">*</span></label>
                        <input id="billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" required data-hosting-input />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="billing_address_line_2" class="mb-2 block text-sm font-semibold text-black">Billing Address Line 2 (optional)</label>
                        <input id="billing_address_line_2" name="billing_address_line_2" type="text" value="{{ old('billing_address_line_2') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-hosting-input />
                    </div>

                    <div>
                        <label for="billing_state" class="mb-2 block text-sm font-semibold text-black">State / Region <span class="text-rose">*</span></label>
                        <select
                            id="billing_state"
                            name="billing_state"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            required
                            data-hosting-input
                            data-billing-state
                            data-old-state="{{ old('billing_state') }}"
                        >
                            <option value="">Select a state</option>
                        </select>
                    </div>

                    <div>
                        <label for="billing_city" class="mb-2 block text-sm font-semibold text-black">City <span class="text-rose">*</span></label>
                        <select
                            id="billing_city"
                            name="billing_city"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            required
                            data-hosting-input
                            data-billing-city
                            data-old-city="{{ old('billing_city') }}"
                        >
                            <option value="">Select a city</option>
                        </select>
                    </div>

                    <div>
                        <label for="billing_postcode" class="mb-2 block text-sm font-semibold text-black">Postal / Zip Code <span class="text-rose">*</span></label>
                        <input id="billing_postcode" name="billing_postcode" type="text" value="{{ old('billing_postcode') }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" required data-hosting-input />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="mb-2 block text-sm font-semibold text-black">Project Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="4" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-hosting-input>{{ old('notes') }}</textarea>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit" data-hosting-submit disabled class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-rose px-6 py-4 text-base font-bold text-white shadow-[0_10px_24px_rgba(224,69,69,0.35)] transition hover:bg-[#cf3a3a] disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-hosting-spinner></span>
                    <span data-hosting-label>{{ __('hosting.continue_checkout') }}</span>
                    <span class="hidden" data-hosting-loading>Preparing Checkout...</span>
                </button>
                </form>

                <x-hosting.order-summary
                    :selected-plan="$selectedPlan"
                    :selected-plan-data="$selectedPlanData"
                    :selected-spec-keys="$selectedSpecKeys ?? []"
                    :selected-spec-data="$selectedSpecData"
                    :selected-specs-data="$selectedSpecsData"
                    :selected-billing-cycle="$selectedBillingCycle ?? 'monthly'"
                    :order-total-usd="$orderTotalUsd"
                    :order-total-display="$orderTotalDisplay"
                    :hosting-amount-usd="$hostingAmountUsd ?? $orderTotalUsd"
                    :hosting-amount-display="$hostingAmountDisplay ?? $orderTotalDisplay"
                    :requires-domain="$requiresDomain ?? false"
                />
            </div>
        </div>
    </x-layout.page-content>

    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.2/build/js/intlTelInput.min.js"></script>
    <script>
        const hostingIntakeForm = document.querySelector('[data-hosting-intake-form]');

        if (hostingIntakeForm) {
            const formAnchor = document.getElementById('hosting-intake-form');
            const phoneInput = hostingIntakeForm.querySelector('#phone');
            const countrySearchWrap = hostingIntakeForm.querySelector('[data-country-search-wrap]');
            const countrySearchInput = hostingIntakeForm.querySelector('[data-country-search]');
            const countryResults = hostingIntakeForm.querySelector('[data-country-results]');
            const countrySelect = hostingIntakeForm.querySelector('[data-billing-country]');
            const stateSelect = hostingIntakeForm.querySelector('[data-billing-state]');
            const citySelect = hostingIntakeForm.querySelector('[data-billing-city]');
            const submitButton = hostingIntakeForm.querySelector('[data-hosting-submit]');
            const requiredInputs = hostingIntakeForm.querySelectorAll('[data-hosting-input][required]');
            const domainInput = hostingIntakeForm.querySelector('[data-hosting-domain-input]');
            const domainOptionSelect = hostingIntakeForm.querySelector('[data-hosting-domain-option]');
            const domainStatus = hostingIntakeForm.querySelector('[data-hosting-domain-status]');
            const domainPrompt = hostingIntakeForm.querySelector('[data-hosting-domain-prompt]');
            const domainSpinner = hostingIntakeForm.querySelector('[data-hosting-domain-spinner]');
            const domainSuggestionsWrap = hostingIntakeForm.querySelector('[data-hosting-domain-suggestions]');
            let allCountryOptions = [];
            let domainCheckOk = !domainInput;
            let domainCheckTimer = null;
            let domainSuggestTimer = null;
            let domainCheckRequestId = 0;
            let domainSuggestRequestId = 0;
            let domainLoadingCount = 0;
            const domainPromptDefault = domainPrompt?.dataset.hostingDomainPromptDefault || @json(__('hosting.domain_check_prompt'));

            const domainCheckMessages = {
                checking: @json(__('hosting.domain_checking')),
                invalid: @json(__('hosting.domain_invalid')),
                suggestionsLoading: @json(__('hosting.domain_suggestions_loading')),
            };

            const orderSummary = document.querySelector('[data-hosting-order-summary]');
            const summaryDomainLabel = orderSummary?.querySelector('[data-hosting-summary-domain-label]');
            const summaryDomainPeriod = orderSummary?.querySelector('[data-hosting-summary-domain-period]');
            const summaryDomainDisplay = orderSummary?.querySelector('[data-hosting-summary-domain-display]');
            const summaryTotalDisplay = orderSummary?.querySelector('[data-hosting-summary-total-display]');
            const requiresDomainSummary = orderSummary?.dataset.requiresDomain === '1';
            const hostingAmountUsd = parseFloat(orderSummary?.dataset.hostingAmountUsd || '0') || 0;
            const usdToNgn = {{ (float) ($usdToNgn ?? 7800) }};
            let domainAmountUsd = 0;
            let domainQuoteOk = !requiresDomainSummary;
            let domainQuoteRequestId = 0;

            const summaryLabels = {
                pending: @json(__('hosting.order_summary_domain_pending')),
                included: @json(__('hosting.order_summary_included')),
                quoteFailed: @json(__('hosting.domain_quote_unavailable')),
            };

            const money = (amount, currency) => {
                if (currency === 'USD') {
                    return '$' + Number(amount).toLocaleString(undefined, {
                        minimumFractionDigits: amount >= 100 ? 0 : 2,
                        maximumFractionDigits: 2,
                    });
                }

                return '₦' + Math.round(amount).toLocaleString();
            };

            const dual = (usd) => money(usd, 'USD') + ' / ' + money(usd * usdToNgn, 'NGN');

            const refreshOrderSummary = () => {
                if (!summaryTotalDisplay) {
                    return;
                }

                summaryTotalDisplay.textContent = dual(hostingAmountUsd + domainAmountUsd);
            };

            const resetDomainQuote = () => {
                domainAmountUsd = 0;
                domainQuoteOk = !requiresDomainSummary;

                if (summaryDomainLabel) {
                    summaryDomainLabel.textContent = summaryLabels.pending;
                }

                if (summaryDomainPeriod) {
                    summaryDomainPeriod.textContent = '';
                }

                if (summaryDomainDisplay) {
                    summaryDomainDisplay.textContent = '—';
                }

                refreshOrderSummary();
            };

            const applyDomainQuote = (quote) => {
                domainAmountUsd = parseFloat(String(quote?.amount_usd ?? 0)) || 0;
                domainQuoteOk = Boolean(quote?.ok);

                if (summaryDomainLabel) {
                    summaryDomainLabel.textContent = quote?.label || summaryLabels.pending;
                }

                if (summaryDomainPeriod) {
                    summaryDomainPeriod.textContent = quote?.period_label || '';
                }

                if (summaryDomainDisplay) {
                    summaryDomainDisplay.textContent = quote?.display || dual(domainAmountUsd);
                }

                refreshOrderSummary();
            };

            const fetchDomainQuote = async () => {
                if (!requiresDomainSummary || !domainInput || !domainOptionSelect) {
                    return;
                }

                const domainOption = String(domainOptionSelect.value || 'register');
                const normalized = normalizeDomainValue(domainInput.value);

                if (!normalized) {
                    resetDomainQuote();
                    updateSubmitState();
                    return;
                }

                const requestId = ++domainQuoteRequestId;

                if (summaryDomainDisplay) {
                    summaryDomainDisplay.textContent = '…';
                }

                try {
                    const response = await fetch(@json(route('hosting.domain.quote')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            domain: normalized,
                            domain_option: domainOption,
                        }),
                    });

                    const quote = await response.json();
                    if (requestId !== domainQuoteRequestId) {
                        return;
                    }

                    if (!response.ok || !quote?.ok) {
                        domainQuoteOk = false;
                        domainAmountUsd = 0;
                        if (summaryDomainLabel) {
                            summaryDomainLabel.textContent = quote?.label || summaryLabels.pending;
                        }
                        if (summaryDomainDisplay) {
                            summaryDomainDisplay.textContent = '—';
                        }
                        refreshOrderSummary();
                        updateSubmitState();
                        return;
                    }

                    applyDomainQuote(quote);
                } catch (error) {
                    if (requestId !== domainQuoteRequestId) {
                        return;
                    }

                    domainQuoteOk = false;
                    domainAmountUsd = 0;
                    if (summaryDomainDisplay) {
                        summaryDomainDisplay.textContent = '—';
                    }
                    refreshOrderSummary();
                }

                updateSubmitState();
            };

            const setDomainLoading = (active, promptMessage = null) => {
                domainLoadingCount = active
                    ? domainLoadingCount + 1
                    : Math.max(0, domainLoadingCount - 1);

                const isLoading = domainLoadingCount > 0;

                if (domainSpinner) {
                    domainSpinner.classList.toggle('hidden', !isLoading);
                }

                if (domainInput) {
                    domainInput.classList.toggle('is-domain-loading', isLoading);
                }

                if (domainPrompt) {
                    if (isLoading && promptMessage) {
                        domainPrompt.textContent = promptMessage;
                        domainPrompt.classList.remove('text-on-blush/65');
                        domainPrompt.classList.add('text-black');
                    } else if (!isLoading) {
                        domainPrompt.textContent = domainPromptDefault;
                        domainPrompt.classList.add('text-on-blush/65');
                        domainPrompt.classList.remove('text-black');
                    }
                }
            };

            const setDomainStatus = (state, message = '') => {
                if (!domainStatus) {
                    return;
                }

                domainStatus.textContent = message;
                domainStatus.classList.remove('hidden', 'text-emerald-700', 'text-rose', 'text-black', 'text-on-blush/65');

                if (state === 'hidden' || message === '') {
                    domainStatus.classList.add('hidden');
                    return;
                }

                domainStatus.classList.remove('hidden');

                if (state === 'checking' || state === 'info') {
                    domainStatus.classList.add('text-black');
                } else if (state === 'ok') {
                    domainStatus.classList.add('text-emerald-700');
                } else if (state === 'error') {
                    domainStatus.classList.add('text-rose');
                } else {
                    domainStatus.classList.add('text-black');
                }
            };

            const extractDomainLabel = (value) => {
                let normalized = String(value || '').trim().toLowerCase();
                normalized = normalized.replace(/^https?:\/\//, '');
                normalized = normalized.split('/')[0].replace(/\.$/, '');
                normalized = normalized.replace(/:\d+$/, '');

                if (normalized.includes('.')) {
                    return normalized.split('.')[0].replace(/-+$/g, '');
                }

                return normalized.replace(/-+$/g, '');
            };

            const renderDomainSuggestions = (suggestions = []) => {
                if (!domainSuggestionsWrap) {
                    return;
                }

                domainSuggestionsWrap.innerHTML = '';

                if (!suggestions.length) {
                    domainSuggestionsWrap.classList.add('hidden');
                    domainSuggestionsWrap.classList.remove('flex');
                    return;
                }

                suggestions.forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = item.label || item.domain;
                    button.dataset.domain = item.domain;
                    button.className = item.available
                        ? 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100'
                        : 'inline-flex items-center rounded-full border border-border bg-white px-3 py-1.5 text-xs font-semibold text-on-blush/55';

                    if (item.available) {
                        button.addEventListener('click', () => {
                            if (!domainInput) return;
                            domainInput.value = item.domain;
                            domainInput.dispatchEvent(new Event('change'));
                            runDomainCheck();
                        });
                    } else {
                        button.disabled = true;
                    }

                    domainSuggestionsWrap.appendChild(button);
                });

                domainSuggestionsWrap.classList.remove('hidden');
                domainSuggestionsWrap.classList.add('flex');
            };

            const normalizeDomainValue = (value) => {
                let normalized = String(value || '').trim().toLowerCase();
                normalized = normalized.replace(/^https?:\/\//, '');
                normalized = normalized.split('/')[0].replace(/\.$/, '');
                normalized = normalized.replace(/:\d+$/, '');

                if (!/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/.test(normalized)) {
                    return null;
                }

                return normalized;
            };

            const runDomainSuggest = async () => {
                if (!domainInput || !domainOptionSelect || !domainSuggestionsWrap) {
                    return;
                }

                const domainOption = String(domainOptionSelect.value || 'register');
                if (domainOption !== 'register') {
                    renderDomainSuggestions([]);
                    setDomainLoading(false);
                    return;
                }

                const label = extractDomainLabel(domainInput.value);
                if (!label || label.length < 2) {
                    renderDomainSuggestions([]);
                    setDomainStatus('hidden');
                    setDomainLoading(false);
                    domainCheckOk = false;
                    updateSubmitState();
                    return;
                }

                const requestId = ++domainSuggestRequestId;
                setDomainLoading(true, domainCheckMessages.suggestionsLoading);
                setDomainStatus('hidden');

                try {
                    const response = await fetch(@json(route('hosting.domain.suggest')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({ query: label }),
                    });

                    const result = await response.json();
                    if (requestId !== domainSuggestRequestId) {
                        return;
                    }

                    if (!response.ok) {
                        renderDomainSuggestions([]);
                        setDomainStatus('error', String(result?.message || domainCheckMessages.invalid));
                        domainCheckOk = false;
                        updateSubmitState();
                        return;
                    }

                    renderDomainSuggestions(Array.isArray(result?.suggestions) ? result.suggestions : []);
                    setDomainStatus('hidden');

                    const exactMatch = normalizeDomainValue(domainInput.value);
                    if (exactMatch) {
                        await runDomainCheck();
                    } else {
                        domainCheckOk = false;
                        updateSubmitState();
                    }
                } catch (error) {
                    if (requestId !== domainSuggestRequestId) {
                        return;
                    }

                    renderDomainSuggestions([]);
                    setDomainStatus('error', @json(__('hosting.domain_check_failed')));
                    domainCheckOk = false;
                    updateSubmitState();
                } finally {
                    setDomainLoading(false);
                }
            };

            const scheduleDomainSuggest = () => {
                if (!domainInput) {
                    return;
                }

                if (domainSuggestTimer) {
                    clearTimeout(domainSuggestTimer);
                }

                domainSuggestTimer = setTimeout(() => {
                    runDomainSuggest();
                }, 350);
            };

            const runDomainCheck = async () => {
                if (!domainInput || !domainOptionSelect) {
                    return;
                }

                const domainOption = String(domainOptionSelect.value || 'register');
                if (domainOption === 'owndomain') {
                    domainCheckOk = true;
                    setDomainStatus('hidden');
                    renderDomainSuggestions([]);
                    setDomainLoading(false);
                    fetchDomainQuote();
                    updateSubmitState();
                    return;
                }

                const normalized = normalizeDomainValue(domainInput.value);
                if (!normalized) {
                    domainCheckOk = false;
                    if (extractDomainLabel(domainInput.value).length >= 2) {
                        setDomainStatus('hidden');
                    } else {
                        setDomainStatus('error', domainCheckMessages.invalid);
                    }
                    updateSubmitState();
                    return;
                }

                const requestId = ++domainCheckRequestId;
                domainCheckOk = false;
                setDomainLoading(true, domainCheckMessages.checking);
                setDomainStatus('hidden');
                updateSubmitState();

                try {
                    const response = await fetch(@json(route('hosting.domain.check')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({
                            domain: normalized,
                            domain_option: domainOption,
                        }),
                    });

                    const result = await response.json();
                    if (requestId !== domainCheckRequestId) {
                        return;
                    }

                    domainCheckOk = Boolean(result?.ok);
                    setDomainStatus(domainCheckOk ? 'ok' : 'error', String(result?.message || domainCheckMessages.invalid));

                    if (domainCheckOk) {
                        await fetchDomainQuote();
                    } else {
                        resetDomainQuote();
                    }
                } catch (error) {
                    if (requestId !== domainCheckRequestId) {
                        return;
                    }

                    domainCheckOk = false;
                    setDomainStatus('error', @json(__('hosting.domain_check_failed')));
                    resetDomainQuote();
                } finally {
                    setDomainLoading(false);
                }

                updateSubmitState();
            };

            const scheduleDomainCheck = (immediate = false) => {
                if (!domainInput) {
                    return;
                }

                if (domainCheckTimer) {
                    clearTimeout(domainCheckTimer);
                }

                if (immediate) {
                    runDomainCheck();
                    return;
                }

                domainCheckTimer = setTimeout(() => {
                    runDomainCheck();
                }, 650);
            };

            const oldCountry = (countrySelect?.dataset.oldCountry || 'NG').toUpperCase();
            const oldState = stateSelect?.dataset.oldState || '';
            const oldCity = citySelect?.dataset.oldCity || '';

            const setSelectOptions = (select, items, placeholder, selectedValue = '') => {
                if (!select) return;
                select.innerHTML = '';

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = placeholder;
                select.appendChild(defaultOption);

                items.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.value;
                    option.textContent = item.label;
                    if (item.countryName) {
                        option.dataset.countryName = item.countryName;
                    }

                    if (selectedValue && selectedValue.toLowerCase() === String(item.value).toLowerCase()) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });
            };

            const setLoadingState = (select, loadingText) => {
                if (!select) return;
                select.innerHTML = '';
                const option = document.createElement('option');
                option.value = '';
                option.textContent = loadingText;
                select.appendChild(option);
            };

            const fetchCountries = async () => {
                if (!countrySelect) return;

                try {
                    setLoadingState(countrySelect, 'Loading countries...');
                    const response = await fetch('{{ route('location.countries') }}');
                    const result = await response.json();

                    const countries = (result?.data || [])
                        .filter((item) => item?.label && item?.value)
                        .sort((a, b) => a.label.localeCompare(b.label));
                    allCountryOptions = countries;

                    setSelectOptions(countrySelect, countries, 'Select a country', oldCountry || 'NG');
                } catch (error) {
                    allCountryOptions = [];
                    setSelectOptions(countrySelect, [], 'Unable to load countries');
                }
            };

            const updateSubmitState = () => {
                if (!submitButton) return;
                const allValid = Array.from(requiredInputs).every((input) => {
                    if (input.tagName === 'SELECT') {
                        return String(input.value || '').trim() !== '';
                    }
                    return input.checkValidity() && String(input.value || '').trim() !== '';
                });

                const domainReady = !domainInput || (domainCheckOk && domainQuoteOk);
                submitButton.disabled = !allValid || !domainReady;
            };

            const fetchStatesByCountryCode = async (countryCode, selectedState = '', selectedCity = '') => {
                const selectedOption = countrySelect?.selectedOptions?.[0];
                const countryName = selectedOption?.dataset.countryName || selectedOption?.textContent?.trim() || '';

                if (!countryName || !stateSelect) return;

                try {
                    setLoadingState(stateSelect, 'Loading states...');
                    setSelectOptions(citySelect, [], 'Select a city');

                    const response = await fetch('{{ route('location.states') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ country: countryName }),
                    });

                    const result = await response.json();
                    const states = result?.data || [];

                    setSelectOptions(stateSelect, states, 'Select a state', selectedState);

                    if (stateSelect.value) {
                        await fetchCities(countryName, stateSelect.value, selectedCity);
                    }
                } catch (error) {
                    setSelectOptions(stateSelect, [], 'Type your state in notes if unavailable');
                    setSelectOptions(citySelect, [], 'Type your city in notes if unavailable');
                }
            };

            const fetchCities = async (countryName, stateName, selectedCity = '') => {
                if (!citySelect) return;

                try {
                    setLoadingState(citySelect, 'Loading cities...');
                    const response = await fetch('{{ route('location.cities') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            country: countryName,
                            state: stateName,
                        }),
                    });

                    const result = await response.json();
                    const cities = result?.data || [];

                    setSelectOptions(citySelect, cities, 'Select a city', selectedCity);
                } catch (error) {
                    setSelectOptions(citySelect, [], 'Type your city in notes if unavailable');
                }
            };

            const initializePhoneInput = () => {
                if (!phoneInput || !window.intlTelInput) return null;

                const iti = window.intlTelInput(phoneInput, {
                    initialCountry: (countrySelect?.value || oldCountry || 'NG').toLowerCase(),
                    autoPlaceholder: 'aggressive',
                    nationalMode: false,
                    separateDialCode: true,
                    strictMode: true,
                    formatAsYouType: true,
                    utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.2/build/js/utils.js',
                });

                phoneInput.addEventListener('countrychange', () => {
                    const selectedData = iti.getSelectedCountryData();
                    if (!selectedData?.iso2 || !countrySelect) return;
                    const code = selectedData.iso2.toUpperCase();
                    if (countrySelect.value !== code) {
                        countrySelect.value = code;
                        countrySelect.dispatchEvent(new Event('change'));
                    }
                    updateSubmitState();
                });

                return iti;
            };

            const filterCountries = () => {
                if (!countrySelect || !countrySearchInput) return [];
                const query = countrySearchInput.value.trim().toLowerCase();

                const filtered = allCountryOptions.filter((country) => {
                    if (!query) return true;
                    return country.label.toLowerCase().includes(query) || country.value.toLowerCase().includes(query);
                });

                return filtered;
            };

            const renderCountryResults = (countries) => {
                if (!countryResults) return;
                countryResults.innerHTML = '';

                if (!countries.length) {
                    const empty = document.createElement('p');
                    empty.className = 'px-3 py-2 text-sm text-on-blush/60';
                    empty.textContent = 'No countries found.';
                    countryResults.appendChild(empty);
                    return;
                }

                countries.forEach((country) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'block w-full px-3 py-2 text-left text-sm text-black transition hover:bg-blush';
                    button.textContent = `${country.label} (${country.value})`;
                    button.dataset.countryValue = country.value;
                    button.addEventListener('click', () => {
                        countrySelect.value = country.value;
                        countrySelect.dispatchEvent(new Event('change'));
                    });
                    countryResults.appendChild(button);
                });
            };

            const showCountrySearch = () => {
                if (!countrySearchWrap) return;
                countrySearchWrap.classList.remove('hidden');
                renderCountryResults(filterCountries());
                if (countrySearchInput) {
                    countrySearchInput.focus();
                    countrySearchInput.select();
                }
            };

            const hideCountrySearch = () => {
                if (!countrySearchWrap) return;
                countrySearchWrap.classList.add('hidden');
                if (countrySearchInput) {
                    countrySearchInput.value = '';
                }
                if (countryResults) {
                    countryResults.innerHTML = '';
                }
            };

            let itiInstance = null;
            let suppressNextOutsideClose = false;
            const openCountryPicker = (event) => {
                event.preventDefault();
                event.stopPropagation();
                suppressNextOutsideClose = true;
                countrySelect?.blur();
                showCountrySearch();
            };

            countrySelect?.addEventListener('mousedown', openCountryPicker);
            countrySelect?.addEventListener('click', openCountryPicker);

            countrySelect?.addEventListener('focus', () => {
                suppressNextOutsideClose = true;
                countrySelect?.blur();
                showCountrySearch();
            });

            countrySelect?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
                    openCountryPicker(event);
                }
            });

            countrySearchWrap?.addEventListener('mousedown', () => {
                suppressNextOutsideClose = true;
            });

            document.addEventListener('click', (event) => {
                if (suppressNextOutsideClose) {
                    suppressNextOutsideClose = false;
                    return;
                }
                const target = event.target;
                if (!target) return;
                if (countrySearchWrap?.contains(target) || countrySelect?.contains(target)) return;
                hideCountrySearch();
            });

            countrySelect?.addEventListener('change', async () => {
                await fetchStatesByCountryCode(countrySelect.value);
                if (itiInstance && countrySelect.value) {
                    itiInstance.setCountry(countrySelect.value.toLowerCase());
                }
                updateSubmitState();
                hideCountrySearch();
            });

            stateSelect?.addEventListener('change', async () => {
                const selectedCountryOption = countrySelect?.selectedOptions?.[0];
                const countryName = selectedCountryOption?.dataset.countryName || selectedCountryOption?.textContent || '';
                if (!countryName || !stateSelect.value) return;
                await fetchCities(countryName, stateSelect.value);
                updateSubmitState();
            });

            const initializeForm = async () => {
                await fetchCountries();
                itiInstance = initializePhoneInput();

                if (countrySelect?.value) {
                    await fetchStatesByCountryCode(countrySelect.value, oldState, oldCity);
                }

                requiredInputs.forEach((input) => {
                    input.addEventListener('input', updateSubmitState);
                    input.addEventListener('change', updateSubmitState);
                });

                countrySearchInput?.addEventListener('input', () => {
                    renderCountryResults(filterCountries());
                });

                domainInput?.addEventListener('input', () => {
                    scheduleDomainSuggest();
                });
                domainInput?.addEventListener('blur', () => {
                    if (normalizeDomainValue(domainInput.value)) {
                        runDomainCheck();
                    }
                });
                domainOptionSelect?.addEventListener('change', () => {
                    resetDomainQuote();
                    scheduleDomainSuggest();
                    if (normalizeDomainValue(domainInput?.value || '')) {
                        runDomainCheck();
                    } else if (String(domainOptionSelect.value || '') === 'owndomain' && normalizeDomainValue(domainInput?.value || '')) {
                        fetchDomainQuote();
                    } else {
                        domainCheckOk = String(domainOptionSelect.value || '') === 'owndomain';
                        updateSubmitState();
                    }
                });

                refreshOrderSummary();

                if (domainInput?.value) {
                    scheduleDomainSuggest();
                }

                updateSubmitState();
            };

            initializeForm();

            const params = new URLSearchParams(window.location.search);
            if (params.has('plan') && formAnchor) {
                window.requestAnimationFrame(() => {
                    formAnchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }

            hostingIntakeForm.addEventListener('submit', () => {
                const inputs = hostingIntakeForm.querySelectorAll('[data-hosting-input]');
                const spinner = hostingIntakeForm.querySelector('[data-hosting-spinner]');
                const label = hostingIntakeForm.querySelector('[data-hosting-label]');
                const loadingLabel = hostingIntakeForm.querySelector('[data-hosting-loading]');

                inputs.forEach((input) => {
                    if (input.tagName === 'SELECT') {
                        input.setAttribute('aria-disabled', 'true');
                        input.classList.add('pointer-events-none');
                    } else {
                        input.setAttribute('readonly', 'readonly');
                    }
                    input.setAttribute('aria-disabled', 'true');
                    input.classList.add('opacity-80');
                });

                if (submitButton) {
                    submitButton.setAttribute('disabled', 'disabled');
                    submitButton.classList.add('opacity-80', 'cursor-not-allowed');
                }

                if (spinner) spinner.classList.remove('hidden');
                if (label) label.classList.add('hidden');
                if (loadingLabel) loadingLabel.classList.remove('hidden');
            });
        }
    </script>
@endsection

