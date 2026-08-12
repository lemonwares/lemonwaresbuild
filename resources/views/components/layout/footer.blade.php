@php
    $directionsHref = 'https://www.google.com/maps/search/?api=1&query=' . urlencode(config('site.address'));

    $businessLinks = [
        ['label' => __('site.nav.about'), 'href' => route('about')],
        ['label' => __('site.common.contact_us'), 'href' => route('contact')],
        ['label' => __('site.footer.case_studies'), 'href' => route('case-studies')],
        ['label' => __('site.nav.faq'), 'href' => route('faq')],
        ['label' => __('site.common.client_login'), 'href' => config('site.whmcs.client_login_url'), 'external' => true],
    ];

    $legalLinks = [
        ['label' => __('site.footer.terms'), 'href' => route('terms')],
        ['label' => __('site.footer.refund'), 'href' => route('refund-policy')],
        ['label' => __('site.footer.privacy'), 'href' => route('privacy-policy')],
        ['label' => __('site.footer.usage'), 'href' => route('usage-terms')],
    ];
@endphp

<footer {{ $attributes->class('site-footer') }}>
    <div class="container-page py-14 sm:py-16">
        <div class="grid gap-10 lg:grid-cols-12 lg:gap-8 xl:gap-10">
            {{-- Brand & address --}}
            <div class="flex flex-col gap-6 lg:col-span-3">
                <x-layout.logo />

                <div class="flex items-center gap-3">
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

                <div>
                    <h2 class="footer-heading mb-2">{{ __('site.footer.address') }}</h2>
                    <p class="body-text">{{ config('site.address') }}</p>
                </div>

                <x-ui.button href="{{ $directionsHref }}" target="_blank" rel="noopener noreferrer" class="w-fit">
                    <span>{{ __('site.footer.get_directions') }}</span>
                    <x-ui.icons.arrow-up-right class="size-4" />
                </x-ui.button>
            </div>

            {{-- Contact cards --}}
            <div class="flex flex-col divide-y divide-border/80 lg:col-span-3">
                <x-layout.footer-contact-card
                    :title="__('site.footer.enquiries')"
                    :value="config('site.email')"
                    href="mailto:{{ config('site.email') }}"
                >
                    <x-ui.icons.send class="size-4" />
                    <span>{{ __('site.footer.email_us') }}</span>
                </x-layout.footer-contact-card>

                <x-layout.footer-contact-card
                    :title="__('site.footer.quick_chat')"
                    :value="config('site.phone')"
                    :href="config('site.whatsapp')"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <x-ui.icons.message-circle class="size-4" />
                    <span>{{ __('site.footer.whatsapp') }}</span>
                </x-layout.footer-contact-card>

                <x-layout.footer-contact-card
                    :title="__('site.footer.call')"
                    :value="config('site.phone')"
                    href="tel:{{ config('site.phone_e164') }}"
                >
                    <x-ui.icons.phone class="size-4" />
                    <span>{{ __('site.footer.dial') }}</span>
                </x-layout.footer-contact-card>
            </div>

            {{-- Navigation --}}
            <div class="grid gap-8 sm:grid-cols-2 lg:col-span-3 lg:grid-cols-1">
                <nav aria-label="{{ __('site.footer.the_business') }}">
                    <h2 class="footer-heading mb-1">{{ __('site.footer.the_business') }}</h2>
                    <ul class="footer-link-list">
                        @foreach ($businessLinks as $link)
                            <li>
                                <a
                                    href="{{ $link['href'] }}"
                                    class="footer-link"
                                    @if (! empty($link['external'])) target="_blank" rel="noopener noreferrer" @endif
                                >{{ $link['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <nav aria-label="{{ __('site.footer.legal') }}">
                    <h2 class="footer-heading mb-1">{{ __('site.footer.legal') }}</h2>
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
                <div id="footer-newsletter" class="footer-newsletter py-1 scroll-mt-28">
                    <h2 class="footer-heading mb-4">{{ __('site.footer.newsletter') }}</h2>

                    @php
                        $newsletterFeedback = session('newsletter_feedback');
                    @endphp

                    @if ($newsletterFeedback)
                        <p @class([
                            'mb-3 rounded-xl px-3 py-2 text-sm',
                            'border border-emerald-200 bg-emerald-50 text-emerald-800' => ($newsletterFeedback['type'] ?? null) === 'success',
                            'border border-sky-200 bg-sky-50 text-sky-800' => ($newsletterFeedback['type'] ?? null) === 'info',
                            'border border-rose/20 bg-rose/5 text-rose' => ($newsletterFeedback['type'] ?? null) === 'error',
                        ])>{{ $newsletterFeedback['message'] ?? '' }}</p>
                    @endif

                    <form action="{{ route('newsletter.subscribe') }}" method="post" class="flex flex-col gap-3" data-newsletter-form>
                        @csrf
                        <label class="sr-only" for="footer-full-name">{{ __('site.footer.full_name') }}</label>
                        <input
                            id="footer-full-name"
                            type="text"
                            name="full_name"
                            placeholder="{{ __('site.footer.full_name') }}"
                            autocomplete="name"
                            value="{{ old('full_name') }}"
                            class="footer-input"
                            required
                            data-newsletter-input
                        >

                        <label class="sr-only" for="footer-email">{{ __('site.footer.email') }}</label>
                        <input
                            id="footer-email"
                            type="email"
                            name="email"
                            placeholder="{{ __('site.footer.email') }}"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            class="footer-input"
                            required
                            data-newsletter-input
                        >

                        <button type="submit" class="btn btn-primary w-full" data-newsletter-button>
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-newsletter-spinner></span>
                            <span data-newsletter-label>{{ __('site.footer.send') }}</span>
                            <span class="hidden" data-newsletter-loading>{{ __('site.footer.sending') }}</span>
                        </button>
                    </form>

                    <div class="footer-reviews mt-6" aria-label="{{ __('site.footer.verified_reviews') }}">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="size-8 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" />
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-on-blush">{{ __('site.footer.verified_reviews') }}</p>
                                <p class="text-xs text-on-blush/60">{{ __('site.footer.google_rating') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="border-t border-border">
        <div class="container-page flex flex-col gap-3 py-6 text-sm text-on-blush/60 sm:flex-row sm:items-center sm:justify-between">
            <p>
                &copy; {{ date('Y') }}
                <a href="{{ config('site.url') }}" class="link font-medium text-on-blush/80 hover:text-rose">{{ config('site.domain') }}</a>
                · {{ __('site.common.all_rights_reserved') }}
            </p>
            <p>
                {{ __('site.common.built_by') }}
                <span class="font-medium text-on-blush/80">{{ config('site.name') }}</span>
                · {{ config('site.tagline') }}
            </p>
        </div>
    </div>
</footer>
