@extends('layouts.account')

@section('title', $order->domain . ' — ' . config('site.short_name'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_domains')"
        :title="$order->domain"
        :lede="$order->isTransfer() ? __('account.product_domain_transfer') : __('account.product_domain_register')"
        :back-href="route('account.domains.index')"
        :back-label="__('account.nav_domains')"
    />

    <section class="account-panel">
        <dl class="account-dl">
            <div>
                <dt>{{ __('account.status_label') }}</dt>
                <dd>{{ $order->payment_status ?: $order->status }}</dd>
            </div>
            <div>
                <dt>{{ __('account.amount') }}</dt>
                <dd>${{ number_format((float) $order->amount_usd, 2) }}</dd>
            </div>
            <div>
                <dt>{{ __('account.reference') }}</dt>
                <dd class="account-mono">{{ $order->payment_reference ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('account.years') }}</dt>
                <dd>{{ $order->reg_period }}</dd>
            </div>
        </dl>
    </section>
@endsection
