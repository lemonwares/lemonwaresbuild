@extends('layouts.app')

@section('title', __('pages.case_studies.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.case_studies.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.case_eyebrow')"
        :title="__('site.pages.case_title')"
        :lede="__('site.pages.case_lede')"
        cta-href="#page-content"
        :cta-label="__('site.pages.case_cta')"
        :art="true"
        art-src="images/heroes/work.webp"
    />

    <x-layout.page-content>
        <div class="space-y-6 body-text">
            <p>{{ __('pages.case_studies.p1') }}</p>
            <p>{{ __('pages.case_studies.p2') }}</p>
        </div>
    </x-layout.page-content>

    <x-case-studies.use-cases />

    <x-home.tech-partners />

    <x-layout.page-content>
        <div class="grid gap-8 sm:grid-cols-3">
            <div>
                <p class="mb-2 text-4xl font-bold text-rose">{{ config('site.years_experience') }}+</p>
                <p class="body-text">{{ __('pages.case_studies.stat_years') }}</p>
            </div>
            <div>
                <p class="mb-2 text-4xl font-bold text-rose">99+</p>
                <p class="body-text">{{ __('pages.case_studies.stat_uptime') }}</p>
            </div>
            <div>
                <p class="mb-2 text-4xl font-bold text-rose">4.8</p>
                <p class="body-text">{{ __('pages.case_studies.stat_rating') }}</p>
            </div>
        </div>

        <div class="mt-12 text-center">
            <p class="mb-6 body-text">{{ __('pages.case_studies.ready') }}</p>
            <x-ui.button href="{{ route('contact') }}">
                <x-ui.icons.arrow-up-right class="size-4" />
                <span>{{ __('pages.case_studies.start_project') }}</span>
            </x-ui.button>
        </div>
    </x-layout.page-content>
@endsection
