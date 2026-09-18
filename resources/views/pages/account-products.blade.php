@extends('layouts.account')

@section('title', __('account.nav_products') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.products_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_products')"
        :title="__('account.products_title')"
        :lede="__('account.products_lede')"
    />

    <div class="account-page-stack">
        <div class="account-chip-row">
            <a href="{{ route('account.domains.index') }}" class="account-chip">{{ __('account.nav_domains') }}</a>
            <a href="{{ route('account.email.index') }}" class="account-chip">{{ __('account.nav_email') }}</a>
            <a href="{{ route('account.vps.index') }}" class="account-chip">{{ __('account.nav_vps') }}</a>
            <a href="{{ route('account.hosting.index') }}" class="account-chip">{{ __('account.nav_hosting') }}</a>
        </div>

        @if ($products->isEmpty())
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.products_empty') }}</p>
                <p class="account-empty-lede">{{ __('account.products_lede') }}</p>
                <div class="account-hero-actions">
                    <a href="{{ route('email.plans') }}" class="account-btn-primary">{{ __('account.buy_email') }}</a>
                    <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="account-btn-ghost">{{ __('account.buy_hosting') }}</a>
                </div>
            </div>
        @else
            <div class="account-list">
                @foreach ($products as $product)
                    <a href="{{ $product['url'] }}" class="account-list-item">
                        <div class="account-list-copy">
                            <p class="account-list-title">
                                <span class="account-pill">{{ __('account.product_type_'.$product['type']) }}</span>
                                <span>{{ $product['label'] }}</span>
                            </p>
                            <p class="account-list-meta">{{ $product['meta'] }}</p>
                        </div>
                        <div class="account-list-side">
                            <span class="account-pill is-muted">{{ $product['status'] }}</span>
                            <span class="account-metric-cta">{{ __('account.manage') }} →</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
