@extends('layouts.account')

@section('title', __('account.profile') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.profile_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_profile')"
        :title="__('account.profile')"
        :lede="__('account.profile_lede')"
    />

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <div class="account-page-stack">
        <form method="POST" action="{{ route('account.profile.update') }}" class="account-page-stack" data-submit-form>
            @csrf
            @method('PUT')

            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.profile_account') }}</h2>
                <p class="account-panel-lede">{{ __('account.profile_account_lede') }}</p>
                <div class="account-form-grid mt-5">
                    <div class="account-field">
                        <label for="name">{{ __('account.name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    </div>
                    <div class="account-field">
                        <label for="job_title">{{ __('account.job_title') }}</label>
                        <input id="job_title" name="job_title" type="text" value="{{ old('job_title', $user->job_title) }}" autocomplete="organization-title">
                    </div>
                    <div class="account-field">
                        <label>{{ __('account.login_email') }}</label>
                        <input type="email" value="{{ $user->email }}" disabled style="background:var(--color-blush-soft);color:color-mix(in srgb, var(--color-on-blush) 70%, transparent)">
                        <p class="mt-1.5 text-xs text-on-blush/55">{{ __('account.login_email_help') }}</p>
                    </div>
                    <div class="account-field">
                        <label for="phone">{{ __('account.phone') }}</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel">
                    </div>
                </div>
            </section>

            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.profile_business') }}</h2>
                <p class="account-panel-lede">{{ __('account.profile_business_lede') }}</p>
                <div class="account-form-grid mt-5">
                    <div class="account-field">
                        <label for="company">{{ __('account.company') }}</label>
                        <input id="company" name="company" type="text" value="{{ old('company', $user->company) }}" autocomplete="organization">
                    </div>
                    <div class="account-field">
                        <label for="trading_name">{{ __('account.trading_name') }}</label>
                        <input id="trading_name" name="trading_name" type="text" value="{{ old('trading_name', $user->trading_name) }}">
                    </div>
                    <div class="account-field">
                        <label for="website">{{ __('account.website') }}</label>
                        <input id="website" name="website" type="text" value="{{ old('website', $user->website) }}" placeholder="https://yourcompany.com">
                    </div>
                    <div class="account-field">
                        <label for="industry">{{ __('account.industry') }}</label>
                        <select id="industry" name="industry">
                            <option value="">{{ __('account.select_industry') }}</option>
                            @foreach ($industries as $industry)
                                <option value="{{ $industry }}" @selected(old('industry', $user->industry) === $industry)>{{ __('account.industries.' . $industry) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="account-field">
                        <label for="tax_id">{{ __('account.tax_id') }}</label>
                        <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id', $user->tax_id) }}">
                    </div>
                    <div class="account-field">
                        <label for="registration_number">{{ __('account.registration_number') }}</label>
                        <input id="registration_number" name="registration_number" type="text" value="{{ old('registration_number', $user->registration_number) }}">
                    </div>
                </div>
            </section>

            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.profile_billing') }}</h2>
                <p class="account-panel-lede">{{ __('account.profile_billing_lede') }}</p>
                <div class="account-form-grid mt-5">
                    <div class="account-field is-full">
                        <label for="billing_address_line_1">{{ __('account.address_line_1') }}</label>
                        <input id="billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1', $user->billing_address_line_1) }}" autocomplete="address-line1">
                    </div>
                    <div class="account-field is-full">
                        <label for="billing_address_line_2">{{ __('account.address_line_2') }}</label>
                        <input id="billing_address_line_2" name="billing_address_line_2" type="text" value="{{ old('billing_address_line_2', $user->billing_address_line_2) }}" autocomplete="address-line2">
                    </div>
                    <div class="account-field">
                        <label for="billing_city">{{ __('account.city') }}</label>
                        <input id="billing_city" name="billing_city" type="text" value="{{ old('billing_city', $user->billing_city) }}" autocomplete="address-level2">
                    </div>
                    <div class="account-field">
                        <label for="billing_state">{{ __('account.state') }}</label>
                        <input id="billing_state" name="billing_state" type="text" value="{{ old('billing_state', $user->billing_state) }}" autocomplete="address-level1">
                    </div>
                    <div class="account-field">
                        <label for="billing_postcode">{{ __('account.postcode') }}</label>
                        <input id="billing_postcode" name="billing_postcode" type="text" value="{{ old('billing_postcode', $user->billing_postcode) }}" autocomplete="postal-code">
                    </div>
                    <div class="account-field">
                        <label for="billing_country">{{ __('account.country') }}</label>
                        <select id="billing_country" name="billing_country">
                            <option value="">{{ __('account.select_country') }}</option>
                            @foreach ($countries as $code => $label)
                                <option value="{{ $code }}" @selected(old('billing_country', $user->billing_country) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('account.settings') }}" class="account-metric-cta">{{ __('account.settings_shortcut') }}</a>
                <x-ui.submit-button :label="__('account.save_profile')" :loading="__('account.saving')" class="account-btn-primary" />
            </div>
        </form>

        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.change_password') }}</h2>
            <p class="account-panel-lede">{{ __('account.change_password_lede') }}</p>
            <form method="POST" action="{{ route('account.profile.password') }}" class="mt-5 space-y-5" data-submit-form>
                @csrf
                @method('PUT')
                <div class="account-field">
                    <label for="current_password">{{ __('account.password_current') }}</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
                </div>
                <div class="account-form-grid">
                    <div class="account-field">
                        <label for="password">{{ __('account.password_new') }}</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password">
                    </div>
                    <div class="account-field">
                        <label for="password_confirmation">{{ __('account.password_confirm') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>
                <x-ui.submit-button :label="__('account.change_password_action')" :loading="__('account.saving')" class="account-btn-primary" />
            </form>
        </section>
    </div>
@endsection
