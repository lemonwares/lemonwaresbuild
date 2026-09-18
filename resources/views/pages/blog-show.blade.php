@extends('layouts.app')

@section('title', $post->title . ' — ' . __('site.nav.blog') . ' — ' . config('site.short_name'))
@section('meta_description', $post->excerpt ?: __('pages.blog.meta_description'))

@php
    $paragraphs = collect(preg_split("/\r\n\r\n|\n\n|\r\r/", (string) $post->body) ?: [])
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
                <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 transition hover:text-white">
                    <x-ui.icons.arrow-left class="size-4" />
                    {{ __('pages.blog.back') }}
                </a>
                <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-white/75">
                    {{ __('pages.blog.eyebrow') }}
                </p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ $post->title }}
                </h1>
                <p class="mt-4 text-sm font-light text-white/80">
                    {{ $post->published_at?->timezone(config('app.timezone'))->format('d M Y') }}
                    @if ($post->author)
                        · {{ $post->author->name }}
                    @endif
                </p>
                @if ($post->excerpt)
                    <p class="mt-5 max-w-2xl text-lg font-light text-white/90">{{ $post->excerpt }}</p>
                @endif
            </div>
        </div>
    </section>

    <x-layout.page-content>
        @if ($post->cover_path)
            <div class="mx-auto mb-10 max-w-3xl overflow-hidden rounded-md">
                <img src="{{ asset('storage/' . $post->cover_path) }}" alt="" class="aspect-[16/9] w-full object-cover" />
            </div>
        @endif
        <article class="mx-auto max-w-3xl space-y-5 body-text">
            @foreach ($paragraphs as $paragraph)
                <p class="whitespace-pre-wrap">{{ $paragraph }}</p>
            @endforeach
        </article>
    </x-layout.page-content>
@endsection
