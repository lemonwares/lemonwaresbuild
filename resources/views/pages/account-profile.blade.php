@extends('layouts.account')

@section('title', __('account.profile') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.profile_lede'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-2">{{ __('account.nav_profile') }}</p>
        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.profile') }}</h1>
        <p class="lede mt-2">{{ __('account.profile_lede') }}</p>
    </div>

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6" data-submit-form>
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6 sm:p-8">
            <h2 class="text-lg font-bold text-black">{{ __('account.profile_account') }}</h2>
            <p class="mt-1 text-sm text-on-blush/65">{{ __('account.profile_account_lede') }}</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="job_title" class="mb-2 block text-sm font-semibold text-black">{{ __('account.job_title') }}</label>
                    <input id="job_title" name="job_title" type="text" value="{{ old('job_title', $user->job_title) }}" autocomplete="organization-title" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-black">{{ __('account.login_email') }}</label>
                    <input type="email" value="{{ $user->email }}" disabled class="footer-input w-full rounded-xl border border-border bg-blush-soft px-4 py-3 text-on-blush/70">
                    <p class="mt-1.5 text-xs text-on-blush/55">{{ __('account.login_email_help') }}</p>
                </div>
                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-black">{{ __('account.phone') }}</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-border bg-white p-6 sm:p-8">
            <h2 class="text-lg font-bold text-black">{{ __('account.profile_business') }}</h2>
            <p class="mt-1 text-sm text-on-blush/65">{{ __('account.profile_business_lede') }}</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="company" class="mb-2 block text-sm font-semibold text-black">{{ __('account.company') }}</label>
                    <input id="company" name="company" type="text" value="{{ old('company', $user->company) }}" autocomplete="organization" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="trading_name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.trading_name') }}</label>
                    <input id="trading_name" name="trading_name" type="text" value="{{ old('trading_name', $user->trading_name) }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="website" class="mb-2 block text-sm font-semibold text-black">{{ __('account.website') }}</label>
                    <input id="website" name="website" type="text" value="{{ old('website', $user->website) }}" placeholder="https://yourcompany.com" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="industry" class="mb-2 block text-sm font-semibold text-black">{{ __('account.industry') }}</label>
                    <select id="industry" name="industry" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                        <option value="">{{ __('account.select_industry') }}</option>
                        @foreach ($industries as $industry)
                            <option value="{{ $industry }}" @selected(old('industry', $user->industry) === $industry)>{{ __('account.industries.' . $industry) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="tax_id" class="mb-2 block text-sm font-semibold text-black">{{ __('account.tax_id') }}</label>
                    <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id', $user->tax_id) }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="registration_number" class="mb-2 block text-sm font-semibold text-black">{{ __('account.registration_number') }}</label>
                    <input id="registration_number" name="registration_number" type="text" value="{{ old('registration_number', $user->registration_number) }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-border bg-white p-6 sm:p-8">
            <h2 class="text-lg font-bold text-black">{{ __('account.profile_billing') }}</h2>
            <p class="mt-1 text-sm text-on-blush/65">{{ __('account.profile_billing_lede') }}</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="billing_address_line_1" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_1') }}</label>
                    <input id="billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1', $user->billing_address_line_1) }}" autocomplete="address-line1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div class="sm:col-span-2">
                    <label for="billing_address_line_2" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_2') }}</label>
                    <input id="billing_address_line_2" name="billing_address_line_2" type="text" value="{{ old('billing_address_line_2', $user->billing_address_line_2) }}" autocomplete="address-line2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="billing_city" class="mb-2 block text-sm font-semibold text-black">{{ __('account.city') }}</label>
                    <input id="billing_city" name="billing_city" type="text" value="{{ old('billing_city', $user->billing_city) }}" autocomplete="address-level2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="billing_state" class="mb-2 block text-sm font-semibold text-black">{{ __('account.state') }}</label>
                    <input id="billing_state" name="billing_state" type="text" value="{{ old('billing_state', $user->billing_state) }}" autocomplete="address-level1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="billing_postcode" class="mb-2 block text-sm font-semibold text-black">{{ __('account.postcode') }}</label>
                    <input id="billing_postcode" name="billing_postcode" type="text" value="{{ old('billing_postcode', $user->billing_postcode) }}" autocomplete="postal-code" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                </div>
                <div>
                    <label for="billing_country" class="mb-2 block text-sm font-semibold text-black">{{ __('account.country') }}</label>
                    <select id="billing_country" name="billing_country" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                        <option value="">{{ __('account.select_country') }}</option>
                        @foreach ($countries as $code => $label)
                            <option value="{{ $code }}" @selected(old('billing_country', $user->billing_country) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('account.settings') }}" class="text-sm font-semibold text-rose hover:underline">{{ __('account.settings_shortcut') }}</a>
            <x-ui.submit-button :label="__('account.save_profile')" :loading="__('account.saving')" class="btn btn-primary" />
        </div>
    </form>
@endsection
