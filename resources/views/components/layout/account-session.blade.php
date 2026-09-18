@props([
    'tone' => 'link',
    'accountLink' => true,
    'iconOnly' => false,
])

@php
    $openClass = $tone === 'button'
        ? 'inline-flex shrink-0 rounded-full border border-border px-4 py-2 text-sm font-semibold text-black transition hover:border-rose hover:text-rose'
        : ($iconOnly
            ? 'header-icon-btn cursor-pointer border-0 bg-transparent'
            : 'nav-contact cursor-pointer border-0 bg-transparent p-0');
@endphp

<div {{ $attributes->class('flex shrink-0 items-center gap-3') }}>
    @auth
        @if ($accountLink)
            <a
                href="{{ route('account.show') }}"
                @class(['header-icon-btn' => $iconOnly, 'nav-contact' => ! $iconOnly])
                @if ($iconOnly) aria-label="{{ __('account.account_title') }}" title="{{ __('account.account_title') }}" @endif
            >
                @if ($iconOnly)
                    <x-ui.icons.user class="size-5" />
                    <span class="sr-only">{{ __('account.account_title') }}</span>
                @else
                    {{ __('account.account_title') }}
                @endif
            </a>
        @endif
        @unless ($iconOnly)
            <x-ui.confirm-modal
                :action="route('logout')"
                :title="__('account.sign_out_confirm_title')"
                :body="__('account.sign_out_confirm_body')"
                :confirm-label="__('account.sign_out_confirm_yes')"
                :cancel-label="__('account.cancel')"
                :open-label="__('account.sign_out')"
                :open-class="$openClass"
            />
        @endunless
    @else
        <a
            href="{{ route('login') }}"
            @class(['header-icon-btn' => $iconOnly, 'nav-contact' => ! $iconOnly])
            @if ($iconOnly) aria-label="{{ __('site.common.client_login') }}" title="{{ __('site.common.client_login') }}" @endif
        >
            @if ($iconOnly)
                <x-ui.icons.user class="size-5" />
                <span class="sr-only">{{ __('site.common.client_login') }}</span>
            @else
                {{ __('site.common.client_login') }}
            @endif
        </a>
    @endauth
</div>
