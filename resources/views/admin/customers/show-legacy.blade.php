@extends('layouts.admin')

@section('title', ($legacyCustomer->full_name ?: 'Legacy Customer') . ' — CRM')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        :title="$legacyCustomer->full_name ?: 'Unknown customer'"
        :lede="$legacyCustomer->company ?: 'No company on file'"
        :back-href="route('admin.customers.index', ['source' => 'legacy'])"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Customers', 'href' => route('admin.customers.index', ['source' => 'legacy'])],
            ['label' => $legacyCustomer->full_name ?: 'Legacy customer'],
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
                <span class="admin-metric-value">{{ array_sum($whmcsServiceSummary) }}</span>
                <span class="admin-metric-meta">Client #{{ $legacyCustomer->whmcs_client_id }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $legacyCustomer->status ?: '—' }}</span>
                <span class="admin-metric-meta">WHMCS client status</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Active</span>
                <span class="admin-metric-value">{{ (int) ($whmcsServiceSummary['active'] ?? 0) }}</span>
                <span class="admin-metric-meta">Live services</span>
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
                        <span class="admin-pill is-info">Legacy WHMCS</span>
                        @if ($legacyCustomer->user)
                            <span class="admin-pill is-ok">Linked Native</span>
                        @endif
                    </div>
                </div>
                <dl class="admin-dl">
                    <div><dt>Name</dt><dd>{{ $legacyCustomer->full_name ?: '—' }}</dd></div>
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

            <section class="admin-panel admin-panel-span">
                <div class="admin-panel-toolbar">
                    <div>
                        <h2 class="admin-dash-panel-title">Services</h2>
                        <p class="admin-dash-panel-lede">WHMCS services for this legacy client.</p>
                    </div>
                    <form method="GET" action="{{ route('admin.customers.legacy.show', $legacyCustomer) }}" class="admin-filter-search">
                        <select id="service_status" name="service_status" class="admin-input admin-input-sm">
                            @foreach (['all', 'active', 'pending', 'suspended', 'terminated', 'cancelled'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected($serviceStatus === $statusOption)>
                                    {{ ucfirst($statusOption) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="admin-btn-ghost">Filter</button>
                    </form>
                </div>

                <div class="admin-pill-row admin-panel-pad">
                    @foreach (['active', 'pending', 'suspended', 'cancelled', 'terminated', 'unknown'] as $statusKey)
                        <span class="admin-pill">{{ ucfirst($statusKey) }}: {{ (int) ($whmcsServiceSummary[$statusKey] ?? 0) }}</span>
                    @endforeach
                </div>

                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Domain / user</th>
                                <th>Cycle</th>
                                <th>Next due</th>
                                <th>Status</th>
                                <th>ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    <td><strong>{{ $service->product_name ?: 'Service #'.$service->whmcs_service_id }}</strong></td>
                                    <td>{{ $service->domain ?: ($service->username ?: '—') }}</td>
                                    <td>{{ $service->billing_cycle ?: '—' }}</td>
                                    <td>{{ $service->next_due_date?->format('d M Y') ?: '—' }}</td>
                                    <td><span class="admin-mini-status">{{ $service->status ?: 'unknown' }}</span></td>
                                    <td>{{ $service->whmcs_service_id }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="admin-table-empty">No WHMCS services found for this customer yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
@endsection
