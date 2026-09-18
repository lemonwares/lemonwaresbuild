@extends('layouts.account')

@section('title', __('account.nav_domains') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.domains_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_domains')"
        :title="__('account.domains_title')"
        :lede="__('account.domains_lede')"
        :back-href="route('account.products.index')"
        :back-label="__('account.nav_products')"
    />

    <div class="account-page-stack">
        @if ($orders->isEmpty())
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.domains_empty') }}</p>
                <p class="account-empty-lede">{{ __('account.domains_lede') }}</p>
            </div>
        @else
            <div class="account-list">
                @foreach ($orders as $order)
                    <a href="{{ route('account.domains.show', $order) }}" class="account-list-item">
                        <div class="account-list-copy">
                            <p class="account-list-title">{{ $order->domain }}</p>
                            <p class="account-list-meta">
                                {{ $order->isTransfer() ? __('account.product_domain_transfer') : __('account.product_domain_register') }}
                                · {{ $order->reg_period }} {{ __('account.years') }}
                            </p>
                        </div>
                        <div class="account-list-side">
                            <span class="account-pill is-muted">{{ $order->payment_status ?: $order->status }}</span>
                            <span class="account-metric-cta">{{ __('account.view') }} →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
