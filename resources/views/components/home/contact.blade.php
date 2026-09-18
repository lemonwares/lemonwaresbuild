<section id="contact" {{ $attributes->class('border-t border-border bg-white') }}>
    <div class="container-page py-16 sm:py-20">
        <div class="mx-auto max-w-2xl text-center">
            <p class="section-label mb-3">{{ __('site.home.contact_label') }}</p>
            <h2 class="heading mb-4">{{ __('site.home.contact_title') }}</h2>
            <p class="lede mx-auto mb-8">
                {{ __('site.home.contact_lede') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <x-ui.button href="mailto:{{ config('site.email') }}">
                    <x-ui.icons.mail class="size-4" />
                    <span>{{ config('site.email') }}</span>
                </x-ui.button>
                <x-ui.button href="tel:{{ config('site.phone_e164') }}" variant="ghost">
                    <x-ui.icons.phone class="size-4" />
                    <span>{{ config('site.phone') }}</span>
                </x-ui.button>
            </div>
        </div>
    </div>
</section>
