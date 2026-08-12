<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="grid items-start gap-4 lg:grid-cols-2 lg:gap-6">
            {{-- Technologies --}}
            <article class="tech-panel tech-panel-light flex flex-col rounded-4xl p-8 sm:p-10">
                <h2 class="max-w-md text-2xl font-bold leading-snug text-black sm:text-3xl">
                    {{ __('site.home.tech_title') }}
                </h2>

                <ul class="mt-8 flex flex-wrap gap-3 font-bold sm:gap-3.5">
                    @foreach (config('site.technologies') as $tech)
                        <li class="font-extrabold">
                            <span class="tech-pill">{{ $tech }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>

            {{-- Partners --}}
            <article class="tech-panel tech-panel-dark flex flex-col rounded-4xl p-8 sm:p-10 lg:mt-16">
                <h2 class="max-w-md text-2xl font-bold leading-snug text-white sm:text-3xl">
                    {{ __('site.home.partners_title') }}
                </h2>

                <ul class="mt-8 grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3">
                    @foreach (config('site.partners') as $partner)
                        <li class="flex items-center">
                            @if (! empty($partner['logo']))
                                <img
                                    src="{{ asset($partner['logo']) }}"
                                    alt="{{ $partner['name'] }}"
                                    class="partner-logo-img max-h-8 w-auto brightness-0 invert"
                                    loading="lazy"
                                >
                            @else
                                <span class="partner-logo-text">{{ $partner['name'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </article>
        </div>
    </div>
</section>
