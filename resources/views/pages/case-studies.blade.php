@extends('layouts.app')

@section('title', __('pages.case_studies.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.case_studies.meta_description'))

@section('content')
    <section class="case-cinema-intro">
        <div class="container-page case-cinema-intro-inner">
            <p class="case-cinema-brand">{{ config('site.name') }}</p>
            <h1 class="case-cinema-intro-title">{{ __('pages.case_studies.title') }}</h1>
            <p class="case-cinema-intro-lede">{{ __('pages.case_studies.lede') }}</p>
            <a href="#reel" class="btn btn-primary case-cinema-intro-cta">
                <span>{{ __('pages.case_studies.cta') }}</span>
                <x-ui.icons.arrow-up-right class="size-4" />
            </a>
        </div>
    </section>

    @if ($caseStudies->isEmpty())
        <section id="reel" class="case-cinema-empty scroll-mt-28" data-reveal>
            <div class="container-page py-16 sm:py-20 text-center">
                <p class="case-cinema-empty-title">{{ __('pages.case_studies.empty_title') }}</p>
                <p class="case-cinema-empty-lede">{{ __('pages.case_studies.empty_lede') }}</p>
                <a href="{{ route('contact') }}" class="btn btn-primary mt-8 inline-flex">
                    <span>{{ __('pages.case_studies.start_project') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
            </div>
        </section>
    @else
        <div id="reel" class="case-cinema-reel scroll-mt-0" role="list">
            @foreach ($caseStudies as $caseStudy)
                <article class="case-cinema-frame" role="listitem" data-reveal>
                    <a
                        href="{{ route('case-studies.show', $caseStudy) }}"
                        class="case-cinema-frame-link"
                        aria-label="{{ __('pages.case_studies.read_more') }}: {{ $caseStudy->title }}"
                    >
                        <div class="case-cinema-frame-media" aria-hidden="true">
                            @if ($caseStudy->cover_path)
                                <img
                                    src="{{ $caseStudy->coverUrl() }}"
                                    alt=""
                                    class="case-cinema-frame-img"
                                    loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                >
                            @else
                                <div class="case-cinema-frame-fallback"></div>
                            @endif
                        </div>
                        <div class="case-cinema-frame-scrim" aria-hidden="true"></div>
                        <div class="case-cinema-frame-copy">
                            @if ($caseStudy->client_name)
                                <p class="case-cinema-frame-client">{{ $caseStudy->client_name }}</p>
                            @endif
                            <h2 class="case-cinema-frame-title">{{ $caseStudy->title }}</h2>
                            @if ($caseStudy->summary)
                                <p class="case-cinema-frame-summary">{{ $caseStudy->summary }}</p>
                            @endif
                            <span class="case-cinema-frame-action">
                                <span>{{ __('pages.case_studies.read_more') }}</span>
                                <x-ui.icons.arrow-up-right class="size-4" />
                            </span>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>
    @endif

    <section class="case-cinema-close" data-reveal>
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.case_studies.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.case_studies.help_lede') }}</p>
            <a href="{{ route('contact') }}" class="btn btn-primary">
                <span>{{ __('pages.case_studies.start_project') }}</span>
                <x-ui.icons.arrow-up-right class="size-4" />
            </a>
        </div>
    </section>
@endsection
