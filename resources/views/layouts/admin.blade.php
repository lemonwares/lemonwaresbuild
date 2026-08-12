<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seoTitle = trim($__env->yieldContent('title', 'Admin — ' . config('site.short_name')));
            $seoDescription = trim($__env->yieldContent('meta_description', 'Lemonwares admin portal.'));
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
    </head>
    <body class="min-h-screen bg-white text-black">
        <header class="border-b border-border bg-white">
            <div class="container-page flex items-center justify-between py-4">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-3">
                    <img
                        src="{{ asset('lemonwareslogo.webp') }}"
                        alt="{{ config('site.name') }}"
                        width="220"
                        height="56"
                        class="h-10 w-auto sm:h-11"
                    >
                    <span class="text-sm font-semibold uppercase tracking-[0.12em] text-rose">
                        Admin
                    </span>
                </a>
                <div class="flex items-center gap-3">
                    @if (session('admin_authenticated'))
                        <nav class="hidden items-center gap-2 md:flex">
                            <a href="{{ route('admin.dashboard') }}" class="rounded-full border border-border px-4 py-2 text-xs font-semibold uppercase tracking-widest text-black transition hover:border-rose hover:text-rose">Dashboard</a>
                            <a href="{{ route('admin.team-members.index') }}" class="rounded-full border border-border px-4 py-2 text-xs font-semibold uppercase tracking-widest text-black transition hover:border-rose hover:text-rose">Team</a>
                            <a href="{{ route('admin.hosting-prices.index') }}" class="rounded-full border border-border px-4 py-2 text-xs font-semibold uppercase tracking-widest text-black transition hover:border-rose hover:text-rose">Prices</a>
                        </nav>

                        <button
                            type="button"
                            data-signout-open
                            class="rounded-full border border-border px-4 py-2 text-sm font-semibold text-black transition hover:border-rose hover:text-rose"
                        >
                            Sign Out
                        </button>

                        <form id="signout-form" method="POST" action="{{ route('admin.logout') }}" class="hidden">
                            @csrf
                        </form>
                    @endif
                </div>
            </div>
        </header>

        <main class="container-page py-8 sm:py-10">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-border bg-blush px-4 py-3 text-sm text-black">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        @if (session('admin_authenticated'))
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
    </body>
</html>

