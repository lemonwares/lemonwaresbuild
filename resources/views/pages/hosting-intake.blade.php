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
        <div id="hosting-intake-form" class="mx-auto max-w-3xl scroll-mt-28">
            @if (session('hosting_feedback'))
                <p @class([
                    'mb-5 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => (session('hosting_feedback.type') === 'success'),
                    'border border-sky-200 bg-sky-50 text-sky-800' => (session('hosting_feedback.type') === 'info'),
                    'border border-rose/20 bg-rose/5 text-rose' => (session('hosting_feedback.type') === 'error'),
                ])>{{ session('hosting_feedback.message') }}</p>
            @endif

            <form method="POST" action="{{ route('hosting.intake.submit') }}" class="space-y-5 rounded-3xl border border-border bg-white p-6 sm:p-8" data-hosting-intake-form>
                @csrf
                <input type="hidden" name="plan" value="{{ old('plan', $selectedPlan) }}">
                <input type="hidden" name="spec" value="{{ old('spec', $selectedSpec ?? '') }}">
                <input type="hidden" name="billing_cycle" value="{{ old('billing_cycle', $selectedBillingCycle ?? 'monthly') }}">

                <div class="rounded-2xl border border-border bg-blush-soft p-4">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('hosting.selected_service') }}</p>
                    <p class="mt-1 text-base font-semibold text-black">
                        {{ $selectedPlanData['name'] ?? 'Hosting Plan' }}
                    </p>
                    <ul class="mt-2 space-y-2">
                        @foreach (($selectedSpecsData ?? [$selectedSpecData]) as $specItem)
                            @if ($specItem)
                                <li class="text-sm text-on-blush/80">
                                    <span class="font-semibold text-black">{{ $specItem['label'] ?? 'Specification' }}</span>
                                    @if (! empty($specItem['price_display']))
                                        <span class="text-rose"> · {{ $specItem['price_display'] }}</span>
                                    @endif
                                    @if (! empty($specItem['period_display']))
                                        <span class="block text-xs font-semibold text-on-blush/70">
                                            {{ __('hosting.period_total') }}: {{ $specItem['period_display'] }}
                                        </span>
                                    @endif
                                    <span class="block text-xs font-semibold uppercase tracking-widest text-on-blush/55">
                                        {{ $specItem['billing_cycle_label'] ?? __('hosting.cycles.' . ($selectedBillingCycle ?? 'monthly')) }}
                                    </span>
                                    @if (! empty($specItem['description']))
                                        <span class="mt-0.5 block">{{ $specItem['description'] }}</span>
                                    @endif
                                </li>
                            @endif
                        @endforeach
                    </ul>
                    @if (! empty($orderTotalDisplay))
                        <p class="mt-3 text-sm font-bold text-black">
                            {{ __('hosting.period_total') }}: <span class="text-rose">{{ $orderTotalDisplay }}</span>
                        </p>
                    @endif
                    <p class="mt-3 text-xs font-semibold text-rose">
                        @if (($selectedPlan ?? '') === 'vps')
                            {{ __('hosting.vps_notice') }}
                        @else
                            {{ __('hosting.whmcs_notice') }}
                        @endif
                    </p>
                    <a href="{{ route('hosting.specifications', ['plan' => $selectedPlan, 'spec' => $selectedSpecKeys ?? [], 'billing_cycle' => $selectedBillingCycle ?? 'monthly']) }}" class="mt-2 inline-flex text-sm font-semibold text-rose hover:underline">
                        {{ __('hosting.change_specification') }}
                    </a>
                </div>

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
            let allCountryOptions = [];

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

                submitButton.disabled = !allValid;
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

