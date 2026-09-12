@php
    $directionsHref = 'https://www.google.com/maps/search/?api=1&query=' . urlencode(config('site.address'));

    $businessLinks = [
        ['label' => __('site.nav.about'),          'href' => route('about')],
        ['label' => __('site.footer.hosting_plans'),'href' => url('/#hosting-plans')],
        ['label' => __('site.nav.email'),           'href' => route('email.plans')],
        ['label' => __('site.common.contact_us'),   'href' => route('contact')],
        ['label' => __('site.footer.case_studies'), 'href' => route('case-studies')],
        ['label' => __('site.nav.faq'),             'href' => route('faq')],
        [
            'label' => auth()->check() ? __('account.account_title') : __('site.common.client_login'),
            'href'  => auth()->check() ? route('account.show') : route('login'),
        ],
    ];

    $legalLinks = [
        ['label' => __('site.footer.terms'),   'href' => route('terms')],
        ['label' => __('site.footer.refund'),  'href' => route('refund-policy')],
        ['label' => __('site.footer.privacy'), 'href' => route('privacy-policy')],
        ['label' => __('site.footer.usage'),   'href' => route('usage-terms')],
    ];
@endphp

<footer {{ $attributes->class('site-footer') }}>

    {{-- ── Main footer body ── --}}
    <div class="container-page py-14 sm:py-16">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-8">

            {{-- Brand column --}}
            <div class="flex flex-col gap-6 lg:col-span-4">
                <x-layout.logo />

                <p class="text-sm font-light leading-relaxed" style="color:var(--color-ink-3); max-width:22rem;">
                    {{ config('site.tagline') }} — reliable cloud infrastructure and digital solutions for growing businesses.
                </p>

                {{-- Social icons --}}
                <div class="flex items-center gap-2">
                    @foreach (config('site.social') as $social)
                        <a
                            href="{{ $social['href'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="footer-social"
                            aria-label="{{ $social['label'] }}"
                        >
                            <x-dynamic-component :component="'ui.icons.' . $social['icon']" class="size-4" />
                        </a>
                    @endforeach
                </div>

                {{-- Address --}}
                <div>
                    <p class="footer-heading mb-1.5">{{ __('site.footer.address') }}</p>
                    <p class="text-sm font-light" style="color:var(--color-ink-3);">{{ config('site.address') }}</p>
                </div>

                <a href="{{ $directionsHref }}" target="_blank" rel="noopener noreferrer"
                   class="btn btn-ghost w-fit gap-2 px-5 py-2.5 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    {{ __('site.footer.get_directions') }}
                </a>
            </div>

            {{-- Contact channels --}}
            <div class="flex flex-col gap-0 divide-y lg:col-span-3" style="border-color:var(--color-border);">
                <x-layout.footer-contact-card
                    :title="__('site.footer.enquiries')"
                    :value="config('site.email')"
                    href="mailto:{{ config('site.email') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/>
                    </svg>
                    <span>{{ __('site.footer.email_us') }}</span>
                </x-layout.footer-contact-card>

                <x-layout.footer-contact-card
                    :title="__('site.footer.quick_chat')"
                    :value="config('site.phone')"
                    :href="config('site.whatsapp')"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                    <span>{{ __('site.footer.whatsapp') }}</span>
                </x-layout.footer-contact-card>

                <x-layout.footer-contact-card
                    :title="__('site.footer.call')"
                    :value="config('site.phone')"
                    href="tel:{{ config('site.phone_e164') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.8 19.79 19.79 0 012 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92l-.08 2z"/>
                    </svg>
                    <span>{{ __('site.footer.dial') }}</span>
                </x-layout.footer-contact-card>
            </div>

            {{-- Nav links --}}
            <div class="grid gap-8 sm:grid-cols-2 lg:col-span-2 lg:grid-cols-1">
                <nav aria-label="{{ __('site.footer.the_business') }}">
                    <p class="footer-heading mb-3">{{ __('site.footer.the_business') }}</p>
                    <ul class="footer-link-list">
                        @foreach ($businessLinks as $link)
                            <li>
                                <a href="{{ $link['href'] }}" class="footer-link"
                                   @if (!empty($link['external'])) target="_blank" rel="noopener noreferrer" @endif>
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <nav aria-label="{{ __('site.footer.legal') }}">
                    <p class="footer-heading mb-3">{{ __('site.footer.legal') }}</p>
                    <ul class="footer-link-list">
                        @foreach ($legalLinks as $link)
                            <li>
                                <a href="{{ $link['href'] }}" class="footer-link">{{ $link['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>

            {{-- Newsletter --}}
            <div class="lg:col-span-3">
                <div id="footer-newsletter" class="scroll-mt-28">
                    <p class="footer-heading mb-1.5">{{ __('site.footer.newsletter') }}</p>
                    <p class="mb-5 text-sm font-light" style="color:var(--color-ink-3);">
                        Stay updated on hosting tips, new services, and special offers.
                    </p>

                    @php $newsletterFeedback = session('newsletter_feedback'); @endphp

                    @if ($newsletterFeedback)
                        <div @class([
                            'mb-4 flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm',
                            'border-emerald-200 bg-emerald-50 text-emerald-800' => ($newsletterFeedback['type'] ?? null) === 'success',
                            'border-sky-200 bg-sky-50 text-sky-800'             => ($newsletterFeedback['type'] ?? null) === 'info',
                        ])>{{ $newsletterFeedback['message'] ?? '' }}</div>
                    @endif

                    <form action="{{ route('newsletter.subscribe') }}" method="post"
                          class="flex flex-col gap-3" data-newsletter-form>
                        @csrf
                        <label class="sr-only" for="footer-full-name">{{ __('site.footer.full_name') }}</label>
                        <input
                            id="footer-full-name" type="text" name="full_name"
                            placeholder="{{ __('site.footer.full_name') }}"
                            autocomplete="name" value="{{ old('full_name') }}"
                            class="footer-input" required data-newsletter-input
                        >
                        <label class="sr-only" for="footer-email">{{ __('site.footer.email') }}</label>
                        <input
                            id="footer-email" type="email" name="email"
                            placeholder="{{ __('site.footer.email') }}"
                            autocomplete="email" value="{{ old('email') }}"
                            class="footer-input" required data-newsletter-input
                        >
                        <button type="submit" class="btn btn-primary w-full" data-newsletter-button>
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/30 border-t-white" data-newsletter-spinner></span>
                            <span data-newsletter-label>{{ __('site.footer.send') }}</span>
                            <span class="hidden" data-newsletter-loading>{{ __('site.footer.sending') }}</span>
                        </button>
                    </form>

                    {{-- Google rating badge --}}
                    <div class="mt-6 flex items-center gap-3">
                        <svg class="size-7 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-bold" style="color:var(--color-ink);">{{ __('site.footer.verified_reviews') }}</p>
                            <p class="text-xs" style="color:var(--color-ink-3);">{{ __('site.footer.google_rating') }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t" style="border-color:var(--color-border);">
        <div class="container-page flex flex-col gap-2 py-5 text-xs sm:flex-row sm:items-center sm:justify-between"
             style="color:var(--color-ink-3);">
            <p>
                &copy; {{ date('Y') }}
                <a href="{{ config('site.url') }}"
                   class="font-semibold transition hover:text-red"
                   style="color:var(--color-ink-2);">{{ config('site.domain') }}</a>
                &middot; {{ __('site.common.all_rights_reserved') }}
            </p>
            <p>
                {{ __('site.common.built_by') }}
                <span class="font-semibold" style="color:var(--color-ink-2);">{{ config('site.name') }}</span>
                &middot; {{ config('site.tagline') }}
            </p>
        </div>
    </div>

</footer>
