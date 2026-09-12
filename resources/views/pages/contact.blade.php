@extends('layouts.app')

@section('title', __('pages.contact.meta_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('pages.contact.meta_description'))

@section('content')
    <x-layout.page-hero
        :eyebrow="__('site.pages.contact_eyebrow')"
        :title="__('site.pages.contact_title')"
        :lede="__('site.pages.contact_lede')"
        cta-href="#contact-content"
        :cta-label="__('site.pages.contact_cta')"
    />

    <section id="contact-content" class="border-t bg-white" style="border-color:var(--color-border);">
        <div class="container-page py-16 sm:py-20">
            <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">

                {{-- Left: contact channels --}}
                <div>
                    <p class="mb-8 body-text">{{ __('pages.contact.intro') }}</p>

                    <div class="flex flex-col gap-0 divide-y" style="border-color:var(--color-border);">
                        {{-- Email --}}
                        <div class="flex items-start gap-4 py-5">
                            <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl"
                                  style="background:var(--color-red-light); color:var(--color-red);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.15em]" style="color:var(--color-ink-3);">{{ __('pages.contact.email') }}</p>
                                <a href="mailto:{{ config('site.email') }}"
                                   class="mt-1 block text-sm font-semibold transition hover:underline"
                                   style="color:var(--color-red);">{{ config('site.email') }}</a>
                            </div>
                        </div>
                        {{-- Phone --}}
                        <div class="flex items-start gap-4 py-5">
                            <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl"
                                  style="background:var(--color-red-light); color:var(--color-red);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 012 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92l-.08 2z"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.15em]" style="color:var(--color-ink-3);">{{ __('pages.contact.phone') }}</p>
                                <a href="tel:{{ config('site.phone_e164') }}"
                                   class="mt-1 block text-sm font-semibold transition hover:underline"
                                   style="color:var(--color-red);">{{ config('site.phone') }}</a>
                            </div>
                        </div>
                        {{-- WhatsApp --}}
                        <div class="flex items-start gap-4 py-5">
                            <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl"
                                  style="background:var(--color-red-light); color:var(--color-red);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.15em]" style="color:var(--color-ink-3);">{{ __('pages.contact.whatsapp') }}</p>
                                <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer"
                                   class="mt-1 block text-sm font-semibold transition hover:underline"
                                   style="color:var(--color-red);">{{ __('pages.contact.chat_whatsapp') }}</a>
                            </div>
                        </div>
                        {{-- Address --}}
                        <div class="flex items-start gap-4 py-5">
                            <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl"
                                  style="background:var(--color-red-light); color:var(--color-red);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.15em]" style="color:var(--color-ink-3);">{{ __('pages.contact.address') }}</p>
                                <p class="mt-1 text-sm font-light" style="color:var(--color-ink-2);">{{ config('site.address') }}</p>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(config('site.address')) }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold transition hover:underline"
                                   style="color:var(--color-red);">
                                    {{ __('site.footer.get_directions') }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7h10v10M7 17 17 7"/></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: contact form --}}
                <div id="contact-form" class="scroll-mt-28 rounded-2xl border p-6 sm:p-8"
                     style="border-color:var(--color-border); background:var(--color-surface-2);">
                    <h2 class="mb-6 text-xl font-bold" style="color:var(--color-ink);">{{ __('pages.contact.form_title') }}</h2>

                    @php($contactFeedback = session('contact_feedback'))

                    @if ($contactFeedback)
                        <p @class([
                            'mb-4 rounded-xl px-4 py-3 text-sm',
                            'border border-emerald-200 bg-emerald-50 text-emerald-800' => ($contactFeedback['type'] ?? null) === 'success',
                            'auth-error' => ($contactFeedback['type'] ?? null) === 'error',
                        ])>{{ $contactFeedback['message'] ?? '' }}</p>
                    @endif

                    <form action="{{ route('contact.store') }}" method="post" class="flex flex-col gap-4" data-contact-form>
                        @csrf

                        <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                            <label for="contact-company">Company</label>
                            <input type="text" name="company" id="contact-company" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <div>
                            <label for="contact-full-name" class="auth-label">{{ __('pages.contact.full_name') }}</label>
                            <input
                                type="text"
                                name="full_name"
                                id="contact-full-name"
                                value="{{ old('full_name') }}"
                                placeholder="{{ __('pages.contact.full_name') }}"
                                autocomplete="name"
                                required
                                class="auth-input bg-white @error('full_name') border-red @enderror"
                            >
                            @error('full_name')
                                <p class="mt-1 text-sm" style="color:var(--color-red);">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="contact-email" class="auth-label">{{ __('pages.contact.email') }}</label>
                            <input
                                type="email"
                                name="email"
                                id="contact-email"
                                value="{{ old('email') }}"
                                placeholder="{{ __('pages.contact.email') }}"
                                autocomplete="email"
                                required
                                class="auth-input bg-white @error('email') border-red @enderror"
                            >
                            @error('email')
                                <p class="mt-1 text-sm" style="color:var(--color-red);">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="contact-subject" class="auth-label">{{ __('pages.contact.subject') }}</label>
                            <input
                                type="text"
                                name="subject"
                                id="contact-subject"
                                value="{{ old('subject') }}"
                                placeholder="{{ __('pages.contact.subject') }}"
                                required
                                class="auth-input bg-white @error('subject') border-red @enderror"
                            >
                            @error('subject')
                                <p class="mt-1 text-sm" style="color:var(--color-red);">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="contact-message" class="auth-label">{{ __('pages.contact.message') }}</label>
                            <textarea
                                name="message"
                                id="contact-message"
                                rows="5"
                                placeholder="{{ __('pages.contact.message') }}"
                                required
                                class="auth-input min-h-[8rem] resize-y bg-white @error('message') border-red @enderror"
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm" style="color:var(--color-red);">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-full py-3.5 text-sm" data-contact-button>
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-contact-spinner></span>
                            <span data-contact-label>{{ __('pages.contact.send') }}</span>
                            <span class="hidden" data-contact-loading>{{ __('pages.contact.sending') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
