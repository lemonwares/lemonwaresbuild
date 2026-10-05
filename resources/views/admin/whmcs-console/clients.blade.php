@extends('layouts.admin')

@section('title', 'WHMCS Console — Clients — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Console"
        lede="Operate WHMCS clients, services, billing, tickets, and domains from LemonWares."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Clients']]"
        class="mb-5"
    />

    @include('admin.whmcs-console._tabs')

    @if (session('status'))
        <p class="mb-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 text-sm font-semibold text-rose">{{ session('error') }}</p>
    @endif
    @if ($errors->any())
        <p class="mb-4 text-sm font-semibold text-rose">{{ $errors->first() }}</p>
    @endif

    <section class="admin-panel">
        <form method="GET" action="{{ route('admin.whmcs-console.clients') }}" class="admin-filter-search mb-5">
            <input type="text" name="q" value="{{ $search }}" class="admin-input" placeholder="Search clients" autocomplete="off">
            <button type="submit" class="admin-btn-ghost">Search</button>
        </form>

        @if ($apiError && $clients === [])
            <p class="admin-muted">{{ $apiError }}</p>
        @elseif ($clients === [])
            <p class="admin-muted">No clients found.</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td class="admin-mono">#{{ $client['id'] ?? '—' }}</td>
                                <td>{{ trim(($client['firstname'] ?? '').' '.($client['lastname'] ?? '')) ?: '—' }}</td>
                                <td>{{ $client['email'] ?? '—' }}</td>
                                <td>{{ $client['companyname'] ?? '—' }}</td>
                                <td>{{ $client['status'] ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.whmcs-console.clients.show', $client['id']) }}" class="admin-btn-ghost">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="admin-muted mt-3">Showing {{ count($clients) }} of {{ $total }}</p>
        @endif
    </section>
@endsection
