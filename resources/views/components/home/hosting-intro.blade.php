<section id="hosting-intro" {{ $attributes->class('bg-white border-t border-border') }}>
    <div class="container-page grid items-center gap-12 py-14 lg:grid-cols-2 lg:gap-16 lg:py-20">
        <div>
            <x-home.eyebrow class="mb-6" :label="__('site.home.hosting_eyebrow')" />

            <h2 class="mb-5 text-4xl font-bold tracking-tight text-on-blush sm:text-5xl lg:text-[3.25rem] lg:leading-[1.1]">
                {{ __('site.home.hosting_title_before') }}
                <span class="italic text-rose">{{ __('site.home.hosting_title_accent') }}</span>
            </h2>

            <p class="lede mb-8">
                {{ __('site.home.hosting_lede') }}
            </p>

            <div class="flex flex-wrap items-center gap-4">
                <x-ui.button href="#hosting-plans">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.home.hosting_plans_cta') }}</span>
                </x-ui.button>

                <a href="#contact" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-on-blush transition hover:text-rose">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-ui.icons.phone class="size-4" />
                    </span>
                    {{ __('site.home.contact_expert') }}
                </a>
            </div>
        </div>

        <x-home.hero-visual />
    </div>
</section>
