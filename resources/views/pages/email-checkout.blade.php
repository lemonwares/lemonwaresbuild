@extends('layouts.app')

@section('title', __('email.checkout_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('email.checkout_lede'))
@section('focus_flow', '1')

@section('content')
    <x-layout.page-hero
        :eyebrow="__('email.eyebrow')"
        :title="__('email.checkout_title')"
        :lede="__('email.checkout_lede')"
        cta-href="#page-content"
        :cta-label="__('email.pay_now')"
    />

    <x-layout.page-content>
        <x-ui.flash />

        @if ($errors->any())
            <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
        @endif

        @php
            $domainSuffixPlaceholder = '@' . __('email.domain_suffix_placeholder');
        @endphp

        <form method="POST" action="{{ route('email.checkout.store') }}" class="space-y-5 rounded-3xl border border-border bg-white p-6 sm:p-8" data-email-checkout data-submit-form>
            @csrf
            <input type="hidden" name="plan" value="{{ $plan['key'] }}">
            <input type="hidden" name="billing_cycle" value="{{ $cycle }}">

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

            <fieldset>
                <legend class="mb-2 text-sm font-semibold text-black">{{ __('email.mailboxes_label') }}</legend>
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
            </fieldset>

            <x-ui.submit-button :label="__('email.pay_now')" :loading="__('account.starting_payment')" class="btn btn-primary w-full sm:w-auto" />
        </form>
    </x-layout.page-content>
@endsection
