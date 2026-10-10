@php
    $money = fn ($value, $currency = null) => $value === null ? '—' : trim(($currency ?: '').' '.number_format((float) $value, 2));
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d M Y') : '—';
    $labels = ['services' => 'Services', 'domains' => 'Domains', 'invoices' => 'Invoices', 'orders' => 'Orders', 'tickets' => 'Tickets'];
@endphp

<section class="admin-panel admin-panel-flush admin-panel-span" id="whmcs-history" aria-label="WHMCS history">
    <div class="admin-panel-toolbar">
        <div>
            <h2 class="admin-dash-panel-title">WHMCS history</h2>
            <p class="admin-dash-panel-lede">
                @if ($history['clientId'] > 0)
                    Client #{{ $history['clientId'] }} · {{ $history['activeServices'] }} active services ·
                    {{ $history['unpaidCount'] }} unpaid {{ \Illuminate\Support\Str::plural('invoice', $history['unpaidCount']) }}
                    @if ($history['unpaidCount'] > 0)({{ number_format($history['unpaidTotal'], 2) }})@endif
                @else
                    This customer is not linked to a WHMCS client yet.
                @endif
            </p>
        </div>
        <div class="admin-segment">
            @foreach ($labels as $key => $label)
                <a
                    href="{{ request()->fullUrlWithQuery(['wtab' => $key, 'wpage' => null]) }}#whmcs-history"
                    @class(['admin-segment-btn', 'is-active' => $history['tab'] === $key])
                >{{ $label }} <span class="text-black/45">{{ $history['counts'][$key] }}</span></a>
            @endforeach
        </div>
    </div>

    <div class="admin-table-wrap is-full">
        <table class="admin-table is-full">
            <thead>
                <tr>
                    @switch ($history['tab'])
                        @case('domains')
                            <th>Domain</th><th>Registrar</th><th>Registered</th><th>Expires</th><th>Renewal</th><th>Auto-renew</th><th>Status</th>
                            @break
                        @case('invoices')
                            <th>Invoice</th><th>Date</th><th>Due</th><th>Paid</th><th>Total</th><th>Method</th><th>Status</th>
                            @break
                        @case('orders')
                            <th>Order</th><th>Placed</th><th>Amount</th><th>Method</th><th>Payment</th><th>Invoice</th><th>Status</th>
                            @break
                        @case('tickets')
                            <th>Ticket</th><th>Subject</th><th>Department</th><th>Priority</th><th>Opened</th><th>Last reply</th><th>Status</th>
                            @break
                        @default
                            <th>Service</th><th>Domain / username</th><th>Server</th><th>Cycle</th><th>Amount</th><th>Next due</th><th>Status</th>
                    @endswitch
                </tr>
            </thead>
            <tbody>
                @forelse ($history['rows'] as $row)
                    <tr>
                        @switch ($history['tab'])
                            @case('domains')
                                <td><strong>{{ $row->domain }}</strong></td>
                                <td>{{ $row->registrar ?: '—' }}</td>
                                <td>{{ $date($row->registration_date) }}</td>
                                <td>{{ $date($row->expiry_date) }}</td>
                                <td>{{ $money($row->recurring_amount) }}</td>
                                <td>{{ $row->auto_renew ? 'Yes' : 'No' }}</td>
                                @break
                            @case('invoices')
                                <td><strong>#{{ $row->invoice_num ?: $row->whmcs_invoice_id }}</strong></td>
                                <td>{{ $date($row->date) }}</td>
                                <td>{{ $date($row->due_date) }}</td>
                                <td>{{ $date($row->date_paid) }}</td>
                                <td>{{ $money($row->total, $row->currency_code) }}</td>
                                <td>{{ $row->payment_method ?: '—' }}</td>
                                @break
                            @case('orders')
                                <td><strong>#{{ $row->order_num ?: $row->whmcs_order_id }}</strong></td>
                                <td>{{ $date($row->placed_at) }}</td>
                                <td>{{ $money($row->amount) }}</td>
                                <td>{{ $row->payment_method ?: '—' }}</td>
                                <td>{{ $row->payment_status ?: '—' }}</td>
                                <td>{{ $row->whmcs_invoice_id ? '#'.$row->whmcs_invoice_id : '—' }}</td>
                                @break
                            @case('tickets')
                                <td><strong>#{{ $row->tid ?: $row->whmcs_ticket_id }}</strong></td>
                                <td>{{ $row->subject ?: '—' }}</td>
                                <td>{{ $row->department ?: '—' }}</td>
                                <td>{{ $row->priority ?: '—' }}</td>
                                <td>{{ $date($row->opened_at) }}</td>
                                <td>{{ $date($row->last_reply_at) }}</td>
                                @break
                            @default
                                <td><strong>{{ $row->product_name ?: 'Service #'.$row->whmcs_service_id }}</strong></td>
                                <td>{{ $row->domain ?: ($row->username ?: '—') }}</td>
                                <td>{{ $row->server_name ?: '—' }}</td>
                                <td>{{ $row->billing_cycle ?: '—' }}</td>
                                <td>{{ $money($row->amount) }}</td>
                                <td>{{ $date($row->next_due_date) }}</td>
                        @endswitch
                        <td><x-admin.status :value="strtolower(str_replace(' ', '_', (string) $row->status))" :label="$row->status ?: 'Unknown'" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="admin-table-empty">No {{ strtolower($labels[$history['tab']]) }} in WHMCS for this customer.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($history['rows']->hasPages())
        <div class="admin-pagination">{{ $history['rows']->fragment('whmcs-history')->links() }}</div>
    @endif
</section>
