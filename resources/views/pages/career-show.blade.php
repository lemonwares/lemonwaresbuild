@extends('layouts.app')

@section('title', $opening->title . ' — ' . __('pages.careers.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', $opening->summary ?: __('pages.careers.meta_description'))

@php
    $parseList = function (?string $text): array {
        if (blank($text)) {
            return [];
        }

        return collect(preg_split("/\r\n|\n|\r/", $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->map(fn ($line) => ltrim($line, "-•* \t"))
            ->filter()
            ->values()
            ->all();
    };

    $responsibilities = $parseList($opening->responsibilities);
    $requirements = $parseList($opening->requirements);
    $descriptionParagraphs = collect(preg_split("/\r\n\r\n|\n\n|\r\r/", (string) $opening->description) ?: [])
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
                <a href="{{ route('careers') }}#open-roles" class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 transition hover:text-white">
                    <x-ui.icons.arrow-left class="size-4" />
                    {{ __('pages.careers.back_to_roles') }}
                </a>
                <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-white/75">
                    {{ __('pages.careers.eyebrow') }}
                </p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl">
                    {{ $opening->title }}
                </h1>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($opening->type)
                        <span class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                            {{ $opening->type }}
                        </span>
                    @endif
                    @if ($opening->location)
                        <span class="rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-semibold text-white">
                            {{ $opening->location }}
                        </span>
                    @endif
                </div>
                @if ($opening->summary)
                    <p class="mt-5 max-w-2xl text-lg font-light text-white/90">
                        {{ $opening->summary }}
                    </p>
                @endif
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a
                        href="{{ route('contact', ['subject' => $opening->applySubject()]) }}"
                        class="btn bg-white text-rose hover:bg-blush"
                    >
                        <span>{{ __('pages.careers.apply') }}</span>
                    </a>
                    <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                        <x-ui.icons.message-circle class="size-4" />
                        {{ __('pages.contact.whatsapp') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-border bg-white" data-reveal>
        <div class="container-page grid gap-10 py-14 sm:py-16 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:gap-14">
            <div class="space-y-10">
                @if (count($descriptionParagraphs) > 0)
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-black">{{ __('pages.careers.detail_about') }}</h2>
                        <div class="mt-4 space-y-4 text-base font-light leading-relaxed text-on-blush/80">
                            @foreach ($descriptionParagraphs as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (count($responsibilities) > 0)
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-black">{{ __('pages.careers.detail_responsibilities') }}</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($responsibilities as $item)
                                <li class="flex gap-3 text-base font-light leading-relaxed text-on-blush/80">
                                    <span class="mt-2 size-1.5 shrink-0 rounded-full bg-rose" aria-hidden="true"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (count($requirements) > 0)
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-black">{{ __('pages.careers.detail_requirements') }}</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($requirements as $item)
                                <li class="flex gap-3 text-base font-light leading-relaxed text-on-blush/80">
                                    <span class="mt-2 size-1.5 shrink-0 rounded-full bg-rose" aria-hidden="true"></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <aside class="h-fit rounded-3xl border border-border bg-blush-soft/50 p-6 sm:p-8 lg:sticky lg:top-28">
                <h2 class="text-lg font-bold text-black">{{ __('pages.careers.detail_apply_title') }}</h2>
                <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ __('pages.careers.detail_apply_lede') }}</p>
                <a
                    href="{{ route('contact', ['subject' => $opening->applySubject()]) }}"
                    class="btn btn-primary mt-6 w-full justify-center"
                >
                    <span>{{ __('pages.careers.apply') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
                <a href="{{ route('careers') }}#open-roles" class="btn btn-ghost mt-3 w-full justify-center">
                    <span>{{ __('pages.careers.back_to_roles') }}</span>
                </a>
            </aside>
        </div>
    </section>
@endsection
