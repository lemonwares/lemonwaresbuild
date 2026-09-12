{{-- TRUST / STATS — white bg, reviews + 4-col stats --}}
<section id="about" {{ $attributes->class('border-t bg-white') }}
         style="border-color:var(--color-border);">

    <div class="container-page py-20 sm:py-24">

        {{-- ── Top: Reviews + headline ── --}}
        <div class="mb-14 grid items-center gap-10 lg:grid-cols-2 lg:gap-16">

            {{-- Reviews carousel --}}
            <x-ui.reviews-carousel />

            {{-- Headline + CTA --}}
            <div>
                <p class="section-label mb-4">{{ __('site.home.trust_title_accent') }}</p>
                <h2 class="heading mb-6 lg:text-right">
                    {{ __('site.home.trust_title_before') }}
                    <span style="color:var(--color-red);">{{ __('site.home.trust_title_accent') }}</span>
                </h2>
                <div class="flex lg:justify-end">
                    <a href="{{ route('about') }}" class="btn btn-ghost gap-2 px-6 py-3 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                        <span>{{ __('site.common.about_us') }}</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Thin red divider --}}
        <div class="mb-14 h-px w-full" style="background:var(--color-red-light);"></div>

        {{-- ── Bottom: 4-col stats ── --}}
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Zero incidents --}}
            <div class="flex flex-col gap-3">
                <p class="font-bold leading-none tracking-tight"
                   style="font-size:clamp(3rem,5vw,4rem); color:var(--color-red);">0</p>
                <div>
                    <p class="text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.trust_stat_incidents') }}
                    </p>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        {{ __('site.home.trust_stat_incidents_body') }}
                    </p>
                </div>
            </div>

            {{-- Uptime --}}
            <div class="flex flex-col gap-3">
                <p class="font-bold leading-none tracking-tight"
                   style="font-size:clamp(3rem,5vw,4rem); color:var(--color-red);">99%</p>
                <div>
                    <p class="text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.trust_stat_uptime') }}
                    </p>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        {{ __('site.home.trust_stat_uptime_body') }}
                    </p>
                </div>
            </div>

            {{-- Years --}}
            <div class="flex flex-col gap-3">
                <p class="font-bold leading-none tracking-tight"
                   style="font-size:clamp(3rem,5vw,4rem); color:var(--color-red);">
                    {{ config('site.years_experience') }}+
                </p>
                <div>
                    <p class="text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.trust_stat_years') }}
                    </p>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        {{ __('site.home.trust_stat_years_body') }}
                    </p>
                </div>
            </div>

            {{-- Daily Backups — different treatment: icon + CTA --}}
            <div class="flex flex-col justify-between rounded-2xl border p-6"
                 style="border-color:var(--color-border); background:var(--color-surface-2);">
                <div>
                    {{-- Shield icon --}}
                    <span class="inline-flex size-11 items-center justify-center rounded-xl"
                          style="background:var(--color-red-light); color:var(--color-red);">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </span>
                    <p class="mt-4 text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.trust_backups') }}
                    </p>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        {{ __('site.home.trust_backups_body') }}
                    </p>
                </div>
                <a href="{{ route('contact') }}"
                   class="btn btn-ghost mt-6 justify-center py-2.5 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                    {{ __('site.common.contact_us') }}
                </a>
            </div>

        </div>
    </div>
</section>
