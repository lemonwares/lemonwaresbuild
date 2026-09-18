<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seoTitle = trim($__env->yieldContent('title', config('site.short_name')));
            $seoDescription = trim($__env->yieldContent('meta_description', __('account.dashboard_lede')));
            $seoImage = asset('lemonwareslogo.webp');
            $seoUrl = url()->current();
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="noindex,nofollow">
        <link rel="canonical" href="{{ $seoUrl }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <link rel="icon" type="image/webp" href="{{ asset('lemonwareslogo.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('lemonwareslogo.webp') }}">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            (function () {
                try {
                    if (localStorage.getItem('lemonwares.account-sidebar') === 'collapsed') {
                        document.documentElement.classList.add('account-sidebar-collapsed');
                    }
                } catch (e) {}
            })();
        </script>
    </head>
    <body class="min-h-screen bg-blush-soft text-black">
        <div class="account-shell" data-account-shell>
            <aside class="account-sidebar" data-account-sidebar id="account-sidebar">
                <div class="account-sidebar-brand">
                    <a href="{{ route('account.show') }}" class="account-sidebar-brand-link" title="{{ config('site.name') }}">
                        <img
                            src="{{ asset('lemonwareslogo.webp') }}"
                            alt="{{ config('site.name') }}"
                            width="220"
                            height="52"
                            class="account-sidebar-logo"
                        >
                    </a>
                </div>

                <div class="account-sidebar-scroll">
                    <x-account.nav />
                </div>

                <div class="account-sidebar-footer">
                    <button
                        type="button"
                        class="account-sidebar-toggle"
                        data-account-sidebar-toggle
                        aria-expanded="true"
                        aria-controls="account-sidebar"
                        title="Collapse sidebar"
                    >
                        <svg class="account-sidebar-toggle-icon is-collapse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M9 3v18" />
                            <path d="m15 9-3 3 3 3" />
                        </svg>
                        <svg class="account-sidebar-toggle-icon is-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M9 3v18" />
                            <path d="m13 15 3-3-3-3" />
                        </svg>
                        <span class="account-nav-label">Collapse</span>
                    </button>
                </div>
            </aside>

            <div class="account-main">
                <header class="account-topbar">
                    <div class="account-topbar-inner">
                        <div class="account-topbar-left">
                            <button
                                type="button"
                                class="account-icon-btn lg:hidden"
                                data-account-nav-open
                                aria-label="{{ __('account.open_menu') }}"
                                aria-expanded="false"
                                aria-controls="account-drawer"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 6h16" />
                                    <path d="M4 12h16" />
                                    <path d="M4 18h16" />
                                </svg>
                            </button>
                            <a href="{{ route('account.show') }}" class="account-topbar-mobile-brand lg:hidden">
                                <img
                                    src="{{ asset('lemonwareslogo.webp') }}"
                                    alt="{{ config('site.name') }}"
                                    width="160"
                                    height="40"
                                    class="account-topbar-mobile-logo"
                                >
                            </a>
                            <span class="hidden text-sm font-semibold uppercase tracking-[0.12em] text-rose sm:inline lg:ml-0">
                                {{ __('account.client_area') }}
                            </span>
                        </div>

                        <div class="account-topbar-actions">
                            <x-layout.locale-switcher />
                            <a href="{{ route('home') }}" class="account-topbar-link hidden sm:inline">
                                {{ __('account.nav_website') }}
                            </a>
                            <x-account.notification-bell :unread-count="auth()->user()?->unreadNotifications()->count() ?? 0" />
                            <x-layout.account-session tone="button" :account-link="false" />
                        </div>
                    </div>
                </header>

                <main class="account-content">
                    <x-ui.flash show-status />
                    <x-ui.flash />
                    <x-ui.flash key="hosting_feedback" />
                    @yield('content')
                </main>
            </div>
        </div>

        <div class="account-drawer-overlay" data-account-drawer-overlay data-account-nav-close></div>
        <aside
            id="account-drawer"
            class="account-drawer"
            data-account-drawer
            aria-hidden="true"
            aria-label="{{ __('account.client_area') }}"
        >
            <div class="account-drawer-head">
                <strong>{{ __('account.client_area') }}</strong>
                <button type="button" class="account-icon-btn" data-account-nav-close aria-label="{{ __('account.close_menu') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <div class="account-drawer-scroll">
                <x-account.nav />
            </div>
        </aside>

        <x-account.complete-profile-modal />
        @stack('scripts')
    </body>
</html>
