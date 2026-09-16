@extends('layouts.app')

@section('title', __('pages.microservices.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.microservices.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('pages.microservices.eyebrow')"
        :title="__('pages.microservices.title')"
        :lede="__('pages.microservices.lede')"
        cta-href="#page-content"
        :cta-label="__('pages.microservices.cta')"
        :art="true"
        art-src="images/heroes/about.webp"
    />

    <x-layout.page-content wide>
        <div class="mb-10 max-w-2xl">
            <p class="section-label mb-3">{{ __('site.nav.microservices') }}</p>
            <h2 class="heading mb-4">{{ __('pages.microservices.services_title') }}</h2>
            <p class="lede">{{ __('pages.microservices.services_lede') }}</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <article class="flex flex-col rounded-3xl border border-white/20 bg-rose p-8 text-white lg:row-span-2">
                <p class="mb-2 text-base font-bold uppercase tracking-wide text-white/80">{{ __('site.nav.web_development') }}</p>
                <h3 class="mb-3 text-2xl font-bold">{{ __('pages.web_development.title') }}</h3>
                <p class="mb-6 text-base text-white/85">{{ __('site.nav.web_development_desc') }}</p>
                <x-ui.button href="{{ route('web-development') }}" class="mt-auto w-fit bg-white text-rose hover:bg-blush!">
                    <span>{{ __('site.nav.web_development_cta') }}</span>
                </x-ui.button>
            </article>

            <article class="card-tech flex flex-col gap-3 p-8">
                <h3 class="text-xl font-bold text-on-blush">{{ __('site.nav.mobile_apps') }}</h3>
                <p class="body-text flex-1">{{ __('site.nav.mobile_apps_desc') }}</p>
                <a href="{{ route('mobile-apps') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                    <span>{{ __('pages.mobile_apps.cta') }}</span>
                    <x-ui.icons.arrow-up-right class="size-3.5" />
                </a>
            </article>

            <article class="card-tech flex flex-col gap-3 p-8">
                <h3 class="text-xl font-bold text-on-blush">{{ __('site.nav.maintenance') }}</h3>
                <p class="body-text flex-1">{{ __('site.nav.maintenance_desc') }}</p>
                <a href="{{ route('maintenance') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                    <span>{{ __('pages.maintenance.cta') }}</span>
                    <x-ui.icons.arrow-up-right class="size-3.5" />
                </a>
            </article>

            <article class="card-tech flex flex-col gap-3 p-8 lg:col-start-2">
                <h3 class="text-xl font-bold text-on-blush">{{ __('site.nav.support') }}</h3>
                <p class="body-text flex-1">{{ __('site.nav.support_desc') }}</p>
                <a href="{{ route('support') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-rose hover:underline">
                    <span>{{ __('pages.support_page.cta') }}</span>
                    <x-ui.icons.arrow-up-right class="size-3.5" />
                </a>
            </article>
        </div>

        <div class="mt-14 border-t border-border pt-10">
            <h2 class="heading mb-3">{{ __('pages.microservices.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.microservices.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button href="{{ route('contact') }}">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.common.contact_us') }}</span>
                </x-ui.button>
                <x-ui.button href="{{ route('case-studies') }}" variant="ghost">
                    <span>{{ __('site.footer.case_studies') }}</span>
                </x-ui.button>
            </div>
        </div>
    </x-layout.page-content>
@endsection
