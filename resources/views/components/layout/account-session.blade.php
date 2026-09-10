@props([
    'tone'        => 'header',
    'accountLink' => true,
])

@php
    $ctaClass   = 'nav-contact';
    $ghostClass = 'inline-flex shrink-0 items-center whitespace-nowrap rounded-full border
                   px-4 py-2 text-sm font-semibold transition hover:border-red hover:text-red'
                   . ' border-[var(--color-border-2)] text-[var(--color-ink-2)]';

    if ($tone === 'button') {
        $ctaClass  = 'inline-flex shrink-0 rounded-full border border-[var(--color-border-2)]
                      px-4 py-2 text-sm font-semibold text-[var(--color-ink)] transition
                      hover:border-red hover:text-red';
        $ghostClass = $ctaClass;
    }
@endphp

<div {{ $attributes->class('flex shrink-0 items-center gap-2') }}>
    @auth
        @if ($accountLink)
            <a href="{{ route('account.show') }}" class="{{ $ghostClass }}">
                {{ __('account.account_title') }}
            </a>
        @endif
        <x-ui.confirm-modal
            :action="route('logout')"
            :title="__('account.sign_out_confirm_title')"
            :body="__('account.sign_out_confirm_body')"
            :confirm-label="__('account.sign_out_confirm_yes')"
            :cancel-label="__('account.cancel')"
            :open-label="__('account.sign_out')"
            :open-class="$ctaClass"
        />
    @else
        <a href="{{ route('login') }}" class="{{ $ctaClass }}">
            {{ __('site.common.client_login') }}
        </a>
    @endauth
</div>
