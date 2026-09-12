@props([
    'eyebrow' => null,
    'title',
    'lede' => null,
    'ctaHref' => null,
    'ctaLabel' => null,
    'art' => false,
    'artSrc' => 'images/hero-cutout.webp',
    'artAlt' => '',
])

@php
    $artEnabled = (bool) $art;
    $artWebp = $artEnabled ? asset($artSrc) : null;
    $artPng = $artEnabled
        ? asset(preg_replace('/\.webp$/i', '.png', $artSrc) ?: $artSrc)
        : null;
@endphp

<section {{ $attributes->class(['bg-white', 'hero-section' => $artEnabled]) }}>
    <div @class([
        'container-page',
        'hero-container' => $artEnabled,
        'py-10 sm:py-14' => ! $artEnabled,
        'hero-container-pad' => $artEnabled,
    ])>
        <div @class(['hero-shell' => $artEnabled, 'relative' => $artEnabled])>
            <div @class([
                'relative rounded-4xl bg-rose px-8 py-16 text-white sm:px-14 sm:py-24',
                'overflow-hidden' => ! $artEnabled,
                'hero-card' => $artEnabled,
            ])>
                <div class="hero-copy relative z-10 max-w-xl">
                    <p class="mb-4 text-base font-medium uppercase tracking-[0.18em] text-white/80">
                        {{ $eyebrow ?? config('site.tagline') }}
                    </p>
                    <h1 class="mb-4 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ $title }}
                    </h1>
                    @if ($lede)
                        <p class="mb-10 text-lg font-light text-white/90">
                            {{ $lede }}
                        </p>
                    @endif
                    @if ($ctaHref && $ctaLabel)
                        <a href="{{ $ctaHref }}" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-medium text-white transition hover:text-blush">
                            <x-ui.icons.arrow-down class="size-4 animate-bounce" />
                            {{ $ctaLabel }}
                        </a>
                    @endif

                    @if ($artEnabled)
                        <div class="hero-breakout-mobile mt-10 lg:hidden">
                            <picture>
                                <source srcset="{{ $artWebp }}" type="image/webp">
                                <img
                                    src="{{ $artPng }}"
                                    alt="{{ $artAlt }}"
                                    class="hero-breakout-img mx-auto w-[min(100%,18rem)]"
                                    loading="eager"
                                    decoding="async"
                                >
                            </picture>
                        </div>
                    @endif
                </div>

                @unless ($artEnabled)
                    <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 lg:block" aria-hidden="true">
                        <div class="absolute bottom-0 right-0 h-[120%] w-[85%] rounded-tl-[4rem] border-[28px] border-white/15"></div>
                        <div class="absolute bottom-0 right-0 h-[95%] w-[65%] rounded-tl-[3rem] border-[28px] border-white/20"></div>
                        <div class="absolute bottom-0 right-0 h-[70%] w-[45%] rounded-tl-[2rem] border-[28px] border-white/25"></div>
                    </div>
                @endunless
            </div>

            @if ($artEnabled)
                <div class="hero-breakout pointer-events-none hidden lg:block" aria-hidden="true">
                    <picture>
                        <source srcset="{{ $artWebp }}" type="image/webp">
                        <img
                            src="{{ $artPng }}"
                            alt=""
                            class="hero-breakout-img"
                            loading="eager"
                            decoding="async"
                        >
                    </picture>
                </div>
            @endif
        </div>
    </div>
</section>
