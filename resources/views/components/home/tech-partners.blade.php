<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="grid items-start gap-4 lg:grid-cols-2 lg:gap-6">
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

            {{-- Mail Lemon journey (replaces case-study list) --}}
            <article class="tech-panel tech-panel-light flex flex-col rounded-4xl border border-border p-8 sm:p-10 lg:mt-16" data-reveal>
                <p class="section-label mb-3">{{ __('site.home.inbox_label') }}</p>
                <h2 class="max-w-md text-2xl font-bold leading-snug text-black sm:text-3xl">
                    {{ __('site.home.inbox_title') }}
                </h2>
                <p class="mt-3 max-w-md text-base font-light leading-relaxed text-black">
                    {{ __('site.home.inbox_lede') }}
                </p>

                <ol class="mt-8 space-y-6">
                    @foreach ([1, 2, 3] as $step)
                        <li class="flex gap-4">
                            <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-rose text-sm font-bold text-white" aria-hidden="true">
                                {{ $step }}
                            </span>
                            <div class="min-w-0 pt-0.5">
                                <p class="text-lg font-semibold tracking-tight text-black">
                                    {{ __('site.home.inbox_step_'.$step.'_title') }}
                                </p>
                                <p class="mt-1 text-sm font-light leading-relaxed text-black">
                                    {{ __('site.home.inbox_step_'.$step.'_body') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-8">
                    <a href="{{ route('email.plans') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-black transition hover:text-rose">
                        {{ __('site.home.inbox_cta') }}
                        <x-ui.icons.arrow-up-right class="size-4" />
                    </a>
                </div>
            </article>
        </div>
    </div>
</section>
