@extends('layouts.app')

@section('title', __('hosting.order_received_title') . ' — ' . config('site.short_name'))
@section('meta_description', 'Your Lemonwares VPS order request was received.')
@section('focus_flow', '1')

@section('content')
    <x-layout.page-hero
        eyebrow="VPS"
        :title="__('hosting.order_received_title')"
        :lede="__('hosting.order_received_lede')"
        cta-href="{{ route('home') }}"
        :cta-label="__('hosting.back_home')"
    />

    <x-layout.page-content>
        <div class="mx-auto max-w-2xl rounded-3xl border border-border bg-white p-6 sm:p-8">
            @if (session('hosting_feedback'))
                <p @class([
                    'mb-5 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => (session('hosting_feedback.type') === 'success'),
                    'border border-sky-200 bg-sky-50 text-sky-800' => (session('hosting_feedback.type') === 'info'),
                    'border border-rose/20 bg-rose/5 text-rose' => (session('hosting_feedback.type') === 'error'),
                ])>{{ session('hosting_feedback.message') }}</p>
            @endif

            <p class="section-label mb-3">Order Summary</p>
            <h2 class="text-2xl font-bold text-black">{{ $lead->plan_name }}</h2>
            <p class="mt-2 text-sm text-on-blush/70">{{ $lead->spec_label }}</p>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">Billing cycle</dt>
                    <dd class="font-semibold text-black">{{ __('hosting.cycles.' . ($lead->billing_cycle ?: 'monthly')) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">Amount</dt>
                    <dd class="font-semibold text-rose">
                        {{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->amount_usd ?? 0)) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">Payment status</dt>
                    <dd class="font-semibold text-black">{{ ucfirst(str_replace('_', ' ', $lead->payment_status ?: $lead->status ?: 'pending')) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-blush/60">Email</dt>
                    <dd class="font-semibold text-black">{{ $lead->email }}</dd>
                </div>
            </dl>

            <p class="mt-6 text-sm text-on-blush/70">
                {{ __('hosting.vps_notice') }}
                @if (($lead->payment_status ?? '') === 'successful' || ($lead->status ?? '') === 'paid')
                    Payment is confirmed. Provisioning will follow on Hetzner.
                @else
                    Complete payment with Flutterwave to continue.
                @endif
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @if (! in_array($lead->payment_status, ['successful'], true) && ($lead->status ?? '') !== 'paid')
                    <form method="POST" action="{{ route('hosting.flutterwave.pay', $lead) }}">
                        @csrf
                        <button type="submit" class="inline-flex rounded-2xl bg-rose px-5 py-3 text-sm font-bold text-white">
                            Pay with Flutterwave
                        </button>
                    </form>
                @endif

                <a href="{{ route('home') }}" class="inline-flex rounded-2xl border border-border px-5 py-3 text-sm font-bold text-black">
                    {{ __('hosting.back_home') }}
                </a>
            </div>
        </div>
    </x-layout.page-content>
@endsection
