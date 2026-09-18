@extends('layouts.app')

@section('title', __('pages.about.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.about.meta_description'))

@php
    $values = __('pages.about.values');
    if (! is_array($values)) {
        $values = [];
    }
    $milestones = __('pages.about.milestones');
    if (! is_array($milestones)) {
        $milestones = [];
    }
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                        {{ __('pages.about.eyebrow') }}
                    </p>
                    <h1 class="mt-4 max-w-2xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ __('pages.about.title') }}
                    </h1>
                    <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                        {{ __('pages.about.lede') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#about-story" class="btn bg-white text-rose hover:bg-blush">
                            <span>{{ __('pages.about.cta') }}</span>
                        </a>
                        <a href="{{ route('team') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                            <span>{{ __('pages.about.cta_team') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                    </div>
                </div>
                <div class="dev-cutout-hero hidden lg:flex" aria-hidden="true">
                    <picture>
                        <source srcset="{{ asset('images/heroes/about.webp') }}" type="image/webp">
                        <img
                            src="{{ asset('images/heroes/about.png') }}"
                            alt=""
                            width="480"
                            height="480"
                            class="dev-cutout-img"
                            loading="eager"
                            decoding="async"
                        >
                    </picture>
                </div>
            </div>
        </div>
    </section>

    <section id="about-story" class="scroll-mt-28 border-b border-border bg-white" data-reveal>
        <div class="container-page grid gap-10 py-14 sm:py-16 lg:grid-cols-2 lg:gap-16">
            <div>
                <p class="section-label mb-3">{{ __('pages.about.story_label') }}</p>
                <h2 class="heading">{{ __('pages.about.story_title') }}</h2>
            </div>
            <div class="space-y-5 text-base font-light leading-relaxed text-on-blush/80 sm:text-lg">
                <p>{{ __('pages.about.p1') }}</p>
                <p>{{ __('pages.about.p2') }}</p>
                <p>{{ __('pages.about.p3') }}</p>
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.about.values_label') }}</p>
                <h2 class="heading">{{ __('pages.about.values_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.about.values_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($values as $value)
                    <article class="hosting-feature-card">
                        <span class="dev-icon-badge mb-4" aria-hidden="true">
                            @if (($value['icon'] ?? '') === 'shield')
                                <x-ui.icons.shield-check class="size-5 text-rose" />
                            @elseif (($value['icon'] ?? '') === 'message')
                                <x-ui.icons.message-circle class="size-5 text-rose" />
                            @elseif (($value['icon'] ?? '') === 'rocket')
                                <x-ui.icons.rocket class="size-5 text-rose" />
                            @else
                                <x-ui.icons.headset class="size-5 text-rose" />
                            @endif
                        </span>
                        <h3 class="text-lg font-bold text-black">{{ $value['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $value['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-about.what-we-do data-reveal />

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-8 sm:grid-cols-3">
                <div>
                    <p class="mb-2 text-4xl font-bold tracking-tight text-rose sm:text-5xl">{{ config('site.years_experience') }}+</p>
                    <p class="text-base font-light text-on-blush/75">{{ __('pages.about.stat_years') }}</p>
                </div>
                <div>
                    <p class="mb-2 text-4xl font-bold tracking-tight text-rose sm:text-5xl">99%</p>
                    <p class="text-base font-light text-on-blush/75">{{ __('pages.about.stat_uptime') }}</p>
                </div>
                <div>
                    <p class="mb-2 text-4xl font-bold tracking-tight text-rose sm:text-5xl">24/7</p>
                    <p class="text-base font-light text-on-blush/75">{{ __('pages.about.stat_support') }}</p>
                </div>
            </div>
        </div>
    </section>

    @if (count($milestones) > 0)
        <section class="section-band border-t border-border" data-reveal>
            <div class="container-page py-16 sm:py-20">
                <div class="mb-10 max-w-2xl">
                    <p class="section-label mb-3">{{ __('pages.about.milestones_label') }}</p>
                    <h2 class="heading">{{ __('pages.about.milestones_title') }}</h2>
                    <p class="lede mt-3">{{ __('pages.about.milestones_lede') }}</p>
                </div>
                <ol class="grid gap-6 lg:grid-cols-3" data-reveal-stagger>
                    @foreach ($milestones as $index => $item)
                        <li class="rounded-3xl border border-border bg-white p-6 sm:p-7">
                            <p class="text-sm font-bold uppercase tracking-widest text-rose">{{ $item['year'] ?? '' }}</p>
                            <h3 class="mt-3 text-lg font-bold text-black">{{ $item['title'] ?? '' }}</h3>
                            <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $item['body'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    <section class="border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page grid gap-8 py-14 sm:py-16 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="section-label mb-3">{{ __('pages.about.base_label') }}</p>
                <h2 class="heading">{{ __('pages.about.base_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.about.base_lede') }}</p>
                <p class="mt-5 text-sm font-medium text-on-blush/80">{{ config('site.address') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('team') }}" class="btn btn-primary">
                    <span>{{ __('pages.about.cta_team') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
                <a href="{{ route('careers') }}" class="btn btn-ghost">
                    <span>{{ __('pages.about.cta_careers') }}</span>
                </a>
                <a href="{{ route('contact') }}" class="btn btn-ghost">
                    <span>{{ __('site.common.contact_us') }}</span>
                </a>
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.about.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.about.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('email.plans') }}" class="btn btn-primary">
                    <span>{{ __('site.nav.email') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
                <a href="{{ route('case-studies') }}" class="btn btn-ghost">
                    <span>{{ __('pages.about.view_case_studies') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
