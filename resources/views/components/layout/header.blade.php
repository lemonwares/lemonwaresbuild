<div data-site-header>
    <header class="site-header-bar site-header-fixed">
        <div class="container-page flex items-center justify-between gap-4 py-4 sm:py-5" data-site-header-bar>
            <x-layout.logo />

            <x-layout.nav />

            <div class="flex items-center gap-2 sm:gap-4">
                <x-layout.locale-switcher />
                <a href="{{ route('contact') }}" class="nav-contact hidden sm:inline-flex">
                    {{ __('site.common.contact_us') }}
                </a>
                <button
                    type="button"
                    class="inline-flex size-10 items-center justify-center rounded-full text-on-blush transition hover:bg-white/70 md:hidden"
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
        class="mobile-nav-overlay md:hidden"
        data-mobile-nav
        aria-hidden="true"
    >
        <div class="mobile-nav-overlay-inner container-page">
            <x-layout.mobile-nav />
        </div>
    </div>
</div>
