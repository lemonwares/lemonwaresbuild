<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-20">
        <div class="email-visual relative order-2 min-h-[20rem] overflow-hidden rounded-3xl border border-border lg:order-1 lg:min-h-[28rem]">
            <img
                src="{{ asset('images/home/business-email.jpg') }}"
                alt="{{ __('site.home.feature_email') }}"
                class="absolute inset-0 size-full object-cover object-center"
                loading="lazy"
                decoding="async"
            >
            <div class="email-visual-overlay absolute inset-0"></div>
            <div class="relative z-10 flex h-full min-h-[20rem] flex-col justify-end gap-3 p-8 lg:min-h-[28rem]">
                <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-rose text-white shadow-lg shadow-rose/30">
                    <x-ui.icons.mail class="size-7" />
                </span>
                <p class="text-2xl font-bold text-white drop-shadow-sm">hello@yourcompany.com</p>
                <p class="max-w-sm text-base font-light text-white/85">{{ __('site.home.email_brand_sample') }}</p>
            </div>
        </div>

        <div class="order-1 lg:order-2">
            <p class="section-label mb-3">{{ __('site.home.email_label') }}</p>
            <h2 class="heading mb-4">
                {{ __('site.home.email_title_before') }}
                <span class="italic text-rose">{{ __('site.home.email_title_accent') }}</span>
                {{ __('site.home.email_title_after') }}
            </h2>
            <p class="lede mb-8">
                {{ __('site.home.email_lede') }}
            </p>

            <x-ui.accordion>
                <x-ui.accordion-item :title="__('site.home.email_lw_title')" :default-open="true">
                    {{ __('site.home.email_lw') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item :title="__('site.home.email_m365_title')">
                    {{ __('site.home.email_m365') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item :title="__('site.home.email_google_title')">
                    {{ __('site.home.email_google') }}
                </x-ui.accordion-item>
            </x-ui.accordion>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-ui.button href="{{ route('email.plans') }}">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.home.feature_email') }}</span>
                </x-ui.button>
                <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-on-blush transition hover:text-rose">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-ui.icons.phone class="size-4" />
                    </span>
                    {{ __('site.home.contact_expert') }}
                </a>
            </div>
        </div>
    </div>
</section>
