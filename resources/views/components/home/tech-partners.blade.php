{{-- TECH + PARTNERS — white bg, two asymmetric panels --}}
<section {{ $attributes->class('border-t bg-white') }} style="border-color:var(--color-border);">
    <div class="container-page py-20 sm:py-24">

        <div class="grid items-start gap-5 lg:grid-cols-2">

            {{-- ══ LEFT: Technologies ══ --}}
            <article class="rounded-3xl border p-8 sm:p-10"
                     style="border-color:var(--color-border); background:var(--color-surface-2);"
                     data-reveal>

                <p class="section-label mb-4">Technologies</p>
                <h2 class="mb-8 text-2xl font-bold leading-snug sm:text-3xl"
                    style="color:var(--color-ink); max-width:26rem;">
                    {{ __('site.home.tech_title') }}
                </h2>

                <ul class="flex flex-wrap gap-2.5" data-reveal-stagger>
                    @foreach (config('site.technologies') as $tech)
                        @php
                            $name = is_array($tech) ? ($tech['name'] ?? '') : $tech;
                            $logo = is_array($tech) ? ($tech['logo'] ?? null) : null;
                        @endphp
                        <li>
                            <span class="tech-pill">
                                @if ($logo)
                                    <img src="{{ asset($logo) }}" alt=""
                                         class="tech-pill-logo" width="18" height="18"
                                         loading="lazy" decoding="async">
                                @endif
                                <span class="text-sm font-medium" style="color:var(--color-ink-2);">{{ $name }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </article>

            {{-- ══ RIGHT: Featured work — drops lower for asymmetry ══ --}}
            <article class="relative overflow-hidden rounded-3xl p-8 sm:p-10 lg:mt-12"
                     style="background:var(--color-ink);"
                     data-reveal>

                {{-- Decorative ring --}}
                <div class="pointer-events-none absolute -right-10 -top-10 size-48 rounded-full border-[32px] border-white/5" aria-hidden="true"></div>

                <p class="section-label mb-4" style="color:rgba(255,255,255,0.4);">Featured Work</p>
                <h2 class="mb-8 text-2xl font-bold leading-snug text-white sm:text-3xl"
                    style="max-width:26rem;">
                    {{ __('site.home.partners_title') }}
                </h2>

                <ul class="divide-y" style="border-color:rgba(255,255,255,0.07);">
                    @foreach (config('site.partners') as $partner)
                        <li>
                            <a href="{{ $partner['href'] ?? route('case-studies') }}"
                               class="group flex items-center justify-between gap-4 py-4 transition">
                                <div>
                                    <p class="text-base font-bold text-white transition group-hover:text-red-mid"
                                       style="--tw-text-opacity:1;">
                                        {{ $partner['name'] }}
                                    </p>
                                    @if (!empty($partner['meta']))
                                        <p class="mt-0.5 text-sm font-light" style="color:rgba(255,255,255,0.4);">
                                            {{ $partner['meta'] }}
                                        </p>
                                    @endif
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     class="size-4 shrink-0 -rotate-45 opacity-0 transition-all duration-200 group-hover:opacity-100"
                                     style="color:var(--color-red-mid);"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                     aria-hidden="true">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    <a href="{{ route('case-studies') }}"
                       class="inline-flex items-center gap-2 text-sm font-bold transition"
                       style="color:var(--color-red-mid);"
                       onmouseover="this.style.color='#fff'"
                       onmouseout="this.style.color='var(--color-red-mid)'">
                        {{ __('site.home.partners_cta') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </article>

        </div>
    </div>
</section>
