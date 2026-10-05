@extends('layouts.admin')

@section('title', 'Domain Order #' . $order->id . ' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $money = \App\Support\HostingPricing::class;
    $bundle = $order->checkout;
@endphp

@section('content')
    <x-admin.page-header
        :title="$order->domain"
        :lede="trim(($order->user?->name ? $order->user->name.' · ' : '').($order->user?->email ?: 'No customer linked'))"
        :back-href="route('admin.domain-orders.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Domain Orders', 'href' => route('admin.domain-orders.index')],
            ['label' => '#'.$order->id],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                @if ($order->user && $order->user->isCustomer())
                    <a href="{{ route('admin.customers.show', $order->user) }}" class="admin-btn-ghost">Customer profile</a>
                @endif
                @if (! $payable->isCancelled() && ($payable->wasPaid() || $payable->whmcs_sync_status === 'failed'))
                    <form method="POST" action="{{ route('admin.domain-orders.retry-whmcs', $order) }}" data-submit-form>
                        @csrf
                        <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Retry WHMCS sync</span>
                            <span class="hidden" data-submit-loading>Syncing…</span>
                        </button>
                    </form>
                @endif
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.orders.partials.errors')

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Order snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ str_replace('_', ' ', $order->status) }}</span>
                <span class="admin-metric-meta">{{ $order->payment_status ?: 'No payment status' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Type</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $order->optionLabel() }}</span>
                <span class="admin-metric-meta">{{ $order->periodLabel() }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Amount</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $money::formatMoney((float) $order->amount_ngn) }}</span>
                <span class="admin-metric-meta">{{ $money::formatMoney((float) $order->amount_usd, 'USD') }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">WHMCS</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ str_replace('_', ' ', (string) ($payable->whmcs_sync_status ?: '—')) }}</span>
                <span class="admin-metric-meta">{{ $payable->whmcs_order_id ? 'Order #'.$payable->whmcs_order_id : 'Not synced' }}</span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Order details</h2>
                </div>
                <dl class="admin-dl">
                    <div><dt>Order ID</dt><dd>#{{ $order->id }}</dd></div>
                    <div><dt>Domain</dt><dd>{{ $order->domain }}</dd></div>
                    <div><dt>Created</dt><dd>{{ $order->created_at?->format('d M Y H:i') }}</dd></div>
                    <div><dt>IP address</dt><dd>{{ $order->ip_address ?: '—' }}</dd></div>
                    <div><dt>WHMCS client</dt><dd>{{ $payable->whmcs_client_id ?: '—' }}</dd></div>
                    <div><dt>WHMCS invoice</dt><dd>{{ $payable->whmcs_invoice_id ?: '—' }}</dd></div>
                </dl>
                @if ($payable->whmcs_sync_error)
                    <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">WHMCS: {{ $payable->whmcs_sync_error }}</p>
                @endif

                @if ($order->isTransfer())
                    <form method="POST" action="{{ route('admin.domain-orders.epp', $order) }}" class="mt-5 space-y-3 border-t border-border pt-5" data-submit-form>
                        @csrf
                        @method('PUT')
                        <label class="admin-field">
                            <span>Transfer (EPP) code</span>
                            <input type="text" name="epp_code" value="{{ old('epp_code', $order->epp_code) }}" class="admin-input font-mono" maxlength="120" required autocomplete="off">
                            @error('epp_code') <em>{{ $message }}</em> @enderror
                        </label>
                        <button type="submit" class="admin-btn-ghost">Update transfer code</button>
                    </form>
                @endif
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">{{ $bundle ? 'Checkout #'.$bundle->id : 'Single order' }}</h2>
                </div>
                @if ($bundle)
                    <p class="admin-dash-panel-lede mb-3">Paid together. Payment, cancel and WHMCS actions apply to every domain below.</p>
                    <div class="admin-table-wrap is-full">
                        <table class="admin-table is-full">
                            <thead><tr><th>Domain</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($bundle->orders as $sibling)
                                    <tr>
                                        <td>
                                            @if ($sibling->id === $order->id)
                                                <strong>{{ $sibling->domain }}</strong> (this)
                                            @else
                                                <a href="{{ route('admin.domain-orders.show', $sibling) }}">{{ $sibling->domain }}</a>
                                            @endif
                                        </td>
                                        <td>{{ $money::formatMoney((float) $sibling->amount_ngn) }}</td>
                                        <td><x-admin.status :value="$sibling->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="admin-dash-panel-lede">This domain was ordered and paid on its own.</p>
                @endif
            </section>
        </div>

        @include('admin.orders.partials.payment-panel', [
            'payable' => $payable,
            'markPaidUrl' => route('admin.domain-orders.mark-paid', $order),
            'verifyUrl' => route('admin.domain-orders.verify-payment', $order),
            'cancelUrl' => route('admin.domain-orders.cancel', $order),
            'reopenUrl' => route('admin.domain-orders.reopen', $order),
            'scopeNote' => $bundle ? 'Payment covers all '.$bundle->orders->count().' domains in checkout #'.$bundle->id.'.' : null,
        ])

        @include('admin.orders.partials.refund-panel', [
            'order' => $order,
            'refundUrl' => route('admin.domain-orders.refund', $order),
            'refundLabel' => 'Refund for '.$order->domain,
        ])

        @include('admin.orders.partials.notes-and-timeline', [
            'order' => $order,
            'notesUrl' => route('admin.domain-orders.notes', $order),
            'events' => $events,
        ])
    </div>
@endsection
