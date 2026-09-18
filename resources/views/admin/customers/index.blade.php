@extends('layouts.admin')

@section('title', 'Customers — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Customers"
        lede="Native Lemonwares accounts and legacy WHMCS customers in one CRM view."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Customers']]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                <form
                    method="GET"
                    action="{{ route('admin.customers.index') }}"
                    class="admin-filter-search"
                    data-admin-clearable-search
                >
                    <input type="hidden" name="source" value="{{ $source }}">
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search name, email, company"
                        class="admin-input"
                        autocomplete="off"
                    >
                    <button type="button" class="admin-btn-ghost" data-admin-search-clear @if ($search === '') hidden @endif>
                        Clear
                    </button>
                    <button type="submit" class="admin-btn-ghost">Search</button>
                </form>
                <form method="POST" action="{{ route('admin.customers.sync-whmcs') }}" data-submit-form>
                    @csrf
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Sync WHMCS</span>
                        <span class="hidden" data-submit-loading>Syncing…</span>
                    </button>
                </form>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-page-stack admin-customers-full">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Customer metrics">
            <a
                href="{{ route('admin.customers.index', ['source' => 'native']) }}"
                @class(['admin-metric', 'is-active' => $source === 'native'])
            >
                <span class="admin-metric-label">Native</span>
                <span class="admin-metric-value">{{ $nativeCount }}</span>
                <span class="admin-metric-meta">Lemonwares accounts</span>
            </a>
            <a
                href="{{ route('admin.customers.index', ['source' => 'legacy']) }}"
                @class(['admin-metric', 'is-active' => $source === 'legacy'])
            >
                <span class="admin-metric-label">Legacy WHMCS</span>
                <span class="admin-metric-value">{{ $legacyCount }}</span>
                <span class="admin-metric-meta">Synced clients</span>
            </a>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New / 7d</span>
                <span class="admin-metric-value">{{ $newNativeWeek }}</span>
                <span class="admin-metric-meta">Native signups</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">With orders</span>
                <span class="admin-metric-value">{{ $withOrdersCount }}</span>
                <span class="admin-metric-meta">Email customers</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Linked</span>
                <span class="admin-metric-value">{{ $linkedLegacyCount }}</span>
                <span class="admin-metric-meta">Native ↔ WHMCS</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Showing</span>
                <span class="admin-metric-value">{{ $customers->total() }}</span>
                <span class="admin-metric-meta">{{ $search !== '' ? 'Filtered results' : ucfirst($source) . ' list' }}</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Customer list">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">{{ $source === 'legacy' ? 'Legacy WHMCS customers' : 'Native customers' }}</h2>
                    <p class="admin-dash-panel-lede">
                        @if ($search !== '')
                            Results for “{{ $search }}”
                        @else
                            {{ $source === 'legacy' ? 'Clients synced from WHMCS.' : 'Accounts created on Lemonwares.' }}
                        @endif
                    </p>
                </div>
                <div class="admin-segment">
                    <a
                        href="{{ route('admin.customers.index', ['source' => 'native', 'q' => $search ?: null]) }}"
                        @class(['admin-segment-btn', 'is-active' => $source === 'native'])
                    >Native</a>
                    <a
                        href="{{ route('admin.customers.index', ['source' => 'legacy', 'q' => $search ?: null]) }}"
                        @class(['admin-segment-btn', 'is-active' => $source === 'legacy'])
                    >Legacy</a>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>{{ $source === 'legacy' ? 'Services' : 'Orders' }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td>
                                    <strong>{{ $source === 'legacy' ? ($customer->full_name ?: '—') : $customer->name }}</strong>
                                </td>
                                <td>{{ $customer->email }}</td>
                                <td>{{ $customer->company ?: '—' }}</td>
                                <td>
                                    <span class="admin-mini-status">
                                        {{ $source === 'legacy' ? $customer->services_count : $customer->email_orders_count }}
                                    </span>
                                </td>
                                <td class="admin-table-actions">
                                    @if ($source === 'legacy')
                                        <a href="{{ route('admin.customers.legacy.show', $customer) }}">View</a>
                                    @else
                                        <a href="{{ route('admin.customers.show', $customer) }}">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-table-empty">No customers match that search.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="admin-pagination">{{ $customers->links() }}</div>
            @endif
        </section>
    </div>
@endsection
