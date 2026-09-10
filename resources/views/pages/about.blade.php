@extends('layouts.app')

@section('title', __('pages.about.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.about.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.about_eyebrow')"
        :title="__('site.pages.about_title')"
        :lede="__('site.pages.about_lede')"
        cta-href="#about-content"
        :cta-label="__('site.pages.about_cta')"
        :art="true"
        art-src="images/heroes/about.webp"
    />

    {{-- Intro prose --}}
    <section id="about-content" class="border-t bg-white" style="border-color:var(--color-border);">
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-12 lg:grid-cols-2 lg:gap-20">
                <div class="space-y-5 body-text">
                    <p>{{ __('pages.about.p1') }}</p>
                    <p>{{ __('pages.about.p2') }}</p>
                </div>
                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4 self-start">
                    @foreach ([
                        ['val' => config('site.years_experience').'+', 'lbl' => __('pages.about.stat_years')],
                        ['val' => '99%',                               'lbl' => __('pages.about.stat_uptime')],
                        ['val' => '4.8★',                             'lbl' => __('pages.about.stat_rating')],
                    ] as $stat)
                        <div class="rounded-2xl border p-5 text-center"
                             style="border-color:var(--color-border); background:var(--color-surface-2);">
                            <p class="text-2xl font-bold" style="color:var(--color-red);">{{ $stat['val'] }}</p>
                            <p class="mt-1.5 text-xs font-light leading-tight" style="color:var(--color-ink-3);">{{ $stat['lbl'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- What we do --}}
    <x-about.what-we-do />

    {{-- Tech stack --}}
    <x-home.tech-partners />

    {{-- CTA band --}}
    <section class="border-t" style="border-color:var(--color-border); background:var(--color-surface-2);">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <p class="section-label mb-4">{{ __('site.pages.about_eyebrow') }}</p>
                <h2 class="heading mb-5">Ready to work with us?</h2>
                <p class="lede mx-auto mb-8">{{ __('site.pages.about_lede') }}</p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('email.plans') }}" class="btn btn-primary gap-2 px-7 py-3.5 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        {{ __('site.nav.email') }}
                    </a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost px-7 py-3.5 text-sm">{{ __('site.common.contact_us') }}</a>
                    <a href="{{ route('case-studies') }}" class="btn btn-ghost px-7 py-3.5 text-sm">{{ __('pages.about.view_case_studies') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
