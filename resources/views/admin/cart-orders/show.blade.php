@extends('layouts.admin')

@section('title', 'Cart Order #' . $order->id . ' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $money = \App\Support\HostingPricing::class;
    $billing = is_array($order->billing_snapshot) ? array_filter($order->billing_snapshot, fn ($v) => is_scalar($v) && $v !== '') : [];
    $shipping = is_array($order->shipping_address) ? array_filter($order->shipping_address, fn ($v) => is_scalar($v) && $v !== '') : [];
@endphp

@section('content')
    <x-admin.page-header
        :title="'Cart order #'.$order->id"
        :lede="trim(($order->user?->name ? $order->user->name.' · ' : '').($order->user?->email ?: 'No customer linked'))"
        :back-href="route('admin.cart-orders.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Cart Orders', 'href' => route('admin.cart-orders.index')],
            ['label' => '#'.$order->id],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                @if ($order->user && $order->user->isCustomer())
                    <a href="{{ route('admin.customers.show', $order->user) }}" class="admin-btn-ghost">Customer profile</a>
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
                <span class="admin-metric-label">Items</span>
                <span class="admin-metric-value">{{ $order->items->count() }}</span>
                <span class="admin-metric-meta">{{ $order->items->pluck('type')->unique()->implode(', ') }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Amount</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $money::formatMoney((float) $order->amount_ngn) }}</span>
                <span class="admin-metric-meta">
                    {{ $money::formatMoney((float) $order->amount_usd, 'USD') }}
                    @if ($order->coupon_code)
                        · code {{ $order->coupon_code }} saved {{ $money::formatMoney((float) $order->discount_ngn) }}
                    @endif
                </span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Setup</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $order->fulfilment_status ?: '—' }}</span>
                <span class="admin-metric-meta">
                    @if ($order->fulfilment_status === 'partial')
                        Some items failed — see below
                    @elseif ($order->fulfilled_at)
                        {{ $order->fulfilled_at->format('d M Y H:i') }}
                    @else
                        Starts after payment
                    @endif
                </span>
            </div>
        </section>

        @if ($order->fulfilment_error)
            <p class="rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
                Setup problem: {{ $order->fulfilment_error }}. Open the linked order below to fix it (for example retry WHMCS on the domain order).
            </p>
        @endif

        <section class="admin-panel admin-panel-flush">
            <div class="admin-panel-toolbar">
                <h2 class="admin-dash-panel-title">Items</h2>
            </div>
            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr><th>Type</th><th>Item</th><th>Amount</th><th>Setup</th><th>Linked order</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>{{ $item->type }}</td>
                                <td><strong>{{ $item->label }}</strong></td>
                                <td>{{ $money::formatMoney((float) $item->amount_ngn) }}</td>
                                <td>
                                    @if ($item->fulfilment_status)
                                        <x-admin.status :value="$item->fulfilment_status" />
                                    @else
                                        —
                                    @endif
                                    @if ($item->fulfilment_error)
                                        <br><span class="text-rose text-xs">{{ $item->fulfilment_error }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->domain_order_id)
                                        <a href="{{ route('admin.domain-orders.show', $item->domain_order_id) }}">Domain order #{{ $item->domain_order_id }}</a>
                                    @elseif ($item->email_order_id)
                                        <a href="{{ route('admin.email-orders.show', $item->email_order_id) }}">Email order #{{ $item->email_order_id }}</a>
                                    @elseif ($item->hosting_lead_id)
                                        <a href="{{ route('admin.hosting-leads.show', $item->hosting_lead_id) }}">Hosting lead #{{ $item->hosting_lead_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if ($billing || $shipping)
            <div class="admin-customer-grid">
                @if ($billing)
                    <section class="admin-panel">
                        <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Billing details at checkout</h2></div>
                        <dl class="admin-dl">
                            @foreach ($billing as $key => $value)
                                <div><dt>{{ ucfirst(str_replace('_', ' ', (string) $key)) }}</dt><dd>{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </section>
                @endif
                @if ($shipping && ! $order->shipping_same_as_billing)
                    <section class="admin-panel">
                        <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Shipping address</h2></div>
                        <dl class="admin-dl">
                            @foreach ($shipping as $key => $value)
                                <div><dt>{{ ucfirst(str_replace('_', ' ', (string) $key)) }}</dt><dd>{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </section>
                @endif
            </div>
        @endif

        @include('admin.orders.partials.payment-panel', [
            'payable' => $order,
            'markPaidUrl' => route('admin.cart-orders.mark-paid', $order),
            'verifyUrl' => route('admin.cart-orders.verify-payment', $order),
            'cancelUrl' => route('admin.cart-orders.cancel', $order),
            'reopenUrl' => route('admin.cart-orders.reopen', $order),
        ])

        @include('admin.orders.partials.refund-panel', [
            'order' => $order,
            'refundUrl' => route('admin.cart-orders.refund', $order),
        ])

        @include('admin.orders.partials.notes-and-timeline', [
            'order' => $order,
            'notesUrl' => route('admin.cart-orders.notes', $order),
            'events' => $events,
        ])
    </div>
@endsection
