<section id="work" {{ $attributes->class('section-band border-t border-border') }}>
    <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-20" data-accordion-gallery>
        <div>
            <p class="section-label mb-3">{{ __('site.home.dev_label') }}</p>
            <h2 class="heading mb-4">
                {{ __('site.home.dev_title_before') }}
                <span class="text-rose">{{ __('site.home.dev_title_accent') }}</span>
                {{ __('site.home.dev_title_after') }}
            </h2>
            <p class="lede mb-8">
                {{ __('site.home.dev_lede') }}
            </p>

            <x-ui.accordion>
                <x-ui.accordion-item :title="__('site.home.dev_wp_title')" gallery-key="wordpress" :default-open="true">
                    {{ __('site.home.dev_wp_body') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item :title="__('site.home.dev_custom_title')" gallery-key="custom">
                    {{ __('site.home.dev_custom_body') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item :title="__('site.home.dev_mobile_title')" gallery-key="mobile">
                    {{ __('site.home.dev_mobile_body') }}
                </x-ui.accordion-item>
            </x-ui.accordion>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-ui.button href="#contact">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.home.dev_title_accent') }}</span>
                </x-ui.button>
                <a href="#contact" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-on-blush transition hover:text-rose">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-ui.icons.phone class="size-4" />
                    </span>
                    {{ __('site.home.contact_expert') }}
                </a>
            </div>
        </div>

        <div class="accordion-gallery relative min-h-[20rem] overflow-hidden rounded-3xl border border-border bg-slate lg:min-h-[28rem]">
            <img
                src="{{ asset('images/home/dev-wordpress.jpg') }}"
                alt="{{ __('site.home.dev_wp_title') }}"
                class="accordion-gallery-image is-active"
                data-gallery-image="wordpress"
                loading="eager"
                decoding="async"
            >
            <img
                src="{{ asset('images/home/dev-custom.jpg') }}"
                alt="{{ __('site.home.dev_custom_title') }}"
                class="accordion-gallery-image"
                data-gallery-image="custom"
                loading="lazy"
                decoding="async"
            >
            <img
                src="{{ asset('images/home/dev-mobile.jpg') }}"
                alt="{{ __('site.home.dev_mobile_title') }}"
                class="accordion-gallery-image"
                data-gallery-image="mobile"
                loading="lazy"
                decoding="async"
            >
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/35 via-transparent to-transparent"></div>
        </div>
    </div>
</section>
