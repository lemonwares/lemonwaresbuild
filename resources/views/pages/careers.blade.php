@extends('layouts.app')

@section('title', __('pages.careers.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.careers.meta_description'))

@php
    $perks = __('pages.careers.perks');
    if (! is_array($perks)) {
        $perks = [];
    }
    $steps = __('pages.careers.steps');
    if (! is_array($steps)) {
        $steps = [];
    }
@endphp

@section('content')
    <section class="hosting-product-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-16 sm:py-20">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-widest text-white/75">
                    {{ __('pages.careers.eyebrow') }}
                </p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ __('pages.careers.title') }}
                </h1>
                <p class="mt-5 max-w-xl text-lg font-light text-white/90">
                    {{ __('pages.careers.lede') }}
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="#open-roles" class="btn bg-white text-rose hover:bg-blush">
                        <span>{{ __('pages.careers.cta') }}</span>
                    </a>
                    <a href="{{ route('team') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/90 transition hover:text-white">
                        <span>{{ __('pages.careers.cta_team') }}</span>
                        <x-ui.icons.arrow-up-right class="size-4" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="section-band border-t border-border" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="section-label mb-3">{{ __('pages.careers.perks_label') }}</p>
                <h2 class="heading">{{ __('pages.careers.perks_title') }}</h2>
                <p class="lede mx-auto mt-3">{{ __('pages.careers.perks_lede') }}</p>
            </div>
            <div class="hosting-feature-grid" data-reveal-stagger>
                @foreach ($perks as $perk)
                    <article class="hosting-feature-card">
                        <span class="dev-icon-badge mb-4" aria-hidden="true">
                            @if (($perk['icon'] ?? '') === 'rocket')
                                <x-ui.icons.rocket class="size-5 text-rose" />
                            @elseif (($perk['icon'] ?? '') === 'shield')
                                <x-ui.icons.shield-check class="size-5 text-rose" />
                            @elseif (($perk['icon'] ?? '') === 'message')
                                <x-ui.icons.message-circle class="size-5 text-rose" />
                            @else
                                <x-ui.icons.headset class="size-5 text-rose" />
                            @endif
                        </span>
                        <h3 class="text-lg font-bold text-black">{{ $perk['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $perk['body'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="open-roles" class="scroll-mt-28 border-t border-border bg-blush-soft/40" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.careers.roles_label') }}</p>
                <h2 class="heading">{{ __('pages.careers.roles_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.careers.roles_lede') }}</p>
            </div>

            <div class="space-y-4">
                @forelse ($openings as $opening)
                    <article class="rounded-3xl border border-border bg-white p-6 sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-xl font-bold tracking-tight text-black">{{ $opening->title }}</h3>
                                    @if ($opening->type)
                                        <span class="rounded-full border border-border bg-blush-soft/80 px-2.5 py-0.5 text-xs font-semibold text-on-blush/80">
                                            {{ $opening->type }}
                                        </span>
                                    @endif
                                </div>
                                @if ($opening->location)
                                    <p class="mt-2 text-sm font-medium text-on-blush/65">{{ $opening->location }}</p>
                                @endif
                                @if ($opening->summary)
                                    <p class="mt-3 max-w-2xl text-sm font-light leading-relaxed text-on-blush/75">{{ $opening->summary }}</p>
                                @endif
                            </div>
                            <a href="{{ route('careers.show', $opening) }}" class="btn btn-primary shrink-0">
                                <span>{{ __('pages.careers.view_role') }}</span>
                                <x-ui.icons.arrow-up-right class="size-4" />
                            </a>
                        </div>
                    </article>
                @empty
                    <article class="rounded-3xl border border-border bg-white p-8 text-center">
                        <p class="text-base font-light text-on-blush/75">{{ __('pages.careers.roles_empty') }}</p>
                    </article>
                @endforelse
            </div>

            <div class="mt-8 rounded-3xl border border-dashed border-rose/30 bg-white/70 p-6 sm:p-8">
                <h3 class="text-lg font-bold text-black">{{ __('pages.careers.open_title') }}</h3>
                <p class="mt-2 max-w-2xl text-sm font-light leading-relaxed text-on-blush/75">{{ __('pages.careers.open_lede') }}</p>
                <a
                    href="{{ route('contact', ['subject' => __('pages.careers.open_subject')]) }}"
                    class="btn btn-ghost mt-5"
                >
                    <span>{{ __('pages.careers.open_cta') }}</span>
                </a>
            </div>
        </div>
    </section>

    <section class="border-t border-border bg-white" data-reveal>
        <div class="container-page py-16 sm:py-20">
            <div class="mb-10 max-w-2xl">
                <p class="section-label mb-3">{{ __('pages.careers.process_label') }}</p>
                <h2 class="heading">{{ __('pages.careers.process_title') }}</h2>
                <p class="lede mt-3">{{ __('pages.careers.process_lede') }}</p>
            </div>
            <ol class="grid gap-6 sm:grid-cols-3">
                @foreach ($steps as $index => $step)
                    <li class="rounded-3xl border border-border bg-blush-soft/40 p-6">
                        <span class="inline-flex size-9 items-center justify-center rounded-full bg-rose text-sm font-bold text-white">
                            {{ $index + 1 }}
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-black">{{ $step['title'] ?? '' }}</h3>
                        <p class="mt-2 text-sm font-light leading-relaxed text-on-blush/75">{{ $step['body'] ?? '' }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="border-t border-border bg-white">
        <div class="container-page py-14 sm:py-16">
            <h2 class="heading mb-3">{{ __('pages.careers.help_title') }}</h2>
            <p class="lede mb-6">{{ __('pages.careers.help_lede') }}</p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('contact', ['subject' => __('pages.careers.open_subject')]) }}" class="btn btn-primary">
                    <span>{{ __('pages.careers.help_cta') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </a>
                <a href="{{ route('about') }}" class="btn btn-ghost">
                    <span>{{ __('site.nav.about') }}</span>
                </a>
            </div>
        </div>
    </section>
@endsection
