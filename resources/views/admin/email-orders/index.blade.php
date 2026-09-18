@extends('layouts.admin')

@section('title', 'Email Orders — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Email Orders"
        lede="Business-email orders billed here and provisioned through Lemon Mail (TrekMail) or the manual provider queue."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Email Orders']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Email order metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $totalOrders }}</span>
                <span class="admin-metric-meta">All orders</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Paid</span>
                <span class="admin-metric-value">{{ $paidOrders }}</span>
                <span class="admin-metric-meta">Paid / provisioned</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Pending setup</span>
                <span class="admin-metric-value">{{ $pendingSetup }}</span>
                <span class="admin-metric-meta">Needs provisioning</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Manual queue</span>
                <span class="admin-metric-value">{{ $manualQueue }}</span>
                <span class="admin-metric-meta">Queued / in progress</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Provisioned</span>
                <span class="admin-metric-value">{{ $provisioned }}</span>
                <span class="admin-metric-meta">Live services</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New / 7d</span>
                <span class="admin-metric-value">{{ $newWeek }}</span>
                <span class="admin-metric-meta">This week</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Email orders">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Orders</h2>
                    <p class="admin-dash-panel-lede">
                        @if ($provider || $mode || $fulfilmentStatus)
                            Filtered results · {{ $orders->total() }} shown
                        @else
                            Latest email orders across providers.
                        @endif
                    </p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.email-orders.index') }}" class="admin-filter-bar">
                <select name="provider" class="admin-input">
                    <option value="">All providers</option>
                    <option value="lemonmail" @selected(($provider ?? '') === 'lemonmail')>Lemon Mail</option>
                    <option value="titan" @selected(($provider ?? '') === 'titan')>Titan</option>
                    <option value="google_workspace" @selected(($provider ?? '') === 'google_workspace')>Google Workspace</option>
                    <option value="ms365" @selected(($provider ?? '') === 'ms365')>Microsoft 365</option>
                </select>
                <select name="mode" class="admin-input">
                    <option value="">All fulfilment modes</option>
                    <option value="auto" @selected(($mode ?? '') === 'auto')>Automatic</option>
                    <option value="manual" @selected(($mode ?? '') === 'manual')>Manual queue</option>
                </select>
                <select name="fulfilment_status" class="admin-input">
                    <option value="">All queue statuses</option>
                    @foreach (\App\Models\EmailOrder::FULFILMENT_STATUSES as $status)
                        <option value="{{ $status }}" @selected(($fulfilmentStatus ?? '') === $status)>{{ __('email.fulfilment_statuses.' . $status) }}</option>
                    @endforeach
                </select>
                <button class="admin-btn-primary" type="submit">Apply</button>
                @if ($provider || $mode || $fulfilmentStatus)
                    <a href="{{ route('admin.email-orders.index') }}" class="admin-btn-ghost">Clear</a>
                @endif
            </form>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Domain</th>
                            <th>Plan</th>
                            <th>Provider</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Queue</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td><strong>#{{ $order->id }}</strong></td>
                                <td>{{ $order->user?->email ?: '—' }}</td>
                                <td><strong>{{ $order->domain }}</strong></td>
                                <td>{{ $order->plan_name }}</td>
                                <td>{{ __('email.providers.' . ($order->provider ?: 'lemonmail')) }}</td>
                                <td>{{ $order->fulfilment_mode ?: 'auto' }}</td>
                                <td><span class="admin-mini-status">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                <td>{{ $order->isManualFulfilment() ? $order->fulfilmentStatusLabel() : '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.email-orders.show', $order) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="admin-table-empty">No email orders match those filters.</td>
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
