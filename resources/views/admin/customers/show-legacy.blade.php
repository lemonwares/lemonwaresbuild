@extends('layouts.admin')

@section('title', $legacyCustomer->displayName() . ' — CRM')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        :title="$legacyCustomer->displayName()"
        :lede="$legacyCustomer->company ?: 'No company on file'"
        :back-href="route('admin.customers.index', ['source' => 'legacy'])"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Customers', 'href' => route('admin.customers.index', ['source' => 'legacy'])],
            ['label' => $legacyCustomer->displayName()],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                @if ($legacyCustomer->user)
                    <a href="{{ route('admin.customers.show', $legacyCustomer->user) }}" class="admin-btn-ghost">Native profile</a>
                @endif
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-customer-detail">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Legacy customer snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">WHMCS services</span>
                <span class="admin-metric-value">{{ $history['counts']['services'] }}</span>
                <span class="admin-metric-meta">Client #{{ $legacyCustomer->whmcs_client_id }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $legacyCustomer->status ?: '—' }}</span>
                <span class="admin-metric-meta">WHMCS client status</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Unpaid invoices</span>
                <span class="admin-metric-value">{{ $history['unpaidCount'] }}</span>
                <span class="admin-metric-meta">{{ $history['activeServices'] }} active services</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Linked</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $legacyCustomer->user ? 'Yes' : 'No' }}</span>
                <span class="admin-metric-meta">Native LemonWares account</span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Profile</h2>
                    <div class="admin-pill-row">
                        <span class="admin-pill is-info">WHMCS</span>
                        @if ($legacyCustomer->user)
                            <span class="admin-pill is-ok">Linked Native</span>
                        @endif
                    </div>
                </div>
                <dl class="admin-dl">
                    <div><dt>Name</dt><dd>{{ $legacyCustomer->full_name ?: '—' }}@if ($legacyCustomer->displayName() !== $legacyCustomer->full_name) <span class="admin-mini-status">placeholder in WHMCS</span>@endif</dd></div>
                    <div><dt>Email</dt><dd>{{ $legacyCustomer->email ?: '—' }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $legacyCustomer->phone ?: '—' }}</dd></div>
                    <div><dt>Company</dt><dd>{{ $legacyCustomer->company ?: '—' }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $legacyCustomer->status ?: '—' }}</dd></div>
                    <div><dt>WHMCS Client ID</dt><dd>{{ $legacyCustomer->whmcs_client_id }}</dd></div>
                    <div class="admin-dl-span">
                        <dt>Linked LemonWares account</dt>
                        <dd>
                            @if ($legacyCustomer->user)
                                <a href="{{ route('admin.customers.show', $legacyCustomer->user) }}">
                                    {{ $legacyCustomer->user->name }} ({{ $legacyCustomer->user->email }})
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

        </div>

        @include('admin.customers.partials.whmcs-history')
    </div>
@endsection
