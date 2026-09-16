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

    $web = $services['web'] ?? [];
    $mobile = $services['mobile'] ?? [];
    $wordpress = $services['wordpress'] ?? [];
    $microservices = $services['microservices'] ?? [];
    $deployments = $services['deployments'] ?? [];
    $testing = $services['testing'] ?? [];
    $maintenance = $services['maintenance'] ?? [];
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
                        <a href="{{ route('contact') }}" class="btn bg-white text-rose hover:bg-blush" data-action-loading data-loading-label="{{ __('account.processing') }}">
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
                <div class="dev-hero-stage hidden lg:block" aria-hidden="true">
                    <div class="dev-hero-window">
                        <div class="dev-hero-window-bar">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="dev-hero-window-body">
                            <div class="dev-hero-line w-[72%]"></div>
                            <div class="dev-hero-line w-[54%]"></div>
                            <div class="dev-hero-line w-[81%]"></div>
                            <div class="dev-hero-card-row">
                                <div class="dev-hero-mini-card"></div>
                                <div class="dev-hero-mini-card is-accent"></div>
                            </div>
                        </div>
                    </div>
                    <div class="dev-hero-phone">
                        <div class="dev-hero-phone-notch"></div>
                        <div class="dev-hero-phone-screen">
                            <div class="dev-hero-line w-[60%]"></div>
                            <div class="dev-hero-line w-[78%]"></div>
                            <div class="dev-hero-dot-row">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-border bg-white">
        <div class="container-page py-12 sm:py-14">
            <p class="mx-auto max-w-3xl text-center text-base leading-relaxed text-on-blush/80 sm:text-lg">
                {{ __('pages.development.body') }}
            </p>
        </div>
    </section>

    <section class="section-band border-t border-border">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.development.intro_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.development.intro_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.development.intro_lede') }}</p>
            </div>
            <ol class="hosting-steps-grid">
                @foreach ($steps as $index => $step)
                    <li class="hosting-step-card">
                        <span class="hosting-step-num">{{ $index + 1 }}</span>
                        <h3 class="mt-4 text-lg font-bold text-black">{{ $step['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-on-blush/75">{{ $step['body'] ?? '' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="dev-services" class="border-t border-border bg-blush-soft/40 scroll-mt-28">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.development.services_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.development.services_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.development.services_lede') }}</p>
            </div>
            <div class="dev-jump-grid">
                @foreach ($services as $key => $service)
                    <a href="#dev-{{ $key }}" class="dev-jump-card">
                        <span class="dev-jump-index">{{ str_pad((string) (array_search($key, array_keys($services), true) + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="dev-jump-title">{{ $service['title'] ?? '' }}</span>
                        <span class="dev-jump-summary">{{ $service['summary'] ?? '' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Web --}}
    <section id="dev-web" class="dev-section scroll-mt-28 border-t border-border bg-white">
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div>
                    <p class="section-label mb-3">{{ $web['eyebrow'] ?? '' }}</p>
                    <h2 class="heading">{{ $web['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $web['lede'] ?? '' }}</p>
                    @if (! empty($web['tags']) && is_array($web['tags']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($web['tags'] as $tag)
                                <span class="dev-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('contact') }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $web['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <div class="dev-stage-browser" aria-hidden="true">
                    <div class="dev-stage-browser-chrome">
                        <span></span><span></span><span></span>
                        <div class="dev-stage-browser-url"></div>
                    </div>
                    <div class="dev-stage-browser-body">
                        <div class="dev-stage-browser-sidebar"></div>
                        <div class="dev-stage-browser-main">
                            <div class="dev-hero-line w-[70%]"></div>
                            <div class="dev-hero-line w-[48%]"></div>
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <div class="dev-stage-tile"></div>
                                <div class="dev-stage-tile is-rose"></div>
                                <div class="dev-stage-tile is-rose"></div>
                                <div class="dev-stage-tile"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <ul class="check-list mt-12 grid gap-3 sm:grid-cols-2">
                @foreach (($web['points'] ?? []) as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Mobile --}}
    <section id="dev-mobile" class="dev-section scroll-mt-28 border-t border-border bg-blush-soft/40">
        <div class="container-page py-16 sm:py-20">
            <div class="grid items-center gap-12 lg:grid-cols-[0.85fr_1.15fr]">
                <div class="dev-stage-phones order-2 lg:order-1" aria-hidden="true">
                    <div class="dev-phone is-back">
                        <div class="dev-phone-notch"></div>
                        <div class="dev-phone-screen">
                            <div class="dev-hero-line w-[55%]"></div>
                            <div class="dev-hero-line w-[72%]"></div>
                            <div class="dev-phone-blob"></div>
                        </div>
                    </div>
                    <div class="dev-phone is-front">
                        <div class="dev-phone-notch"></div>
                        <div class="dev-phone-screen is-dark">
                            <div class="dev-hero-line is-light w-[62%]"></div>
                            <div class="dev-hero-line is-light w-[44%]"></div>
                            <div class="dev-phone-cta"></div>
                        </div>
                    </div>
                </div>
                <div class="order-1 lg:order-2">
                    <p class="section-label mb-3">{{ $mobile['eyebrow'] ?? '' }}</p>
                    <h2 class="heading">{{ $mobile['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $mobile['lede'] ?? '' }}</p>
                    @if (! empty($mobile['tags']) && is_array($mobile['tags']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($mobile['tags'] as $tag)
                                <span class="dev-tag">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <ul class="check-list mt-8 space-y-3">
                        @foreach (($mobile['points'] ?? []) as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('contact') }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $mobile['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- WordPress --}}
    <section id="dev-wordpress" class="dev-section scroll-mt-28 border-t border-border bg-white">
        <div class="container-page py-16 sm:py-20">
            <div class="dev-featured-band">
                <div class="dev-featured-copy">
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ $wordpress['eyebrow'] ?? '' }}</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $wordpress['title'] ?? '' }}</h2>
                    <p class="mt-4 max-w-xl text-base font-light leading-relaxed text-white/90">{{ $wordpress['lede'] ?? '' }}</p>
                    @if (! empty($wordpress['tags']) && is_array($wordpress['tags']))
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($wordpress['tags'] as $tag)
                                <span class="dev-tag is-on-rose">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                    <a href="{{ route('contact') }}" class="btn mt-8 bg-white text-rose hover:bg-blush">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $wordpress['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <ul class="dev-featured-list">
                    @foreach (($wordpress['points'] ?? []) as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Microservices --}}
    <section id="dev-microservices" class="dev-section scroll-mt-28 border-t border-border bg-blush-soft/40">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ $microservices['eyebrow'] ?? '' }}</p>
                <h2 class="heading">{{ $microservices['title'] ?? '' }}</h2>
                <p class="lede mx-auto mt-3">{{ $microservices['lede'] ?? '' }}</p>
            </div>
            <div class="dev-nodes" aria-hidden="true">
                <div class="dev-node">Auth</div>
                <div class="dev-node is-center">API Gateway</div>
                <div class="dev-node">Billing</div>
                <div class="dev-node">Notify</div>
                <div class="dev-node is-center">Data</div>
                <div class="dev-node">Webhooks</div>
            </div>
            <ul class="check-list mt-12 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (($microservices['points'] ?? []) as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
            <div class="mt-10 text-center">
                <a href="{{ route('contact') }}" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ $microservices['cta'] ?? __('pages.development.cta') }}</span>
                </a>
            </div>
        </div>
    </section>

    {{-- Deployments --}}
    <section id="dev-deployments" class="dev-section scroll-mt-28 border-t border-border bg-white">
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ $deployments['eyebrow'] ?? '' }}</p>
                <h2 class="heading">{{ $deployments['title'] ?? '' }}</h2>
                <p class="lede mt-3">{{ $deployments['lede'] ?? '' }}</p>
            </div>
            <ol class="dev-pipeline">
                @foreach (($deployments['pipeline'] ?? ['Build', 'Test', 'Stage', 'Ship']) as $index => $label)
                    <li class="dev-pipeline-step">
                        <span class="dev-pipeline-num">{{ $index + 1 }}</span>
                        <span class="dev-pipeline-label">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
            <ul class="check-list mt-12 grid gap-3 sm:grid-cols-2">
                @foreach (($deployments['points'] ?? []) as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
            <a href="{{ route('contact') }}" class="btn btn-primary mt-10">
                <x-ui.icons.arrow-up-right class="size-4" />
                <span>{{ $deployments['cta'] ?? __('pages.development.cta') }}</span>
            </a>
        </div>
    </section>

    {{-- Testing --}}
    <section id="dev-testing" class="dev-section scroll-mt-28 border-t border-border bg-blush-soft/40">
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-2 lg:items-start">
                <div>
                    <p class="section-label mb-3">{{ $testing['eyebrow'] ?? '' }}</p>
                    <h2 class="heading">{{ $testing['title'] ?? '' }}</h2>
                    <p class="lede mt-3">{{ $testing['lede'] ?? '' }}</p>
                    <a href="{{ route('contact') }}" class="btn btn-primary mt-8">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ $testing['cta'] ?? __('pages.development.cta') }}</span>
                    </a>
                </div>
                <div class="dev-qa-board">
                    @foreach (($testing['points'] ?? []) as $index => $point)
                        <div class="dev-qa-row">
                            <span class="dev-qa-pass" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-3.5"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span>{{ $point }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Maintenance --}}
    <section id="dev-maintenance" class="dev-section scroll-mt-28 border-t border-border bg-white">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ $maintenance['eyebrow'] ?? '' }}</p>
                <h2 class="heading">{{ $maintenance['title'] ?? '' }}</h2>
                <p class="lede mx-auto mt-3">{{ $maintenance['lede'] ?? '' }}</p>
            </div>
            <div class="hosting-plan-grid">
                @foreach (($maintenance['points'] ?? []) as $index => $point)
                    <article class="hosting-plan-card">
                        <span class="hosting-step-num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <p class="mt-4 text-base font-semibold leading-relaxed text-black">{{ $point }}</p>
                    </article>
                @endforeach
            </div>
            <div class="mt-10 text-center">
                <a href="{{ route('contact') }}" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ $maintenance['cta'] ?? __('pages.development.cta') }}</span>
                </a>
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
                <a href="{{ route('contact') }}" class="btn btn-primary">
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
