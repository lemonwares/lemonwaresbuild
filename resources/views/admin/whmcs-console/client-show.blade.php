@extends('layouts.admin')

@section('title', 'WHMCS Client #'.$clientId.' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Client #{{ $clientId }}"
        lede="{{ trim(($client['firstname'] ?? '').' '.($client['lastname'] ?? '')) }} · {{ $client['email'] ?? '' }}"
        :back-href="route('admin.whmcs-console.clients')"
        back-label="All clients"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Clients'], ['label' => '#'.$clientId]]"
        class="mb-5"
    />

    @include('admin.whmcs-console._tabs')

    @if (session('status'))
        <p class="mb-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 text-sm font-semibold text-rose">{{ session('error') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <h2 class="admin-dash-panel-title">Profile</h2>
                @if ($local?->user_id)
                    <a href="{{ route('admin.customers.show', $local->user_id) }}" class="admin-btn-ghost">Open local customer</a>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.whmcs-console.clients.update', $clientId) }}" class="admin-edit-grid" data-submit-form>
                @csrf
                @method('PUT')
                <label class="admin-field">
                    <span>First name</span>
                    <input type="text" name="firstname" value="{{ old('firstname', $client['firstname'] ?? '') }}" class="admin-input" required>
                </label>
                <label class="admin-field">
                    <span>Last name</span>
                    <input type="text" name="lastname" value="{{ old('lastname', $client['lastname'] ?? '') }}" class="admin-input" required>
                </label>
                <label class="admin-field admin-field-span">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email', $client['email'] ?? '') }}" class="admin-input" required>
                </label>
                <label class="admin-field">
                    <span>Company</span>
                    <input type="text" name="companyname" value="{{ old('companyname', $client['companyname'] ?? '') }}" class="admin-input">
                </label>
                <label class="admin-field">
                    <span>Phone</span>
                    <input type="text" name="phonenumber" value="{{ old('phonenumber', $client['phonenumber'] ?? '') }}" class="admin-input">
                </label>
                <div class="admin-field-span flex flex-wrap gap-3">
                    <button type="submit" class="admin-btn-primary">Save in WHMCS</button>
                </div>
            </form>

            <form
                method="POST"
                action="{{ route('admin.whmcs-console.clients.close', $clientId) }}"
                class="mt-6"
                data-confirm
                data-confirm-title="Close WHMCS client?"
                data-confirm-body="This closes the client in WHMCS. Continue?"
            >
                @csrf
                <button type="submit" class="admin-btn-ghost text-rose">Close client</button>
            </form>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <h2 class="admin-dash-panel-title">Services</h2>
                <a href="{{ route('admin.whmcs-console.services', ['client_id' => $clientId]) }}" class="admin-btn-ghost">Manage</a>
            </div>
            @if ($services === [])
                <p class="admin-muted">No products/services.</p>
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
                                    <td>{{ $service['name'] ?? $service['groupname'] ?? '—' }}</td>
                                    <td>{{ $service['domain'] ?? '—' }}</td>
                                    <td>{{ $service['status'] ?? '—' }}</td>
                                    <td class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.whmcs-console.services.unsuspend') }}">
                                            @csrf
                                            <input type="hidden" name="service_id" value="{{ $sid }}">
                                            <input type="hidden" name="client_id" value="{{ $clientId }}">
                                            <button type="submit" class="admin-btn-ghost">Unsuspend</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.whmcs-console.services.suspend') }}">
                                            @csrf
                                            <input type="hidden" name="service_id" value="{{ $sid }}">
                                            <input type="hidden" name="client_id" value="{{ $clientId }}">
                                            <button type="submit" class="admin-btn-ghost">Suspend</button>
                                        </form>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.whmcs-console.services.terminate') }}"
                                            data-confirm
                                            data-confirm-title="Terminate service?"
                                            data-confirm-body="This terminates the module in WHMCS."
                                        >
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

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <h2 class="admin-dash-panel-title">Recent invoices</h2>
            </div>
            @if ($invoices === [])
                <p class="admin-muted">No invoices.</p>
            @else
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td class="admin-mono">#{{ $invoice['id'] ?? '—' }}</td>
                                    <td>{{ $invoice['date'] ?? '—' }}</td>
                                    <td>{{ $invoice['total'] ?? '—' }} {{ $invoice['currencycode'] ?? '' }}</td>
                                    <td>{{ $invoice['status'] ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('admin.whmcs-console.invoices.show', $invoice['id']) }}" class="admin-btn-ghost">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <h2 class="admin-dash-panel-title">Domains</h2>
                <a href="{{ route('admin.whmcs-console.domains', ['client_id' => $clientId]) }}" class="admin-btn-ghost">Manage</a>
            </div>
            @if ($domains === [])
                <p class="admin-muted">No domains.</p>
            @else
                <ul class="space-y-2 text-sm">
                    @foreach ($domains as $domain)
                        <li>
                            <span class="font-semibold">{{ $domain['domainname'] ?? $domain['domain'] ?? '—' }}</span>
                            <span class="admin-muted">· {{ $domain['status'] ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
