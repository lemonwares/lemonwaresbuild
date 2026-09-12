@extends('layouts.app')

@section('title', __('pages.about.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.about.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.about_eyebrow')"
        :title="__('site.pages.about_title')"
        :lede="__('site.pages.about_lede')"
        cta-href="#page-content"
        :cta-label="__('site.pages.about_cta')"
        :art="true"
        art-src="images/heroes/about.webp"
    />

    <x-layout.page-content>
        <div class="space-y-6 body-text">
            <p>{{ __('pages.about.p1') }}</p>
            <p>{{ __('pages.about.p2') }}</p>
        </div>
    </x-layout.page-content>

    <x-about.what-we-do />

    <x-home.tech-partners />

    <x-layout.page-content>
        <div class="grid gap-8 sm:grid-cols-3">
            <div>
                <p class="mb-2 text-4xl font-bold text-rose">{{ config('site.years_experience') }}+</p>
                <p class="body-text">{{ __('pages.about.stat_years') }}</p>
            </div>
            <div>
                <p class="mb-2 text-4xl font-bold text-rose">99+</p>
                <p class="body-text">{{ __('pages.about.stat_uptime') }}</p>
            </div>
            <div>
                <p class="mb-2 text-4xl font-bold text-rose">4.8</p>
                <p class="body-text">{{ __('pages.about.stat_rating') }}</p>
            </div>
        </div>

        <div class="mt-10 flex flex-wrap gap-4">
            <x-ui.button href="{{ route('email.plans') }}">
                <x-ui.icons.arrow-up-right class="size-4" />
                <span>{{ __('site.nav.email') }}</span>
            </x-ui.button>
            <x-ui.button href="{{ route('contact') }}" variant="ghost">
                <span>{{ __('site.common.contact_us') }}</span>
            </x-ui.button>
            <x-ui.button href="{{ route('case-studies') }}" variant="ghost">
                <span>{{ __('pages.about.view_case_studies') }}</span>
            </x-ui.button>
        </div>
    </x-layout.page-content>
@endsection
