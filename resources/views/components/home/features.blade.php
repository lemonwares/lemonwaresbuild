{{-- FEATURES BENTO — white bg, editorial grid --}}
<section {{ $attributes->class('border-t bg-white') }} style="border-color:var(--color-border);">
    <div class="container-page py-20 sm:py-24">

        {{-- Section label --}}
        <p class="section-label mb-10">Why Lemonwares</p>

        {{-- ── Bento grid ──
             Desktop: 5-col × 2-row
             Mobile:  single column stacked
        ── --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:grid-rows-2">

            {{-- ① HERO TILE — red, 2×2 — "Foundation" --}}
            <article
                class="relative flex flex-col justify-between overflow-hidden rounded-3xl p-8
                       sm:col-span-2 lg:col-span-2 lg:row-span-2"
                style="background:var(--color-red); min-height:18rem;"
            >
                {{-- Decorative rings --}}
                <div class="pointer-events-none absolute -right-12 -top-12 size-52 rounded-full border-[40px] border-white/10" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-10 -left-10 size-36 rounded-full border-[28px] border-white/8" aria-hidden="true"></div>

                <div class="relative z-10">
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.22em] text-white/60">
                        {{ __('site.home.features_uptime') }}
                    </p>
                    <p class="mt-3 font-bold leading-none text-white"
                       style="font-size:clamp(4rem,8vw,6rem); letter-spacing:-0.04em;">
                        99%
                    </p>
                </div>

                <div class="relative z-10 mt-6">
                    <div class="mb-4 h-px w-12 bg-white/25"></div>
                    <h3 class="text-xl font-bold leading-snug text-white sm:text-2xl">
                        {{ __('site.home.features_foundation') }}
                    </h3>
                </div>
            </article>

            {{-- ② Fast performance --}}
            <article
                class="flex flex-col justify-between rounded-3xl border p-6
                       lg:col-span-2"
                style="border-color:var(--color-border); background:var(--color-surface-2);"
            >
                <span class="inline-flex size-11 items-center justify-center rounded-2xl"
                      style="background:var(--color-red-light); color:var(--color-red);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </span>
                <div class="mt-6">
                    <h3 class="text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.feature_fast') }}
                    </h3>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        SSD-backed servers tuned for low latency and quick page loads.
                    </p>
                </div>
            </article>

            {{-- ③ Secure --}}
            <article
                class="flex flex-col justify-between rounded-3xl border p-6"
                style="border-color:var(--color-border); background:var(--color-surface-2);"
            >
                <span class="inline-flex size-11 items-center justify-center rounded-2xl"
                      style="background:var(--color-red-light); color:var(--color-red);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </span>
                <div class="mt-6">
                    <h3 class="text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.feature_secure') }}
                    </h3>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        SSL, firewalls, daily backups — security baked in by default.
                    </p>
                </div>
            </article>

            {{-- ④ WordPress / Dev — wide ink tile --}}
            <article
                class="relative flex flex-col justify-between overflow-hidden rounded-3xl p-8
                       sm:col-span-2 lg:col-span-3"
                style="background:var(--color-ink); min-height:12rem;"
            >
                <div class="pointer-events-none absolute -right-8 -top-8 size-36 rounded-full border-[24px] border-white/6" aria-hidden="true"></div>

                <span class="relative z-10 inline-flex size-11 items-center justify-center rounded-2xl bg-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-white" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
                    </svg>
                </span>

                <div class="relative z-10 mt-6">
                    <h3 class="text-lg font-bold text-white">{{ __('site.home.feature_wp_title') }}</h3>
                    <p class="mt-2 max-w-sm text-sm font-light text-white/55">
                        {{ __('site.home.feature_wp_body') }}
                    </p>
                </div>
            </article>

            {{-- ⑤ Lemon Mail — link tile --}}
            <a
                href="{{ route('email.plans') }}"
                class="group flex flex-col justify-between rounded-3xl border p-6 transition-all duration-200
                       hover:border-red hover:shadow-md"
                style="border-color:var(--color-border); background:var(--color-surface-2);"
            >
                <span class="inline-flex size-11 items-center justify-center rounded-2xl transition"
                      style="background:var(--color-red-light); color:var(--color-red);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/>
                    </svg>
                </span>
                <div class="mt-6">
                    <h3 class="text-base font-bold transition group-hover:text-red" style="color:var(--color-ink);">
                        {{ __('site.home.feature_email') }}
                    </h3>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        Branded inboxes on your domain. Simple plans, instant setup.
                    </p>
                    <p class="mt-3 text-xs font-bold transition group-hover:text-red"
                       style="color:var(--color-red);">
                        View plans →
                    </p>
                </div>
            </a>

            {{-- ⑥ Expert support --}}
            <article
                class="flex flex-col justify-between rounded-3xl border p-6"
                style="border-color:var(--color-border); background:var(--color-surface-2);"
            >
                <span class="inline-flex size-11 items-center justify-center rounded-2xl"
                      style="background:var(--color-red-light); color:var(--color-red);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/>
                    </svg>
                </span>
                <div class="mt-6">
                    <h3 class="text-base font-bold" style="color:var(--color-ink);">
                        {{ __('site.home.feature_support') }}
                    </h3>
                    <p class="mt-1.5 text-sm font-light" style="color:var(--color-ink-3);">
                        Real human support from a team that knows your stack.
                    </p>
                </div>
            </article>

        </div>
    </div>
</section>
