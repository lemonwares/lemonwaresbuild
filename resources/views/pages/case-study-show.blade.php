@extends('layouts.app')

@section('title', $caseStudy->title . ' — ' . __('pages.case_studies.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', $caseStudy->summary ?: __('pages.case_studies.meta_description'))

@php
    $paragraphs = collect(preg_split("/\r\n\r\n|\n\n|\r\r/", (string) $caseStudy->description) ?: [])
        ->map(fn ($p) => trim((string) $p))
        ->filter()
        ->values()
        ->all();

    $liveUrl = filled($caseStudy->cta_url) ? $caseStudy->cta_url : null;
    if ($liveUrl && str_starts_with($liveUrl, '/')) {
        $liveUrl = url($liveUrl);
    }
    $liveLabel = filled($caseStudy->cta_label)
        ? $caseStudy->cta_label
        : __('pages.case_studies.view_live');
@endphp

@section('content')
    <div
        @if ($liveUrl)
            data-case-live-preview
            data-preview-url="{{ $liveUrl }}"
        @endif
    >
        <section class="case-cinema-opening">
            <div class="case-cinema-opening-media" aria-hidden="true">
                @if ($caseStudy->cover_path)
                    <img
                        src="{{ $caseStudy->coverUrl() }}"
                        alt=""
                        class="case-cinema-opening-img"
                    >
                @else
                    <div class="case-cinema-frame-fallback"></div>
                @endif
            </div>
            <div class="case-cinema-opening-scrim" aria-hidden="true"></div>
            <div class="case-cinema-opening-inner">
                <a href="{{ route('case-studies') }}" class="case-cinema-back">
                    <x-ui.icons.arrow-left class="size-4" />
                    <span>{{ __('pages.case_studies.back') }}</span>
                </a>
                <div class="case-cinema-opening-copy">
                    @if ($caseStudy->client_name)
                        <p class="case-cinema-frame-client">{{ $caseStudy->client_name }}</p>
                    @endif
                    <h1 class="case-cinema-opening-title">{{ $caseStudy->title }}</h1>
                    @if ($caseStudy->summary)
                        <p class="case-cinema-opening-summary">{{ $caseStudy->summary }}</p>
                    @endif
                    @if ($liveUrl)
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button type="button" class="btn bg-white text-rose hover:bg-blush" data-case-live-open>
                                <span>{{ $liveLabel }}</span>
                                <x-ui.icons.monitor class="size-4" />
                            </button>
                            <a href="{{ $liveUrl }}" class="btn btn-ghost border-white/30 text-white hover:bg-white/10" target="_blank" rel="noopener noreferrer">
                                <span>{{ __('pages.case_studies.open_new_tab') }}</span>
                                <x-ui.icons.arrow-up-right class="size-4" />
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if ($caseStudy->client_name || $caseStudy->outcome)
            <section class="case-cinema-facts border-t border-border" data-reveal>
                <div class="container-page case-cinema-facts-inner">
                    @if ($caseStudy->client_name)
                        <div class="case-cinema-fact">
                            <p class="case-cinema-fact-label">{{ __('pages.case_studies.client_label') }}</p>
                            <p class="case-cinema-fact-value">{{ $caseStudy->client_name }}</p>
                        </div>
                    @endif
                    @if ($caseStudy->outcome)
                        <div class="case-cinema-fact case-cinema-fact-wide">
                            <p class="case-cinema-fact-label">{{ __('pages.case_studies.outcome_label') }}</p>
                            <p class="case-cinema-fact-value">{{ $caseStudy->outcome }}</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="section-band border-t border-border" data-reveal>
            <div class="container-page py-14 sm:py-16">
                <article class="mx-auto max-w-3xl space-y-5 body-text">
                    @forelse ($paragraphs as $paragraph)
                        <p class="whitespace-pre-wrap">{{ $paragraph }}</p>
                    @empty
                        @if ($caseStudy->summary)
                            <p>{{ $caseStudy->summary }}</p>
                        @endif
                    @endforelse
                </article>

                <div class="mx-auto mt-12 max-w-3xl flex flex-wrap items-center gap-3">
                    @if ($liveUrl)
                        <button type="button" class="btn btn-primary" data-case-live-open>
                            <span>{{ $liveLabel }}</span>
                            <x-ui.icons.monitor class="size-4" />
                        </button>
                    @endif
                    <a href="{{ route('contact') }}" @class(['btn', 'btn-primary' => ! $liveUrl, 'btn-ghost' => $liveUrl])>
                        <span>{{ __('pages.case_studies.start_project') }}</span>
                        <x-ui.icons.arrow-up-right class="size-4" />
                    </a>
                    <a href="{{ route('case-studies') }}" class="btn btn-ghost">
                        <span>{{ __('pages.case_studies.back') }}</span>
                    </a>
                </div>
            </div>
        </section>

        @if ($liveUrl)
            <div
                class="case-live-preview"
                data-case-live-dialog
                role="dialog"
                aria-modal="true"
                aria-labelledby="case-live-preview-title"
                hidden
            >
                <div class="case-live-preview-chrome">
                    <div class="case-live-preview-chrome-start">
                        <p id="case-live-preview-title" class="case-live-preview-title">{{ $caseStudy->title }}</p>
                        <p class="case-live-preview-hint">{{ __('pages.case_studies.live_preview_hint') }}</p>
                    </div>
                    <div class="case-live-preview-chrome-actions">
                        <a
                            href="{{ $liveUrl }}"
                            class="case-live-preview-link"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <span>{{ __('pages.case_studies.open_new_tab') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                        <button type="button" class="case-live-preview-close" data-case-live-close>
                            <x-ui.icons.x class="size-4" />
                            <span>{{ __('pages.case_studies.close_preview') }}</span>
                        </button>
                    </div>
                </div>

                <div class="case-live-preview-stage">
                    <div class="case-live-preview-loading" data-case-live-loading hidden>
                        <span class="case-live-preview-spinner" aria-hidden="true"></span>
                        <span>{{ __('pages.case_studies.live_preview_loading') }}</span>
                    </div>

                    <div class="case-live-preview-blocked" data-case-live-blocked hidden>
                        <p class="case-live-preview-blocked-title">{{ __('pages.case_studies.live_preview_blocked_title') }}</p>
                        <p class="case-live-preview-blocked-lede">{{ __('pages.case_studies.live_preview_blocked_lede') }}</p>
                        <a href="{{ $liveUrl }}" class="btn btn-primary mt-6" target="_blank" rel="noopener noreferrer">
                            <span>{{ __('pages.case_studies.open_new_tab') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </a>
                        <button type="button" class="btn btn-ghost mt-3" data-case-live-close>
                            <span>{{ __('pages.case_studies.close_preview') }}</span>
                        </button>
                    </div>

                    <iframe
                        class="case-live-preview-frame"
                        data-case-live-frame
                        title="{{ __('pages.case_studies.live_preview_frame_title', ['title' => $caseStudy->title]) }}"
                        referrerpolicy="no-referrer-when-downgrade"
                        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox allow-downloads"
                        loading="lazy"
                    ></iframe>
                </div>
            </div>
        @endif
    </div>
@endsection
