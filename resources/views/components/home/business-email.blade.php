<section {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-20">
        <div class="relative order-2 overflow-hidden rounded-3xl border border-border bg-blush-soft p-8 min-h-[20rem] lg:order-1 lg:min-h-[28rem]">
            <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(224,69,69,0.08),transparent_60%)]"></div>
            <div class="relative z-10 flex h-full flex-col justify-end gap-4">
                <span class="inline-flex size-14 items-center justify-center rounded-2xl bg-rose text-white">
                    <x-ui.icons.mail class="size-7" />
                </span>
                <p class="text-2xl font-bold text-on-blush">hello@yourcompany.com</p>
                <p class="body-text">{{ __('site.home.email_brand_sample') }}</p>
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
                <x-ui.accordion-item title="Microsoft 365" :default-open="true">
                    {{ __('site.home.email_m365') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item title="Google Workspace">
                    {{ __('site.home.email_google') }}
                </x-ui.accordion-item>

                <x-ui.accordion-item title="Titan Business Email">
                    {{ __('site.home.email_titan') }}
                </x-ui.accordion-item>
            </x-ui.accordion>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-ui.button href="#contact">
                    <x-ui.icons.arrow-up-right class="size-4" />
                    <span>{{ __('site.home.feature_email') }}</span>
                </x-ui.button>
                <a href="#contact" class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-on-blush transition hover:text-rose">
                    <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-blush text-rose">
                        <x-ui.icons.phone class="size-4" />
                    </span>
                    {{ __('site.home.contact_expert') }}
                </a>
            </div>
        </div>
    </div>
</section>
