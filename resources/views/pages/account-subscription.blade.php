@extends('layouts.account')

@section('title', ($service->product_name ?: __('account.subscription_whmcs')) . ' — ' . config('site.short_name'))
@section('meta_description', __('account.subscriptions_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_subscriptions')"
        :title="$service->product_name ?: __('account.subscription_whmcs')"
        :lede="$service->domain ?: __('account.subscriptions_lede')"
        :back-href="route('account.subscriptions.index')"
        :back-label="__('account.nav_subscriptions')"
    >
        <x-slot:actions>
            @if ($siteUrl)
                <a href="{{ $siteUrl }}" target="_blank" rel="noopener noreferrer" class="account-btn-ghost">{{ __('account.visit_site') }}</a>
            @endif
            @if ($cpanelUrl)
                <a href="{{ $cpanelUrl }}" class="account-btn-primary">{{ __('account.open_cpanel') }}</a>
            @endif
        </x-slot:actions>
    </x-account.page-header>

    <div class="account-page-stack">
        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.subscription_details') }}</h2>
            <dl class="account-dl mt-5">
                <div>
                    <dt>{{ __('account.status_label') }}</dt>
                    <dd><span class="account-pill is-muted">{{ $service->status ?: '—' }}</span></dd>
                </div>
                <div>
                    <dt>{{ __('account.stat_domain') }}</dt>
                    <dd>{{ $service->domain ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.billing_cycle') }}</dt>
                    <dd>{{ $service->billing_cycle ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.next_due') }}</dt>
                    <dd>{{ $service->next_due_date?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}</dd>
                </div>
                @if ($service->username)
                    <div>
                        <dt>{{ __('account.username') }}</dt>
                        <dd class="account-mono">{{ $service->username }}</dd>
                    </div>
                @endif
                <div>
                    <dt>{{ __('account.subscription_service_id') }}</dt>
                    <dd class="account-mono">#{{ $service->whmcs_service_id }}</dd>
                </div>
            </dl>

            <div class="account-hero-actions">
                @if ($siteUrl)
                    <a href="{{ $siteUrl }}" target="_blank" rel="noopener noreferrer" class="account-btn-ghost">{{ __('account.visit_site') }}</a>
                @endif
                @if ($cpanelUrl)
                    <a href="{{ $cpanelUrl }}" class="account-btn-primary">{{ __('account.open_cpanel') }}</a>
                @endif
                <a href="{{ route('account.invoices.index') }}" class="account-btn-ghost">{{ __('account.nav_invoices') }}</a>
            </div>
        </section>

        @if ($relatedInvoices->isNotEmpty())
            <section class="account-panel account-panel-flush">
                <div class="account-panel-toolbar">
                    <div>
                        <h2 class="account-panel-title">{{ __('account.subscription_related_invoices') }}</h2>
                        <p class="account-panel-lede">{{ __('account.subscription_related_invoices_lede') }}</p>
                    </div>
                </div>
                <div class="account-list">
                    @foreach ($relatedInvoices as $invoice)
                        <a href="{{ $invoice['url'] }}" class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $invoice['label'] }}</p>
                                <p class="account-list-meta">
                                    <span class="account-mono">{{ $invoice['reference'] }}</span>
                                    · {{ $invoice['currency'] }} {{ number_format((float) $invoice['amount'], 2) }}
                                    · {{ $invoice['date'] ?: '—' }}
                                </p>
                            </div>
                            <div class="account-list-side">
                                <span class="account-pill is-muted">{{ $invoice['status'] ?: '—' }}</span>
                                <span class="account-metric-cta">{{ __('account.view') }} →</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
