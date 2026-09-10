@extends('layouts.app')

@section('title', __('pages.case_studies.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.case_studies.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.case_eyebrow')"
        :title="__('site.pages.case_title')"
        :lede="__('site.pages.case_lede')"
        cta-href="#case-content"
        :cta-label="__('site.pages.case_cta')"
    />

    {{-- Intro --}}
    <section id="case-content" class="border-t bg-white" style="border-color:var(--color-border);">
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">
                <div class="space-y-5 body-text">
                    <p>{{ __('pages.case_studies.p1') }}</p>
                    <p>{{ __('pages.case_studies.p2') }}</p>
                </div>
                {{-- Key numbers --}}
                <div class="grid grid-cols-3 gap-4 self-start">
                    @foreach ([
                        ['val' => config('site.years_experience').'+', 'lbl' => __('pages.case_studies.stat_years')],
                        ['val' => '99+',                               'lbl' => __('pages.case_studies.stat_uptime')],
                        ['val' => '4.8★',                             'lbl' => __('pages.case_studies.stat_rating')],
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

    {{-- Use-case rows --}}
    <x-case-studies.use-cases />

    {{-- Tech stack --}}
    <x-home.tech-partners />

    {{-- Start a project CTA --}}
    <section class="border-t" style="border-color:var(--color-border); background:var(--color-ink);">
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto max-w-xl text-center">
                <p class="section-label mb-4" style="color:rgba(255,255,255,0.35);">
                    {{ __('site.pages.case_eyebrow') }}
                </p>
                <h2 class="mb-5 text-3xl font-bold text-white sm:text-4xl">
                    {{ __('pages.case_studies.ready') }}
                </h2>
                <a href="{{ route('contact') }}" class="btn btn-primary gap-2 px-8 py-4 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    {{ __('pages.case_studies.start_project') }}
                </a>
            </div>
        </div>
    </section>
@endsection
