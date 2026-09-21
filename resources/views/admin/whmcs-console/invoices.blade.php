@extends('layouts.admin')

@section('title', 'WHMCS Console — Invoices — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Console"
        lede="Browse and record payments on WHMCS invoices."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Invoices']]"
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
                @foreach (['Unpaid', 'Paid', 'Cancelled', 'Refunded', 'all'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt === 'all' ? 'All statuses' : $opt }}</option>
                @endforeach
            </select>
            <button type="submit" class="admin-btn-ghost">Filter</button>
        </form>

        @if ($invoices === [])
            <p class="admin-muted">{{ $apiError ?: 'No invoices found.' }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Due</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="admin-mono">#{{ $invoice['id'] ?? '—' }}</td>
                                <td>#{{ $invoice['userid'] ?? '—' }}</td>
                                <td>{{ $invoice['date'] ?? '—' }}</td>
                                <td>{{ $invoice['duedate'] ?? '—' }}</td>
                                <td>{{ $invoice['total'] ?? '—' }}</td>
                                <td>{{ $invoice['status'] ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.whmcs-console.invoices.show', $invoice['id']) }}" class="admin-btn-ghost">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="admin-muted mt-3">Showing {{ count($invoices) }} of {{ $total }}</p>
        @endif
    </section>
@endsection
