@extends('layouts.admin')

@section('title', 'All Orders — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $money = \App\Support\HostingPricing::class;
@endphp

@section('content')
    <x-admin.page-header
        title="All Orders"
        lede="Email, hosting, domain and cart orders in one list, newest first."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'All Orders']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Order metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Matching</span>
                <span class="admin-metric-value">{{ $orders->total() }}</span>
                <span class="admin-metric-meta">Orders in this view</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Paid</span>
                <span class="admin-metric-value">{{ $paidCount }}</span>
                <span class="admin-metric-meta">{{ $money::formatMoney((float) $paidTotalNgn) }} received</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Unpaid</span>
                <span class="admin-metric-value">{{ $unpaidCount }}</span>
                <span class="admin-metric-meta">Not cancelled</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="All orders">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="admin-filter-bar">
                <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Domain, email, name or reference">
                <select name="type" class="admin-input">
                    <option value="">All products</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="payment" class="admin-input">
                    <option value="">Any payment</option>
                    <option value="paid" @selected($payment === 'paid')>Paid</option>
                    <option value="unpaid" @selected($payment === 'unpaid')>Unpaid</option>
                    <option value="cancelled" @selected($payment === 'cancelled')>Cancelled</option>
                </select>
                <button class="admin-btn-primary" type="submit">Apply</button>
                @if ($search || $type || $payment)
                    <a href="{{ route('admin.orders.index') }}" class="admin-btn-ghost">Clear</a>
                @endif
            </form>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $row)
                            <tr>
                                <td>
                                    <span class="admin-muted text-xs admin-nowrap">{{ $types[$row['type']] }} #{{ $row['id'] }}</span><br>
                                    <strong>{{ $row['label'] }}</strong>
                                </td>
                                <td>{{ $row['customer'] ?: '—' }}</td>
                                <td class="admin-nowrap admin-num">{{ $money::formatMoney($row['amount_ngn']) }}</td>
                                <td><x-admin.status :value="$row['cancelled'] ? 'cancelled' : ($row['paid'] ? 'paid' : 'awaiting_payment')" :label="$row['cancelled'] ? 'Cancelled' : ($row['paid'] ? 'Paid' : 'Unpaid')" /></td>
                                <td><x-admin.status :value="$row['status']" /></td>
                                <td class="admin-nowrap">{{ $row['created_at']?->format('d M Y') }}</td>
                                <td class="admin-table-actions"><a href="{{ $row['href'] }}">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="admin-table-empty">No orders match those filters.</td>
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
