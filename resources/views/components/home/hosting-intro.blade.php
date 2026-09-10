{{-- HOSTING INTRO — white bg, split layout, stat tiles right side --}}
<section id="hosting-intro" {{ $attributes->class('border-t bg-white') }} style="border-color:var(--color-border);">
    <div class="container-page py-20 sm:py-24 lg:py-28">
        <div class="grid items-center gap-14 lg:grid-cols-2 lg:gap-20">

            {{-- ══ LEFT: Copy ══ --}}
            <div>
                <p class="section-label mb-4">{{ __('site.home.hosting_eyebrow') }}</p>

                <h2 class="mb-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-[3.25rem] lg:leading-[1.08]"
                    style="color:var(--color-ink);">
                    {{ __('site.home.hosting_title_before') }}
                    <span style="color:var(--color-red);">{{ __('site.home.hosting_title_accent') }}</span>
                </h2>

                <p class="lede mb-10">{{ __('site.home.hosting_lede') }}</p>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="#hosting-plans" class="btn btn-primary gap-2 px-7 py-3.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                        <span>{{ __('site.home.hosting_plans_cta') }}</span>
                    </a>
                    <a href="#contact"
                       class="inline-flex shrink-0 items-center gap-2.5 text-sm font-semibold transition"
                       style="color:var(--color-ink-2);">
                        <span class="inline-flex size-9 items-center justify-center rounded-full border transition"
                              style="border-color:var(--color-border-2); color:var(--color-red);">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8a19.79 19.79 0 01-3.07-8.68A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92l-.08 2z"/>
                            </svg>
                        </span>
                        {{ __('site.home.contact_expert') }}
                    </a>
                </div>
            </div>

            {{-- ══ RIGHT: Stat grid + image ══ --}}
            <div class="flex flex-col gap-4">

                {{-- 2×2 stat tiles --}}
                <div class="grid grid-cols-2 gap-4">

                    {{-- Uptime --}}
                    <div class="flex flex-col justify-between rounded-2xl border p-6"
                         style="border-color:var(--color-border); background:var(--color-surface-2);">
                        <p class="text-[2.75rem] font-bold leading-none tracking-tight"
                           style="color:var(--color-red);">99%</p>
                        <div class="mt-4">
                            <p class="text-sm font-bold" style="color:var(--color-ink);">Uptime Focus</p>
                            <p class="mt-1 text-xs font-light" style="color:var(--color-ink-3);">
                                Built to keep your site available when customers need it.
                            </p>
                        </div>
                    </div>

                    {{-- Experience --}}
                    <div class="flex flex-col justify-between rounded-2xl border p-6"
                         style="border-color:var(--color-border); background:var(--color-surface-2);">
                        <p class="text-[2.75rem] font-bold leading-none tracking-tight"
                           style="color:var(--color-red);">{{ config('site.years_experience') }}+</p>
                        <div class="mt-4">
                            <p class="text-sm font-bold" style="color:var(--color-ink);">Years Experience</p>
                            <p class="mt-1 text-xs font-light" style="color:var(--color-ink-3);">
                                Hosting, email, web and mobile for growing businesses.
                            </p>
                        </div>
                    </div>

                    {{-- Zero incidents --}}
                    <div class="flex flex-col justify-between rounded-2xl border p-6"
                         style="border-color:var(--color-border); background:var(--color-surface-2);">
                        <p class="text-[2.75rem] font-bold leading-none tracking-tight"
                           style="color:var(--color-red);">0</p>
                        <div class="mt-4">
                            <p class="text-sm font-bold" style="color:var(--color-ink);">Data-Loss Incidents</p>
                            <p class="mt-1 text-xs font-light" style="color:var(--color-ink-3);">
                                Secure infrastructure with daily backups and monitoring.
                            </p>
                        </div>
                    </div>

                    {{-- Support --}}
                    <div class="relative flex flex-col justify-between overflow-hidden rounded-2xl p-6"
                         style="background:var(--color-red);">
                        {{-- Decorative ring --}}
                        <div class="pointer-events-none absolute -right-6 -top-6 size-28 rounded-full border-[20px] border-white/10" aria-hidden="true"></div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-7 text-white/80" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 12h6m-3-3v6M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                        </svg>
                        <div class="mt-4">
                            <p class="text-sm font-bold text-white">Expert Support</p>
                            <p class="mt-1 text-xs font-light text-white/70">
                                Real help from people who know hosting.
                            </p>
                        </div>
                    </div>

                </div>

                {{-- Photo strip --}}
                <div class="relative overflow-hidden rounded-2xl" style="aspect-ratio:16/6;">
                    <img
                        src="{{ asset('images/services/hosting.jpg') }}"
                        alt="Hosting infrastructure"
                        class="size-full object-cover object-center"
                        loading="lazy"
                        decoding="async"
                    >
                    {{-- Overlay so photo doesn't compete with tiles --}}
                    <div class="absolute inset-0"
                         style="background:linear-gradient(90deg, rgba(255,255,255,0.18) 0%, rgba(0,0,0,0.22) 100%);"></div>
                </div>

            </div>

        </div>
    </div>
</section>
