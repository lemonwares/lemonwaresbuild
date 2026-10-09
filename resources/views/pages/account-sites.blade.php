@extends('layouts.account')

@section('title', __('account.service_hosting') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.hosting_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs
        :items="[__('account.nav_products') => route('account.products.index'), __('account.nav_hosting') => null]"
        :back="route('account.products.index')"
    />
@endsection

@section('content')
    <x-account.table-card :title="__('account.service_hosting')" :count="$sharedHosting->count()">
        <x-slot:tools>
            <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="acc-btn is-primary">{{ __('account.buy_hosting') }}</a>
        </x-slot:tools>

        @if ($sharedHosting->isEmpty())
            <div class="acc-empty">
                <p class="acc-empty-title">{{ __('account.no_hosting') }}</p>
                <p>{{ __('account.hosting_lede') }}</p>
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.service_hosting') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sharedHosting as $site)
                            <tr>
                                <td>
                                    <a href="{{ route('account.hosting.show', $site) }}" class="acc-link">{{ $site->displayName() }}</a>
                                    <span class="acc-cell-sub">{{ $site->plan_name }}{{ $site->spec_label ? ' · ' . $site->spec_label : '' }}</span>
                                </td>
                                <td><x-account.status :value="$site->statusLabel()" /></td>
                                <td class="is-actions">
                                    <div class="acc-row-actions">
                                        @if ($site->panelUrl())
                                            <a href="{{ $site->panelUrl() }}" target="_blank" rel="noopener noreferrer" class="acc-btn is-sm">{{ __('account.open_panel') }}</a>
                                        @endif
                                        <a href="{{ route('account.hosting.show', $site) }}" class="acc-btn is-sm">{{ __('account.view_service') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-account.table-card>
@endsection
