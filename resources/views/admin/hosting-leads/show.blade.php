@extends('layouts.admin')

@section('title', 'Hosting Lead #' . $lead->id . ' — CRM')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        :title="$lead->full_name"
        :lede="trim($lead->plan_name.($lead->spec_label ? ' · '.$lead->spec_label : ''))"
        :back-href="route('admin.hosting-leads.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Hosting Leads', 'href' => route('admin.hosting-leads.index')],
            ['label' => '#'.$lead->id],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                <form method="POST" action="{{ route('admin.hosting-leads.retry-whmcs-sync', $lead) }}" data-submit-form>
                    @csrf
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Retry WHMCS Sync</span>
                        <span class="hidden" data-submit-loading>Syncing…</span>
                    </button>
                </form>
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Lead snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ str_replace('_', ' ', $lead->status ?: 'pending') }}</span>
                <span class="admin-metric-meta">{{ $lead->payment_status ?: 'No payment status' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Amount</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->amount_usd ?? 0)) }}</span>
                <span class="admin-metric-meta">{{ $lead->billing_cycle ?: 'No cycle' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">WHMCS</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $lead->whmcs_sync_status ?: '—' }}</span>
                <span class="admin-metric-meta">{{ $lead->whmcs_order_id ? 'Order #'.$lead->whmcs_order_id : 'Not synced' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Client</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $lead->whmcs_client_id ?: '—' }}</span>
                <span class="admin-metric-meta">WHMCS client ID</span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Contact</h2>
                    <div class="admin-pill-row">
                        <span class="admin-pill">{{ $lead->statusLabel() }}</span>
                    </div>
                </div>
                <dl class="admin-dl">
                    <div><dt>Email</dt><dd><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></dd></div>
                    <div><dt>Phone</dt><dd>{{ $lead->phone ?: '—' }}</dd></div>
                    <div><dt>Company</dt><dd>{{ $lead->company ?: '—' }}</dd></div>
                    @if ($lead->hostname && $lead->isShared())
                        <div class="admin-dl-span"><dt>Primary domain</dt><dd>{{ $lead->hostname }}</dd></div>
                    @endif
                    <div class="admin-dl-span">
                        <dt>Address</dt>
                        <dd>
                            {{ collect([$lead->billing_address_line_1, $lead->billing_address_line_2, $lead->billing_city, $lead->billing_state, $lead->billing_postcode, $lead->billing_country])->filter()->join(', ') ?: '—' }}
                        </dd>
                    </div>
                    @if ($lead->notes)
                        <div class="admin-dl-span"><dt>Notes</dt><dd class="whitespace-pre-wrap">{{ $lead->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Order & WHMCS</h2>
                </div>
                <dl class="admin-dl">
                    <div><dt>Plan</dt><dd>{{ $lead->plan_name }}{{ $lead->spec_label ? ' · '.$lead->spec_label : '' }}</dd></div>
                    <div><dt>Billing cycle</dt><dd>{{ $lead->billing_cycle ?: '—' }}</dd></div>
                    <div><dt>Amount</dt><dd>{{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->amount_usd ?? 0)) }}</dd></div>
                    <div><dt>Status</dt><dd>{{ str_replace('_', ' ', $lead->status ?: 'pending') }} / {{ $lead->payment_status ?: '—' }}</dd></div>
                    <div><dt>WHMCS client ID</dt><dd>{{ $lead->whmcs_client_id ?: '—' }}</dd></div>
                    <div><dt>WHMCS order ID</dt><dd>{{ $lead->whmcs_order_id ?: '—' }}</dd></div>
                    <div><dt>WHMCS invoice ID</dt><dd>{{ $lead->whmcs_invoice_id ?: '—' }}</dd></div>
                    <div><dt>WHMCS sync status</dt><dd>{{ $lead->whmcs_sync_status ?: '—' }}</dd></div>
                    <div class="admin-dl-span"><dt>WHMCS sync error</dt><dd>{{ $lead->whmcs_sync_error ?: '—' }}</dd></div>
                </dl>
                @if ($lead->whmcs_sync_error)
                    <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $lead->whmcs_sync_error }}</p>
                @endif
            </section>
        </div>
    </div>
@endsection
