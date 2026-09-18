@extends('layouts.app')

@section('title', __('pages.microsoft_365.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.microsoft_365.meta_description'))

@php
    $steps = __('pages.microsoft_365.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
    $apps = __('pages.microsoft_365.apps');
    if (! is_array($apps)) {
        $apps = [];
    }
    $setupItems = __('pages.microsoft_365.setup_items');
    if (! is_array($setupItems)) {
        $setupItems = [];
    }
    $audienceItems = __('pages.microsoft_365.audience_items');
    if (! is_array($audienceItems)) {
        $audienceItems = [];
    }
    $features = __('pages.microsoft_365.features');
    if (! is_array($features)) {
        $features = [];
    }
    $highlights = __('pages.microsoft_365.highlights');
    if (! is_array($highlights)) {
        $highlights = [];
    }
    $fitSuite = __('pages.microsoft_365.fit_suite');
    if (! is_array($fitSuite)) {
        $fitSuite = [];
    }
    $fitMailemon = __('pages.microsoft_365.fit_mailemon');
    if (! is_array($fitMailemon)) {
        $fitMailemon = [];
    }
    $faqItems = __('pages.microsoft_365.faq_items');
    if (! is_array($faqItems)) {
        $faqItems = [];
    }

    $quoteHref = route('contact', [
        'subject' => __('pages.microsoft_365.cta_subject'),
    ]).'#contact-form';
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.microsoft_365.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.microsoft_365.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.microsoft_365.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ $quoteHref }}" class="btn bg-white text-rose hover:bg-blush">
                            <span>{{ __('pages.microsoft_365.cta') }}</span>
                        </a>
                        <a href="#m365-apps" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <x-ui.icons.arrow-down class="size-4" />
                            {{ __('pages.microsoft_365.apps_title') }}
                        </a>
                    </div>
                </div>
                <div class="hidden justify-end lg:flex" aria-hidden="true">
                    <img src="{{ asset('images/brands/microsoft-365.svg') }}" alt="" width="220" height="220" class="w-full max-w-[14rem] brightness-0 invert opacity-95">
                </div>
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.microsoft_365.intro_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.microsoft_365.intro_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.microsoft_365.intro_lede') }}</p>
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

    <section id="m365-apps" class="scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.microsoft_365.apps_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.microsoft_365.apps_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.microsoft_365.apps_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($apps as $app)
                    <article class="hosting-feature-card">
                        <span class="dev-icon-badge mb-4" aria-hidden="true">
                            <x-ui.icons.monitor class="size-5 text-rose" />
                        </span>
                        <h3 class="text-base font-bold text-black">{{ $app['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $app['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.microsoft_365.setup_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.microsoft_365.setup_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.microsoft_365.setup_lede') }}</p>
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

    <section id="m365-features" class="scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.microsoft_365.features_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.microsoft_365.features_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.microsoft_365.features_lede') }}</p>
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

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.microsoft_365.audience_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.microsoft_365.audience_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.microsoft_365.audience_lede') }}</p>
            </div>
            <ul class="check-list grid gap-3 sm:grid-cols-2">
                @foreach ($audienceItems as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.microsoft_365.fit_eyebrow') }}</p>
                <h2 class="heading">{{ __('pages.microsoft_365.fit_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.microsoft_365.fit_lede') }}</p>
            </div>
            <div class="grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-border bg-white p-7 sm:p-8">
                    <h3 class="text-lg font-bold text-black">{{ __('pages.microsoft_365.fit_suite_title') }}</h3>
                    <ul class="check-list mt-5 space-y-3">
                        @foreach ($fitSuite as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
                <article class="rounded-3xl border border-border bg-blush-soft/50 p-7 sm:p-8">
                    <h3 class="text-lg font-bold text-black">{{ __('pages.microsoft_365.fit_mailemon_title') }}</h3>
                    <ul class="check-list mt-5 space-y-3">
                        @foreach ($fitMailemon as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('email.plans') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                        <span>{{ __('pages.microsoft_365.fit_mailemon_cta') }}</span>
                        <x-ui.icons.arrow-up-right class="size-3.5" />
                    </a>
                </article>
            </div>
        </div>
    </section>

    @if (count($faqItems) > 0)
        <section class="border-t border-border bg-blush-soft/40" data-reveal>
            <div class="container-page py-16 sm:py-20">
                <div class="mx-auto max-w-3xl">
                    <h2 class="heading mb-8 text-center">{{ __('pages.microsoft_365.faq_title') }}</h2>
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
            <h2 class="heading mb-3">{{ __('pages.microsoft_365.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.microsoft_365.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ $quoteHref }}" class="btn btn-primary">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('pages.microsoft_365.cta') }}</span>
                </a>
                <a href="{{ route('email.plans') }}" class="btn btn-ghost">
                    <span>{{ __('site.nav.email') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
