{{--
    HOMEPAGE HERO — v2
    White bg, split layout.
    Left:  eyebrow → giant headline → lede → CTAs → service pill links
    Right: ink card composition — status bar + 3 service tiles + cutout breakout
    Bottom: marquee ticker
--}}
<section class="relative overflow-hidden bg-white" {{ $attributes }}>

    {{-- Dot-grid texture --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true"
         style="background-image:radial-gradient(circle, var(--color-border) 1px, transparent 1px);
                background-size:28px 28px; opacity:0.5;"></div>

    <div class="container-page relative z-10">
        <div class="grid items-center gap-8 pb-0 pt-24 sm:pt-28
                    lg:grid-cols-[1fr_1fr] lg:gap-12 lg:pt-28 xl:gap-16">

            {{-- ══════ LEFT: Copy ══════ --}}
            <div class="flex flex-col justify-center">

                {{-- Eyebrow --}}
                <div class="mb-6 inline-flex w-fit items-center gap-2.5 rounded-full border px-4 py-1.5"
                     style="border-color:var(--color-border); background:var(--color-surface-2);">
                    <span class="size-1.5 rounded-full" style="background:var(--color-red);" aria-hidden="true"></span>
                    <span class="text-[11px] font-bold uppercase tracking-[0.2em]"
                          style="color:var(--color-ink-3);">{{ __('site.home.hero_eyebrow') }}</span>
                </div>

                {{-- Headline --}}
                <h1 class="hero-headline mb-5">
                    @php
                        $full   = __('site.home.hero_title');
                        $parts  = explode(',', $full);
                        $last   = array_pop($parts);
                        $before = implode(',', $parts) . ($parts ? ',' : '');
                    @endphp
                    <span style="color:var(--color-ink);">{{ trim($before) }}</span><br>
                    <span style="color:var(--color-red);">{{ trim($last) }}</span>
                </h1>

                {{-- Lede --}}
                <p class="hero-lede mb-8">{{ __('site.home.hero_lede') }}</p>

                {{-- CTAs --}}
                <div class="mb-9 flex flex-wrap items-center gap-3">
                    <a href="#hosting-intro"
                       class="btn btn-primary gap-2 px-7 py-3.5 text-sm">
                        {{ __('site.common.get_started') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost px-7 py-3.5 text-sm">
                        {{ __('site.common.contact_us') }}
                    </a>
                </div>

                {{-- Service quick-links --}}
                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        ['label' => 'Web Hosting',     'href' => '#hosting-plans'],
                        ['label' => 'Business Email',  'href' => route('email.plans')],
                        ['label' => 'VPS Servers',     'href' => route('hosting.specifications', ['plan' => 'vps'])],
                        ['label' => 'Web & Mobile Dev','href' => '#work'],
                    ] as $pill)
                        <a href="{{ $pill['href'] }}"
                           class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5
                                  text-xs font-semibold transition hover:border-red hover:text-red"
                           style="border-color:var(--color-border); color:var(--color-ink-2);">
                            <span class="size-1 rounded-full" style="background:var(--color-red);"></span>
                            {{ $pill['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ══════ RIGHT: Visual composition card ══════ --}}
            <div class="relative hidden lg:block" aria-hidden="true">

                {{-- Main ink card --}}
                <div class="relative overflow-hidden rounded-3xl pt-14"
                     style="background:var(--color-ink); min-height:32rem;">

                    {{-- Decorative rings inside card --}}
                    <div class="pointer-events-none absolute -right-14 -top-14 size-56 rounded-full border-[36px] border-white/5"></div>
                    <div class="pointer-events-none absolute -bottom-10 -left-10 size-40 rounded-full border-[24px] border-white/4"></div>

                    {{-- Soft red glow inside card --}}
                    <div class="pointer-events-none absolute right-0 top-0 h-48 w-48 rounded-full blur-[80px]"
                         style="background:rgba(220,38,38,0.18);"></div>

                    {{-- Status bar --}}
                    <div class="relative z-10 mx-6 mb-5 flex items-center justify-between rounded-2xl border px-4 py-2.5"
                         style="border-color:rgba(255,255,255,0.08); background:rgba(255,255,255,0.05);">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.8)]"></span>
                            <span class="text-xs font-semibold text-white/70">All systems operational</span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/35">
                            {{ config('site.domain') }}
                        </span>
                    </div>

                    {{-- 3 service mini-cards --}}
                    <div class="relative z-10 mx-6 mb-5 grid grid-cols-3 gap-3">
                        @foreach ([
                            ['icon' => 'M3 12a9 9 0 1018 0 9 9 0 00-18 0m9-4v8m-4-4h8', 'label' => 'cPanel Hosting', 'value' => 'Web'],
                            ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'label' => 'Lemon Mail', 'value' => 'Email'],
                            ['icon' => 'M5 12h14M12 5l7 7-7 7', 'label' => 'AMD EPYC', 'value' => 'VPS'],
                        ] as $svc)
                            <div class="flex flex-col items-center gap-2 rounded-2xl p-4 text-center"
                                 style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08);">
                                <span class="inline-flex size-9 items-center justify-center rounded-xl"
                                      style="background:rgba(220,38,38,0.18); color:var(--color-red);">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="{{ $svc['icon'] }}"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-white/40">{{ $svc['value'] }}</p>
                                    <p class="mt-0.5 text-xs font-semibold text-white/80">{{ $svc['label'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Stat row inside card --}}
                    <div class="relative z-10 mx-6 mb-0 flex items-center gap-0 divide-x overflow-hidden rounded-2xl"
                         style="divide-color:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04);">
                        @foreach ([['val'=>'99%','lbl'=>'Uptime'],['val'=>'0','lbl'=>'Incidents'],['val'=>config('site.years_experience').'+','lbl'=>'Years']] as $stat)
                            <div class="flex flex-1 flex-col items-center py-3" style="border-color:rgba(255,255,255,0.08);">
                                <span class="text-lg font-bold" style="color:var(--color-red);">{{ $stat['val'] }}</span>
                                <span class="text-[10px] font-medium text-white/40">{{ $stat['lbl'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Cutout breaks out of the card from the bottom --}}
                    <div class="relative z-20 -mb-2 mt-2 flex justify-center">
                        <picture>
                            <source srcset="{{ asset('hero-cutout.webp') }}" type="image/webp">
                            <img src="{{ asset('hero-cutout.png') }}" alt=""
                                 class="hero-art w-[min(88%,20rem)]"
                                 loading="eager" decoding="async" fetchpriority="high">
                        </picture>
                    </div>
                </div>

                {{-- Card outer glow --}}
                <div class="pointer-events-none absolute -bottom-6 left-1/2 h-20 w-3/4 -translate-x-1/2 rounded-full blur-[40px]"
                     style="background:rgba(0,0,0,0.15);"></div>
            </div>

        </div>

        {{-- Mobile art — below copy --}}
        <div class="relative mx-auto my-6 w-[min(70vw,16rem)] lg:hidden" aria-hidden="true">
            <div class="absolute inset-0 rounded-full blur-[60px]"
                 style="background:rgba(220,38,38,0.10);"></div>
            <picture>
                <source srcset="{{ asset('hero-cutout.webp') }}" type="image/webp">
                <img src="{{ asset('hero-cutout.png') }}" alt=""
                     class="hero-art relative z-10 w-full"
                     loading="eager" decoding="async" fetchpriority="high">
            </picture>
        </div>
    </div>

    {{-- ══════ Marquee ticker ══════ --}}
    <div class="ticker-wrap relative z-10 overflow-hidden border-t py-3.5"
         style="border-color:var(--color-border);">
        <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-16 bg-gradient-to-r from-white to-transparent" aria-hidden="true"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-16 bg-gradient-to-l from-white to-transparent" aria-hidden="true"></div>

        @php
            $items = ['Cloud Hosting','Business Email','Web Development','Mobile Apps','VPS Servers','cPanel & Plesk','WordPress Hosting','DNS Management','12+ Years Experience'];
            $all   = array_merge($items, $items);
        @endphp

        <div class="ticker-track flex w-max items-center" aria-hidden="true">
            @foreach ($all as $item)
                <span class="flex items-center">
                    <span class="whitespace-nowrap px-5 text-[11px] font-bold uppercase tracking-[0.2em]"
                          style="color:var(--color-ink-3);">{{ $item }}</span>
                    <span class="size-1 shrink-0 rounded-full" style="background:var(--color-red-mid);"></span>
                </span>
            @endforeach
        </div>
    </div>

</section>
