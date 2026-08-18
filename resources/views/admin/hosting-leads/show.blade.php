@extends('layouts.admin')

@section('title', 'Hosting Lead #' . $lead->id . ' — CRM')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Hosting lead</p>
        <h1 class="heading">{{ $lead->full_name }}</h1>
        <p class="lede mt-3">{{ $lead->plan_name }}{{ $lead->spec_label ? ' · ' . $lead->spec_label : '' }}</p>
    </div>

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
            @if ($lead->notes)
                <div class="sm:col-span-2">
                    <dt class="text-on-blush/55">Notes</dt>
                    <dd class="font-semibold">{{ $lead->notes }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <a href="{{ route('admin.hosting-leads.index') }}" class="btn btn-ghost mt-6">Back to leads</a>
@endsection
