@extends('layouts.admin')

@section('title', 'WHMCS Console — Orders — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Console"
        lede="Accept, cancel, or set WHMCS orders back to pending."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Orders']]"
        class="mb-5"
    />

    @include('admin.whmcs-console._tabs')

    @if (session('status'))
        <p class="mb-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 text-sm font-semibold text-rose">{{ session('error') }}</p>
    @endif

    <section class="admin-panel">
        <form method="GET" class="admin-filter-search mb-5">
            <select name="status" class="admin-input">
                @foreach (['Pending', 'Active', 'Cancelled', 'Fraud', 'all'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt === 'all' ? 'All statuses' : $opt }}</option>
                @endforeach
            </select>
            <button type="submit" class="admin-btn-ghost">Filter</button>
        </form>

        @if ($orders === [])
            <p class="admin-muted">{{ $apiError ?: 'No orders found.' }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php $oid = (int) ($order['id'] ?? 0); @endphp
                            <tr>
                                <td class="admin-mono">#{{ $oid }}</td>
                                <td>#{{ $order['userid'] ?? '—' }}</td>
                                <td>{{ $order['date'] ?? '—' }}</td>
                                <td>{{ $order['amount'] ?? $order['total'] ?? '—' }}</td>
                                <td>{{ $order['status'] ?? '—' }}</td>
                                <td class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.whmcs-console.orders.accept') }}">
                                        @csrf
                                        <input type="hidden" name="order_id" value="{{ $oid }}">
                                        <input type="hidden" name="autosetup" value="1">
                                        <button type="submit" class="admin-btn-ghost">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whmcs-console.orders.pending') }}">
                                        @csrf
                                        <input type="hidden" name="order_id" value="{{ $oid }}">
                                        <button type="submit" class="admin-btn-ghost">Pending</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whmcs-console.orders.cancel') }}" data-confirm data-confirm-title="Cancel order?" data-confirm-body="Cancels this order in WHMCS.">
                                        @csrf
                                        <input type="hidden" name="order_id" value="{{ $oid }}">
                                        <button type="submit" class="admin-btn-ghost text-rose">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="admin-muted mt-3">Showing {{ count($orders) }} of {{ $total }}</p>
        @endif
    </section>
@endsection
