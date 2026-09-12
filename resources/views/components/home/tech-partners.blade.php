<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="grid items-start gap-4 lg:grid-cols-2 lg:gap-6">
            {{-- Technologies --}}
            <article class="tech-panel tech-panel-light flex flex-col rounded-4xl p-8 sm:p-10" data-reveal>
                <h2 class="max-w-md text-2xl font-bold leading-snug text-black sm:text-3xl">
                    {{ __('site.home.tech_title') }}
                </h2>

                <ul class="mt-8 flex flex-wrap gap-3 font-bold sm:gap-3.5" data-reveal-stagger>
                    @foreach (config('site.technologies') as $tech)
                        @php
                            $name = is_array($tech) ? ($tech['name'] ?? '') : $tech;
                            $logo = is_array($tech) ? ($tech['logo'] ?? null) : null;
                        @endphp
                        <li>
                            <span class="tech-pill">
                                @if ($logo)
                                    <img
                                        src="{{ asset($logo) }}"
                                        alt=""
                                        class="tech-pill-logo"
                                        width="20"
                                        height="20"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @endif
                                <span>{{ $name }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </article>

            {{-- Featured work (replaces fake partner logos until real brand marks exist) --}}
            <article class="tech-panel tech-panel-dark flex flex-col rounded-4xl p-8 sm:p-10 lg:mt-16" data-reveal>
                <h2 class="max-w-md text-2xl font-bold leading-snug text-white sm:text-3xl">
                    {{ __('site.home.partners_title') }}
                </h2>

                <ul class="mt-8 space-y-5">
                    @foreach (config('site.partners') as $partner)
                        <li>
                            <a href="{{ $partner['href'] ?? route('case-studies') }}" class="group block">
                                <p class="text-lg font-semibold tracking-tight text-white transition group-hover:text-blush">
                                    {{ $partner['name'] }}
                                </p>
                                @if (! empty($partner['meta']))
                                    <p class="mt-0.5 text-sm font-light text-white/55">{{ $partner['meta'] }}</p>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-8">
                    <a href="{{ route('case-studies') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blush transition hover:text-white">
                        {{ __('site.home.partners_cta') }}
                        <x-ui.icons.arrow-up-right class="size-4" />
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>
