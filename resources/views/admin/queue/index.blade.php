@extends('layouts.admin')

@section('title', 'Needs action — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    @php
        $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '—';
        $money = fn ($value) => $value === null ? '—' : number_format((float) $value, 2);
        $customer = function (?int $clientId) use ($clients) {
            $link = \App\Support\AdminWorkQueue::clientLink($clients, $clientId);
            if (! $link) {
                return '—';
            }

            return $link['href'] ? '<a href="'.e($link['href']).'">'.e($link['label']).'</a>' : e($link['label']);
        };
        $total = $orders->count() + $domains->count() + $services->count() + $handoff->count() + $carts->count() + $emails->count();
    @endphp

    <x-admin.page-header
        title="Needs action"
        lede="Customers waiting on us: approve orders, record payments, register domains and set up services. Everything here runs in WHMCS for you."
        :breadcrumbs="[['label' => 'Needs action']]"
        class="mb-5"
    />

    @error('whmcs')
        <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
    @enderror
    @if ($errors->any() && ! $errors->has('whmcs'))
        <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
    @endif

    <div class="admin-page-stack">
        @if ($total === 0)
            <section class="admin-panel">
                <p class="admin-dash-panel-title">All clear</p>
                <p class="admin-dash-panel-lede">Nothing is waiting on you. New paid orders, pending domains and services will show up here.</p>
            </section>
        @endif

        @if ($orders->isNotEmpty())
            <section class="admin-panel admin-panel-flush">
                <div class="admin-panel-toolbar">
                    <div>
                        <h2 class="admin-dash-panel-title">Orders waiting for approval</h2>
                        <p class="admin-dash-panel-lede">{{ $orders->count() }} pending in WHMCS. Accepting sets up the services and sends domains to the registrar.</p>
                    </div>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr><th>Order</th><th>Customer</th><th>Placed</th><th>Amount</th><th>Payment</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                @php
                                    $invoice = $order->whmcs_invoice_id ? $invoices->get($order->whmcs_invoice_id) : null;
                                    $paid = strtolower((string) $order->payment_status) === 'paid' || $invoice?->status === 'Paid';
                                @endphp
                                <tr>
                                    <td><strong>#{{ $order->order_num ?: $order->whmcs_order_id }}</strong></td>
                                    <td>{!! $customer($order->whmcs_client_id) !!}</td>
                                    <td>{{ $date($order->placed_at) }}</td>
                                    <td>{{ $money($order->amount) }}</td>
                                    <td>
                                        <x-admin.status :value="$paid ? 'paid' : 'awaiting_payment'" :label="$paid ? 'Paid' : 'Unpaid'" />
                                        @if ($order->whmcs_invoice_id)
                                            <span class="admin-mini-status">Invoice #{{ $order->whmcs_invoice_id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-start gap-2">
                                            @if (! $paid && $invoice?->status === 'Unpaid')
                                                <details class="admin-queue-pay">
                                                    <summary class="admin-btn-ghost">Record payment</summary>
                                                    <form method="POST" action="{{ route('admin.queue.invoices.payment', $invoice) }}" class="admin-queue-pay-form">
                                                        @csrf
                                                        <input name="amount" type="number" step="0.01" min="0.01" value="{{ $invoice->total }}" class="admin-input" required aria-label="Amount">
                                                        <select name="gateway" class="admin-input" required aria-label="Payment method">
                                                            @forelse ($gateways as $module => $label)
                                                                <option value="{{ $module }}" @selected($module === $invoice->payment_method)>{{ $label }}</option>
                                                            @empty
                                                                <option value="{{ config('site.whmcs.payment_method') }}">{{ config('site.whmcs.payment_method') }}</option>
                                                            @endforelse
                                                        </select>
                                                        <input name="transid" type="text" placeholder="Bank / transaction reference" class="admin-input" required aria-label="Reference">
                                                        <input name="date" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="admin-input" required aria-label="Date paid">
                                                        <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Save payment</button>
                                                    </form>
                                                </details>
                                            @endif
                                            <form method="POST" action="{{ route('admin.queue.orders.accept', $order) }}" onsubmit="return confirm('{{ $paid ? 'Accept this order and set everything up now?' : 'This order is NOT paid. Accept and set it up anyway?' }}')">
                                                @csrf
                                                <button type="submit" class="{{ $paid ? 'admin-btn-primary' : 'admin-btn-ghost' }}">Accept &amp; set up</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.queue.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order in WHMCS?')">
                                                @csrf
                                                <button type="submit" class="admin-btn-danger">Cancel</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($domains->isNotEmpty())
            <section class="admin-panel admin-panel-flush">
                <div class="admin-panel-toolbar">
                    <div>
                        <h2 class="admin-dash-panel-title">Domains waiting on the registrar</h2>
                        <p class="admin-dash-panel-lede">{{ $domains->count() }} not yet registered or transferred. Sending charges your registrar account.</p>
                    </div>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr><th>Domain</th><th>Customer</th><th>Type</th><th>Ordered</th><th>Registrar</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($domains as $domain)
                                @php $transfer = $domain->status === 'Pending Transfer'; @endphp
                                <tr>
                                    <td><strong>{{ $domain->domain }}</strong></td>
                                    <td>{!! $customer($domain->whmcs_client_id) !!}</td>
                                    <td>{{ $transfer ? 'Transfer' : 'Registration' }}</td>
                                    <td>{{ $date($domain->registration_date) }}</td>
                                    <td>{{ $domain->registrar ?: '—' }}</td>
                                    <td><x-admin.status value="pending" :label="$domain->status" /></td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.queue.domains.send', $domain) }}" onsubmit="return confirm('{{ $transfer ? 'Submit the transfer of '.$domain->domain.' to the registrar?' : 'Register '.$domain->domain.' at the registrar now? Your registrar account will be charged.' }}')">
                                            @csrf
                                            <button type="submit" class="admin-btn-primary">{{ $transfer ? 'Submit transfer' : 'Register now' }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($services->isNotEmpty())
            <section class="admin-panel admin-panel-flush">
                <div class="admin-panel-toolbar">
                    <div>
                        <h2 class="admin-dash-panel-title">Services waiting for setup</h2>
                        <p class="admin-dash-panel-lede">{{ $services->count() }} hosting or server services not yet created on the server.</p>
                    </div>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr><th>Service</th><th>Customer</th><th>Domain</th><th>Cycle</th><th>Amount</th><th>Ordered</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($services as $service)
                                <tr>
                                    <td><strong>{{ $service->product_name ?: 'Service #'.$service->whmcs_service_id }}</strong></td>
                                    <td>{!! $customer($service->whmcs_client_id) !!}</td>
                                    <td>{{ $service->domain ?: '—' }}</td>
                                    <td>{{ $service->billing_cycle ?: '—' }}</td>
                                    <td>{{ $money($service->amount) }}</td>
                                    <td>{{ $date($service->registration_date) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.queue.services.provision', $service) }}" onsubmit="return confirm('Create this service on the server now?')">
                                            @csrf
                                            <button type="submit" class="admin-btn-primary">Set up now</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($handoff->isNotEmpty() || $carts->isNotEmpty())
            <section class="admin-panel admin-panel-flush">
                <div class="admin-panel-toolbar">
                    <div>
                        <h2 class="admin-dash-panel-title">Paid on our site, not finished in WHMCS</h2>
                        <p class="admin-dash-panel-lede">The customer paid (or the payment needs checking) but the hand-off to WHMCS did not complete.</p>
                    </div>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr><th>Order</th><th>Customer</th><th>Placed</th><th>Payment</th><th>Problem</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($handoff as $order)
                                <tr>
                                    <td><strong>{{ $order->domain }}</strong> <span class="admin-mini-status">domain</span></td>
                                    <td>{{ $order->user?->email ?: '—' }}</td>
                                    <td>{{ $date($order->created_at) }}</td>
                                    <td><x-admin.status :value="$order->payment_status" /></td>
                                    <td class="text-xs">{{ \Illuminate\Support\Str::limit((string) ($order->whmcs_sync_error ?: $order->whmcs_sync_status ?? 'Not sent to WHMCS'), 70) }}</td>
                                    <td>
                                        <div class="flex gap-2">
                                            @if ($order->payment_status === 'successful')
                                                <form method="POST" action="{{ route('admin.domain-orders.retry-whmcs', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="admin-btn-primary">Retry WHMCS</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('admin.domain-orders.show', $order) }}" class="admin-btn-ghost">Open</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($carts as $cart)
                                <tr>
                                    <td><strong>Cart #{{ $cart->id }}</strong> <span class="admin-mini-status">cart</span></td>
                                    <td>{{ $cart->user?->email ?: '—' }}</td>
                                    <td>{{ $date($cart->created_at) }}</td>
                                    <td><x-admin.status :value="$cart->payment_status" /></td>
                                    <td class="text-xs">{{ $cart->fulfilment_status ? 'Fulfilment '.$cart->fulfilment_status : 'Payment needs checking' }}</td>
                                    <td><a href="{{ route('admin.cart-orders.show', $cart) }}" class="admin-btn-ghost">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($emails->isNotEmpty())
            <section class="admin-panel admin-panel-flush">
                <div class="admin-panel-toolbar">
                    <div>
                        <h2 class="admin-dash-panel-title">Email orders to fulfil</h2>
                        <p class="admin-dash-panel-lede">Paid email services (not on WHMCS) waiting for manual setup.</p>
                    </div>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr><th>Domain</th><th>Customer</th><th>Plan</th><th>Paid</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($emails as $email)
                                <tr>
                                    <td><strong>{{ $email->domain }}</strong></td>
                                    <td>{{ $email->user?->email ?: '—' }}</td>
                                    <td>{{ $email->plan_name ?: ($email->plan_key ?: '—') }}</td>
                                    <td>{{ $date($email->created_at) }}</td>
                                    <td><a href="{{ route('admin.email-orders.show', $email) }}" class="admin-btn-primary">Fulfil</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
