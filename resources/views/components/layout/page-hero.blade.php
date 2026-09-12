@props([
    'eyebrow'  => null,
    'title',
    'lede'     => null,
    'ctaHref'  => null,
    'ctaLabel' => null,
    'art'      => false,
    'artSrc'   => 'hero-cutout.webp',
    'artAlt'   => '',
])

@php
    $artEnabled = (bool) $art;
    $artWebp    = $artEnabled ? asset($artSrc) : null;
    $artPng     = $artEnabled ? asset(preg_replace('/\.webp$/i', '.png', $artSrc)) : null;
@endphp

<section {{ $attributes->class(['page-hero-section relative overflow-hidden']) }}>

    {{-- Subtle dot-grid — matches homepage hero texture --}}
    <div
        class="pointer-events-none absolute inset-0"
        aria-hidden="true"
        style="background-image:radial-gradient(circle, var(--color-border) 1px, transparent 1px);
               background-size:28px 28px; opacity:0.4;"
    ></div>

    {{-- Soft red glow top-right --}}
    <div
        class="pointer-events-none absolute right-0 top-0 h-full w-1/2"
        aria-hidden="true"
        style="background:radial-gradient(ellipse 55% 55% at 80% 15%,
               rgba(220,38,38,0.07) 0%, transparent 70%);"
    ></div>

    <div class="container-page relative z-10">

        @if ($artEnabled)
            {{-- ── Split layout when art is present ── --}}
            <div class="grid items-center gap-10 py-16 sm:py-20 lg:grid-cols-2 lg:gap-14 lg:py-24">

                {{-- Copy --}}
                <div>
                    @if ($eyebrow)
                        <p class="section-label mb-4">{{ $eyebrow }}</p>
                    @endif

                    <h1 class="mb-5 text-4xl font-bold tracking-tight sm:text-5xl lg:text-[3.25rem] lg:leading-[1.08]"
                        style="color:var(--color-ink);">
                        {{ $title }}
                    </h1>

                    @if ($lede)
                        <p class="lede mb-8">{{ $lede }}</p>
                    @endif

                    @if ($ctaHref && $ctaLabel)
                        <a href="{{ $ctaHref }}"
                           class="inline-flex items-center gap-2 text-sm font-semibold transition"
                           style="color:var(--color-ink-3);">
                            <span class="inline-flex size-8 items-center justify-center rounded-full border transition"
                                  style="border-color:var(--color-border-2);">
                                <x-ui.icons.arrow-down class="size-3.5 animate-bounce" />
                            </span>
                            {{ $ctaLabel }}
                        </a>
                    @endif

                    {{-- Mobile art --}}
                    <div class="relative mt-10 block lg:hidden" aria-hidden="true">
                        <div class="absolute inset-0 rounded-full blur-[70px]"
                             style="background:rgba(220,38,38,0.09);"></div>
                        <picture>
                            <source srcset="{{ $artWebp }}" type="image/webp">
                            <img src="{{ $artPng }}" alt="{{ $artAlt }}"
                                 class="hero-art relative z-10 mx-auto w-[min(100%,16rem)]"
                                 loading="eager" decoding="async">
                        </picture>
                    </div>
                </div>

                {{-- Desktop art --}}
                <div class="pointer-events-none relative hidden lg:flex lg:items-center lg:justify-end"
                     aria-hidden="true">
                    <div class="absolute left-1/2 top-1/2 size-[22rem] -translate-x-1/2 -translate-y-1/2
                                rounded-full blur-[90px]"
                         style="background:rgba(220,38,38,0.09);"></div>
                    <picture>
                        <source srcset="{{ $artWebp }}" type="image/webp">
                        <img src="{{ $artPng }}" alt=""
                             class="hero-art relative z-10 w-full max-w-[26rem]"
                             loading="eager" decoding="async" fetchpriority="high">
                    </picture>
                </div>

            </div>

        @else
            {{-- ── Single-column layout (no art) ── --}}
            <div class="max-w-2xl py-14 sm:py-18 lg:py-20">

                @if ($eyebrow)
                    <p class="section-label mb-4">{{ $eyebrow }}</p>
                @endif

                <h1 class="mb-5 text-4xl font-bold tracking-tight sm:text-5xl"
                    style="color:var(--color-ink);">
                    {{ $title }}
                </h1>

                @if ($lede)
                    <p class="lede mb-8">{{ $lede }}</p>
                @endif

                @if ($ctaHref && $ctaLabel)
                    <a href="{{ $ctaHref }}"
                       class="inline-flex items-center gap-2 text-sm font-semibold transition"
                       style="color:var(--color-ink-3);">
                        <span class="inline-flex size-8 items-center justify-center rounded-full border transition"
                              style="border-color:var(--color-border-2);">
                            <x-ui.icons.arrow-down class="size-3.5 animate-bounce" />
                        </span>
                        {{ $ctaLabel }}
                    </a>
                @endif

            </div>
        @endif

    </div>

</section>
