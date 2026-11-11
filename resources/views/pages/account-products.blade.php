@extends('layouts.account')

@section('title', __('account.nav_products') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.products_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs :items="[__('account.nav_products') => null]" />
@endsection

@section('content')
    <x-account.table-card :title="__('account.nav_products')" :count="$products->count()">
        <x-slot:tools>
            <a href="{{ route('email.plans') }}" class="acc-btn is-primary">{{ __('account.buy_email') }}</a>
        </x-slot:tools>

        <div class="acc-tabs">
            <a href="{{ route('account.domains.index') }}" class="acc-tab">{{ __('account.nav_domains') }}</a>
            <a href="{{ route('account.email.index') }}" class="acc-tab">{{ __('account.nav_email') }}</a>
            <a href="{{ route('account.vps.index') }}" class="acc-tab">{{ __('account.nav_vps') }}</a>
            <a href="{{ route('account.hosting.index') }}" class="acc-tab">{{ __('account.nav_hosting') }}</a>
        </div>

        @if ($products->isEmpty())
            <div class="acc-empty">
                <p class="acc-empty-title">{{ __('account.products_empty') }}</p>
                <p>{{ __('account.products_lede') }}</p>
                <p class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('email.plans') }}" class="acc-btn is-primary">{{ __('account.buy_email') }}</a>
                    <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="acc-btn">{{ __('account.buy_hosting') }}</a>
                </p>
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.subscription') }}</th>
                            <th>{{ __('account.subscription_type') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ $product['url'] }}" class="acc-link">{{ $product['label'] }}</a>
                                    @if ($product['meta'])
                                        <span class="acc-cell-sub">{{ $product['meta'] }}</span>
                                    @endif
                                </td>
                                <td class="acc-cell-muted">{{ __('account.product_type_' . $product['type']) }}</td>
                                <td><x-account.status :value="$product['status']" /></td>
                                <td class="is-actions"><a href="{{ $product['url'] }}" class="acc-btn is-sm">{{ __('account.manage') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-account.table-card>
@endsection
