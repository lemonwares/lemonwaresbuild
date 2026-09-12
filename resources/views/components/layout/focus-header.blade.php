<header class="site-header-fixed site-header-bar is-elevated" data-focus-header data-site-header-bar>
    <div class="container-page flex items-center justify-between gap-4 py-3.5">

        <x-layout.logo />

        {{-- Secure checkout label --}}
        <div class="hidden items-center gap-2 md:flex" style="color:var(--color-ink-3);">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round" aria-hidden="true">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
            <span class="text-sm font-semibold">{{ __('hosting.secure_checkout') }}</span>
        </div>

        <div class="flex items-center gap-3">
            <x-layout.locale-switcher />
            <x-layout.account-session />
        </div>
    </div>
</header>
<div class="site-header-spacer" data-site-header-spacer aria-hidden="true"></div>
