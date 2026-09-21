@extends('layouts.app')

@section('title', __('pages.development.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.development.meta_description'))

@php
    $steps = __('pages.development.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
    $services = __('pages.development.services');
    if (! is_array($services)) {
        $services = [];
    }
    $faqItems = __('pages.development.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }

    $contactFor = static function (?string $subject = null): string {
        $url = route('contact', array_filter([
            'subject' => filled($subject) ? $subject : null,
        ]));

        return $url.'#contact-form';
    };

    $stepIcons = ['search', 'rocket', 'wrench'];
    $serviceIcons = [
        'web' => 'monitor',
        'mobile' => 'smartphone',
        'wordpress' => 'layout',
        'microservices' => 'boxes',
        'deployments' => 'cloud-upload',
        'testing' => 'clipboard-check',
        'maintenance' => 'shield',
    ];
    $serviceArt = [
        'web' => 'images/development/development-web.webp',
        'mobile' => 'images/development/development-mobile.webp',
        'wordpress' => 'images/development/development-wordpress.webp',
        'microservices' => 'images/development/development-microservices.webp',
        'deployments' => 'images/development/development-deployments.webp',
        'testing' => 'images/development/development-testing.webp',
        'maintenance' => 'images/development/development-maintenance.webp',
    ];
    $serviceArtPng = [
        'web' => 'images/development/development-web.png',
        'mobile' => 'images/development/development-mobile.png',
        'wordpress' => 'images/development/development-wordpress.png',
        'microservices' => 'images/development/development-microservices.png',
        'deployments' => 'images/development/development-deployments.png',
        'testing' => 'images/development/development-testing.png',
        'maintenance' => 'images/development/development-maintenance.png',
    ];

    $web = $services['web'] ?? [];
    $mobile = $services['mobile'] ?? [];
    $wordpress = $services['wordpress'] ?? [];
    $microservices = $services['microservices'] ?? [];
    $deployments = $services['deployments'] ?? [];
    $testing = $services['testing'] ?? [];
    $maintenance = $services['maintenance'] ?? [];

    $stackLogos = collect(config('site.technologies', []))
        ->filter(fn ($tech) => is_array($tech) && filled($tech['logo'] ?? null))
        ->values()
        ->all();
    $stackColA = [];
    $stackColB = [];
    foreach ($stackLogos as $i => $tech) {
        if ($i % 2 === 0) {
            $stackColA[] = $tech;
        } else {
            $stackColB[] = $tech;
        }
    }

    $featuredBuilds = $featuredBuilds ?? collect();
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.development.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.development.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.development.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ $contactFor(__('pages.development.cta')) }}" class="btn bg-white text-rose hover:bg-blush" data-action-loading data-loading-label="{{ __('account.processing') }}">
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-rose/30 border-t-rose" data-action-spinner aria-hidden="true"></span>
                            <span data-action-label>{{ __('pages.development.cta') }}</span>
                            <span class="hidden" data-action-loading-label>{{ __('account.processing') }}</span>
                        </a>
                        <a href="#dev-services" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <x-ui.icons.arrow-down class="size-4" />
                            {{ __('pages.development.services_title') }}
                        </a>
                    </div>
                </div>
                <div class="dev-cutout-hero hidden lg:flex" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset('images/development/development-hero.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('images/development/development-hero.png') }}"
                            alt=""
                            width="520"
                            height="520"
                            class="dev-cutout-img"
                            loading="eager"
                            decoding="async"
                        >
                    </picture>
                </div>
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="section-label mb-3">{{ __('pages.development.built_eyebrow') }}</p>
                    <h2 class="heading">{{ __('pages.development.built_title') }}</h2>
                    <p class="lede mt-3">{{ __('pages.development.built_lede') }}</p>
                </div>
                <a href="{{ route('case-studies') }}" class="btn btn-primary shrink-0 self-start sm:self-auto">
                    <span>{{ __('pages.development.built_more') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
            </div>

            @if ($featuredBuilds->isEmpty())
                <p class="text-sm font-light text-on-blush/70">{{ __('pages.development.built_empty') }}</p>
            @else
                <div class="dev-built-grid">
                    @foreach ($featuredBuilds as $build)
                        <a
                            href="{{ route('case-studies.show', $build) }}"
                            class="dev-built-card"
                        >
                            <div class="dev-built-media" aria-hidden="true">
                                @if ($build->cover_path)
                                    <img
                                        src="{{ $build->coverUrl() }}"
                                        alt=""
                                        class="dev-built-img"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                    >
                                @else
                                    <div class="dev-built-fallback"></div>
                                @endif
                            </div>
                            <div class="dev-built-copy">
                                @if ($build->client_name)
                                    <p class="dev-built-client">{{ $build->client_name }}</p>
                                @endif
                                <h3 class="dev-built-title">{{ $build->title }}</h3>
                                @if ($build->summary)
                                    <p class="dev-built-summary">{{ $build->summary }}</p>
                                @endif
                                <span class="dev-built-action">
                                    <span>{{ __('pages.development.built_view') }}</span>
                                    <x-ui.icons.arrow-up-right class="size-4" />
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-14 sm:py-16">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:gap-14">
                <div class="dev-section-copy max-w-xl">
                    <p class="dev-stack-eyebrow mb-3">{{ __('pages.development.eyebrow') }}</p>
                    <p class="text-base font-light leading-relaxed text-on-blush/80 sm:text-lg">
                        {{ __('pages.development.body') }}
                    </p>
                </div>

                <div class="dev-stack-stage" aria-hidden="true">
                    <div class="dev-stack-fade"></div>
                    <div class="dev-stack-columns">
                        <div class="dev-stack-col is-up">
                            <div class="dev-stack-track">
                                @foreach ([0, 1] as $loopCopy)
                                    @foreach ($stackColA as $tech)
                                        <span class="dev-stack-chip" title="{{ $tech['name'] ?? '' }}">
                                            <img
                                                src="{{ asset($tech['logo']) }}"
                                                alt=""
                                                width="28"
                                                height="28"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                            <span>{{ $tech['name'] ?? '' }}</span>
                                        </span>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        <div class="dev-stack-col is-down">
                            <div class="dev-stack-track">
                                @foreach ([0, 1] as $loopCopy)
                                    @foreach ($stackColB as $tech)
                                        <span class="dev-stack-chip" title="{{ $tech['name'] ?? '' }}">
                                            <img
                                                src="{{ asset($tech['logo']) }}"
                                                alt=""
                                                width="28"
                                                height="28"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                            <span>{{ $tech['name'] ?? '' }}</span>
                                        </span>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.development.intro_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.development.intro_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.development.intro_lede') }}</p>
            </div>
            <ol class="hosting-steps-grid" data-reveal-stagger>
                @foreach ($steps as $index => $step)
                    @php($icon = $stepIcons[$index] ?? 'zap')
                    <li class="hosting-step-card">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-dynamic-component :component="'ui.icons.'.$icon" class="size-5 text-rose" />
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-black">{{ $step['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-on-blush/75">{{ $step['body'] ?? '' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="dev-services" class="border-t border-border bg-blush-soft/40 scroll-mt-28" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.development.services_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.development.services_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.development.services_lede') }}</p>
            </div>
            <div class="dev-jump-grid" data-reveal-stagger>
                @foreach ($services as $key => $service)
                    @php($icon = $serviceIcons[$key] ?? 'code')
                    <a href="#dev-{{ $key }}" class="dev-jump-card">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-dynamic-component :component="'ui.icons.'.$icon" class="size-5 text-rose" />
                        </span>
                        <span class="dev-jump-title">{{ $service['title'] ?? '' }}</span>
                        <span class="dev-jump-summary">{{ $service['summary'] ?? '' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Web --}}
    <section id="dev-web" class="dev-section scroll-mt-28 border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="dev-section-copy">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-ui.icons.monitor class="size-5 text-rose" />
                        </span>
                        <p class="section-label mb-0">{{ $web['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="heading">{{ $web['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $web['summary'] ?? '' }}</p>
                    @if (! empty($web['tags']) && is_array($web['tags']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($web['tags'] as $tag)
                                <span class="dev-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ $contactFor($web['cta'] ?? __('pages.development.cta')) }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $web['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <div class="dev-cutout-stage" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['web']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['web']) }}" alt="" width="480" height="480" class="dev-cutout-img" loading="lazy" decoding="async">
                    </picture>
                </div>
            </div>
            <ul class="check-list mt-12 grid gap-3 sm:grid-cols-2">
                @foreach (array_slice(($web['points'] ?? []), 0, 3) as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Mobile --}}
    <section id="dev-mobile" class="dev-section is-flip scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="dev-cutout-stage order-2 lg:order-1" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['mobile']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['mobile']) }}" alt="" width="480" height="480" class="dev-cutout-img" loading="lazy" decoding="async">
                    </picture>
                </div>
                <div class="dev-section-copy order-1 lg:order-2">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-ui.icons.smartphone class="size-5 text-rose" />
                        </span>
                        <p class="section-label mb-0">{{ $mobile['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="heading">{{ $mobile['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $mobile['summary'] ?? '' }}</p>
                    @if (! empty($mobile['tags']) && is_array($mobile['tags']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($mobile['tags'] as $tag)
                                <span class="dev-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <ul class="check-list mt-8 space-y-3">
                        @foreach (array_slice(($mobile['points'] ?? []), 0, 3) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ $contactFor($mobile['cta'] ?? __('pages.development.cta')) }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $mobile['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- WordPress --}}
    <section id="dev-wordpress" class="dev-section scroll-mt-28 border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="dev-featured-band">
                <div class="dev-featured-copy">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge is-on-rose" aria-hidden="true">
                            <x-ui.icons.layout class="size-5 text-white" />
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ $wordpress['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="mt-1 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $wordpress['title'] ?? '' }}</h2>
                    <p class="mt-4 max-w-xl text-base font-light leading-relaxed text-white/90">{{ $wordpress['summary'] ?? '' }}</p>
                    @if (! empty($wordpress['tags']) && is_array($wordpress['tags']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($wordpress['tags'] as $tag)
                                <span class="dev-tag is-on-rose">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ $contactFor($wordpress['cta'] ?? __('pages.development.cta')) }}" class="btn mt-8 bg-white text-rose hover:bg-blush">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $wordpress['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <div class="dev-featured-art" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['wordpress']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['wordpress']) }}" alt="" width="420" height="420" class="dev-cutout-img is-on-rose" loading="lazy" decoding="async">
                    </picture>
                </div>
            </div>
            <ul class="check-list mt-10 grid gap-3 sm:grid-cols-2">
                @foreach (array_slice(($wordpress['points'] ?? []), 0, 3) as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Microservices --}}
    <section id="dev-microservices" class="dev-section scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="dev-section-copy">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-ui.icons.boxes class="size-5 text-rose" />
                        </span>
                        <p class="section-label mb-0">{{ $microservices['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="heading">{{ $microservices['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $microservices['summary'] ?? '' }}</p>
                    <ul class="check-list mt-8 space-y-3">
                        @foreach (array_slice(($microservices['points'] ?? []), 0, 3) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ $contactFor($microservices['cta'] ?? __('pages.development.cta')) }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $microservices['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <div class="dev-cutout-stage" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['microservices']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['microservices']) }}" alt="" width="480" height="480" class="dev-cutout-img" loading="lazy" decoding="async">
                    </picture>
                </div>
            </div>
        </div>
    </section>

    {{-- Deployments --}}
    <section id="dev-deployments" class="dev-section is-flip scroll-mt-28 border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="dev-cutout-stage order-2 lg:order-1" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['deployments']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['deployments']) }}" alt="" width="480" height="480" class="dev-cutout-img" loading="lazy" decoding="async">
                    </picture>
                </div>
                <div class="dev-section-copy order-1 lg:order-2">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-ui.icons.cloud-upload class="size-5 text-rose" />
                        </span>
                        <p class="section-label mb-0">{{ $deployments['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="heading">{{ $deployments['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $deployments['summary'] ?? '' }}</p>
                    <ol class="dev-pipeline mt-8">
                        @foreach (($deployments['pipeline'] ?? ['Build', 'Test', 'Stage', 'Ship']) as $index => $label)
                            <li class="dev-pipeline-step">
                                <span class="dev-pipeline-num">{{ $index + 1 }}</span>
                                <span class="dev-pipeline-label">{{ $label }}</span>
                            </li>
                        @endforeach
                    </ol>
                    <ul class="check-list mt-8 space-y-3">
                        @foreach (array_slice(($deployments['points'] ?? []), 0, 3) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ $contactFor($deployments['cta'] ?? __('pages.development.cta')) }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $deployments['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Testing --}}
    <section id="dev-testing" class="dev-section scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="dev-section-copy">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-ui.icons.clipboard-check class="size-5 text-rose" />
                        </span>
                        <p class="section-label mb-0">{{ $testing['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="heading">{{ $testing['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $testing['summary'] ?? '' }}</p>
                    <div class="dev-qa-board mt-8">
                        @foreach (array_slice(($testing['points'] ?? []), 0, 3) as $point)
                            <div class="dev-qa-row">
                                <span class="dev-qa-pass" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-3.5"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <span>{{ $point }}</span>
                            </div>
                        @endforeach
                    </div>
                    <a href="{{ $contactFor($testing['cta'] ?? __('pages.development.cta')) }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $testing['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <div class="dev-cutout-stage" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['testing']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['testing']) }}" alt="" width="480" height="480" class="dev-cutout-img" loading="lazy" decoding="async">
                    </picture>
                </div>
            </div>
        </div>
    </section>

    {{-- Maintenance --}}
    <section id="dev-maintenance" class="dev-section is-flip scroll-mt-28 border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="dev-cutout-stage order-2 lg:order-1" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset($serviceArt['maintenance']) }}" type="image/webp">
                        <img src="{{ asset($serviceArtPng['maintenance']) }}" alt="" width="480" height="480" class="dev-cutout-img" loading="lazy" decoding="async">
                    </picture>
                </div>
                <div class="dev-section-copy order-1 lg:order-2">
                    <div class="mb-4 inline-flex items-center gap-2">
                        <span class="dev-icon-badge" aria-hidden="true">
                            <x-ui.icons.shield class="size-5 text-rose" />
                        </span>
                        <p class="section-label mb-0">{{ $maintenance['eyebrow'] ?? '' }}</p>
                    </div>
                    <h2 class="heading">{{ $maintenance['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $maintenance['summary'] ?? '' }}</p>
                    <ul class="check-list mt-8 space-y-3">
                        @foreach (array_slice(($maintenance['points'] ?? []), 0, 3) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ $contactFor($maintenance['cta'] ?? __('pages.development.cta')) }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $maintenance['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    @if (count($faqItems) > 0)
        <section class="border-t border-border bg-blush-soft/40">
            <div class="container-page py-16 sm:py-20">
                <div class="mx-auto max-w-3xl">
                    <h2 class="heading mb-8 text-center">{{ __('pages.development.faq_title') }}</h2>
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

    <section class="border-t border-border bg-white">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.development.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.development.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $contactFor(__('pages.development.cta')) }}" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('pages.development.cta') }}</span>
                </a>
                <a href="{{ route('support') }}" class="btn btn-ghost">
                    <span>{{ __('site.nav.support') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
