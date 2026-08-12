<section id="about" {{ $attributes->class('section-band border-t border-border') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="mb-14 grid items-center gap-10 lg:grid-cols-2">
            <x-ui.reviews-carousel />

            <div>
                <h2 class="heading mb-4 lg:text-right">
                    {{ __('site.home.trust_title_before') }}
                    <span class="text-rose">{{ __('site.home.trust_title_accent') }}</span>
                </h2>
                <div class="flex lg:justify-end">
                    <x-ui.button href="{{ route('about') }}">
                        <x-ui.icons.arrow-up-right class="size-4" />
                        <span>{{ __('site.common.about_us') }}</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="mb-2 text-5xl font-bold text-rose">0</p>
                <p class="mb-2 text-base font-semibold text-on-blush">{{ __('site.home.trust_stat_incidents') }}</p>
                <p class="body-text">{{ __('site.home.trust_stat_incidents_body') }}</p>
            </div>
            <div>
                <p class="mb-2 text-5xl font-bold text-rose">99+</p>
                <p class="mb-2 text-base font-semibold text-on-blush">{{ __('site.home.trust_stat_uptime') }}</p>
                <p class="body-text">{{ __('site.home.trust_stat_uptime_body') }}</p>
            </div>
            <div>
                <p class="mb-2 text-5xl font-bold text-rose">{{ config('site.years_experience') }}+</p>
                <p class="mb-2 text-base font-semibold text-on-blush">{{ __('site.home.trust_stat_years') }}</p>
                <p class="body-text">{{ __('site.home.trust_stat_years_body') }}</p>
            </div>
            <div class="flex flex-col justify-between gap-4">
                <div>
                    <p class="mb-2 text-base font-semibold text-on-blush">{{ __('site.home.trust_backups') }}</p>
                    <p class="body-text">{{ __('site.home.trust_backups_body') }}</p>
                </div>
                <x-ui.button href="{{ route('contact') }}" variant="ghost" class="w-fit">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.common.contact_us') }}</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</section>
