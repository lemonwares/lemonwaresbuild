<div data-site-header>
    <header class="site-header-bar site-header-fixed">
        <div class="container-page site-header-inner" data-site-header-bar>
            <div class="site-header-start">
                <x-layout.logo />
            </div>

            <x-layout.nav class="site-header-nav" />

            <div class="site-header-end">
                <button
                    type="button"
                    class="header-icon-btn"
                    data-domain-search-open
                    aria-haspopup="dialog"
                    aria-controls="domain-search-modal"
                    aria-expanded="false"
                    aria-label="{{ __('site.nav.domain_search_open') }}"
                    title="{{ __('site.nav.domain_search_open') }}"
                >
                    <x-ui.icons.search class="size-5" />
                    <span class="sr-only">{{ __('site.nav.domain_search_open') }}</span>
                </button>

                <a
                    href="{{ route('support') }}"
                    class="header-icon-btn"
                    aria-label="{{ __('site.nav.support') }}"
                    title="{{ __('site.nav.support') }}"
                >
                    <x-ui.icons.life-buoy class="size-5" />
                    <span class="sr-only">{{ __('site.nav.support') }}</span>
                </a>

                <x-layout.locale-switcher />

                @php($cartCount = \App\Support\Cart::count())
                <a
                    href="{{ route('cart') }}"
                    @class([
                        'header-icon-btn relative',
                        'hidden' => $cartCount < 1,
                    ])
                    aria-label="{{ __('cart.nav') }}"
                    title="{{ __('cart.nav') }}"
                    data-site-cart-link
                    data-domain-cart-link
                >
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l1.2 8.4A2 2 0 0 0 8.2 14H17a2 2 0 0 0 2-1.6L20 7H6" />
                        <circle cx="9" cy="19" r="1.2" />
                        <circle cx="17" cy="19" r="1.2" />
                    </svg>
                    <span
                        class="absolute -right-0.5 -top-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-rose px-1 text-[10px] font-bold leading-4 text-white {{ $cartCount > 0 ? '' : 'hidden' }}"
                        data-site-cart-count
                        data-domain-cart-count
                    >{{ $cartCount > 0 ? $cartCount : '' }}</span>
                    <span class="sr-only">{{ __('cart.nav') }}</span>
                </a>

                <x-layout.account-session icon-only class="hidden lg:flex" />

                <button
                    type="button"
                    class="inline-flex size-10 items-center justify-center rounded-full text-on-blush transition hover:bg-white/70 lg:hidden"
                    data-mobile-nav-toggle
                    data-open-label="{{ __('site.common.open_menu') }}"
                    data-close-label="{{ __('site.common.close_menu') }}"
                    aria-controls="mobile-nav"
                    aria-expanded="false"
                    aria-label="{{ __('site.common.open_menu') }}"
                >
                    <x-ui.icons.menu data-mobile-nav-open-icon class="size-5" />
                    <x-ui.icons.x data-mobile-nav-close-icon class="hidden size-5" />
                </button>
            </div>
        </div>
    </header>

    {{-- Keeps page content below the fixed header --}}
    <div class="site-header-spacer" data-site-header-spacer aria-hidden="true"></div>

    {{-- Portaled to <body> by JS so fixed + backdrop-blur on the header never clip it --}}
    <div
        id="mobile-nav"
        class="mobile-nav-overlay lg:hidden"
        data-mobile-nav
        aria-hidden="true"
    >
        <div class="mobile-nav-overlay-inner container-page">
            <x-layout.mobile-nav />
        </div>
    </div>

    <div
        id="domain-search-modal"
        class="domain-search-modal"
        data-domain-search-modal
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="domain-search-modal-title"
    >
        <div class="domain-search-modal-backdrop" data-domain-search-dismiss></div>
        <div class="domain-search-modal-panel" data-domain-search-panel>
            <div class="domain-search-modal-head">
                <div>
                    <p class="domain-search-modal-eyebrow">{{ __('site.nav.domain_search_eyebrow') }}</p>
                    <h2 id="domain-search-modal-title" class="domain-search-modal-title">
                        {{ __('site.nav.domain_search_title') }}
                    </h2>
                </div>
                <button
                    type="button"
                    class="header-icon-btn"
                    data-domain-search-dismiss
                    aria-label="{{ __('site.nav.domain_search_close') }}"
                >
                    <x-ui.icons.x class="size-5" />
                </button>
            </div>
            <x-layout.header-domain-search />
            <p class="domain-search-modal-hint">{{ __('site.nav.domain_search_hint') }}</p>
        </div>
    </div>
</div>
