@extends('layouts.app')

@section('title', __('hosting.order_received_title') . ' — ' . config('site.short_name'))
@section('meta_description', $lead->isShared() ? __('hosting.whmcs_notice') : 'Your Lemonwares VPS order request was received.')
@section('focus_flow', '1')

@section('content')
    <x-layout.page-hero
        :eyebrow="$lead->isShared() ? __('hosting.secure_checkout') : 'VPS'"
        :title="__('hosting.order_received_title')"
        :lede="$lead->isShared() ? __('hosting.order_received_whmcs_lede') : __('hosting.order_received_lede')"
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
                @if ($lead->hostname && $lead->isShared())
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/60">{{ __('hosting.domain_label') }}</dt>
                        <dd class="font-semibold text-black">{{ $lead->hostname }}</dd>
                    </div>
                @endif
                @if ($lead->hosting_amount_usd !== null || $lead->domain_amount_usd !== null)
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/60">{{ __('hosting.order_summary_hosting') }}</dt>
                        <dd class="font-semibold text-black">{{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->hosting_amount_usd ?? 0)) }}</dd>
                    </div>
                    @if ((float) ($lead->domain_amount_usd ?? 0) > 0 || $lead->hostname)
                        <div class="flex justify-between gap-4 border-b border-border pb-3">
                            <dt class="text-on-blush/60">{{ __('hosting.order_summary_domain') }}</dt>
                            <dd class="font-semibold text-black">
                                @if ((float) ($lead->domain_amount_usd ?? 0) > 0)
                                    {{ \App\Support\HostingPricing::dualPriceDisplay((float) $lead->domain_amount_usd) }}
                                @else
                                    {{ __('hosting.order_summary_included') }}
                                @endif
                            </dd>
                        </div>
                    @endif
                @endif
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
                @if ($lead->checkout_provider === 'whmcs' && $lead->whmcs_sync_status === 'checkout_synced')
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/60">WHMCS client</dt>
                        <dd class="font-semibold text-black">#{{ $lead->whmcs_client_id }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/60">WHMCS order</dt>
                        <dd class="font-semibold text-black">#{{ $lead->whmcs_order_id }} (pending)</dd>
                    </div>
                    @if ($lead->whmcs_invoice_id)
                        <div class="flex justify-between gap-4 border-b border-border pb-3">
                            <dt class="text-on-blush/60">WHMCS invoice</dt>
                            <dd class="font-semibold text-black">#{{ $lead->whmcs_invoice_id }}</dd>
                        </div>
                    @endif
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-on-blush/60">Email</dt>
                    <dd class="font-semibold text-black">{{ $lead->email }}</dd>
                </div>
            </dl>

            <p class="mt-6 text-sm text-on-blush/70">
                @if ($lead->isShared())
                    @if ($lead->whmcs_sync_status === 'checkout_synced' && \App\Support\WhmcsSettings::deferPaymentRedirect())
                        {{ __('hosting.order_received_whmcs_pending') }}
                    @elseif ($lead->isAwaitingPayment())
                        {{ __('hosting.payment_awaiting') }}
                    @elseif ($lead->isPaid())
                        {{ __('hosting.payment_confirmed_shared') }}
                    @else
                        {{ __('hosting.whmcs_notice') }}
                    @endif
                @else
                    {{ __('hosting.vps_notice') }}
                    @if ($lead->isPaid())
                        {{ __('hosting.payment_confirmed_vps') }}
                    @else
                        {{ __('hosting.payment_awaiting') }}
                    @endif
                @endif
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($lead->isAwaitingPayment())
                    <form method="POST" action="{{ route('hosting.flutterwave.pay', $lead) }}">
                        @csrf
                        <button type="submit" class="inline-flex rounded-2xl bg-rose px-5 py-3 text-sm font-bold text-white">
                            {{ __('email.pay_with_flutterwave') }}
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
