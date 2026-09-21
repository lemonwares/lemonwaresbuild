<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seoTitle = trim($__env->yieldContent('title', 'Admin — ' . config('site.short_name')));
            $seoDescription = trim($__env->yieldContent('meta_description', 'LemonWares admin portal.'));
            $seoImage = asset('lemonwareslogo.webp');
            $seoUrl = url()->current();
            $adminAuthed = session('admin_authenticated');
            $adminNotifications = $adminNotifications ?? [];
            $adminNotificationCount = $adminNotificationCount ?? 0;
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
                    if (localStorage.getItem('lemonwares.admin-sidebar') === 'collapsed') {
                        document.documentElement.classList.add('admin-sidebar-collapsed');
                    }
                } catch (e) {}
            })();
        </script>
    </head>
    <body @class(['min-h-screen text-black', 'bg-blush-soft' => $adminAuthed, 'bg-white' => ! $adminAuthed])>
        @if ($adminAuthed)
            <div class="admin-shell" data-admin-shell>
                <aside class="admin-sidebar" data-admin-sidebar id="admin-sidebar">
                    <div class="admin-sidebar-brand">
                        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-brand-link" title="{{ config('site.name') }}">
                            <img
                                src="{{ asset('lemonwareslogo.webp') }}"
                                alt="{{ config('site.name') }}"
                                width="220"
                                height="52"
                                class="admin-sidebar-logo"
                            >
                        </a>
                    </div>

                    <div class="admin-sidebar-scroll">
                        <x-admin.nav />
                    </div>

                    <div class="admin-sidebar-footer">
                        <button
                            type="button"
                            class="admin-sidebar-toggle"
                            data-admin-sidebar-toggle
                            aria-expanded="true"
                            aria-controls="admin-sidebar"
                            title="Collapse sidebar"
                        >
                            <svg class="admin-sidebar-toggle-icon is-collapse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <path d="M9 3v18" />
                                <path d="m15 9-3 3 3 3" />
                            </svg>
                            <svg class="admin-sidebar-toggle-icon is-expand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <path d="M9 3v18" />
                                <path d="m13 15 3-3-3-3" />
                            </svg>
                            <span class="admin-nav-label">Collapse</span>
                        </button>
                    </div>
                </aside>

                <div class="admin-main">
                    <header class="admin-topbar">
                        <div class="admin-topbar-inner">
                            <div class="admin-topbar-left">
                                <div class="admin-topbar-mobile-brand lg:hidden">
                                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center">
                                        <img
                                            src="{{ asset('lemonwareslogo.webp') }}"
                                            alt="{{ config('site.name') }}"
                                            width="160"
                                            height="40"
                                            class="admin-topbar-mobile-logo"
                                        >
                                    </a>
                                </div>
                            </div>

                            <div class="admin-topbar-actions">
                                <button
                                    type="button"
                                    class="admin-icon-btn"
                                    data-admin-search-open
                                    aria-label="Search admin"
                                    title="Search (Ctrl/⌘ K)"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="m20 20-3.5-3.5" />
                                    </svg>
                                </button>

                                <div class="admin-notif">
                                    <button
                                        type="button"
                                        class="admin-icon-btn"
                                        data-admin-notif-open
                                        aria-label="Notifications"
                                        aria-expanded="false"
                                        aria-haspopup="true"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                                        </svg>
                                        @if ($adminNotificationCount > 0)
                                            <span class="admin-notif-badge">{{ $adminNotificationCount > 9 ? '9+' : $adminNotificationCount }}</span>
                                        @endif
                                    </button>

                                    <div class="admin-notif-panel" data-admin-notif-panel hidden>
                                        <div class="admin-notif-head">
                                            <strong>Notifications</strong>
                                            <span>{{ $adminNotificationCount }} open</span>
                                        </div>
                                        <ul class="admin-notif-list">
                                            @forelse ($adminNotifications as $note)
                                                <li>
                                                    <a href="{{ $note['href'] }}">
                                                        <span class="admin-notif-title">{{ $note['title'] }}</span>
                                                        <span class="admin-notif-meta">{{ $note['meta'] }}</span>
                                                    </a>
                                                </li>
                                            @empty
                                                <li class="admin-notif-empty">You're all caught up.</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    data-signout-open
                                    class="admin-topbar-signout"
                                >
                                    Sign Out
                                </button>
                                <form id="signout-form" method="POST" action="{{ route('admin.logout') }}" class="hidden">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </header>

                    <div class="admin-mobile-nav">
                        <x-admin.nav />
                    </div>

                    <main class="admin-content">
                        @unless ($__env->hasSection('hide_auto_breadcrumbs'))
                            <x-admin.breadcrumbs :items="\App\Support\AdminBreadcrumbs::fromCurrentRoute()" class="mb-5" />
                        @endunless

                        @if (session('status'))
                            <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
                        @endif

                        @yield('content')
                    </main>
                </div>
            </div>

            <div
                class="admin-search-modal"
                data-admin-search-modal
                data-search-url="{{ route('admin.search') }}"
                hidden
                role="dialog"
                aria-modal="true"
                aria-label="Search"
            >
                <div class="admin-search-dialog">
                    <div class="admin-search-bar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                        <input
                            type="search"
                            data-admin-search-input
                            placeholder="Search customers, orders, leads, tickets, pages…"
                            autocomplete="off"
                            spellcheck="false"
                        >
                        <button type="button" class="admin-search-esc" data-admin-search-close>Esc</button>
                    </div>
                    <div class="admin-search-results" data-admin-search-results>
                        <p class="admin-search-hint">Type to search anything on the platform.</p>
                    </div>
                </div>
            </div>

            <div id="signout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
                <div class="w-full max-w-md rounded-3xl border border-border bg-white p-6 shadow-2xl">
                    <h3 class="text-xl font-bold text-black">Confirm Sign Out</h3>
                    <p class="mt-2 body-text">Are you sure you want to sign out of the admin panel?</p>
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" data-signout-cancel class="rounded-xl border border-border px-4 py-2 text-sm font-semibold text-black transition hover:border-rose hover:text-rose">
                            Cancel
                        </button>
                        <button type="button" data-signout-confirm class="inline-flex items-center gap-2 rounded-xl bg-rose px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#c93737]">
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/30 border-t-white" data-signout-spinner></span>
                            <span data-signout-label>Yes, Sign Out</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            <main>
                @yield('content')
            </main>
        @endif

        <script>
            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', () => {
                    const button = form.querySelector('[data-submit-button]');
                    if (!button) return;

                    const defaultLabel = button.querySelector('[data-submit-label]');
                    const loadingLabel = button.querySelector('[data-submit-loading]');
                    const spinner = button.querySelector('[data-submit-spinner]');

                    button.disabled = true;
                    button.classList.add('opacity-80', 'cursor-not-allowed');

                    if (defaultLabel) defaultLabel.classList.add('hidden');
                    if (loadingLabel) loadingLabel.classList.remove('hidden');
                    if (spinner) spinner.classList.remove('hidden');
                });
            });

            const signoutOpenButton = document.querySelector('[data-signout-open]');
            const signoutModal = document.getElementById('signout-modal');
            const signoutCancel = document.querySelector('[data-signout-cancel]');
            const signoutConfirm = document.querySelector('[data-signout-confirm]');
            const signoutForm = document.getElementById('signout-form');
            const signoutSpinner = document.querySelector('[data-signout-spinner]');
            const signoutLabel = document.querySelector('[data-signout-label]');

            if (signoutOpenButton && signoutModal && signoutCancel && signoutConfirm && signoutForm) {
                signoutOpenButton.addEventListener('click', () => {
                    signoutModal.classList.remove('hidden');
                    signoutModal.classList.add('flex');
                });

                signoutCancel.addEventListener('click', () => {
                    signoutModal.classList.add('hidden');
                    signoutModal.classList.remove('flex');
                });

                signoutModal.addEventListener('click', (event) => {
                    if (event.target === signoutModal) {
                        signoutModal.classList.add('hidden');
                        signoutModal.classList.remove('flex');
                    }
                });

                signoutConfirm.addEventListener('click', () => {
                    signoutConfirm.disabled = true;
                    signoutConfirm.classList.add('opacity-80', 'cursor-not-allowed');
                    if (signoutSpinner) signoutSpinner.classList.remove('hidden');
                    if (signoutLabel) signoutLabel.textContent = 'Signing Out...';
                    signoutForm.submit();
                });
            }
        </script>
        @stack('scripts')
    </body>
</html>
