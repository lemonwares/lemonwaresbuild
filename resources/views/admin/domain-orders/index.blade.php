@extends('layouts.admin')

@section('title', 'Domain Orders — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Domain Orders"
        lede="Every domain registration and transfer, including ones bought through the cart."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Domain Orders']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Domain order metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $totalOrders }}</span>
                <span class="admin-metric-meta">All domain orders</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Unpaid</span>
                <span class="admin-metric-value">{{ $awaitingCount }}</span>
                <span class="admin-metric-meta">Awaiting or failed payment</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Paid</span>
                <span class="admin-metric-value">{{ $paidCount }}</span>
                <span class="admin-metric-meta">Paid or submitted</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">WHMCS failed</span>
                <span class="admin-metric-value">{{ $whmcsFailedCount }}</span>
                <span class="admin-metric-meta"><a href="{{ route('admin.domain-orders.index', ['whmcs' => 'failed']) }}">Show them</a></span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New / 7d</span>
                <span class="admin-metric-value">{{ $newWeek }}</span>
                <span class="admin-metric-meta">This week</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Domain orders">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Orders</h2>
                    <p class="admin-dash-panel-lede">{{ $orders->total() }} shown</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.domain-orders.index') }}" class="admin-filter-bar">
                <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Domain, email or reference">
                <select name="status" class="admin-input">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ str_replace('_', ' ', $statusOption) }}</option>
                    @endforeach
                </select>
                <select name="option" class="admin-input">
                    <option value="">Register &amp; transfer</option>
                    <option value="register" @selected($option === 'register')>Register</option>
                    <option value="transfer" @selected($option === 'transfer')>Transfer</option>
                </select>
                <select name="whmcs" class="admin-input">
                    <option value="">Any WHMCS status</option>
                    <option value="failed" @selected($whmcs === 'failed')>WHMCS failed</option>
                </select>
                <button class="admin-btn-primary" type="submit">Apply</button>
                @if ($search || $status || $option || $whmcs)
                    <a href="{{ route('admin.domain-orders.index') }}" class="admin-btn-ghost">Clear</a>
                @endif
            </form>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Domain</th>
                            <th>Type</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>WHMCS</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong>#{{ $order->id }}</strong></td>
                                <td><strong>{{ $order->domain }}</strong></td>
                                <td>{{ $order->option }} · {{ $order->reg_period }}y</td>
                                <td>{{ $order->user?->email ?: '—' }}</td>
                                <td>{{ \App\Support\HostingPricing::formatMoney((float) $order->amount_ngn) }}</td>
                                <td><x-admin.status :value="$order->status" /></td>
                                <td>{{ $order->whmcs_sync_status ? str_replace('_', ' ', $order->whmcs_sync_status) : ($order->checkout?->whmcs_sync_status ? str_replace('_', ' ', $order->checkout->whmcs_sync_status) : '—') }}</td>
                                <td class="admin-nowrap">{{ $order->created_at?->format('d M Y') }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.domain-orders.show', $order) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="admin-table-empty">No domain orders match those filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="admin-pagination">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
@endsection
