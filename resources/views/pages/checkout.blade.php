@extends('layouts.app')

@section('title', __('cart.checkout_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('cart.checkout_lede'))

@section('content')
    @php
        $isGuest = auth()->guest();
        $user = $authUser ?? auth()->user();
        $shippingSame = old('shipping_same_as_billing', '1') !== '0';
    @endphp

    <section class="domain-cart-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-14 sm:py-18">
            <a href="{{ route('cart') }}" class="page-back">
                <x-ui.icons.arrow-left class="page-back-icon" />
                {{ __('site.common.go_back') }}
            </a>
            <h1 class="mt-8 max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('cart.checkout_title') }}
            </h1>
            <p class="domain-cart-lede mt-4 max-w-2xl text-base sm:text-lg">
                {{ __('cart.checkout_lede') }}
            </p>
        </div>
    </section>

    <section class="domain-cart-body">
        <div class="container-page py-10 sm:py-14">
            @if (session('cart_feedback'))
                <p @class([
                    'mb-5 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => session('cart_feedback.type') === 'success',
                    'border border-sky-200 bg-sky-50 text-sky-800' => session('cart_feedback.type') === 'info',
                    'border border-rose/20 bg-rose/5 text-rose' => session('cart_feedback.type') === 'error',
                ])>{{ session('cart_feedback.message') }}</p>
            @endif

            @if ($errors->any())
                <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
            @endif

            <form
                method="POST"
                action="{{ route('checkout.store') }}"
                class="site-checkout-layout"
                data-site-checkout
                data-submit-form
                data-account-status-url="{{ route('checkout.account-status') }}"
                data-guest-checkout="{{ $isGuest ? '1' : '0' }}"
                data-initial-guest-status="{{ $guestAccountStatus }}"
                data-login-url="{{ $loginUrl }}"
            >
                @csrf

                <div class="site-checkout-main">
                    @guest
                        <fieldset class="site-checkout-step" data-account-section>
                            <div class="site-checkout-step-head">
                                <span class="site-checkout-step-num" aria-hidden="true">1</span>
                                <div>
                                    <legend class="site-checkout-step-title">{{ __('cart.checkout_contact_title') }}</legend>
                                    <p class="site-checkout-step-lede" data-account-lede>{{ __('cart.checkout_contact_lede') }}</p>
                                </div>
                            </div>

                            <div
                                class="{{ $guestAccountStatus === 'existing' ? '' : 'hidden' }} rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose"
                                data-existing-account
                            >
                                <p class="font-semibold">{{ __('cart.checkout_welcome_back') }}</p>
                                <p class="mt-1">{{ __('cart.checkout_sign_in_body') }}</p>
                                <a
                                    href="{{ $loginUrl }}"
                                    class="btn btn-primary mt-3 inline-flex"
                                    data-checkout-sign-in
                                    data-action-loading
                                    data-loading-label="{{ __('account.signing_in') }}"
                                >
                                    <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-action-spinner aria-hidden="true"></span>
                                    <span data-action-label>{{ __('cart.checkout_sign_in') }}</span>
                                    <span class="hidden" data-action-loading-label>{{ __('account.signing_in') }}</span>
                                </a>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2" data-new-account-fields>
                                <div class="sm:col-span-2">
                                    <label for="email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-email>
                                    @error('email')
                                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="{{ in_array($guestAccountStatus, ['new', 'pending'], true) ? '' : 'hidden' }}" data-name-wrap>
                                    <label for="name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-name @if ($guestAccountStatus === 'new') required @endif>
                                    @error('name')
                                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="{{ $guestAccountStatus === 'new' ? '' : 'hidden' }}" data-password-wrap>
                                    <label for="password" class="mb-2 block text-sm font-semibold text-black">{{ __('cart.checkout_create_password') }}</label>
                                    <input id="password" name="password" type="password" autocomplete="new-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-password @if ($guestAccountStatus === 'new') required @endif>
                                    <p class="mt-2 text-sm text-on-blush/65">{{ __('cart.checkout_create_password_help') }}</p>
                                    @error('password')
                                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="{{ $guestAccountStatus === 'new' ? '' : 'hidden' }}" data-password-confirm-wrap>
                                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password_confirm') }}</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-password-confirm @if ($guestAccountStatus === 'new') required @endif>
                                </div>
                            </div>
                        </fieldset>
                    @else
                        <div class="site-checkout-notice">
                            {{ __('cart.checkout_signed_in_as', ['email' => $user->email]) }}
                        </div>
                    @endguest

                    <fieldset class="site-checkout-step {{ $isGuest && $guestAccountStatus === 'existing' ? 'hidden' : '' }}" data-billing-section @if ($isGuest && $guestAccountStatus === 'existing') disabled @endif>
                        <div class="site-checkout-step-head">
                            <span class="site-checkout-step-num" aria-hidden="true" data-billing-step-num>{{ $isGuest ? '2' : '1' }}</span>
                            <div>
                                <legend class="site-checkout-step-title">{{ __('cart.checkout_billing_title') }}</legend>
                                <p class="site-checkout-step-lede">{{ __('cart.checkout_billing_lede') }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            @auth
                                <div class="sm:col-span-2">
                                    <label for="name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                                    @error('name')
                                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endauth

                            <div>
                                <label for="company" class="mb-2 block text-sm font-semibold text-black">{{ __('account.company') }}</label>
                                <input id="company" name="company" type="text" value="{{ old('company', $user?->company) }}" required autocomplete="organization" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                @error('company')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="phone" class="mb-2 block text-sm font-semibold text-black">{{ __('account.phone') }}</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user?->phone) }}" required autocomplete="tel" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                @error('phone')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="billing_address_line_1" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_1') }}</label>
                                <input id="billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1', $user?->billing_address_line_1) }}" required autocomplete="address-line1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                @error('billing_address_line_1')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="billing_address_line_2" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_2') }} <span class="font-normal text-on-blush/55">({{ __('domain.optional') }})</span></label>
                                <input id="billing_address_line_2" name="billing_address_line_2" type="text" value="{{ old('billing_address_line_2', $user?->billing_address_line_2) }}" autocomplete="address-line2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            </div>
                            <div>
                                <label for="billing_city" class="mb-2 block text-sm font-semibold text-black">{{ __('account.city') }}</label>
                                <input id="billing_city" name="billing_city" type="text" value="{{ old('billing_city', $user?->billing_city) }}" required autocomplete="address-level2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                @error('billing_city')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billing_state" class="mb-2 block text-sm font-semibold text-black">{{ __('account.state') }}</label>
                                <input id="billing_state" name="billing_state" type="text" value="{{ old('billing_state', $user?->billing_state) }}" required autocomplete="address-level1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                @error('billing_state')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billing_postcode" class="mb-2 block text-sm font-semibold text-black">{{ __('account.postcode') }}</label>
                                <input id="billing_postcode" name="billing_postcode" type="text" value="{{ old('billing_postcode', $user?->billing_postcode) }}" required autocomplete="postal-code" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                @error('billing_postcode')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billing_country" class="mb-2 block text-sm font-semibold text-black">{{ __('account.country') }}</label>
                                <select id="billing_country" name="billing_country" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-billing-required>
                                    <option value="">{{ __('account.select_country') }}</option>
                                    @foreach ($countryOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('billing_country', $user?->billing_country ?: 'NG') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('billing_country')
                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="site-checkout-step {{ $isGuest && $guestAccountStatus === 'existing' ? 'hidden' : '' }}" data-shipping-section @if ($isGuest && $guestAccountStatus === 'existing') disabled @endif>
                        <div class="site-checkout-step-head">
                            <span class="site-checkout-step-num" aria-hidden="true" data-shipping-step-num>{{ $isGuest ? '3' : '2' }}</span>
                            <div>
                                <legend class="site-checkout-step-title">{{ __('cart.checkout_shipping_title') }}</legend>
                                <p class="site-checkout-step-lede">{{ __('cart.checkout_shipping_lede') }}</p>
                            </div>
                        </div>

                        <label class="flex items-start gap-3 text-sm text-black">
                            <input type="hidden" name="shipping_same_as_billing" value="0">
                            <input
                                type="checkbox"
                                name="shipping_same_as_billing"
                                value="1"
                                class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose"
                                data-shipping-same
                                @checked($shippingSame)
                            >
                            <span>
                                <span class="font-semibold">{{ __('cart.checkout_shipping_same') }}</span>
                                <span class="mt-1 block text-on-blush/65">{{ __('cart.checkout_shipping_same_help') }}</span>
                            </span>
                        </label>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 {{ $shippingSame ? 'hidden' : '' }}" data-shipping-fields>
                            <div class="sm:col-span-2">
                                <label for="shipping_address_line_1" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_1') }}</label>
                                <input id="shipping_address_line_1" name="shipping_address_line_1" type="text" value="{{ old('shipping_address_line_1') }}" autocomplete="shipping address-line1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-shipping-required>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="shipping_address_line_2" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_2') }} <span class="font-normal text-on-blush/55">({{ __('domain.optional') }})</span></label>
                                <input id="shipping_address_line_2" name="shipping_address_line_2" type="text" value="{{ old('shipping_address_line_2') }}" autocomplete="shipping address-line2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            </div>
                            <div>
                                <label for="shipping_city" class="mb-2 block text-sm font-semibold text-black">{{ __('account.city') }}</label>
                                <input id="shipping_city" name="shipping_city" type="text" value="{{ old('shipping_city') }}" autocomplete="shipping address-level2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-shipping-required>
                            </div>
                            <div>
                                <label for="shipping_state" class="mb-2 block text-sm font-semibold text-black">{{ __('account.state') }}</label>
                                <input id="shipping_state" name="shipping_state" type="text" value="{{ old('shipping_state') }}" autocomplete="shipping address-level1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-shipping-required>
                            </div>
                            <div>
                                <label for="shipping_postcode" class="mb-2 block text-sm font-semibold text-black">{{ __('account.postcode') }}</label>
                                <input id="shipping_postcode" name="shipping_postcode" type="text" value="{{ old('shipping_postcode') }}" autocomplete="shipping postal-code" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-shipping-required>
                            </div>
                            <div>
                                <label for="shipping_country" class="mb-2 block text-sm font-semibold text-black">{{ __('account.country') }}</label>
                                <select id="shipping_country" name="shipping_country" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-shipping-required>
                                    <option value="">{{ __('account.select_country') }}</option>
                                    @foreach ($countryOptions as $code => $label)
                                        <option value="{{ $code }}" @selected(old('shipping_country', 'NG') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    @if ($hasTransfer || $hasEmail || $hasHosting)
                        <fieldset
                            class="site-checkout-step {{ $isGuest && $guestAccountStatus === 'existing' ? 'hidden' : '' }}"
                            data-mail-setup
                            data-domain-details
                            @if ($isGuest && $guestAccountStatus === 'existing') disabled @endif
                        >
                            <div class="site-checkout-step-head">
                                <span class="site-checkout-step-num" aria-hidden="true" data-details-step-num>{{ $isGuest ? '4' : '3' }}</span>
                                <div>
                                    <legend class="site-checkout-step-title">{{ __('cart.order_details_title') }}</legend>
                                    <p class="site-checkout-step-lede">{{ __('cart.order_details_lede') }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @foreach ($items as $item)
                                    @if (($item['type'] ?? '') === 'domain' && ($item['option'] ?? '') === 'transfer')
                                        <div class="site-checkout-item-block">
                                            <label for="epp-{{ $item['id'] }}" class="mb-2 block text-sm font-semibold text-black">
                                                {{ __('domain.epp_label') }} — {{ $item['domain'] }}
                                            </label>
                                            <input
                                                id="epp-{{ $item['id'] }}"
                                                name="epp[{{ $item['id'] }}]"
                                                type="text"
                                                value="{{ old('epp.'.$item['id']) }}"
                                                required
                                                autocomplete="off"
                                                class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                                                data-domain-epp
                                            >
                                            <p class="mt-2 text-sm text-on-blush/65">{{ __('domain.epp_help') }}</p>
                                            @error('epp.'.$item['id'])
                                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif

                                    @if (($item['type'] ?? '') === 'email')
                                        <div class="site-checkout-item-block">
                                            <p class="text-sm font-semibold text-black">{{ $item['plan_name'] ?? $item['label'] }}</p>
                                            <div>
                                                <label for="email-domain-{{ $item['id'] }}" class="mb-2 block text-sm font-semibold text-black">{{ __('cart.email_domain_label') }}</label>
                                                <input
                                                    id="email-domain-{{ $item['id'] }}"
                                                    name="email_domain[{{ $item['id'] }}]"
                                                    type="text"
                                                    value="{{ old('email_domain.'.$item['id'], $item['domain'] ?? '') }}"
                                                    required
                                                    class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                                                    placeholder="example.com"
                                                >
                                                @error('email_domain.'.$item['id'])
                                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <p class="mb-2 text-sm font-semibold text-black">{{ __('cart.email_mailboxes_label') }}</p>
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    @foreach (($item['mailboxes'] ?? []) as $index => $local)
                                                        <input
                                                            name="mailboxes[{{ $item['id'] }}][]"
                                                            type="text"
                                                            value="{{ old('mailboxes.'.$item['id'].'.'.$index, $local) }}"
                                                            required
                                                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                                                            placeholder="mailbox"
                                                        >
                                                    @endforeach
                                                </div>
                                                @error('mailboxes.'.$item['id'])
                                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    @endif

                                    @if (($item['type'] ?? '') === 'hosting' && ($item['checkout_provider'] ?? '') === 'whmcs')
                                        <div class="site-checkout-item-block">
                                            <p class="text-sm font-semibold text-black">{{ $item['label'] }}</p>
                                            <div>
                                                <label for="hostname-{{ $item['id'] }}" class="mb-2 block text-sm font-semibold text-black">{{ __('cart.hosting_hostname_label') }}</label>
                                                <input
                                                    id="hostname-{{ $item['id'] }}"
                                                    name="hostname[{{ $item['id'] }}]"
                                                    type="text"
                                                    value="{{ old('hostname.'.$item['id'], $item['hostname'] ?? '') }}"
                                                    required
                                                    class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                                                    placeholder="example.com"
                                                >
                                                @error('hostname.'.$item['id'])
                                                    <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <input type="hidden" name="domain_option[{{ $item['id'] }}]" value="{{ old('domain_option.'.$item['id'], 'owndomain') }}">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                </div>

                <aside class="site-checkout-aside">
                    <div class="site-checkout-summary">
                        <p class="site-checkout-summary-label">{{ __('cart.order_summary') }}</p>
                        <ul class="site-checkout-summary-list">
                            @foreach ($items as $item)
                                @php $type = $item['type'] ?? 'domain'; @endphp
                                <li class="site-checkout-summary-row">
                                    <div class="min-w-0">
                                        <p class="site-checkout-summary-type">{{ __('cart.type_'.$type) }}</p>
                                        <p class="site-checkout-summary-name">{{ $item['label'] ?? $item['domain'] ?? 'Item' }}</p>
                                        <p class="site-checkout-summary-meta">
                                            @if ($type === 'domain')
                                                {{ ($item['option'] ?? '') === 'transfer' ? __('domain.option_transfer') : __('domain.option_register') }}
                                                · {{ $item['period_label'] ?? '' }}
                                            @elseif ($type === 'email')
                                                {{ $item['billing_cycle_label'] ?? '' }}
                                            @else
                                                {{ $item['spec_label'] ?? '' }}
                                                · {{ $item['billing_cycle_label'] ?? '' }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="site-checkout-summary-price">{{ $item['display'] }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-4 flex items-end justify-between gap-3">
                            <div>
                                <p class="site-checkout-summary-total-label">{{ __('domain.amount_label') }}</p>
                                <p class="site-checkout-summary-total">{{ $priceDisplay }}</p>
                            </div>
                            <a href="{{ route('cart') }}" class="text-sm font-semibold text-rose hover:underline">
                                {{ __('cart.edit_cart') }}
                            </a>
                        </div>

                        <div class="site-checkout-pay mt-6" data-pay-wrap>
                            <x-ui.submit-button
                                :label="__('domain.pay_now')"
                                :loading="__('account.starting_payment')"
                                class="btn btn-primary w-full"
                                disabled
                            />
                            <p class="site-checkout-hint" data-checkout-hint>{{ __('cart.checkout_continue_hint') }}</p>
                        </div>
                    </div>
                </aside>
            </form>
        </div>
    </section>
@endsection
