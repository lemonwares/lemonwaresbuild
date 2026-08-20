@extends('layouts.app')

@section('title', __('email.checkout_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('email.checkout_lede'))
@section('focus_flow', '1')

@section('content')
    <x-layout.page-hero
        :eyebrow="__('email.eyebrow')"
        :title="$plan['is_manual'] ? __('email.request_setup') : __('email.checkout_title')"
        :lede="$plan['is_manual'] ? __('email.manual_queue_note') : __('email.checkout_lede')"
        cta-href="#page-content"
        :cta-label="$plan['is_manual'] ? __('email.request_setup') : __('email.pay_now')"
    />

    <x-layout.page-content>
        <x-ui.flash />

        @if ($errors->any())
            <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
        @endif

        @php
            $domainSuffixPlaceholder = '@' . __('email.domain_suffix_placeholder');
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
            action="{{ route('email.checkout.store') }}"
            class="space-y-5 rounded-3xl border border-border bg-white p-6 sm:p-8"
            data-email-checkout
            data-submit-form
            data-account-status-url="{{ route('email.checkout.account-status') }}"
            data-guest-checkout="{{ $isGuest ? '1' : '0' }}"
            data-initial-needs-business="{{ $showBusinessInitially ? '1' : '0' }}"
            data-initial-guest-status="{{ $guestStatus }}"
            data-password-help-new="{{ __('email.checkout_password_help') }}"
            data-password-help-existing="{{ __('email.checkout_password_existing_help') }}"
        >
            @csrf
            <input type="hidden" name="plan" value="{{ $plan['key'] }}">
            <input type="hidden" name="billing_cycle" value="{{ $cycle }}">

            {{-- Step: plan summary --}}
            <div class="rounded-2xl border border-border bg-blush-soft p-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/60">{{ __('email.selected_plan') }}</p>
                <p class="mt-1 text-base font-semibold text-black">{{ $plan['name'] }}</p>
                <p class="mt-1 text-sm text-on-blush/80">
                    {{ $plan['period_display'] }} · {{ $plan['billing_cycle_label'] }} ·
                    {{ trans_choice('email.mailboxes', $plan['mailboxes'], ['count' => $plan['mailboxes']]) }}
                </p>
                <a href="{{ route('email.plans', ['billing_cycle' => $cycle]) }}" class="mt-2 inline-flex text-sm font-semibold text-rose hover:underline">
                    {{ __('email.change_plan') }}
                </a>
            </div>

            @guest
                {{-- Step 1: account (email first) --}}
                <fieldset class="space-y-4 rounded-2xl border border-border bg-blush-soft/50 p-4 sm:p-5">
                    <legend class="px-1 text-sm font-semibold text-black">1. {{ __('email.checkout_account_title') }}</legend>
                    <p class="text-sm text-on-blush/70 {{ in_array($guestStatus, ['existing_complete', 'existing_incomplete'], true) ? 'hidden' : '' }}" data-account-lede>{{ __('email.checkout_account_lede') }}</p>
                    <p class="{{ in_array($guestStatus, ['existing_complete', 'existing_incomplete'], true) ? '' : 'hidden' }} text-sm font-semibold text-rose" data-welcome-back>{{ __('email.checkout_welcome_back') }}</p>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-black">{{ __('account.email') }}</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            data-checkout-email
                        >
                        <p class="mt-2 text-sm text-on-blush/65">{{ __('email.checkout_email_help') }}</p>
                        @error('email')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="{{ $showNameInitially ? '' : 'hidden' }}" data-name-wrap>
                        <label for="name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            data-checkout-name
                            @if ($showNameInitially) required @endif
                        >
                        @error('name')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="{{ $showPasswordInitially ? '' : 'hidden' }}" data-password-wrap>
                        <label for="password" class="mb-2 block text-sm font-semibold text-black">{{ __('account.password') }}</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="{{ $guestStatus === 'new' ? 'new-password' : 'current-password' }}"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            data-checkout-password
                            @if ($showPasswordInitially) required @endif
                        >
                        <p class="mt-2 text-sm text-on-blush/65" data-password-help>
                            {{ in_array($guestStatus, ['existing_complete', 'existing_incomplete'], true) ? __('email.checkout_password_existing_help') : __('email.checkout_password_help') }}
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
                        {{ __('email.checkout_profile_reuse') }}
                    </p>
                @endif
            @endauth

            {{-- Step 2: business (only when needed) --}}
            <fieldset
                class="space-y-4 rounded-2xl border border-border bg-blush-soft/50 p-4 sm:p-5 {{ $showBusinessInitially ? '' : 'hidden' }}"
                data-business-section
                @if (! $showBusinessInitially) disabled @endif
            >
                <legend class="px-1 text-sm font-semibold text-black">
                    <span data-business-step-label>{{ $isGuest ? '2.' : '1.' }}</span>
                    {{ __('email.checkout_business_title') }}
                </legend>
                <p class="text-sm text-on-blush/70">{{ __('email.checkout_business_lede') }}</p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div data-business-field="company" class="{{ ! $isGuest && ! in_array('company', $missingBusinessFields, true) ? 'hidden' : '' }}">
                        <label for="company" class="mb-2 block text-sm font-semibold text-black">{{ __('account.company') }}</label>
                        <input
                            id="company"
                            name="company"
                            type="text"
                            value="{{ old('company', $authUser?->company) }}"
                            autocomplete="organization"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            data-business-required
                            @if ($showBusinessInitially && (! $isGuest && in_array('company', $missingBusinessFields, true))) required @endif
                        >
                        @error('company')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>
                    <div data-business-field="phone" class="{{ ! $isGuest && ! in_array('phone', $missingBusinessFields, true) ? 'hidden' : '' }}">
                        <label for="phone" class="mb-2 block text-sm font-semibold text-black">{{ __('account.phone') }}</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone', $authUser?->phone) }}"
                            autocomplete="tel"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            data-business-required
                            @if ($showBusinessInitially && (! $isGuest && in_array('phone', $missingBusinessFields, true))) required @endif
                        >
                        @error('phone')
                            <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                        @enderror
                    </div>
                    <div data-business-field="billing_country" class="{{ ! $isGuest && ! in_array('billing_country', $missingBusinessFields, true) ? 'hidden' : '' }}">
                        <label for="billing_country" class="mb-2 block text-sm font-semibold text-black">{{ __('account.country') }}</label>
                        <select
                            id="billing_country"
                            name="billing_country"
                            class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                            data-business-required
                            @if ($showBusinessInitially && (! $isGuest && in_array('billing_country', $missingBusinessFields, true))) required @endif
                        >
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
                        <label for="billing_city" class="mb-2 block text-sm font-semibold text-black">{{ __('account.city') }} <span class="font-normal text-on-blush/55">({{ __('email.optional') }})</span></label>
                        <input id="billing_city" name="billing_city" type="text" value="{{ old('billing_city', $authUser?->billing_city) }}" autocomplete="address-level2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="billing_address_line_1" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_1') }} <span class="font-normal text-on-blush/55">({{ __('email.optional') }})</span></label>
                        <input id="billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1', $authUser?->billing_address_line_1) }}" autocomplete="address-line1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                    </div>
                </div>
            </fieldset>

            {{-- Step 3: mail setup (revealed when account/business gate passes) --}}
            <fieldset
                class="space-y-4 rounded-2xl border border-border bg-blush-soft/50 p-4 sm:p-5 {{ $isGuest && $guestStatus === 'pending' ? 'hidden' : '' }}"
                data-mail-setup
                @if ($isGuest && $guestStatus === 'pending') disabled @endif
            >
                <legend class="px-1 text-sm font-semibold text-black">
                    <span data-mail-step-label>{{ $isGuest ? '3.' : ($needsBusiness ? '2.' : '1.') }}</span>
                    {{ __('email.checkout_mail_setup_title') }}
                </legend>
                <p class="text-sm text-on-blush/70">{{ __('email.checkout_mail_setup_lede') }}</p>

                <div>
                    <label for="domain" class="mb-2 block text-sm font-semibold text-black">{{ __('email.domain_label') }}</label>
                    <input
                        id="domain"
                        name="domain"
                        type="text"
                        value="{{ old('domain') }}"
                        required
                        placeholder="yourcompany.com"
                        class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3"
                        data-email-domain-input
                        data-domain-placeholder="{{ $domainSuffixPlaceholder }}"
                    >
                    <p class="mt-2 text-sm text-on-blush/65">{{ __('email.domain_help') }}</p>
                    @error('domain')
                        <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-black">{{ __('email.mailboxes_label') }}</p>
                    <p class="mb-4 text-sm text-on-blush/65">{{ __('email.mailboxes_help') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($localParts as $index => $part)
                            <div class="flex min-w-0 items-center gap-2">
                                <input
                                    name="mailboxes[]"
                                    type="text"
                                    value="{{ $part }}"
                                    required
                                    class="footer-input min-w-0 flex-1 rounded-xl border border-border bg-white px-4 py-3"
                                    aria-label="{{ __('email.mailboxes_label') }} {{ $index + 1 }}"
                                >
                                <span
                                    data-email-domain-suffix
                                    class="max-w-[7.5rem] shrink-0 truncate text-sm font-medium text-on-blush/70 sm:max-w-[9.5rem]"
                                    title="{{ $domainSuffixPlaceholder }}"
                                >{{ $domainSuffixPlaceholder }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </fieldset>

            <x-ui.submit-button
                :label="$plan['is_manual'] ? __('email.request_setup') : __('email.pay_now')"
                :loading="__('account.starting_payment')"
                class="btn btn-primary w-full sm:w-auto"
                disabled
            />
            <p class="text-sm text-on-blush/60" data-checkout-hint>{{ __('email.checkout_continue_hint') }}</p>
        </form>
    </x-layout.page-content>
@endsection
