@extends('layouts.admin')

@section('title', 'Reports — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $money = \App\Support\HostingPricing::class;
    $maxMonth = max(1, ...array_map(fn ($m) => $m['total'], array_values($summary['months'])));
@endphp

@section('content')
    <x-admin.page-header
        title="Reports"
        :lede="'Sales from '.$from->format('d M Y').' to '.$to->format('d M Y').'. Paid orders by the date they were placed, after refunds and discount codes.'"
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Reports']]"
        class="mb-5"
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('admin.reports.index') }}" class="admin-customers-toolbar">
                <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="admin-input">
                <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="admin-input">
                <button type="submit" class="admin-btn-ghost">Show</button>
                <a href="{{ route('admin.reports.export', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}" class="admin-btn-primary">Export CSV</a>
            </form>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Totals">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Net sales</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $money::formatMoney($summary['net']) }}</span>
                <span class="admin-metric-meta">{{ $money::formatMoney($summary['gross']) }} before refunds &amp; codes</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Paid orders</span>
                <span class="admin-metric-value">{{ $summary['orders'] }}</span>
                <span class="admin-metric-meta">Average {{ $money::formatMoney($summary['average']) }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Refunds &amp; codes</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $money::formatMoney($summary['refunds'] + $summary['discounts']) }}</span>
                <span class="admin-metric-meta">{{ $money::formatMoney($summary['refunds']) }} refunded · {{ $money::formatMoney($summary['discounts']) }} discounts</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New customers</span>
                <span class="admin-metric-value">{{ $summary['newCustomers'] }}</span>
                <span class="admin-metric-meta">In this period</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Cart checkouts paid</span>
                <span class="admin-metric-value">{{ $summary['funnel']['rate'] }}%</span>
                <span class="admin-metric-meta">{{ $summary['funnel']['paid'] }} of {{ $summary['funnel']['checkouts'] }} started</span>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Net sales by month</h2></div>
            <div class="space-y-2">
                @foreach ($summary['months'] as $month)
                    <div class="grid items-center gap-3 text-sm" style="grid-template-columns: 6rem 1fr 8rem;">
                        <span>{{ $month['label'] }}</span>
                        <div class="h-5 rounded bg-blush-soft/60">
                            <div class="h-5 rounded bg-rose" style="width: {{ max(0, round($month['total'] / $maxMonth * 100, 1)) }}%"></div>
                        </div>
                        <span class="text-right font-semibold admin-num">{{ $money::formatMoney($month['total']) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="admin-table-wrap is-full mt-6">
                <table class="admin-table is-full">
                    <thead>
                        <tr><th>Month</th>@foreach ($products as $label)<th class="text-right">{{ $label }}</th>@endforeach<th class="text-right">Total</th><th class="text-right">Orders</th><th class="text-right">New customers</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['months'] as $month)
                            <tr>
                                <td>{{ $month['label'] }}</td>
                                @foreach (array_keys($products) as $key)
                                    <td class="text-right">{{ $money::formatMoney($month[$key]) }}</td>
                                @endforeach
                                <td class="text-right admin-num"><strong>{{ $money::formatMoney($month['total']) }}</strong></td>
                                <td class="text-right">{{ $month['orders'] }}</td>
                                <td class="text-right">{{ $month['customers'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Best sellers</h2></div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead><tr><th>Product</th><th class="text-right">Orders</th><th class="text-right">Sales</th></tr></thead>
                        <tbody>
                            @forelse ($summary['bestSellers'] as $row)
                                <tr><td>{{ $row['name'] }}</td><td class="text-right">{{ $row['orders'] }}</td><td class="text-right">{{ $money::formatMoney($row['amount']) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="admin-table-empty">No paid orders in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Needs attention</h2>
                </div>
                <p class="text-sm"><strong>{{ $attention['unpaidCount'] }}</strong> unpaid open orders worth <strong>{{ $money::formatMoney($attention['unpaidValue']) }}</strong>. <a href="{{ route('admin.orders.index', ['payment' => 'unpaid']) }}">See them</a></p>

                <p class="admin-dash-panel-title mt-5 mb-2">Renewals in the next 30 days</p>
                <ul class="space-y-2 text-sm">
                    @forelse ($attention['emailRenewals'] as $order)
                        <li><a href="{{ route('admin.email-orders.show', $order) }}">{{ $order->domain }}</a> · email · due {{ $order->period_ends_at->format('d M') }} · {{ $order->user?->email }}</li>
                    @empty
                    @endforelse
                    @foreach ($attention['whmcsRenewals'] as $service)
                        <li>{{ collect([$service->domain, $service->product_name])->filter()->unique()->implode(' · ') ?: 'WHMCS service #'.$service->whmcs_service_id }} · due {{ $service->next_due_date->format('d M') }}</li>
                    @endforeach
                    @if ($attention['emailRenewals']->isEmpty() && $attention['whmcsRenewals']->isEmpty())
                        <li class="admin-muted">Nothing due.</li>
                    @endif
                </ul>
            </section>
        </div>
    </div>
@endsection
