<header {{ $attributes->class('site-header') }}>
    <div class="container-page relative flex items-center justify-between gap-4 py-4 sm:py-5">
        <x-layout.logo />

        <x-layout.nav />

        <div class="flex items-center gap-3 sm:gap-4">
            <x-layout.locale-switcher />
            <a href="{{ config('site.whmcs.client_login_url') }}" class="hidden text-sm font-semibold text-on-blush/80 transition hover:text-on-blush sm:inline-flex" target="_blank" rel="noopener noreferrer">
                {{ __('site.common.client_login') }}
            </a>
            <a href="{{ route('contact') }}" class="nav-contact">
                {{ __('site.common.contact_us') }}
            </a>
        </div>
    </div>
</header>
