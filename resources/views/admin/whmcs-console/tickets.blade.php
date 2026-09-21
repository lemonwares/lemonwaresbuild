@extends('layouts.admin')

@section('title', 'WHMCS Console — Tickets — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Console"
        lede="View and reply to WHMCS support tickets."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Tickets']]"
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
                @foreach (['Open', 'Answered', 'Customer-Reply', 'Closed', 'all'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt === 'all' ? 'All statuses' : $opt }}</option>
                @endforeach
            </select>
            <button type="submit" class="admin-btn-ghost">Filter</button>
        </form>

        @if ($tickets === [])
            <p class="admin-muted">{{ $apiError ?: 'No tickets found.' }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Last reply</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="admin-mono">#{{ $ticket['id'] ?? '—' }}</td>
                                <td>{{ $ticket['subject'] ?? '—' }}</td>
                                <td>#{{ $ticket['userid'] ?? '—' }}</td>
                                <td>{{ $ticket['status'] ?? '—' }}</td>
                                <td>{{ $ticket['lastreply'] ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.whmcs-console.tickets.show', $ticket['id']) }}" class="admin-btn-ghost">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="admin-muted mt-3">Showing {{ count($tickets) }} of {{ $total }}</p>
        @endif
    </section>
@endsection
