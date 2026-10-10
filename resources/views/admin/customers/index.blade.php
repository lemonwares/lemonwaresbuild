@extends('layouts.admin')

@section('title', 'Customers — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    @php
        $sources = ['all' => 'All customers', 'whmcs' => 'WHMCS clients', 'site' => 'Site accounts'];
        $money = fn ($amount, $currency = null) => (['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€'][strtoupper((string) $currency)] ?? ($currency ? $currency.' ' : '₦')).number_format((float) $amount, 2);
    @endphp

    <x-admin.page-header
        title="Customers"
        lede="Everyone who buys from us: WHMCS clients and site accounts in one list."
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
                    @if ($source !== 'all')
                        <input type="hidden" name="source" value="{{ $source }}">
                    @endif
                    @if ($tag !== '')
                        <input type="hidden" name="tag" value="{{ $tag }}">
                    @endif
                    <input
                        type="text"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search name, email, company, client #"
                        class="admin-input"
                        autocomplete="off"
                    >
                    <button type="button" class="admin-btn-ghost" data-admin-search-clear @if ($search === '') hidden @endif>
                        Clear
                    </button>
                    <button type="submit" class="admin-btn-ghost">Search</button>
                </form>
                <a href="{{ route('admin.customers.export', array_filter(['q' => $search, 'tag' => $tag])) }}" class="admin-btn-ghost">Export CSV</a>
                <a href="{{ route('admin.customers.create') }}" class="admin-btn-ghost">New customer</a>
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
            <a href="{{ route('admin.customers.index') }}" @class(['admin-metric', 'is-active' => $source === 'all'])>
                <span class="admin-metric-label">All customers</span>
                <span class="admin-metric-value">{{ number_format($metrics['total']) }}</span>
                <span class="admin-metric-meta">{{ $metrics['linked'] }} linked to a site account</span>
            </a>
            <a href="{{ route('admin.customers.index', ['source' => 'whmcs']) }}" @class(['admin-metric', 'is-active' => $source === 'whmcs'])>
                <span class="admin-metric-label">WHMCS clients</span>
                <span class="admin-metric-value">{{ number_format($metrics['whmcs']) }}</span>
                <span class="admin-metric-meta">Synced from billing</span>
            </a>
            <a href="{{ route('admin.customers.index', ['source' => 'site']) }}" @class(['admin-metric', 'is-active' => $source === 'site'])>
                <span class="admin-metric-label">Site accounts</span>
                <span class="admin-metric-value">{{ number_format($metrics['site']) }}</span>
                <span class="admin-metric-meta">+{{ $metrics['new_week'] }} this week</span>
            </a>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Active services</span>
                <span class="admin-metric-value">{{ number_format($metrics['active_services']) }}</span>
                <span class="admin-metric-meta">Across all clients</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Unpaid invoices</span>
                <span class="admin-metric-value">{{ number_format($metrics['unpaid_count']) }}</span>
                <span class="admin-metric-meta">{{ collect($metrics['unpaid_by_currency'])->map(fn ($amount, $currency) => $money($amount, $currency))->implode(' · ') ?: 'Nothing' }} outstanding</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Customer list">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">{{ $sources[$source] }}</h2>
                    <p class="admin-dash-panel-lede">
                        {{ number_format($customers->total()) }} total
                        @if ($tag !== '')
                            · tagged “{{ $tag }}” · <a href="{{ route('admin.customers.index', array_filter(['q' => $search, 'source' => $source === 'all' ? null : $source])) }}">clear tag</a>
                        @elseif ($search !== '')
                            · results for “{{ $search }}”
                        @endif
                    </p>
                </div>
                <div class="admin-segment">
                    @foreach (['all' => 'All', 'whmcs' => 'WHMCS', 'site' => 'Site'] as $key => $label)
                        <a
                            href="{{ route('admin.customers.index', array_filter(['source' => $key === 'all' ? null : $key, 'q' => $search ?: null, 'tag' => $tag ?: null])) }}"
                            @class(['admin-segment-btn', 'is-active' => $source === $key])
                        >{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Company</th>
                            <th>WHMCS #</th>
                            <th>Services</th>
                            <th>Unpaid</th>
                            <th>Credit</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $row)
                            @php
                                $user = $row->user_id ? $users->get($row->user_id) : null;
                                $svc = $row->whmcs_client_id ? $services->get($row->whmcs_client_id) : null;
                                $due = $row->whmcs_client_id ? $unpaid->get($row->whmcs_client_id) : null;
                                $status = $user?->suspended_at ? 'Suspended' : ($row->status ?: 'Active');
                                $href = $user
                                    ? route('admin.customers.show', $user)
                                    : ($row->whmcs_row_id ? route('admin.customers.legacy.show', $row->whmcs_row_id) : null);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ \App\Models\WhmcsCustomer::cleanName($row->name, $row->company, $row->email) }}</strong>
                                    @foreach ((array) ($user?->admin_tags ?? []) as $customerTag)
                                        <a href="{{ route('admin.customers.index', ['tag' => $customerTag]) }}" class="admin-mini-status">{{ $customerTag }}</a>
                                    @endforeach
                                    <div class="text-xs text-black/50">{{ $row->email }}</div>
                                </td>
                                <td>{{ $row->company ?: '—' }}</td>
                                <td>{{ $row->whmcs_client_id ? '#'.$row->whmcs_client_id : '—' }}</td>
                                <td>{{ $svc ? $svc->active.' of '.$svc->total.' active' : '—' }}</td>
                                <td>
                                    @if ($due)
                                        <strong class="text-red-700">{{ $money($due->amount, $due->currency ?: $row->currency_code) }}</strong>
                                        <div class="text-xs text-black/50">{{ $due->invoice_count }} {{ \Illuminate\Support\Str::plural('invoice', (int) $due->invoice_count) }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->credit !== null && (float) $row->credit > 0 ? $money($row->credit, $row->currency_code) : '—' }}</td>
                                <td>
                                    <x-admin.status :value="in_array(strtolower($status), ['closed', 'inactive'], true) ? 'cancelled' : strtolower($status)" :label="$status" />
                                    @unless ($row->whmcs_client_id)
                                        <span class="admin-mini-status">site only</span>
                                    @endunless
                                </td>
                                <td>{{ $row->joined_at ? \Illuminate\Support\Carbon::parse($row->joined_at)->format('d M Y') : '—' }}</td>
                                <td class="admin-table-actions">
                                    @if ($href)
                                        <a href="{{ $href }}">View</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="admin-table-empty">No customers match that search.</td>
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
