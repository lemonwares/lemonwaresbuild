@php
    $mainLinks = [
        ['label' => __('site.nav.home'),         'href' => route('home'),          'active' => request()->routeIs('home')],
        ['label' => __('site.nav.about'),         'href' => route('about'),         'active' => request()->routeIs('about')],
        ['label' => __('site.nav.services'),      'href' => url('/#hosting-plans'), 'active' => false],
        ['label' => __('site.nav.email'),         'href' => route('email.plans'),   'active' => request()->routeIs('email.*')],
        ['label' => __('site.nav.work'),          'href' => route('case-studies'),  'active' => request()->routeIs('case-studies')],
        ['label' => __('site.nav.team'),          'href' => route('team'),          'active' => request()->routeIs('team')],
        ['label' => __('site.nav.faq'),           'href' => route('faq'),           'active' => request()->routeIs('faq')],
        ['label' => __('site.common.contact_us'), 'href' => route('contact'),       'active' => request()->routeIs('contact')],
    ];

    $legalLinks = [
        ['label' => __('site.footer.terms'),   'href' => route('terms')],
        ['label' => __('site.footer.refund'),  'href' => route('refund-policy')],
        ['label' => __('site.footer.privacy'), 'href' => route('privacy-policy')],
    ];
@endphp

<div {{ $attributes }}>

    {{-- ── Primary navigation links ── --}}
    <nav aria-label="{{ __('site.nav.primary') }}">
        <ul class="mobile-nav-links" role="list">
            @foreach ($mainLinks as $link)
                <li>
                    <a
                        href="{{ $link['href'] }}"
                        @class(['mobile-nav-link', 'is-active' => $link['active']])
                        @if ($link['active']) aria-current="page" @endif
                    >
                        {{-- Active indicator dot --}}
                        @if ($link['active'])
                            <span class="mr-2 inline-block size-1.5 translate-y-[-1px] rounded-full bg-red align-middle" aria-hidden="true"></span>
                        @endif
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ── CTA buttons ── --}}
    <div class="mt-8 flex flex-col gap-3">
        @auth
            <a
                href="{{ route('account.show') }}"
                class="flex w-full items-center justify-center rounded-2xl py-4 text-base font-bold text-white transition hover:opacity-90"
                style="background:var(--color-red);"
            >
                {{ __('account.account_title') }}
            </a>
            <x-ui.confirm-modal
                :action="route('logout')"
                :title="__('account.sign_out_confirm_title')"
                :body="__('account.sign_out_confirm_body')"
                :confirm-label="__('account.sign_out_confirm_yes')"
                :cancel-label="__('account.cancel')"
                :open-label="__('account.sign_out')"
                open-class="flex w-full items-center justify-center rounded-2xl border py-4 text-base font-bold transition hover:border-red hover:text-red border-[var(--color-border-2)] text-[var(--color-ink-2)]"
            />
        @else
            <a
                href="{{ route('login') }}"
                class="flex w-full items-center justify-center rounded-2xl py-4 text-base font-bold text-white transition hover:opacity-90"
                style="background:var(--color-red);"
            >
                {{ __('site.common.client_login') }}
            </a>
            <a
                href="{{ route('register') }}"
                class="flex w-full items-center justify-center rounded-2xl border py-4 text-base font-bold transition hover:border-red hover:text-red"
                style="border-color:var(--color-border-2); color:var(--color-ink-2);"
            >
                {{ __('site.common.register') ?? 'Create account' }}
            </a>
        @endauth
    </div>

    {{-- ── Locale switcher ── --}}
    <div class="mt-7">
        <x-layout.locale-switcher />
    </div>

    {{-- ── Legal links ── --}}
    <nav class="mt-8 flex flex-wrap gap-x-5 gap-y-2" aria-label="{{ __('site.footer.legal') }}">
        @foreach ($legalLinks as $link)
            <a
                href="{{ $link['href'] }}"
                class="text-xs font-medium transition hover:text-red"
                style="color:var(--color-ink-3);"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

</div>
