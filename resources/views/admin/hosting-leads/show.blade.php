@extends('layouts.admin')

@section('title', 'Hosting Lead #' . $lead->id . ' — CRM')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Hosting lead</p>
        <h1 class="heading">{{ $lead->full_name }}</h1>
        <p class="lede mt-3">{{ $lead->plan_name }}{{ $lead->spec_label ? ' · ' . $lead->spec_label : '' }}</p>
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </p>
    @endif

    <div class="rounded-3xl border border-border bg-white p-6">
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-on-blush/55">Email</dt>
                <dd class="font-semibold">{{ $lead->email }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">Phone</dt>
                <dd class="font-semibold">{{ $lead->phone }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">Company</dt>
                <dd class="font-semibold">{{ $lead->company ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">Status</dt>
                <dd class="font-semibold">{{ str_replace('_', ' ', $lead->status ?: 'pending') }} / {{ $lead->payment_status ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">Billing cycle</dt>
                <dd class="font-semibold">{{ $lead->billing_cycle ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">Amount</dt>
                <dd class="font-semibold">{{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->amount_usd ?? 0)) }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-on-blush/55">Address</dt>
                <dd class="font-semibold">
                    {{ collect([$lead->billing_address_line_1, $lead->billing_address_line_2, $lead->billing_city, $lead->billing_state, $lead->billing_postcode, $lead->billing_country])->filter()->join(', ') ?: '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-on-blush/55">WHMCS client ID</dt>
                <dd class="font-semibold">{{ $lead->whmcs_client_id ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">WHMCS order ID</dt>
                <dd class="font-semibold">{{ $lead->whmcs_order_id ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">WHMCS invoice ID</dt>
                <dd class="font-semibold">{{ $lead->whmcs_invoice_id ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-on-blush/55">WHMCS sync status</dt>
                <dd class="font-semibold">{{ $lead->whmcs_sync_status ?: '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-on-blush/55">WHMCS sync error</dt>
                <dd class="font-semibold">{{ $lead->whmcs_sync_error ?: '—' }}</dd>
            </div>
            @if ($lead->notes)
                <div class="sm:col-span-2">
                    <dt class="text-on-blush/55">Notes</dt>
                    <dd class="font-semibold">{{ $lead->notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <form method="POST" action="{{ route('admin.hosting-leads.retry-whmcs-sync', $lead) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Retry WHMCS Sync</button>
        </form>
        <a href="{{ route('admin.hosting-leads.index') }}" class="btn btn-ghost">Back to leads</a>
    </div>
@endsection
