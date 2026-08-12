@extends('layouts.app')

@section('title', __('hosting.choose_spec_title') . ' — ' . config('site.short_name'))
@section('meta_description', 'Choose your hosting specification before completing billing details.')
@section('focus_flow', '1')

@section('content')
    <style>
        [data-spec-accordion] [data-spec-accordion-panel] {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 320ms ease;
        }

        [data-spec-accordion][data-open="true"] [data-spec-accordion-panel] {
            grid-template-rows: 1fr;
        }

        [data-spec-accordion] [data-spec-accordion-inner] {
            overflow: hidden;
            min-height: 0;
        }

        [data-spec-accordion] [data-spec-accordion-chevron] {
            transition: transform 320ms ease;
        }

        [data-spec-accordion][data-open="true"] [data-spec-accordion-chevron] {
            transform: rotate(180deg);
        }

        [data-spec-check] { display: none; }
        [data-spec-card][data-selected="true"] [data-spec-check] { display: inline-flex; }
        [data-spec-selected-label] { display: none; }
        [data-spec-card][data-selected="true"] [data-spec-selected-label] { display: block; }
    </style>

    <x-layout.page-hero
        :eyebrow="__('hosting.step_1')"
        :title="__('hosting.choose_spec_title')"
        :lede="__('hosting.choose_spec_lede')"
        cta-href="#page-content"
        :cta-label="__('hosting.view_plans')"
    />

    <x-layout.page-content full>
        @if (session('hosting_feedback'))
            <p @class([
                'mb-5 rounded-xl px-4 py-3 text-sm',
                'border border-emerald-200 bg-emerald-50 text-emerald-800' => (session('hosting_feedback.type') === 'success'),
                'border border-sky-200 bg-sky-50 text-sky-800' => (session('hosting_feedback.type') === 'info'),
                'border border-rose/20 bg-rose/5 text-rose' => (session('hosting_feedback.type') === 'error'),
            ])>{{ session('hosting_feedback.message') }}</p>
        @endif

        <div class="mb-8 rounded-2xl border border-border bg-white p-5 sm:p-6">
            <p class="section-label mb-2">{{ $plan['name'] }}</p>
            <h2 class="text-2xl font-bold text-black sm:text-3xl">{{ $plan['title'] }}</h2>
            <p class="mt-2 max-w-3xl body-text">{{ $plan['summary'] }}</p>
            <p class="mt-3 text-sm font-semibold text-rose">
                @if (($planSlug ?? '') === 'vps')
                    {{ __('hosting.billed_monthly_vps') }}
                @else
                    {{ __('hosting.billed_monthly_shared') }}
                @endif
            </p>
            <p class="mt-2 text-xs font-semibold uppercase tracking-widest text-on-blush/55">
                Live rate · 1 USD ≈ ₦{{ number_format($usdToNgn, 0) }}
            </p>
        </div>

        <form action="{{ route('hosting.intake') }}" method="GET" data-hosting-spec-form>
            <input type="hidden" name="plan" value="{{ $planSlug }}">

            <div class="mb-6 rounded-3xl border border-border bg-white p-5 sm:p-6">
                <p class="text-sm font-semibold text-black">{{ __('hosting.billing_period') }}</p>
                <p class="mt-1 text-sm text-on-blush/70">{{ __('hosting.billing_period_help') }}</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($billingCycles as $cycleKey => $cycle)
                        <label class="cursor-pointer rounded-2xl border border-border p-4 transition hover:border-rose/40 has-[:checked]:border-rose has-[:checked]:bg-blush-soft has-[:checked]:ring-2 has-[:checked]:ring-rose/20">
                            <input
                                type="radio"
                                name="billing_cycle"
                                value="{{ $cycleKey }}"
                                class="sr-only"
                                @checked($selectedBillingCycle === $cycleKey)
                                data-billing-cycle
                            >
                            <p class="text-sm font-bold text-black">{{ __('hosting.cycles.' . $cycleKey) }}</p>
                            @if (($cycle['discount_percent'] ?? 0) > 0)
                                <p class="mt-1 text-xs font-semibold text-rose">{{ __('hosting.save_percent', ['percent' => $cycle['discount_percent']]) }}</p>
                            @else
                                <p class="mt-1 text-xs font-semibold text-on-blush/55">Standard rate</p>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-7" data-spec-grid>
                @foreach ($specifications as $spec)
                    @php
                        $specKey = $spec['key'];
                        $isSelected = in_array(strtolower((string) $specKey), $selectedSpecKeys ?? [], true);
                        $specHighlights = $spec['highlights'] ?? [];
                        $specDetails = $spec['details'] ?? [];
                        $metricKeys = ['storage', 'bandwidth', 'cpu', 'ram', 'websites'];
                    @endphp

                    <article
                        @class([
                            'flex flex-col overflow-hidden rounded-3xl border bg-white transition duration-200',
                            'border-rose shadow-[0_16px_40px_rgba(224,69,69,0.22)] ring-2 ring-rose/30' => $isSelected,
                            'border-border hover:border-rose/30 hover:shadow-[0_12px_32px_rgba(224,69,69,0.08)]' => ! $isSelected,
                        ])
                        data-spec-card
                        data-selected="{{ $isSelected ? 'true' : 'false' }}"
                        data-monthly-usd="{{ $spec['price_amount'] ?? 0 }}"
                    >
                        <label class="relative flex flex-1 cursor-pointer flex-col p-6 sm:p-7">
                            <input
                                type="checkbox"
                                name="spec[]"
                                value="{{ $specKey }}"
                                class="sr-only"
                                @checked($isSelected)
                                data-spec-input
                            >

                            <span
                                class="absolute right-6 top-6 size-5 shrink-0 items-center justify-center rounded-full border border-rose bg-rose text-white sm:right-7 sm:top-7"
                                data-spec-check
                                aria-hidden="true"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-3">
                                    <path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>

                            <div class="flex flex-1 flex-col" data-spec-body>
                                <div class="mb-5">
                                    <p class="text-xl font-bold text-black sm:text-2xl">{{ $spec['label'] }}</p>
                                    @if (! empty($spec['price_display']))
                                        <p class="mt-2 text-2xl font-bold text-rose" data-spec-price>{{ $spec['price_display'] }}</p>
                                        <p class="mt-1 text-sm font-semibold text-on-blush/70" data-spec-period>
                                            {{ __('hosting.period_total') }}: {{ $spec['period_display'] ?? $spec['price_display'] }}
                                        </p>
                                    @else
                                        <p class="mt-2 text-sm font-semibold text-on-blush/50">{{ __('hosting.price_on_request') }}</p>
                                    @endif
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-on-blush/60" data-spec-cycle-label>
                                        {{ $spec['billing_cycle_label'] ?? __('hosting.cycles.monthly') }}
                                    </p>
                                    <p class="mt-2 text-sm leading-relaxed text-on-blush/70 sm:text-base">{{ $spec['description'] }}</p>
                                </div>

                                <ul class="mb-4 flex flex-wrap gap-2">
                                    @foreach ($metricKeys as $metricKey)
                                        @if (! empty($spec[$metricKey]))
                                            <li class="rounded-full border border-border bg-white px-3 py-1 text-xs font-semibold text-on-blush/70">
                                                {{ strtoupper($metricKey) }}: {{ $spec[$metricKey] }}
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>

                                @if (! empty($specHighlights))
                                    <ul class="check-list flex flex-col gap-2 text-sm">
                                        @foreach ($specHighlights as $highlight)
                                            <li>{{ $highlight }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <p class="mt-4 text-xs font-semibold uppercase tracking-widest text-rose" data-spec-selected-label>
                                {{ __('hosting.selected') }}
                            </p>
                        </label>

                        @if (! empty($specDetails))
                            <div class="border-t border-border" data-spec-accordion data-open="false">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 px-6 py-4 text-left text-sm font-semibold text-on-blush transition hover:text-rose sm:px-7"
                                    data-spec-accordion-trigger
                                    aria-expanded="false"
                                >
                                    <span data-spec-accordion-label>{{ __('hosting.read_more') }}</span>
                                    <x-ui.icons.chevron-down class="size-4 shrink-0 text-on-blush/40" data-spec-accordion-chevron />
                                </button>

                                <div data-spec-accordion-panel>
                                    <div class="space-y-4 border-t border-border/70 px-6 pb-6 pt-4 sm:px-7" data-spec-accordion-inner>
                                        @if (! empty($specDetails['best_for']))
                                            <div>
                                                <p class="mb-1 text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('hosting.best_for') }}</p>
                                                <p class="text-sm leading-relaxed text-on-blush/80">{{ $specDetails['best_for'] }}</p>
                                            </div>
                                        @endif

                                        @if (! empty($specDetails['includes']))
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('hosting.whats_included') }}</p>
                                                <ul class="check-list flex flex-col gap-2 text-sm">
                                                    @foreach ($specDetails['includes'] as $item)
                                                        <li>{{ $item }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="mt-8 rounded-3xl border border-border bg-white p-5 sm:p-6">
                <p class="mb-4 text-sm text-on-blush/70">{{ __('hosting.select_prompt') }}</p>
                <button
                    type="submit"
                    data-spec-submit
                    disabled
                    aria-disabled="true"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-rose px-6 py-4 text-base font-bold text-white shadow-[0_10px_24px_rgba(224,69,69,0.35)] transition hover:bg-[#cf3a3a] disabled:pointer-events-none disabled:cursor-not-allowed disabled:bg-on-blush/30 disabled:text-white/80 disabled:shadow-none disabled:opacity-100"
                >
                    {{ __('hosting.continue_billing') }}
                </button>
            </div>
        </form>
    </x-layout.page-content>

    @php
        $cyclesForJs = collect($billingCycles)->mapWithKeys(function ($cycle, $key) {
            return [
                $key => [
                    'months' => $cycle['months'],
                    'discount_percent' => $cycle['discount_percent'],
                    'label' => __('hosting.cycles.' . $key),
                ],
            ];
        })->all();
    @endphp

    <script>
        const specForm = document.querySelector('[data-hosting-spec-form]');

        if (specForm) {
            const submitButton = specForm.querySelector('[data-spec-submit]');
            const cards = specForm.querySelectorAll('[data-spec-card]');
            const inputs = specForm.querySelectorAll('[data-spec-input]');
            const cycleInputs = specForm.querySelectorAll('[data-billing-cycle]');
            const usdToNgn = {{ (float) $usdToNgn }};
            const cycles = @json($cyclesForJs);
            const periodTotalLabel = @json(__('hosting.period_total'));
            const showLessLabel = @json(__('hosting.show_less'));
            const readMoreLabel = @json(__('hosting.read_more'));

            const money = (amount, currency) => {
                if (currency === 'USD') {
                    return '$' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: amount >= 100 ? 0 : 2, maximumFractionDigits: 2 });
                }
                return '₦' + Math.round(amount).toLocaleString();
            };

            const dual = (usd) => money(usd, 'USD') + ' / ' + money(usd * usdToNgn, 'NGN');

            const selectedCycle = () => {
                const checked = Array.from(cycleInputs).find((input) => input.checked);
                return checked?.value || 'monthly';
            };

            const selectedSpecCount = () => Array.from(inputs).filter((input) => input.checked).length;

            const setSubmitEnabled = (enabled) => {
                if (!submitButton) return;
                submitButton.disabled = !enabled;
                submitButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
            };

            const refreshCyclePrices = () => {
                const cycleKey = selectedCycle();
                const cycle = cycles[cycleKey] || cycles.monthly;

                cards.forEach((card) => {
                    const monthly = Number(card.dataset.monthlyUsd || 0);
                    if (!monthly) return;

                    const subtotal = monthly * Number(cycle.months || 1);
                    const total = subtotal * (1 - (Number(cycle.discount_percent || 0) / 100));
                    const priceEl = card.querySelector('[data-spec-price]');
                    const periodEl = card.querySelector('[data-spec-period]');
                    const labelEl = card.querySelector('[data-spec-cycle-label]');

                    if (priceEl) priceEl.textContent = dual(monthly) + ' /mo';
                    if (periodEl) periodEl.textContent = periodTotalLabel + ': ' + dual(total);
                    if (labelEl) labelEl.textContent = cycle.label;
                });
            };

            const syncCardState = (card, isSelected) => {
                card.dataset.selected = isSelected ? 'true' : 'false';
                card.classList.toggle('border-rose', isSelected);
                card.classList.toggle('shadow-[0_16px_40px_rgba(224,69,69,0.22)]', isSelected);
                card.classList.toggle('ring-2', isSelected);
                card.classList.toggle('ring-rose/30', isSelected);
                card.classList.toggle('border-border', !isSelected);
                card.classList.toggle('hover:border-rose/30', !isSelected);
                card.classList.toggle('hover:shadow-[0_12px_32px_rgba(224,69,69,0.08)]', !isSelected);
            };

            const syncSelectionUi = () => {
                const selectedCount = selectedSpecCount();

                cards.forEach((card) => {
                    const input = card.querySelector('[data-spec-input]');
                    const isSelected = Boolean(input?.checked);
                    syncCardState(card, isSelected);
                    card.classList.toggle('opacity-55', selectedCount > 0 && !isSelected);
                    card.classList.toggle('scale-[0.985]', selectedCount > 0 && !isSelected);
                });

                setSubmitEnabled(selectedCount > 0);
            };

            inputs.forEach((input) => input.addEventListener('change', syncSelectionUi));
            cycleInputs.forEach((input) => input.addEventListener('change', refreshCyclePrices));

            specForm.addEventListener('submit', (event) => {
                if (selectedSpecCount() === 0) {
                    event.preventDefault();
                    setSubmitEnabled(false);
                }
            });

            const setAccordionOpen = (accordion, open) => {
                accordion.setAttribute('data-open', open ? 'true' : 'false');
                const trigger = accordion.querySelector('[data-spec-accordion-trigger]');
                const label = accordion.querySelector('[data-spec-accordion-label]');
                if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (label) label.textContent = open ? showLessLabel : readMoreLabel;
            };

            specForm.querySelectorAll('[data-spec-accordion]').forEach((accordion) => {
                setAccordionOpen(accordion, false);
                const trigger = accordion.querySelector('[data-spec-accordion-trigger]');
                if (!trigger) return;

                trigger.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    const willOpen = accordion.getAttribute('data-open') !== 'true';
                    if (willOpen) {
                        specForm.querySelectorAll('[data-spec-accordion]').forEach((other) => {
                            if (other !== accordion) setAccordionOpen(other, false);
                        });
                    }
                    setAccordionOpen(accordion, willOpen);
                });
            });

            // Force clean initial state: no selection = inactive CTA.
            inputs.forEach((input) => {
                if (!input.checked) return;
            });
            setSubmitEnabled(selectedSpecCount() > 0);
            refreshCyclePrices();
            syncSelectionUi();
        }
    </script>
@endsection
