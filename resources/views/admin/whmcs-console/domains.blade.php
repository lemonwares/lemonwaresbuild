@extends('layouts.admin')

@section('title', 'WHMCS Console — Domains — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Console"
        lede="Lock, unlock, or renew domains managed in WHMCS."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Domains']]"
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
            <input type="number" name="client_id" value="{{ $clientId ?: '' }}" class="admin-input" placeholder="Filter by client ID (optional)" min="1">
            <button type="submit" class="admin-btn-ghost">Load</button>
        </form>

        @if ($domains === [])
            <p class="admin-muted">{{ $apiError ?: 'No domains found.' }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Domain</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($domains as $domain)
                            @php $did = (int) ($domain['id'] ?? 0); @endphp
                            <tr>
                                <td class="admin-mono">#{{ $did }}</td>
                                <td>{{ $domain['domainname'] ?? $domain['domain'] ?? '—' }}</td>
                                <td>#{{ $domain['userid'] ?? '—' }}</td>
                                <td>{{ $domain['status'] ?? '—' }}</td>
                                <td>{{ $domain['expirydate'] ?? '—' }}</td>
                                <td class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.whmcs-console.domains.lock') }}">
                                        @csrf
                                        <input type="hidden" name="domain_id" value="{{ $did }}">
                                        <input type="hidden" name="lock" value="1">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <button type="submit" class="admin-btn-ghost">Lock</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whmcs-console.domains.lock') }}">
                                        @csrf
                                        <input type="hidden" name="domain_id" value="{{ $did }}">
                                        <input type="hidden" name="lock" value="0">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <button type="submit" class="admin-btn-ghost">Unlock</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whmcs-console.domains.renew') }}" data-confirm data-confirm-title="Renew domain?" data-confirm-body="Requests a 1-year renew via WHMCS.">
                                        @csrf
                                        <input type="hidden" name="domain_id" value="{{ $did }}">
                                        <input type="hidden" name="regperiod" value="1">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <button type="submit" class="admin-btn-ghost">Renew</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="admin-muted mt-3">Showing {{ count($domains) }} of {{ $total }}</p>
        @endif
    </section>
@endsection
