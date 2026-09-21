@extends('layouts.admin')

@section('title', 'WHMCS Console — Services — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Console"
        lede="Suspend, unsuspend, or terminate client services."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Services']]"
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
        <form method="GET" action="{{ route('admin.whmcs-console.services') }}" class="admin-filter-search mb-5">
            <input type="number" name="client_id" value="{{ $clientId ?: '' }}" class="admin-input" placeholder="WHMCS client ID" min="1">
            <button type="submit" class="admin-btn-ghost">Load services</button>
        </form>

        @if ($clientId < 1)
            <p class="admin-muted">Enter a WHMCS client ID to load their services, or open a client from the Clients tab.</p>
        @elseif ($services === [])
            <p class="admin-muted">{{ $apiError ?: 'No services for this client.' }}</p>
        @else
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $service)
                            @php $sid = (int) ($service['id'] ?? 0); @endphp
                            <tr>
                                <td class="admin-mono">#{{ $sid }}</td>
                                <td>{{ $service['name'] ?? '—' }}</td>
                                <td>{{ $service['domain'] ?? '—' }}</td>
                                <td>{{ $service['status'] ?? '—' }}</td>
                                <td class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.whmcs-console.services.suspend') }}">
                                        @csrf
                                        <input type="hidden" name="service_id" value="{{ $sid }}">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <button type="submit" class="admin-btn-ghost">Suspend</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whmcs-console.services.unsuspend') }}">
                                        @csrf
                                        <input type="hidden" name="service_id" value="{{ $sid }}">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <button type="submit" class="admin-btn-ghost">Unsuspend</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.whmcs-console.services.terminate') }}" data-confirm data-confirm-title="Terminate?" data-confirm-body="Terminates this service in WHMCS.">
                                        @csrf
                                        <input type="hidden" name="service_id" value="{{ $sid }}">
                                        <input type="hidden" name="client_id" value="{{ $clientId }}">
                                        <button type="submit" class="admin-btn-ghost text-rose">Terminate</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
