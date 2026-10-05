@extends('layouts.admin')

@section('title', 'Cart Orders — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Cart Orders"
        lede="Combined checkouts with domains, email and hosting paid together."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Cart Orders']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Cart order metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $totalOrders }}</span>
                <span class="admin-metric-meta">All cart checkouts</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Unpaid</span>
                <span class="admin-metric-value">{{ $awaitingCount }}</span>
                <span class="admin-metric-meta">Awaiting or failed payment</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Paid</span>
                <span class="admin-metric-value">{{ $paidCount }}</span>
                <span class="admin-metric-meta">Paid or fulfilled</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Partly set up</span>
                <span class="admin-metric-value">{{ $partialCount }}</span>
                <span class="admin-metric-meta"><a href="{{ route('admin.cart-orders.index', ['fulfilment' => 'partial']) }}">Need attention</a></span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New / 7d</span>
                <span class="admin-metric-value">{{ $newWeek }}</span>
                <span class="admin-metric-meta">This week</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Cart orders">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Orders</h2>
                    <p class="admin-dash-panel-lede">{{ $orders->total() }} shown</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.cart-orders.index') }}" class="admin-filter-bar">
                <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Item, email or reference">
                <select name="status" class="admin-input">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ str_replace('_', ' ', $statusOption) }}</option>
                    @endforeach
                </select>
                <select name="fulfilment" class="admin-input">
                    <option value="">Any setup status</option>
                    <option value="partial" @selected($fulfilment === 'partial')>Partly set up</option>
                </select>
                <button class="admin-btn-primary" type="submit">Apply</button>
                @if ($search || $status || $fulfilment)
                    <a href="{{ route('admin.cart-orders.index') }}" class="admin-btn-ghost">Clear</a>
                @endif
            </form>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Items</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Setup</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong>#{{ $order->id }}</strong></td>
                                <td>{{ $order->items->pluck('label')->take(3)->implode(', ') }}{{ $order->items->count() > 3 ? ' +'.($order->items->count() - 3) : '' }}</td>
                                <td>{{ $order->user?->email ?: '—' }}</td>
                                <td>{{ \App\Support\HostingPricing::formatMoney((float) $order->amount_ngn) }}</td>
                                <td><x-admin.status :value="$order->status" /></td>
                                <td>{{ $order->fulfilment_status ?: '—' }}</td>
                                <td class="admin-nowrap">{{ $order->created_at?->format('d M Y') }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.cart-orders.show', $order) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="admin-table-empty">No cart orders match those filters.</td>
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
