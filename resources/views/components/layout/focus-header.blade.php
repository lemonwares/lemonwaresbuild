<header class="site-header-bar site-header-fixed" data-focus-header>
    <div class="container-page flex items-center justify-between gap-4 py-4 sm:py-5" data-site-header-bar>
        <x-layout.logo />

        <div class="hidden text-sm font-semibold text-on-blush/70 md:block">
            {{ __('hosting.secure_checkout') }}
        </div>

        <div class="flex items-center gap-3">
            <x-layout.locale-switcher />
            <x-layout.account-session />
        </div>
    </div>
</header>
<div class="site-header-spacer" data-site-header-spacer aria-hidden="true"></div>
