@extends('layouts.app')

@section('title', __('email.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('email.meta_description'))
@section('meta_image', asset('images/brands/mailemon-logo.png'))

@php
    $steps = __('email.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
    $apps = __('email.apps');
    if (! is_array($apps)) {
        $apps = [];
    }
    $setupItems = __('email.setup_items');
    if (! is_array($setupItems)) {
        $setupItems = [];
    }
    $platformItems = __('email.platform_items');
    if (! is_array($platformItems)) {
        $platformItems = [];
    }
    $validationPoints = __('email.validation_points');
    if (! is_array($validationPoints)) {
        $validationPoints = [];
    }
    $authItems = __('email.auth_items');
    if (! is_array($authItems)) {
        $authItems = [];
    }
    $audienceItems = __('email.audience_items');
    if (! is_array($audienceItems)) {
        $audienceItems = [];
    }
    $features = __('email.features');
    if (! is_array($features)) {
        $features = [];
    }
    $highlights = __('email.highlights');
    if (! is_array($highlights)) {
        $highlights = [];
    }
    $faqItems = __('email.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }
    $mailemonPlans = collect($plans ?? [])
        ->filter(fn ($plan) => ($plan['provider'] ?? 'lemonmail') === 'lemonmail')
        ->values()
        ->all();
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('email.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('email.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('email.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#email-plans" class="btn bg-white text-rose hover:bg-blush">
                            <x-ui.icons.arrow-down class="size-4" />
                            <span>{{ __('email.cta') }}</span>
                        </a>
                        <a href="{{ route('microsoft-365') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <span>{{ __('email.providers.ms365') }}</span>
                            <x-ui.icons.arrow-up-right class="size-3.5" />
                        </a>
                    </div>
                </div>
                <div class="dev-cutout-hero hidden lg:flex" aria-hidden="true">
                    <div class="flex w-full max-w-md items-center justify-center rounded-[2rem] bg-white px-8 py-10 shadow-[0_24px_60px_rgba(0,0,0,0.18)]">
                        <img
                            src="{{ asset('images/brands/mailemon-logo.png') }}"
                            alt="Mailemon"
                            width="420"
                            height="60"
                            class="h-auto w-full max-w-[18rem]"
                            loading="eager"
                            decoding="async"
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <x-ui.flash />
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('email.intro_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.intro_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('email.intro_lede') }}</p>
            </div>
            <ol class="hosting-steps-grid" data-reveal-stagger>
                @foreach ($steps as $index => $step)
                    <li class="hosting-step-card">
                        <span class="hosting-step-num">{{ $index + 1 }}</span>
                        <h3 class="mt-4 text-lg font-bold text-black">{{ $step['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $step['body'] ?? '' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="email-apps" class="scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('email.apps_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.apps_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('email.apps_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($apps as $app)
                    <article class="hosting-feature-card">
                        <span class="dev-icon-badge mb-4" aria-hidden="true">
                            <x-ui.icons.mail class="size-5 text-rose" />
                        </span>
                        <h3 class="text-base font-bold text-black">{{ $app['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $app['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="email-plans" class="border-t border-border bg-white scroll-mt-28">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.plans_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.plans_title') }}</h2>
                <p class="lede mt-3">{{ __('email.plans_lede') }}</p>
            </div>

            <div
                class="mx-auto mb-10 max-w-3xl"
                data-email-plans
                data-selected-cycle="{{ $selectedCycle }}"
                data-checkout-base="{{ $checkoutBaseUrl }}"
                data-plans-url="{{ route('email.plans') }}"
            >
                <p class="text-center text-sm font-semibold text-black">{{ __('email.choose_period') }}</p>
                <div
                    class="mt-4 flex w-full gap-2 overflow-x-auto rounded-2xl border border-border bg-white p-2"
                    role="tablist"
                    aria-label="{{ __('email.choose_period') }}"
                >
                    @foreach ($billingCycleOptions as $option)
                        <button
                            type="button"
                            role="tab"
                            data-email-cycle-tab
                            data-cycle="{{ $option['key'] }}"
                            data-discount="{{ $option['discount_percent'] }}"
                            aria-selected="{{ $option['key'] === $selectedCycle ? 'true' : 'false' }}"
                            @class([
                                'flex min-w-[9.5rem] flex-1 flex-col items-center rounded-xl px-4 py-3 text-center transition',
                                'bg-rose text-white shadow-[0_8px_20px_rgba(224,69,69,0.25)]' => $option['key'] === $selectedCycle,
                                'text-black hover:bg-blush-soft' => $option['key'] !== $selectedCycle,
                            ])
                        >
                            <span class="text-sm font-bold">{{ $option['label'] }}</span>
                            @if ($option['discount_percent'] > 0)
                                <span
                                    data-email-cycle-badge
                                    @class([
                                        'mt-1 text-[0.65rem] font-semibold uppercase tracking-widest',
                                        'text-white/80' => $option['key'] === $selectedCycle,
                                        'text-rose' => $option['key'] !== $selectedCycle,
                                    ])
                                >
                                    {{ __('hosting.save_percent', ['percent' => $option['discount_percent']]) }}
                                </span>
                            @else
                                <span
                                    data-email-cycle-badge
                                    @class([
                                        'mt-1 text-[0.65rem] font-semibold uppercase tracking-widest',
                                        'text-white/70' => $option['key'] === $selectedCycle,
                                        'text-on-blush/55' => $option['key'] !== $selectedCycle,
                                    ])
                                >{{ __('email.standard_rate') }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="hosting-plan-grid" data-email-plans-grid>
                @foreach ($mailemonPlans as $index => $plan)
                    <article
                        @class([
                            'hosting-plan-card',
                            'hosting-plan-card-featured' => $plan['featured'],
                        ])
                        data-email-plan-card
                        data-plan-key="{{ $plan['key'] }}"
                        data-pricing='@json($plan['pricing_by_cycle'])'
                    >
                        @if ($plan['featured'])
                            <p class="hosting-panel-badge mb-3">{{ __('email.most_popular') }}</p>
                        @endif
                        <p @class([
                            'text-xs font-semibold uppercase tracking-widest',
                            'text-white/75' => $plan['featured'],
                            'text-rose' => ! $plan['featured'],
                        ])>
                            {{ $plan['provider_label'] }}
                        </p>
                        <h3 @class(['mt-2 text-lg font-bold', 'text-white' => $plan['featured'], 'text-black' => ! $plan['featured']])>
                            {{ $plan['name'] }}
                        </h3>
                        <p @class(['mt-1 text-sm', 'text-white/80' => $plan['featured'], 'text-on-blush/70' => ! $plan['featured']])>
                            {{ $plan['summary'] }}
                        </p>
                        <p @class([
                            'mt-3 inline-flex w-fit rounded-full px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-widest',
                            'bg-white/15 text-white' => $plan['featured'],
                            'bg-blush-soft text-rose' => ! $plan['featured'],
                        ])>
                            {{ trans_choice('email.mailboxes', $plan['mailboxes'], ['count' => $plan['mailboxes']]) }}
                        </p>

                        <div @class(['mt-5 border-t pt-4', 'border-white/20' => $plan['featured'], 'border-border' => ! $plan['featured']])>
                            <p @class(['text-2xl font-bold tracking-tight', 'text-white' => $plan['featured'], 'text-black' => ! $plan['featured']]) data-email-period-price>
                                {{ $plan['period_display'] }}
                            </p>
                            <p @class(['mt-1 text-sm', 'text-white/75' => $plan['featured'], 'text-on-blush/60' => ! $plan['featured']]) data-email-cycle-meta>
                                {{ $plan['pricing_by_cycle'][$selectedCycle]['cycle_meta'] ?? $plan['billing_cycle_label'] }}
                            </p>
                            <p @class(['mt-2 text-sm font-medium', 'text-white/90' => $plan['featured'], 'text-on-blush/70' => ! $plan['featured']]) data-email-per-mailbox>
                                {{ $plan['pricing_by_cycle'][$selectedCycle]['per_mailbox_line'] ?? __('email.per_mailbox_price', ['price' => $plan['per_mailbox_display']]) }}
                            </p>
                        </div>

                        <ul @class(['mt-4 space-y-1.5 text-sm', 'text-white/80' => $plan['featured'], 'text-on-blush/75' => ! $plan['featured']])>
                            @foreach ([__('email.outlook_apps'), __('email.dns_included'), __('email.webmail_included'), __('email.support_included')] as $item)
                                <li class="flex gap-2">
                                    <span @class(['mt-1.5 size-1.5 shrink-0 rounded-full', 'bg-white' => $plan['featured'], 'bg-rose' => ! $plan['featured']]) aria-hidden="true"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <form
                            method="POST"
                            action="{{ route('cart.email.add') }}"
                            class="mt-6"
                            data-email-plan-cta-form
                        >
                            @csrf
                            <input type="hidden" name="plan" value="{{ $plan['key'] }}">
                            <input type="hidden" name="billing_cycle" value="{{ $selectedCycle }}" data-email-plan-cycle-input>
                            <button
                                type="submit"
                                data-email-plan-cta
                                data-action-loading
                                data-loading-label="{{ __('account.processing') }}"
                                @class([
                                    'btn w-full shrink justify-center',
                                    'bg-white text-rose hover:bg-blush' => $plan['featured'],
                                    'btn-primary' => ! $plan['featured'],
                                ])
                            >
                                <span @class([
                                    'hidden size-4 animate-spin rounded-full border-2',
                                    'border-rose/30 border-t-rose' => $plan['featured'],
                                    'border-white/35 border-t-white' => ! $plan['featured'],
                                ]) data-action-spinner aria-hidden="true"></span>
                                <span data-action-label>{{ __('domain.add_to_cart') }}</span>
                                <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.setup_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.setup_title') }}</h2>
                <p class="lede mt-3">{{ __('email.setup_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($setupItems as $item)
                    <article class="hosting-feature-card">
                        <h3 class="text-base font-bold text-black">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $item['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.platform_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.platform_title') }}</h2>
                <p class="lede mt-3">{{ __('email.platform_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($platformItems as $item)
                    <article class="hosting-feature-card">
                        <span class="dev-icon-badge mb-4" aria-hidden="true">
                            <x-ui.icons.mail class="size-5 text-rose" />
                        </span>
                        <h3 class="text-base font-bold text-black">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $item['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
                <div>
                    <p class="section-label mb-3">{{ __('email.validation_eyebrow') }}</p>
                    <h2 class="heading">{{ __('email.validation_title') }}</h2>
                    <p class="lede mt-3">{{ __('email.validation_lede') }}</p>
                </div>
                <ul class="check-list grid gap-3 sm:grid-cols-2">
                    @foreach ($validationPoints as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.auth_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.auth_title') }}</h2>
                <p class="lede mt-3">{{ __('email.auth_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($authItems as $item)
                    <article class="hosting-feature-card">
                        <p class="inline-flex rounded-full bg-blush-soft px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-widest text-rose">
                            {{ $item['code'] ?? '' }}
                        </p>
                        <h3 class="mt-3 text-base font-bold text-black">{{ $item['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $item['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.features_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.features_title') }}</h2>
                <p class="lede mt-3">{{ __('email.features_lede') }}</p>
            </div>
            <div class="hosting-feature-grid">
                @foreach ($features as $feature)
                    <article class="hosting-feature-card">
                        <h3 class="text-base font-bold text-black">{{ $feature['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $feature['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
            <ul class="check-list mt-10 grid gap-3 md:grid-cols-2">
                @foreach ($highlights as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.audience_eyebrow') }}</p>
                <h2 class="heading">{{ __('email.audience_title') }}</h2>
                <p class="lede mt-3">{{ __('email.audience_lede') }}</p>
            </div>
            <ul class="check-list grid gap-3 sm:grid-cols-2">
                @foreach ($audienceItems as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section id="email-suites" class="border-t border-border bg-white scroll-mt-28" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('email.enterprise_title') }}</p>
                <h2 class="heading">{{ __('email.enterprise_heading') }}</h2>
                <p class="lede mt-3">{{ __('email.enterprise_lede') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                @foreach ($enterpriseProducts as $product)
                    <article class="hosting-panel-card">
                        @if (($product['key'] ?? '') === 'microsoft_365')
                            <img src="{{ asset('images/brands/microsoft-365.svg') }}" alt="" width="40" height="40" class="size-10">
                        @elseif (($product['key'] ?? '') === 'google_workspace')
                            <img src="{{ asset('images/brands/google-workspace.svg') }}" alt="" width="40" height="40" class="size-10">
                        @endif
                        <h3 class="mt-4 text-xl font-bold text-on-blush">{{ $product['name'] }}</h3>
                        <p class="mt-3 flex-1 body-text">{{ $product['summary'] }}</p>
                        <a href="{{ $product['href'] }}" class="btn btn-ghost mt-8 w-fit border-rose/30 text-rose hover:bg-blush-soft">
                            {{ __('email.enterprise_cta') }}
                            <x-ui.icons.arrow-up-right class="size-3.5" />
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if (count($faqItems) > 0)
        <section class="border-t border-border bg-white">
            <div class="container-page py-16 sm:py-20">
                <div class="mx-auto max-w-3xl">
                    <h2 class="heading mb-8 text-center">{{ __('email.faq_title') }}</h2>
                    <x-ui.accordion>
                        @foreach ($faqItems as $index => $item)
                            <x-ui.accordion-item :title="$item['question'] ?? ''" :default-open="$index === 0">
                                {{ $item['answer'] ?? '' }}
                            </x-ui.accordion-item>
                        @endforeach
                    </x-ui.accordion>
                </div>
            </div>
        </section>
    @endif

    <section class="border-t border-border bg-blush-soft/40">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('email.help_title') }}</h2>
            <p class="lede mb-6">{{ __('email.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="#email-plans" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('email.cta') }}</span>
                </a>
                <a href="{{ route('contact') }}" class="btn btn-ghost">
                    <span>{{ __('site.common.contact_us') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
