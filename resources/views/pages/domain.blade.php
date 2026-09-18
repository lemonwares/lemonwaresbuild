@extends('layouts.app')

@section('title', __('pages.domain.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.domain.meta_description'))

@php
    $initialTab = strtolower((string) request()->query('tab', 'register'));
    if (! in_array($initialTab, ['register', 'transfer'], true)) {
        $initialTab = 'register';
    }
    $initialQuery = trim((string) request()->query('q', ''));

    $benefitCards = [
        [
            'variant' => 'trusted',
            'title' => __('pages.domain.benefit_trusted_title'),
            'body' => __('pages.domain.benefit_trusted_body'),
            'cta' => __('pages.domain.benefit_trusted_cta'),
            'href' => '#domain-tool',
        ],
        [
            'variant' => 'price',
            'title' => __('pages.domain.benefit_price_title'),
            'body' => __('pages.domain.benefit_price_body'),
            'cta' => __('pages.domain.benefit_price_cta'),
            'href' => '#domain-tool',
        ],
        [
            'variant' => 'support',
            'title' => __('pages.domain.benefit_support_title'),
            'body' => __('pages.domain.benefit_support_body'),
            'cta' => __('pages.domain.benefit_support_cta'),
            'href' => route('contact'),
        ],
    ];

    $features = [
        [
            'title' => __('pages.domain.feature_search_title'),
            'body' => __('pages.domain.feature_search_body'),
            'cta' => __('pages.domain.feature_search_cta'),
            'href' => '#domain-tool',
            'art' => 'search',
            'flip' => false,
        ],
        [
            'title' => __('pages.domain.feature_transfer_title'),
            'body' => __('pages.domain.feature_transfer_body'),
            'cta' => __('pages.domain.feature_transfer_cta'),
            'href' => '#domain-tool',
            'art' => 'transfer',
            'flip' => true,
        ],
        [
            'title' => __('pages.domain.feature_manage_title'),
            'body' => __('pages.domain.feature_manage_body'),
            'cta' => __('pages.domain.feature_manage_cta'),
            'href' => route('cloud-hosting'),
            'art' => 'manage',
            'flip' => false,
        ],
    ];

    $faqItems = __('pages.domain.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }
@endphp

@section('content')
    @if (session('domain_feedback'))
        <div class="container-page pt-6">
            <p @class([
                'rounded-xl px-4 py-3 text-sm',
                'border border-emerald-200 bg-emerald-50 text-emerald-800' => session('domain_feedback.type') === 'success',
                'border border-sky-200 bg-sky-50 text-sky-800' => session('domain_feedback.type') === 'info',
                'border border-rose/20 bg-rose/5 text-rose' => session('domain_feedback.type') === 'error',
            ])>{{ session('domain_feedback.message') }}</p>
        </div>
    @endif

    <section class="domain-landing-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <x-domain.chip-field class="domain-landing-hero-chip" />

        <div class="container-page relative z-10 py-14 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('pages.domain.hero_title') }}
                </h1>
                <p class="domain-landing-hero-lede mx-auto mt-4 max-w-2xl text-base font-normal sm:text-lg">
                    {{ __('pages.domain.hero_lede') }}
                </p>
            </div>

            <div
                id="domain-tool"
                class="domain-landing-search scroll-mt-28"
                data-domain-search
                data-check-url="{{ route('hosting.domain.check') }}"
                data-suggest-url="{{ route('hosting.domain.suggest') }}"
                data-quote-url="{{ route('hosting.domain.quote') }}"
                data-continue-url="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}"
                data-cart-add-url="{{ route('domain.cart.add') }}"
                data-csrf="{{ csrf_token() }}"
                data-initial-tab="{{ $initialTab }}"
                data-initial-q="{{ $initialQuery }}"
                data-msg-checking="{{ __('pages.domain.checking') }}"
                data-msg-suggestions="{{ __('pages.domain.suggestions_loading') }}"
                data-msg-invalid="{{ __('hosting.domain_invalid') }}"
                data-msg-available-title="{{ __('pages.domain.available_title') }}"
                data-msg-transfer-title="{{ __('pages.domain.transfer_ready_title') }}"
                data-msg-unavailable-title="{{ __('pages.domain.unavailable_title') }}"
                data-msg-unavailable-lede="{{ __('pages.domain.unavailable_lede') }}"
                data-msg-check-failed="{{ __('pages.domain.check_failed') }}"
                data-msg-price="{{ __('pages.domain.price_label') }}"
                data-msg-search-cta="{{ __('pages.domain.search_cta') }}"
                data-msg-transfer-cta="{{ __('pages.domain.transfer_cta') }}"
                data-msg-continue="{{ __('pages.domain.continue') }}"
                data-msg-continue-lede="{{ __('pages.domain.continue_lede') }}"
                data-msg-transfer-continue-lede="{{ __('pages.domain.transfer_continue_lede') }}"
                data-msg-buy-domain="{{ __('domain.buy_now') }}"
                data-msg-buy-transfer="{{ __('domain.buy_now') }}"
                data-msg-add-cart="{{ __('domain.add_to_cart') }}"
                data-msg-added-cart="Added"
                data-msg-suggestion-available="{{ __('hosting.domain_suggestion_available') }}"
                data-msg-suggestion-taken="{{ __('hosting.domain_suggestion_taken') }}"
            >
                <div class="domain-landing-tabs" role="tablist" aria-label="{{ __('pages.domain.hero_title') }}">
                    <button
                        type="button"
                        role="tab"
                        class="domain-landing-tab {{ $initialTab === 'register' ? 'is-active' : '' }}"
                        data-domain-tab="register"
                        aria-selected="{{ $initialTab === 'register' ? 'true' : 'false' }}"
                    >
                        {{ __('pages.domain.tab_register') }}
                    </button>
                    <button
                        type="button"
                        role="tab"
                        class="domain-landing-tab {{ $initialTab === 'transfer' ? 'is-active' : '' }}"
                        data-domain-tab="transfer"
                        aria-selected="{{ $initialTab === 'transfer' ? 'true' : 'false' }}"
                    >
                        {{ __('pages.domain.tab_transfer') }}
                    </button>
                </div>

                <p class="mb-4 text-center text-base text-white" data-domain-tab-help>
                    {{ $initialTab === 'transfer' ? __('pages.domain.transfer_help') : __('pages.domain.register_help') }}
                </p>
                <p class="hidden" data-domain-help-register>{{ __('pages.domain.register_help') }}</p>
                <p class="hidden" data-domain-help-transfer>{{ __('pages.domain.transfer_help') }}</p>

                <form class="domain-landing-field" data-domain-form>
                    <div class="domain-landing-input-wrap">
                        <x-ui.icons.search class="domain-landing-input-icon size-5" aria-hidden="true" />
                        <label class="sr-only" for="domain-search-q">{{ __('site.nav.domain_search_aria') }}</label>
                        <input
                            id="domain-search-q"
                            type="text"
                            name="q"
                            value="{{ $initialQuery }}"
                            placeholder="{{ __('pages.domain.search_placeholder') }}"
                            class="domain-landing-input"
                            autocomplete="off"
                            spellcheck="false"
                            data-domain-input
                        >
                        <span class="domain-search-spinner hidden" data-domain-spinner aria-hidden="true">
                            <span class="size-4 animate-spin rounded-full border-2 border-on-blush/20 border-t-rose"></span>
                        </span>
                    </div>
                    <button type="submit" class="domain-landing-submit" data-domain-submit>
                        <span data-domain-submit-label>{{ $initialTab === 'transfer' ? __('pages.domain.transfer_cta') : __('pages.domain.search_cta') }}</span>
                    </button>
                </form>

                <div class="domain-search-suggestions mt-4 hidden" data-domain-suggestions></div>
                <div class="domain-search-result hidden" data-domain-result aria-live="polite"></div>
            </div>

            <p class="domain-landing-social mt-8 text-center text-base font-medium">
                {{ __('pages.domain.social_proof') }}
            </p>
        </div>
    </section>

    <section class="domain-landing-benefits">
        <div class="container-page">
            <div class="domain-landing-benefit-grid">
                @foreach ($benefitCards as $card)
                    <article class="domain-landing-benefit-card">
                        <x-domain.svg-benefit class="mx-auto mb-4 h-24 w-28 text-on-blush/50" :variant="$card['variant']" />
                        <h2 class="text-xl font-bold text-black">{{ $card['title'] }}</h2>
                        <p class="mt-2 flex-1 text-base leading-relaxed text-on-blush/70">{{ $card['body'] }}</p>
                        <a href="{{ $card['href'] }}" class="domain-landing-benefit-cta">
                            {{ $card['cta'] }}
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="domain-landing-why">
        <div class="container-page">
            <div class="domain-landing-why-card">
                <h2 class="text-center text-3xl font-bold tracking-tight text-black sm:text-4xl">
                    {{ __('pages.domain.why_title') }}
                </h2>
                <p class="mt-6 text-center text-base leading-relaxed text-on-blush/75">
                    {{ __('pages.domain.why_body') }}
                </p>
            </div>
        </div>
    </section>

    <section class="domain-landing-choose">
        <div class="container-page py-16 text-center sm:py-20">
            <h2 class="mx-auto max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-4xl">
                {{ __('pages.domain.choose_title') }}
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-white/90">
                {{ __('pages.domain.choose_lede') }}
            </p>
            <a href="#domain-tool" class="domain-landing-choose-cta">
                {{ __('pages.domain.choose_cta') }}
            </a>
        </div>
    </section>

    <section class="domain-landing-features">
        <div class="container-page space-y-20 sm:space-y-28">
            @foreach ($features as $feature)
                <div
                    @class([
                        'domain-landing-feature',
                        'is-flip' => $feature['flip'],
                    ])
                    data-reveal
                >
                    <div class="domain-landing-feature-copy">
                        <h2 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">
                            {{ $feature['title'] }}
                        </h2>
                        <p class="mt-5 max-w-xl text-base leading-relaxed text-on-blush/80">
                            {{ $feature['body'] }}
                        </p>
                        <a href="{{ $feature['href'] }}" class="domain-landing-feature-link">
                            {{ $feature['cta'] }}
                        </a>
                    </div>
                    <div class="domain-landing-feature-art">
                        @php
                            $artMap = [
                                'search' => 'images/domain/domain-search',
                                'transfer' => 'images/domain/domain-transfer',
                                'manage' => 'images/domain/domain-manage',
                            ];
                            $artBase = $artMap[$feature['art']] ?? 'images/domain/domain-search';
                        @endphp
                        <picture>
                            <source srcset="{{ asset($artBase.'.webp') }}" type="image/webp">
                            <img
                                src="{{ asset($artBase.'.png') }}"
                                alt=""
                                width="480"
                                height="480"
                                loading="lazy"
                                decoding="async"
                                class="domain-landing-feature-img domain-landing-feature-cutout"
                            >
                        </picture>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="domain-landing-faq" id="domain-faq">
        <div class="container-page py-16 sm:py-20">
            <h2 class="mx-auto mb-10 max-w-3xl text-center text-3xl font-bold tracking-tight text-white sm:text-4xl">
                {{ __('pages.domain.faq_title') }}
            </h2>
            <div class="mx-auto max-w-2xl">
                <x-ui.accordion flush class="domain-landing-faq-list">
                    @foreach ($faqItems as $index => $item)
                        <x-ui.accordion-item :title="$item['question']" :default-open="$index === 0">
                            {{ $item['answer'] }}
                        </x-ui.accordion-item>
                    @endforeach
                </x-ui.accordion>
            </div>
            <div class="mt-12 text-center">
                <p class="mb-4 text-base text-white/90">{{ __('pages.domain.help_lede') }}</p>
                <div class="domain-landing-faq-actions">
                    <a href="{{ route('email.plans') }}" class="domain-landing-choose-cta">
                        {{ __('site.nav.email') }}
                    </a>
                    <a href="{{ route('contact') }}" class="domain-landing-faq-ghost">
                        {{ __('site.common.contact_us') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
