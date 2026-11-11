<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page py-16 sm:py-20">
        <div>
            {{-- Technologies: logos only --}}
            <article class="tech-panel tech-panel-light flex flex-col rounded-4xl p-8 sm:p-10" data-reveal>
                <h2 class="max-w-md text-2xl font-bold leading-snug text-black sm:text-3xl">
                    {{ __('site.home.tech_title') }}
                </h2>

                <ul class="mt-8 flex flex-wrap gap-3 sm:gap-3.5" data-reveal-stagger>
                    @foreach (config('site.technologies') as $tech)
                        @php
                            $name = is_array($tech) ? ($tech['name'] ?? '') : $tech;
                            $logo = is_array($tech) ? ($tech['logo'] ?? null) : null;
                        @endphp
                        @continue(blank($logo))
                        <li>
                            <span class="tech-logo" title="{{ $name }}">
                                <img
                                    src="{{ asset($logo) }}"
                                    alt="{{ $name }}"
                                    class="tech-logo-img"
                                    width="28"
                                    height="28"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </span>
                        </li>
                    @endforeach
                </ul>
            </article>

        </div>
    </div>
</section>
