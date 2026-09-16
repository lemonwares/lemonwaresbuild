@props([
    'eyebrow' => null,
    'title',
    'lede' => null,
    'ctaHref' => null,
    'ctaLabel' => null,
    'art' => false,
    'artSrc' => 'images/hero-cutout.webp',
    'artAlt' => '',
    'ink' => 'light',
])

@php
    $artMode = $art === true || $art === 1 || $art === '1' || $art === 'true'
        ? 'image'
        : (is_string($art) && $art !== '' && $art !== 'false' ? $art : null);
    $artEnabled = filled($artMode);
    $darkInk = $ink === 'dark';
    $artPath = (string) $artSrc;
    $artIsWebp = str_ends_with(strtolower($artPath), '.webp');
    $artPrimary = $artMode === 'image' ? asset($artPath) : null;
    $artFallback = $artMode === 'image' && $artIsWebp
        ? asset(preg_replace('/\.webp$/i', '.png', $artPath) ?: $artPath)
        : $artPrimary;
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
                'relative rounded-4xl px-8 py-16 sm:px-14 sm:py-24',
                'bg-rose text-white' => ! $darkInk,
                'bg-blush text-black' => $darkInk,
                'overflow-hidden' => ! $artEnabled,
                'hero-card' => $artEnabled,
            ])>
                <div class="hero-copy relative z-10 max-w-xl">
                    <p @class([
                        'mb-4 text-base font-medium uppercase tracking-[0.18em]',
                        'text-white/80' => ! $darkInk,
                        'text-black' => $darkInk,
                    ])>
                        {{ $eyebrow ?? config('site.tagline') }}
                    </p>
                    <h1 class="mb-4 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                        {{ $title }}
                    </h1>
                    @if ($lede)
                        <p @class([
                            'mb-10 text-lg font-light',
                            'text-white/90' => ! $darkInk,
                            'text-black' => $darkInk,
                        ])>
                            {{ $lede }}
                        </p>
                    @endif
                    @if ($ctaHref && $ctaLabel)
                        <a
                            href="{{ $ctaHref }}"
                            @class([
                                'inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-medium transition',
                                'text-white hover:text-blush' => ! $darkInk,
                                'text-black hover:text-rose' => $darkInk,
                            ])
                        >
                            <x-ui.icons.arrow-down class="size-4 animate-bounce" />
                            {{ $ctaLabel }}
                        </a>
                    @endif

                    @if ($artEnabled)
                        <div class="hero-breakout-mobile mt-10 lg:hidden">
                            @if ($artMode === 'messages')
                                <x-home.mail-messages-stage class="mx-auto w-[min(100%,20rem)]" :float="! $darkInk" />
                            @elseif ($artMode === 'mail')
                                <x-home.mail-mockup class="mx-auto w-[min(100%,20rem)]" compact />
                            @elseif ($artIsWebp)
                                <picture>
                                    <source srcset="{{ $artPrimary }}" type="image/webp">
                                    <img
                                        src="{{ $artFallback }}"
                                        alt="{{ $artAlt }}"
                                        class="hero-breakout-img mx-auto w-[min(100%,18rem)]"
                                        loading="eager"
                                        decoding="async"
                                    >
                                </picture>
                            @else
                                <img
                                    src="{{ $artPrimary }}"
                                    alt="{{ $artAlt }}"
                                    class="hero-breakout-img mx-auto w-[min(100%,18rem)]"
                                    loading="eager"
                                    decoding="async"
                                >
                            @endif
                        </div>
                    @endif
                </div>

                @unless ($artEnabled)
                    <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 lg:block" aria-hidden="true">
                        <div @class([
                            'absolute bottom-0 right-0 h-[120%] w-[85%] rounded-tl-[4rem] border-[28px]',
                            'border-white/15' => ! $darkInk,
                            'border-black/10' => $darkInk,
                        ])></div>
                        <div @class([
                            'absolute bottom-0 right-0 h-[95%] w-[65%] rounded-tl-[3rem] border-[28px]',
                            'border-white/20' => ! $darkInk,
                            'border-black/10' => $darkInk,
                        ])></div>
                        <div @class([
                            'absolute bottom-0 right-0 h-[70%] w-[45%] rounded-tl-[2rem] border-[28px]',
                            'border-white/25' => ! $darkInk,
                            'border-black/10' => $darkInk,
                        ])></div>
                    </div>
                @endunless
            </div>

            @if ($artEnabled)
                <div class="hero-breakout pointer-events-none hidden lg:block" aria-hidden="true">
                    @if ($artMode === 'messages')
                        <x-home.mail-messages-stage class="hero-breakout-img hero-breakout-messages" :float="! $darkInk" />
                    @elseif ($artMode === 'mail')
                        <x-home.mail-mockup class="hero-breakout-img" />
                    @elseif ($artIsWebp)
                        <picture>
                            <source srcset="{{ $artPrimary }}" type="image/webp">
                            <img
                                src="{{ $artFallback }}"
                                alt=""
                                class="hero-breakout-img"
                                loading="eager"
                                decoding="async"
                            >
                        </picture>
                    @else
                        <img
                            src="{{ $artPrimary }}"
                            alt=""
                            class="hero-breakout-img"
                            loading="eager"
                            decoding="async"
                        >
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
