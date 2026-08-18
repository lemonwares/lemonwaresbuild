@php
    $exploreLinks = [
        [
            'label' => __('site.nav.home'),
            'href' => route('home'),
            'active' => request()->routeIs('home'),
        ],
        [
            'label' => __('site.nav.about'),
            'href' => route('about'),
            'active' => request()->routeIs('about'),
        ],
        [
            'label' => __('site.nav.services'),
            'href' => url('/#hosting-plans'),
            'active' => false,
        ],
        [
            'label' => __('site.nav.work'),
            'href' => route('case-studies'),
            'active' => request()->routeIs('case-studies'),
        ],
        [
            'label' => __('site.nav.team'),
            'href' => route('team'),
            'active' => request()->routeIs('team'),
        ],
        [
            'label' => __('site.nav.faq'),
            'href' => route('faq'),
            'active' => request()->routeIs('faq'),
        ],
        [
            'label' => __('site.common.contact_us'),
            'href' => route('contact'),
            'active' => request()->routeIs('contact'),
        ],
    ];

    if (auth()->check()) {
        $exploreLinks[] = [
            'label' => __('account.account_title'),
            'href' => route('account.show'),
            'active' => request()->routeIs('account.*'),
        ];
    } else {
        $exploreLinks[] = [
            'label' => __('site.common.client_login'),
            'href' => route('login'),
            'active' => request()->routeIs('login', 'register'),
        ];
    }

    $legalLinks = [
        [
            'label' => __('site.footer.terms'),
            'href' => route('terms'),
            'active' => request()->routeIs('terms'),
        ],
        [
            'label' => __('site.footer.refund'),
            'href' => route('refund-policy'),
            'active' => request()->routeIs('refund-policy'),
        ],
        [
            'label' => __('site.footer.privacy'),
            'href' => route('privacy-policy'),
            'active' => request()->routeIs('privacy-policy'),
        ],
        [
            'label' => __('site.footer.usage'),
            'href' => route('usage-terms'),
            'active' => request()->routeIs('usage-terms'),
        ],
    ];
@endphp

<div {{ $attributes->class('mobile-nav-panel') }}>
    <nav class="mobile-nav-links" aria-label="{{ __('site.nav.primary') }}">
        <p class="mobile-nav-heading">{{ __('site.footer.the_business') }}</p>
        @foreach ($exploreLinks as $link)
            <a
                href="{{ $link['href'] }}"
                @class(['mobile-nav-link', 'is-active' => $link['active']])
                @if ($link['active']) aria-current="page" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    @auth
        <div class="mt-4 border-t border-border pt-4">
            <x-layout.account-session class="flex-col items-start gap-3" :account-link="false" />
        </div>
    @endauth

    <nav class="mobile-nav-links mt-4 border-t border-border pt-4" aria-label="{{ __('site.footer.legal') }}">
        <p class="mobile-nav-heading">{{ __('site.footer.legal') }}</p>
        @foreach ($legalLinks as $link)
            <a
                href="{{ $link['href'] }}"
                @class(['mobile-nav-link', 'is-active' => $link['active']])
                @if ($link['active']) aria-current="page" @endif
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
</div>
