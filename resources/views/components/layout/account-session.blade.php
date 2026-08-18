@props([
    'tone' => 'link',
    'accountLink' => true,
])

@php
    $openClass = $tone === 'button'
        ? 'inline-flex shrink-0 rounded-full border border-border px-4 py-2 text-sm font-semibold text-black transition hover:border-rose hover:text-rose'
        : 'nav-contact cursor-pointer border-0 bg-transparent p-0';
@endphp

<div {{ $attributes->class('flex shrink-0 items-center gap-3') }}>
    @auth
        @if ($accountLink)
            <a href="{{ route('account.show') }}" class="nav-contact">
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
            :open-class="$openClass"
        />
    @else
        <a href="{{ route('login') }}" class="nav-contact">
            {{ __('site.common.client_login') }}
        </a>
    @endauth
</div>
