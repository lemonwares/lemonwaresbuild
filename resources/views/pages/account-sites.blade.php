@extends('layouts.account')

@section('title', __('account.service_hosting') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.hosting_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_hosting')"
        :title="__('account.service_hosting')"
        :lede="__('account.hosting_lede')"
    >
        <x-slot:actions>
            <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="account-btn-ghost">{{ __('account.buy_hosting') }}</a>
        </x-slot:actions>
    </x-account.page-header>

    <section class="account-panel">
        @if ($sharedHosting->isEmpty())
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.no_hosting') }}</p>
                <div class="account-hero-actions">
                    <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="account-btn-primary">{{ __('account.buy_hosting') }}</a>
                </div>
            </div>
        @else
            <div class="account-list">
                @foreach ($sharedHosting as $site)
                    <div class="account-list-item">
                        <div class="account-list-copy">
                            <p class="account-list-title">{{ $site->displayName() }}</p>
                            <p class="account-list-meta">{{ $site->plan_name }}{{ $site->spec_label ? ' · ' . $site->spec_label : '' }}</p>
                            <p class="account-list-meta">{{ $site->statusLabel() }}</p>
                        </div>
                        <div class="account-list-side">
                            <a href="{{ route('account.hosting.show', $site) }}" class="account-btn-ghost">{{ __('account.view_service') }}</a>
                            @if ($site->panelUrl())
                                <a href="{{ $site->panelUrl() }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('account.open_panel') }}</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
