@props([])

@php
    $user = auth()->user();
    $show = $user
        && $user->isCustomer()
        && ! $user->hasCompleteBusinessProfile();
    $countries = config('site.country_options', []);
    $industries = array_keys(__('account.industries'));
@endphp

@if ($show)
    <div
        data-complete-profile-modal
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 px-4 py-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="complete-profile-title"
    >
        <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-border bg-white p-6 shadow-2xl sm:p-8" data-complete-profile-panel>
            <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ __('account.complete_profile_eyebrow') }}</p>
            <h2 id="complete-profile-title" class="mt-2 text-2xl font-bold text-black">{{ __('account.complete_profile_title') }}</h2>
            <p class="mt-2 text-sm text-on-blush/75">{{ __('account.complete_profile_lede') }}</p>

            @if ($errors->any() && old('_complete_profile'))
                <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route('account.profile.business') }}" class="mt-6 space-y-6" data-submit-form data-complete-profile-form>
                @csrf
                @method('PUT')
                <input type="hidden" name="_complete_profile" value="1">

                <section class="space-y-4 rounded-2xl border border-border bg-blush-soft/40 p-4 sm:p-5">
                    <div>
                        <h3 class="text-base font-bold text-black">{{ __('account.profile_account') }}</h3>
                        <p class="mt-1 text-sm text-on-blush/65">{{ __('account.profile_account_lede') }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="complete_name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.name') }}</label>
                            <input id="complete_name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('name')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_job_title" class="mb-2 block text-sm font-semibold text-black">{{ __('account.job_title') }}</label>
                            <input id="complete_job_title" name="job_title" type="text" value="{{ old('job_title', $user->job_title) }}" required autocomplete="organization-title" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('job_title')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-black">{{ __('account.login_email') }}</label>
                            <input type="email" value="{{ $user->email }}" disabled class="footer-input w-full rounded-xl border border-border bg-white/70 px-4 py-3 text-on-blush/70">
                        </div>
                        <div>
                            <label for="complete_phone" class="mb-2 block text-sm font-semibold text-black">{{ __('account.phone') }}</label>
                            <input id="complete_phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required autocomplete="tel" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('phone')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl border border-border bg-blush-soft/40 p-4 sm:p-5">
                    <div>
                        <h3 class="text-base font-bold text-black">{{ __('account.profile_business') }}</h3>
                        <p class="mt-1 text-sm text-on-blush/65">{{ __('account.profile_business_lede') }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="complete_company" class="mb-2 block text-sm font-semibold text-black">{{ __('account.company') }}</label>
                            <input id="complete_company" name="company" type="text" value="{{ old('company', $user->company) }}" required autocomplete="organization" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('company')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_trading_name" class="mb-2 block text-sm font-semibold text-black">{{ __('account.trading_name') }}</label>
                            <input id="complete_trading_name" name="trading_name" type="text" value="{{ old('trading_name', $user->trading_name) }}" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('trading_name')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_website" class="mb-2 block text-sm font-semibold text-black">{{ __('account.website') }} <span class="font-normal text-on-blush/55">({{ __('email.optional') }})</span></label>
                            <input id="complete_website" name="website" type="text" value="{{ old('website', $user->website) }}" placeholder="https://yourcompany.com" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                        </div>
                        <div>
                            <label for="complete_industry" class="mb-2 block text-sm font-semibold text-black">{{ __('account.industry') }}</label>
                            <select id="complete_industry" name="industry" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                                <option value="">{{ __('account.select_industry') }}</option>
                                @foreach ($industries as $industry)
                                    <option value="{{ $industry }}" @selected(old('industry', $user->industry) === $industry)>{{ __('account.industries.' . $industry) }}</option>
                                @endforeach
                            </select>
                            @error('industry')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_tax_id" class="mb-2 block text-sm font-semibold text-black">{{ __('account.tax_id') }} <span class="font-normal text-on-blush/55">({{ __('email.optional') }})</span></label>
                            <input id="complete_tax_id" name="tax_id" type="text" value="{{ old('tax_id', $user->tax_id) }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                        </div>
                        <div>
                            <label for="complete_registration_number" class="mb-2 block text-sm font-semibold text-black">{{ __('account.registration_number') }} <span class="font-normal text-on-blush/55">({{ __('email.optional') }})</span></label>
                            <input id="complete_registration_number" name="registration_number" type="text" value="{{ old('registration_number', $user->registration_number) }}" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-2xl border border-border bg-blush-soft/40 p-4 sm:p-5">
                    <div>
                        <h3 class="text-base font-bold text-black">{{ __('account.profile_billing') }}</h3>
                        <p class="mt-1 text-sm text-on-blush/65">{{ __('account.profile_billing_lede') }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="complete_billing_address_line_1" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_1') }}</label>
                            <input id="complete_billing_address_line_1" name="billing_address_line_1" type="text" value="{{ old('billing_address_line_1', $user->billing_address_line_1) }}" required autocomplete="address-line1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('billing_address_line_1')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="complete_billing_address_line_2" class="mb-2 block text-sm font-semibold text-black">{{ __('account.address_line_2') }} <span class="font-normal text-on-blush/55">({{ __('email.optional') }})</span></label>
                            <input id="complete_billing_address_line_2" name="billing_address_line_2" type="text" value="{{ old('billing_address_line_2', $user->billing_address_line_2) }}" autocomplete="address-line2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                        </div>
                        <div>
                            <label for="complete_billing_city" class="mb-2 block text-sm font-semibold text-black">{{ __('account.city') }}</label>
                            <input id="complete_billing_city" name="billing_city" type="text" value="{{ old('billing_city', $user->billing_city) }}" required autocomplete="address-level2" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('billing_city')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_billing_state" class="mb-2 block text-sm font-semibold text-black">{{ __('account.state') }}</label>
                            <input id="complete_billing_state" name="billing_state" type="text" value="{{ old('billing_state', $user->billing_state) }}" required autocomplete="address-level1" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('billing_state')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_billing_postcode" class="mb-2 block text-sm font-semibold text-black">{{ __('account.postcode') }}</label>
                            <input id="complete_billing_postcode" name="billing_postcode" type="text" value="{{ old('billing_postcode', $user->billing_postcode) }}" required autocomplete="postal-code" class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                            @error('billing_postcode')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="complete_billing_country" class="mb-2 block text-sm font-semibold text-black">{{ __('account.country') }}</label>
                            <select id="complete_billing_country" name="billing_country" required class="footer-input w-full rounded-xl border border-border bg-white px-4 py-3">
                                <option value="">{{ __('account.select_country') }}</option>
                                @foreach ($countries as $code => $label)
                                    <option value="{{ $code }}" @selected(old('billing_country', $user->billing_country ?: 'NG') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('billing_country')
                                <p class="mt-2 text-sm text-rose">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <x-ui.submit-button :label="__('account.complete_profile_save')" :loading="__('account.processing')" class="btn btn-primary w-full" />
            </form>
        </div>
    </div>
@endif
