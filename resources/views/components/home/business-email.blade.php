<section {{ $attributes->class('border-t border-border bg-white') }} id="business-email">
    <div class="container-page grid items-stretch gap-12 py-16 lg:grid-cols-2 lg:py-20">
        <div class="order-2 flex lg:order-1">
            <x-home.mail-balance-panel class="h-full w-full" />
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
                <x-ui.accordion-item
                    :title="__('site.home.email_lw_title')"
                    logo="images/brands/mailemon-logo.png"
                    :default-open="true"
                >
                    {{ __('site.home.email_lw') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item
                    :title="__('site.home.email_m365_title')"
                    logo="images/brands/microsoft-365.svg"
                >
                    {{ __('site.home.email_m365') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item
                    :title="__('site.home.email_google_title')"
                    logo="images/brands/google-workspace.svg"
                >
                    {{ __('site.home.email_google') }}
                </x-ui.accordion-item>
            </x-ui.accordion>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-ui.button href="{{ route('email.plans') }}">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.home.feature_email') }}</span>
                </x-ui.button>
                <a href="{{ route('contact') }}" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-black transition hover:text-rose">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-ui.icons.phone class="size-4" />
                    </span>
                    {{ __('site.home.contact_expert') }}
                </a>
            </div>
        </div>
    </div>
</section>
