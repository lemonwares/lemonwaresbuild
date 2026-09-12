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

            <div id="contact-form" class="card-tech scroll-mt-28 p-6 sm:p-8">
                <h2 class="footer-heading mb-4">{{ __('pages.contact.form_title') }}</h2>

                @php($contactFeedback = session('contact_feedback'))

                @if ($contactFeedback)
                    <p @class([
                        'mb-4 rounded-2xl px-4 py-3 text-sm',
                        'border border-emerald-200 bg-emerald-50 text-emerald-800' => ($contactFeedback['type'] ?? null) === 'success',
                        'border border-rose/20 bg-rose/5 text-rose' => ($contactFeedback['type'] ?? null) === 'error',
                    ])>{{ $contactFeedback['message'] ?? '' }}</p>
                @endif

                <form action="{{ route('contact.store') }}" method="post" class="flex flex-col gap-3" data-contact-form>
                    @csrf

                    <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="contact-company">Company</label>
                        <input type="text" name="company" id="contact-company" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <input
                            type="text"
                            name="full_name"
                            value="{{ old('full_name') }}"
                            placeholder="{{ __('pages.contact.full_name') }}"
                            autocomplete="name"
                            required
                            class="footer-input @error('full_name') border-rose/50 @enderror"
                        >
                        @error('full_name')
                            <p class="mt-1 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="{{ __('pages.contact.email') }}"
                            autocomplete="email"
                            required
                            class="footer-input @error('email') border-rose/50 @enderror"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <input
                            type="text"
                            name="subject"
                            value="{{ old('subject') }}"
                            placeholder="{{ __('pages.contact.subject') }}"
                            required
                            class="footer-input @error('subject') border-rose/50 @enderror"
                        >
                        @error('subject')
                            <p class="mt-1 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <textarea
                            name="message"
                            rows="5"
                            placeholder="{{ __('pages.contact.message') }}"
                            required
                            class="footer-input min-h-[8rem] resize-y @error('message') border-rose/50 @enderror"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-fit" data-contact-button>
                        <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-contact-spinner></span>
                        <span data-contact-label>{{ __('pages.contact.send') }}</span>
                        <span class="hidden" data-contact-loading>{{ __('pages.contact.sending') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </x-layout.page-content>
@endsection
