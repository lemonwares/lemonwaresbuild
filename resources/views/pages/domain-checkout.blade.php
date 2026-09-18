@extends('layouts.app')

@section('title', __('domain.checkout_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('domain.checkout_lede'))
@section('focus_flow', '1')

@section('content')
    <x-layout.page-hero
        :eyebrow="__('pages.domain.eyebrow')"
        :title="__('domain.checkout_title')"
        :lede="$hasTransfer ? __('domain.checkout_transfer_lede') : __('domain.checkout_lede')"
        cta-href="#page-content"
        :cta-label="__('domain.pay_now')"
    />

    <x-layout.page-content>
        <x-ui.flash />

        @if (session('domain_feedback'))
            <p @class([
                'mb-5 rounded-xl px-4 py-3 text-sm',
                'border border-emerald-200 bg-emerald-50 text-emerald-800' => session('domain_feedback.type') === 'success',
                'border border-sky-200 bg-sky-50 text-sky-800' => session('domain_feedback.type') === 'info',
                'border border-rose/20 bg-rose/5 text-rose' => session('domain_feedback.type') === 'error',
            ])>{{ session('domain_feedback.message') }}</p>
        @endif

        @if ($errors->any())
            <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
        @endif

        @php
            $authUser = auth()->user();
            $isGuest = auth()->guest();
            $guestStatus = $guestAccountStatus ?? 'pending';
            $showBusinessInitially = $isGuest
                ? in_array($guestStatus, ['new', 'existing_incomplete'], true)
                : (bool) $needsBusiness;
            $showNameInitially = $isGuest && $guestStatus === 'new';
            $showPasswordInitially = $isGuest && $guestStatus !== 'pending';
        @endphp

        <form
            method="POST"
            action="{{ route('domain.checkout.store') }}"
            class="space-y-5 rounded-3xl border border-border bg-white p-6 sm:p-8"
            data-domain-checkout
            data-submit-form
            data-account-status-url="{{ route('domain.checkout.account-status') }}"
            data-guest-checkout="{{ $isGuest ? '1' : '0' }}"
            data-initial-needs-business="{{ $showBusinessInitially ? '1' : '0' }}"
            data-initial-guest-status="{{ $guestStatus }}"
            data-password-help-new="{{ __('domain.checkout_password_help') }}"
            data-password-help-existing="{{ __('domain.checkout_password_existing_help') }}"
        >
            @csrf

            <div class="rounded-2xl border border-border bg-blush-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('domain.selected_domain') }}</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($items as $item)
                        <li class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <span class="font-semibold text-black">{{ $item['domain'] }}</span>
                            <span class="text-on-blush/75">
                                {{ ($item['option'] ?? '') === 'transfer' ? __('domain.option_transfer') : __('domain.option_register') }}
                                · {{ $item['period_label'] }}
                                · {{ $item['display'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 text-base font-bold text-black">{{ __('domain.amount_label') }}: {{ $priceDisplay }}</p>
                <a href="{{ route('domain.cart') }}" class="mt-2 inline-flex text-sm font-semibold text-rose hover:underline">
                    {{ __('domain.change_domain') }}
                </a>
            </div>

            @guest
                <fieldset class="space-y-4 rounded-2xl border border-border bg-blush-soft/50 p-4 sm:p-5">
                    <legend class="px-1 text-sm font-semibold text-black">1. {{ __('domain.checkout_account_title') }}</legend>
                    <p class="text-sm text-on-blush/70 {{ in_array($guestStatus, ['existing_complete', 'existing_incomplete'], true) ? 'hidden' : '' }}" data-account-lede>{{ __('domain.checkout_account_lede') }}</p>
                    <p class="{{ in_array($guestStatus, ['existing_complete', 'existing_incomplete'], true) ? '' : 'hidden' }} text-sm font-semibold text-rose" data-welcome-back>{{ __('domain.checkout_welcome_back') }}</p>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-email>
                        <p class="mt-2 text-sm text-on-blush/65">{{ __('domain.checkout_email_help') }}</p>
                        @error('email')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="{{ $showNameInitially ? '' : 'hidden' }}" data-name-wrap>
                        <label for="name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-name @if ($showNameInitially) required @endif>
                        @error('name')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="{{ $showPasswordInitially ? '' : 'hidden' }}" data-password-wrap>
                        <label for="password" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password') }}</label>
                        <input id="password" name="password" type="password" autocomplete="{{ $guestStatus === 'new' ? 'new-password' : 'current-password' }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-checkout-password @if ($showPasswordInitially) required @endif>
                        <p class="mt-2 text-sm text-on-blush/65" data-password-help>
                            {{ in_array($guestStatus, ['existing_complete', 'existing_incomplete'], true) ? __('domain.checkout_password_existing_help') : __('domain.checkout_password_help') }}
                        </p>
                        @error('password')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>
            @endguest

            @auth
                @if (! $needsBusiness)
                    <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ __('domain.checkout_profile_reuse') }}
                    </p>
                @endif
            @endauth

            <fieldset
                class="space-y-4 rounded-2xl border border-border bg-blush-soft/50 p-4 sm:p-5 {{ $showBusinessInitially ? '' : 'hidden' }}"
                data-business-section
                @if (! $showBusinessInitially) disabled @endif
            >
                <legend class="px-1 text-sm font-semibold text-black">
                    <span data-business-step-label>{{ $isGuest ? '2.' : '1.' }}</span>
                    {{ __('domain.checkout_business_title') }}
                </legend>
                <p class="text-sm text-on-blush/70">{{ __('domain.checkout_business_lede') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div data-business-field="company" class="{{ ! $isGuest && ! in_array('company', $missingBusinessFields, true) ? 'hidden' : '' }}">
                        <label for="company" class="mb-2 block text-sm font-semibold text-black">{{ __('account.company') }}</label>
                        <input id="company" name="company" type="text" value="{{ old('company', $authUser?->company) }}" autocomplete="organization" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-business-required>
                        @error('company')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>
                    <div data-business-field="phone" class="{{ ! $isGuest && ! in_array('phone', $missingBusinessFields, true) ? 'hidden' : '' }}">
                        <label for="phone" class="mb-2 block text-sm font-semibold text-black">{{ __('account.phone') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $authUser?->phone) }}" autocomplete="tel" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-business-required>
                        @error('phone')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>
                    <div data-business-field="billing_country" class="{{ ! $isGuest && ! in_array('billing_country', $missingBusinessFields, true) ? 'hidden' : '' }}">
                        <label for="billing_country" class="mb-2 block text-sm font-semibold text-black">{{ __('account.country') }}</label>
                        <select id="billing_country" name="billing_country" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3" data-business-required>
                            <option value="">{{ __('account.select_country') }}</option>
                            @foreach ($countryOptions as $code => $label)
                                <option value="{{ $code }}" @selected(old('billing_country', $authUser?->billing_country ?: 'NG') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('billing_country')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="billing_city" class="mb-2 block text-sm font-semibold text-black">{{ __('account.city') }} <span class="font-normal text-on-blush/55">({{ __('domain.optional') }})</span></label>
                        <input id="billing_city" name="billing_city" type="text" value="{{ old('billing_city', $authUser?->billing_city) }}" autocomplete="address-level2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="billing_address_line_1" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_1') }} <span class="font-normal text-on-blush/55">({{ __('domain.optional') }})</span></label>
                        <input id="billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1', $authUser?->billing_address_line_1) }}" autocomplete="address-line1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                    </div>
                </div>
            </fieldset>

            <fieldset
                class="space-y-4 rounded-2xl border border-border bg-blush-soft/50 p-4 sm:p-5 {{ $isGuest && $guestStatus === 'pending' ? 'hidden' : '' }}"
                data-mail-setup
                data-domain-details
                @if ($isGuest && $guestStatus === 'pending') disabled @endif
            >
                <legend class="px-1 text-sm font-semibold text-black">
                    <span data-mail-step-label>{{ $isGuest ? '3.' : ($needsBusiness ? '2.' : '1.') }}</span>
                    {{ $hasTransfer ? __('domain.option_transfer') : __('domain.option_register') }}
                </legend>

                @forelse (collect($items)->where('option', 'transfer') as $item)
                    <div>
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
                @empty
                    <p class="text-sm text-on-blush/70">{{ __('domain.amount_label') }} · {{ $priceDisplay }}</p>
                @endforelse
            </fieldset>

            <x-ui.submit-button
                :label="__('domain.pay_now')"
                :loading="__('account.starting_payment')"
                class="btn btn-primary w-full sm:w-auto"
                disabled
            />
            <p class="text-sm text-on-blush/60" data-checkout-hint>{{ __('domain.checkout_continue_hint') }}</p>
        </form>
    </x-layout.page-content>
@endsection
