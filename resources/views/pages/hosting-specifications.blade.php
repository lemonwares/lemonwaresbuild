@extends('layouts.app')

@section('title', __('hosting.choose_spec_title') . ' — ' . config('site.short_name'))
@section('meta_description', 'Choose your hosting specification before completing billing details.')

@php
    $planName = (string) ($plan['name'] ?? $planSlug);
    $planTitle = (string) ($plan['title'] ?? $planName);
    $planSummary = (string) ($plan['summary'] ?? '');
    $isVps = ($planSlug ?? '') === 'vps';
@endphp

@section('content')
    <style>
        [data-spec-accordion] [data-spec-accordion-panel] {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 400ms ease;
        }

        [data-spec-accordion][data-open="true"] [data-spec-accordion-panel] {
            grid-template-rows: 1fr;
        }

        [data-spec-accordion] [data-spec-accordion-inner] {
            overflow: hidden;
            min-height: 0;
        }

        [data-spec-accordion] [data-spec-accordion-chevron] {
            transition: transform 400ms ease;
        }

        [data-spec-accordion][data-open="true"] [data-spec-accordion-chevron] {
            transform: rotate(180deg);
        }

        [data-spec-check] { display: none; }
        [data-spec-card][data-selected="true"] [data-spec-check] { display: inline-flex; }
        [data-spec-selected-label] { display: none; }
        [data-spec-card][data-selected="true"] [data-spec-selected-label] { display: block; }
    </style>

    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-14 sm:py-18">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                    {{ __('hosting.step_1') }} · {{ $planName }}
                </p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ __('hosting.choose_spec_title') }}
                </h1>
                <p class="mt-4 max-w-2xl text-lg font-light text-white/90">
                    {{ __('hosting.choose_spec_lede') }}
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#hosting-specs" class="btn bg-white text-rose hover:bg-blush">
                        <x-ui.icons.arrow-down class="size-4" />
                        <span>{{ __('hosting.view_plans') }}</span>
                    </a>
                    <a
                        href="{{ $isVps ? route('vps') : (($planSlug ?? '') === 'plesk' ? route('plesk') : route('cloud-hosting')) }}"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white"
                    >
                        <x-ui.icons.arrow-left class="size-4" />
                        <span>
                            @if ($isVps)
                                {{ __('pages.vps.meta_title') }}
                            @elseif (($planSlug ?? '') === 'plesk')
                                {{ __('pages.plesk.meta_title') }}
                            @else
                                {{ __('pages.cloud_hosting.meta_title') }}
                            @endif
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-border bg-white">
        <div class="container-page py-10 sm:py-12">
            <p class="section-label mb-2">{{ $planName }}</p>
            <h2 class="text-2xl font-bold text-black sm:text-3xl">{{ $planTitle }}</h2>
            <p class="mt-2 max-w-3xl body-text">{{ $planSummary }}</p>
            <p class="mt-3 text-sm font-semibold text-rose">
                @if ($isVps)
                    {{ __('hosting.billed_monthly_vps') }}
                @else
                    {{ __('hosting.billed_monthly_shared') }}
                @endif
            </p>
        </div>
    </section>

    <section id="hosting-specs" class="section-band scroll-mt-28">
        <div class="container-page py-14 sm:py-16">
            @if (session('hosting_feedback'))
                <p @class([
                    'mb-6 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => (session('hosting_feedback.type') === 'success'),
                    'border border-sky-200 bg-sky-50 text-sky-800' => (session('hosting_feedback.type') === 'info'),
                    'border border-rose/20 bg-rose/5 text-rose' => (session('hosting_feedback.type') === 'error'),
                ])>{{ session('hosting_feedback.message') }}</p>
            @endif

            <form action="{{ route('hosting.intake') }}" method="GET" data-hosting-spec-form>
                @csrf
                <input type="hidden" name="plan" value="{{ $planSlug }}">
                @if (filled(request('domain')))
                    <input type="hidden" name="domain" value="{{ request('domain') }}">
                @endif
                @if (filled(request('domain_option')))
                    <input type="hidden" name="domain_option" value="{{ request('domain_option') }}">
                @endif

                <div class="mb-8 rounded-3xl border border-border bg-white p-5 sm:p-6">
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
                                    <p class="mt-1 text-xs font-semibold text-on-blush/55">{{ __('hosting.cycles.monthly') }}</p>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="hosting-plan-grid" data-spec-grid>
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
                                'hosting-plan-card relative transition duration-200',
                                'border-rose shadow-[0_16px_40px_rgba(224,69,69,0.22)] ring-2 ring-rose/30' => $isSelected,
                            ])
                            data-spec-card
                            data-selected="{{ $isSelected ? 'true' : 'false' }}"
                            data-monthly-ngn="{{ $spec['price_amount'] ?? 0 }}"
                        >
                            <label class="relative flex flex-1 cursor-pointer flex-col">
                                <input
                                    type="checkbox"
                                    name="spec[]"
                                    value="{{ $specKey }}"
                                    class="sr-only"
                                    @checked($isSelected)
                                    data-spec-input
                                >

                                <span
                                    class="absolute right-0 top-0 size-5 shrink-0 items-center justify-center rounded-full border border-rose bg-rose text-white"
                                    data-spec-check
                                    aria-hidden="true"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-3">
                                        <path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>

                                <div class="flex flex-1 flex-col" data-spec-body>
                                    <h3 class="text-lg font-bold text-black sm:text-xl">{{ $spec['label'] }}</h3>
                                    @if (! empty($spec['price_display']))
                                        <p class="mt-3 text-2xl font-bold text-rose" data-spec-price>{{ $spec['price_display'] }}</p>
                                        <p class="mt-1 text-sm font-semibold text-on-blush/70" data-spec-period>
                                            {{ __('hosting.period_total') }}: {{ $spec['period_display'] ?? $spec['price_display'] }}
                                        </p>
                                    @else
                                        <p class="mt-3 text-sm font-semibold text-on-blush/50">{{ __('hosting.price_on_request') }}</p>
                                    @endif
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-widest text-on-blush/55" data-spec-cycle-label>
                                        {{ $spec['billing_cycle_label'] ?? __('hosting.cycles.monthly') }}
                                    </p>
                                    <p class="mt-3 text-sm leading-relaxed text-on-blush/70">{{ $spec['description'] }}</p>

                                    <dl class="hosting-plan-specs">
                                        @foreach ($metricKeys as $metricKey)
                                            @if (! empty($spec[$metricKey]))
                                                <div>
                                                    <dt>{{ strtoupper($metricKey) }}</dt>
                                                    <dd>{{ $spec[$metricKey] }}</dd>
                                                </div>
                                            @endif
                                        @endforeach
                                    </dl>

                                    @if (! empty($specHighlights))
                                        <ul class="mt-4 space-y-1.5 text-sm text-on-blush/75">
                                            @foreach ($specHighlights as $highlight)
                                                <li class="flex gap-2">
                                                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-rose" aria-hidden="true"></span>
                                                    <span>{{ $highlight }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <p class="mt-4 text-xs font-semibold uppercase tracking-widest text-rose" data-spec-selected-label>
                                    {{ __('hosting.selected') }}
                                </p>
                            </label>

                            @if (! empty($specDetails))
                                <div class="mt-4 border-t border-border pt-2" data-spec-accordion data-open="false">
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 py-2 text-left text-sm font-semibold text-on-blush transition hover:text-rose"
                                        data-spec-accordion-trigger
                                        aria-expanded="false"
                                    >
                                        <span data-spec-accordion-label>{{ __('hosting.read_more') }}</span>
                                        <x-ui.icons.chevron-down class="size-4 shrink-0 text-on-blush/40" data-spec-accordion-chevron />
                                    </button>

                                    <div data-spec-accordion-panel>
                                        <div class="space-y-4 pb-2 pt-2" data-spec-accordion-inner>
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

                <div class="mt-10 rounded-3xl border border-border bg-white p-5 sm:p-6">
                    <p class="mb-4 text-sm text-on-blush/70">{{ __('hosting.select_prompt') }}</p>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                        <button
                            type="submit"
                            formaction="{{ route('cart.hosting.add') }}"
                            formmethod="post"
                            data-spec-submit
                            data-action-loading
                            data-loading-label="{{ __('account.processing') }}"
                            disabled
                            aria-disabled="true"
                            class="btn btn-ghost w-full shrink justify-center border-rose/30 text-rose hover:bg-blush-soft disabled:pointer-events-none disabled:opacity-40 sm:min-w-0 sm:flex-1"
                        >
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-rose/30 border-t-rose" data-action-spinner aria-hidden="true"></span>
                            <span data-action-label>{{ __('domain.add_to_cart') }}</span>
                            <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                        </button>
                        <button
                            type="submit"
                            data-spec-submit
                            data-action-loading
                            data-loading-label="{{ __('account.processing') }}"
                            disabled
                            aria-disabled="true"
                            class="btn btn-primary w-full shrink justify-center disabled:pointer-events-none disabled:opacity-40 sm:min-w-0 sm:flex-1"
                        >
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-action-spinner aria-hidden="true"></span>
                            <span data-action-label>{{ __('hosting.continue_billing') }}</span>
                            <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </section>

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
            const submitButtons = specForm.querySelectorAll('[data-spec-submit]');
            const cards = specForm.querySelectorAll('[data-spec-card]');
            const inputs = specForm.querySelectorAll('[data-spec-input]');
            const cycleInputs = specForm.querySelectorAll('[data-billing-cycle]');
            const cycles = @json($cyclesForJs);
            const periodTotalLabel = @json(__('hosting.period_total'));
            const showLessLabel = @json(__('hosting.show_less'));
            const readMoreLabel = @json(__('hosting.read_more'));
            const monthlySuffix = @json(\App\Support\HostingPricing::monthlySuffix());

            const moneyNgn = (amount) => '₦' + Math.round(Number(amount) || 0).toLocaleString();

            const selectedCycle = () => {
                const checked = Array.from(cycleInputs).find((input) => input.checked);
                return checked?.value || 'monthly';
            };

            const selectedSpecCount = () => Array.from(inputs).filter((input) => input.checked).length;

            const setSubmitEnabled = (enabled) => {
                submitButtons.forEach((submitButton) => {
                    submitButton.disabled = !enabled;
                    submitButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
                });
            };

            const refreshCyclePrices = () => {
                const cycleKey = selectedCycle();
                const cycle = cycles[cycleKey] || cycles.monthly;

                cards.forEach((card) => {
                    const monthly = Number(card.dataset.monthlyNgn || 0);
                    if (!monthly) return;

                    const subtotal = monthly * Number(cycle.months || 1);
                    const total = subtotal * (1 - (Number(cycle.discount_percent || 0) / 100));
                    const priceEl = card.querySelector('[data-spec-price]');
                    const periodEl = card.querySelector('[data-spec-period]');
                    const labelEl = card.querySelector('[data-spec-cycle-label]');

                    if (priceEl) priceEl.textContent = moneyNgn(monthly) + ' ' + monthlySuffix;
                    if (periodEl) periodEl.textContent = periodTotalLabel + ': ' + moneyNgn(total);
                    if (labelEl) labelEl.textContent = cycle.label;
                });
            };

            const syncCardState = (card, isSelected) => {
                card.dataset.selected = isSelected ? 'true' : 'false';
                card.classList.toggle('border-rose', isSelected);
                card.classList.toggle('shadow-[0_16px_40px_rgba(224,69,69,0.22)]', isSelected);
                card.classList.toggle('ring-2', isSelected);
                card.classList.toggle('ring-rose/30', isSelected);
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

            setSubmitEnabled(selectedSpecCount() > 0);
            refreshCyclePrices();
            syncSelectionUi();
        }
    </script>
@endsection
