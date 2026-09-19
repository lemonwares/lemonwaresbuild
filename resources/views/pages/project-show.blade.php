@extends('layouts.app')

@section('title', $project->title . ' — ' . __('pages.projects.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', $project->summary ?: __('pages.projects.meta_description'))

@php
    $paragraphs = collect(preg_split("/\r\n\r\n|\n\n|\r\r/", (string) $project->description) ?: [])
        ->map(fn ($p) => trim((string) $p))
        ->filter()
        ->values()
        ->all();
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="max-w-3xl">
                <a href="{{ route('projects') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 transition hover:text-white">
                    <x-ui.icons.arrow-left class="size-4" />
                    {{ __('pages.projects.back') }}
                </a>
                <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-white/75">
                    {{ __('pages.projects.eyebrow') }}
                </p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ $project->title }}
                </h1>
                @if ($project->client_name)
                    <p class="mt-4 text-sm font-light text-white/80">{{ $project->client_name }}</p>
                @endif
                @if ($project->summary)
                    <p class="mt-5 max-w-2xl text-lg font-light text-white/90">{{ $project->summary }}</p>
                @endif
                @if ($project->project_url)
                    <a href="{{ $project->project_url }}" target="_blank" rel="noopener noreferrer" class="btn mt-8 bg-white text-rose hover:bg-blush">
                        {{ __('pages.projects.visit') }}
                    </a>
                @endif
            </div>
        </div>
    </section>

    <x-layout.page-content>
        @if ($project->cover_path)
            <div class="mx-auto mb-10 max-w-3xl overflow-hidden rounded-md">
                <img src="{{ $project->coverUrl() }}" alt="" class="aspect-[16/9] w-full object-cover" />
            </div>
        @endif
        <article class="mx-auto max-w-3xl space-y-5 body-text">
            @forelse ($paragraphs as $paragraph)
                <p class="whitespace-pre-wrap">{{ $paragraph }}</p>
            @empty
                <p>{{ $project->summary }}</p>
            @endforelse
        </article>
    </x-layout.page-content>
@endsection
