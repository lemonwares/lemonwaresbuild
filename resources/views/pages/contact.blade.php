@extends('layouts.app')

@section('title', __('pages.contact.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.contact.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.contact_eyebrow')"
        :title="__('site.pages.contact_title')"
        :lede="__('site.pages.contact_lede')"
        cta-href="#page-content"
        :cta-label="__('site.pages.contact_cta')"
        :art="true"
        art-src="images/heroes/contact.webp"
    />

    <x-layout.page-content wide>
        <div class="grid gap-10 lg:grid-cols-2">
            <div class="space-y-8">
                <p class="body-text">{{ __('pages.contact.intro') }}</p>

                <div class="space-y-6">
                    <div>
                        <h2 class="footer-heading mb-2">{{ __('pages.contact.email') }}</h2>
                        <a href="mailto:{{ config('site.email') }}" class="link text-base font-light">{{ config('site.email') }}</a>
                    </div>
                    <div>
                        <h2 class="footer-heading mb-2">{{ __('pages.contact.phone') }}</h2>
                        <a href="tel:{{ config('site.phone_e164') }}" class="link text-base font-light">{{ config('site.phone') }}</a>
                    </div>
                    <div>
                        <h2 class="footer-heading mb-2">{{ __('pages.contact.whatsapp') }}</h2>
                        <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="link text-base font-light">{{ __('pages.contact.chat_whatsapp') }}</a>
                    </div>
                    <div>
                        <h2 class="footer-heading mb-2">{{ __('pages.contact.address') }}</h2>
                        <p class="body-text">{{ config('site.address') }}</p>
                        <x-ui.button href="https://www.google.com/maps/search/?api=1&query={{ urlencode(config('site.address')) }}" target="_blank" rel="noopener noreferrer" class="mt-4">
                            <span>{{ __('site.footer.get_directions') }}</span>
                            <x-ui.icons.arrow-up-right class="size-4" />
                        </x-ui.button>
                    </div>
                </div>
            </div>

            <div class="card-tech p-6 sm:p-8">
                <h2 class="footer-heading mb-4">{{ __('pages.contact.form_title') }}</h2>
                <form action="#" method="post" class="flex flex-col gap-3">
                    @csrf
                    <input type="text" name="full_name" placeholder="{{ __('pages.contact.full_name') }}" autocomplete="name" class="footer-input">
                    <input type="email" name="email" placeholder="{{ __('pages.contact.email') }}" autocomplete="email" class="footer-input">
                    <input type="text" name="subject" placeholder="{{ __('pages.contact.subject') }}" class="footer-input">
                    <textarea name="message" rows="5" placeholder="{{ __('pages.contact.message') }}" class="footer-input min-h-[8rem] resize-y"></textarea>
                    <button type="submit" class="btn btn-primary w-fit">{{ __('pages.contact.send') }}</button>
                </form>
            </div>
        </div>
    </x-layout.page-content>
@endsection
