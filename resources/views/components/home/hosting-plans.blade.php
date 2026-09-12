{{-- HOSTING PLANS — off-white bg, 3-column plan cards --}}
<section id="hosting-plans" {{ $attributes->class('border-t') }}
         style="border-color:var(--color-border); background:var(--color-surface-2);">

    <div class="container-page py-20 sm:py-24">

        {{-- Section header --}}
        <div class="mb-12 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-xl">
                <p class="section-label mb-3">{{ __('site.home.plans_label') }}</p>
                <h2 class="heading">{{ __('site.home.plans_title') }}</h2>
            </div>
            <p class="max-w-xs text-sm font-light sm:text-right" style="color:var(--color-ink-3);">
                {{ __('site.home.plans_lede') }}
            </p>
        </div>

        {{-- Plan cards --}}
        <div class="grid gap-5 md:grid-cols-3">

            {{-- ── cPanel — RED featured ── --}}
            <article
                class="group relative flex flex-col overflow-hidden rounded-3xl p-8 transition-all duration-300
                       hover:-translate-y-1 hover:shadow-2xl"
                style="background:var(--color-red); color:#fff;"
            >
                {{-- Decorative rings --}}
                <div class="pointer-events-none absolute -right-10 -top-10 size-44 rounded-full border-[32px] border-white/10" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-8 -left-8 size-32 rounded-full border-[20px] border-white/8" aria-hidden="true"></div>

                <div class="relative z-10 flex flex-1 flex-col">
                    <div class="mb-6">
                        <span class="inline-block rounded-full bg-white/15 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white/80">
                            cPanel
                        </span>
                        <h3 class="mt-3 text-2xl font-bold text-white sm:text-[1.6rem]">
                            {{ __('site.home.cpanel_title') }}
                        </h3>
                        <p class="mt-2.5 text-sm font-light leading-relaxed text-white/75">
                            {{ __('site.home.cpanel_summary') }}
                        </p>
                    </div>

                    <ul class="mb-8 flex flex-1 flex-col gap-2.5">
                        @foreach (config('site.hosting_plans.cpanel.highlights', []) as $item)
                            <li class="flex items-start gap-2.5 text-sm text-white/85">
                                <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-white/20">
                                    <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}"
                       class="mt-auto inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold transition hover:bg-red-light"
                       style="color:var(--color-red);">
                        {{ __('site.home.cpanel_cta') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </article>

            {{-- ── Plesk — WHITE clean ── --}}
            <article
                class="group flex flex-col overflow-hidden rounded-3xl border bg-white p-8 transition-all duration-300
                       hover:-translate-y-1 hover:shadow-xl"
                style="border-color:var(--color-border);"
            >
                <div class="mb-6">
                    <span class="inline-block rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em]"
                          style="background:var(--color-surface-2); color:var(--color-ink-3);">
                        Plesk
                    </span>
                    <h3 class="mt-3 text-2xl font-bold sm:text-[1.6rem]" style="color:var(--color-ink);">
                        {{ __('site.home.plesk_title') }}
                    </h3>
                    <p class="mt-2.5 text-sm font-light leading-relaxed" style="color:var(--color-ink-3);">
                        {{ __('site.home.plesk_summary') }}
                    </p>
                </div>

                <ul class="mb-8 flex flex-1 flex-col gap-2.5">
                    @foreach (config('site.hosting_plans.plesk.highlights', []) as $item)
                        <li class="flex items-start gap-2.5 text-sm" style="color:var(--color-ink-2);">
                            <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full"
                                  style="background:var(--color-red-light);">
                                <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="var(--color-red)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            </span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('hosting.specifications', ['plan' => 'plesk']) }}"
                   class="btn btn-ghost mt-auto justify-center py-3 text-sm">
                    {{ __('site.home.plesk_cta') }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </article>

            {{-- ── VPS — INK/BLACK ── --}}
            <article
                class="group relative flex flex-col overflow-hidden rounded-3xl p-8 transition-all duration-300
                       hover:-translate-y-1 hover:shadow-2xl"
                style="background:var(--color-ink); color:#fff;"
            >
                {{-- Decorative rings --}}
                <div class="pointer-events-none absolute -right-8 -top-8 size-40 rounded-full border-[28px] border-white/6" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-6 right-6 size-24 rounded-full border-[16px] border-white/5" aria-hidden="true"></div>

                <div class="relative z-10 flex flex-1 flex-col">
                    <div class="mb-6">
                        <span class="inline-block rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white/60">
                            AMD EPYC VPS
                        </span>
                        <h3 class="mt-3 text-2xl font-bold text-white sm:text-[1.6rem]">
                            {{ __('site.home.vps_title') }}
                        </h3>
                        <p class="mt-2.5 text-sm font-light leading-relaxed text-white/60">
                            {{ __('site.home.vps_summary') }}
                        </p>
                    </div>

                    <ul class="mb-8 flex flex-1 flex-col gap-2.5">
                        @foreach (config('site.hosting_plans.vps.highlights', []) as $item)
                            <li class="flex items-start gap-2.5 text-sm text-white/75">
                                <span class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-white/15">
                                    <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}"
                       class="mt-auto inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-bold transition"
                       style="background:var(--color-red); color:#fff;"
                       onmouseover="this.style.background='var(--color-red-dark)'"
                       onmouseout="this.style.background='var(--color-red)'">
                        {{ __('site.home.vps_cta') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </article>

        </div>
    </div>
</section>
