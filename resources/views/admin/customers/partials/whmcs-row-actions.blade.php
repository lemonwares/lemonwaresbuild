@php
    $canFull = \App\Support\AdminPermissions::currentCan('orders', 'full');
    $confirm = fn (string $text) => 'return confirm('.e(json_encode($text)).')';
@endphp

<div class="flex items-start gap-2">
    @switch ($tab)
        @case('services')
            @if ($row->status === 'Pending')
                <form method="POST" action="{{ route('admin.queue.services.provision', $row) }}" onsubmit="{{ $confirm('Create this service on the server now?') }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary">Set up</button>
                </form>
            @endif
            @if ($row->status === 'Active')
                <form method="POST" action="{{ route('admin.queue.services.cpanel', $row) }}" target="_blank">
                    @csrf
                    <button type="submit" class="admin-btn-ghost">Control panel</button>
                </form>
                <details class="admin-queue-pay">
                    <summary class="admin-btn-ghost">Suspend</summary>
                    <form method="POST" action="{{ route('admin.queue.services.suspend', $row) }}" class="admin-queue-pay-form">
                        @csrf
                        <input name="reason" type="text" value="Overdue on payment" class="admin-input" aria-label="Reason" style="grid-column: 1 / -1">
                        <button type="submit" class="admin-btn-danger" style="grid-column: 1 / -1">Suspend service</button>
                    </form>
                </details>
            @endif
            @if ($row->status === 'Suspended')
                <form method="POST" action="{{ route('admin.queue.services.unsuspend', $row) }}" onsubmit="{{ $confirm('Unsuspend this service?') }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary">Unsuspend</button>
                </form>
            @endif
            @if (in_array($row->status, ['Active', 'Suspended'], true) && ($products ?? []) !== [])
                <details class="admin-queue-pay">
                    <summary class="admin-btn-ghost">Change package</summary>
                    <form method="POST" action="{{ route('admin.queue.services.package', $row) }}" class="admin-queue-pay-form" onsubmit="{{ $confirm('Move this service to the new package? The server account is changed straight away.') }}">
                        @csrf
                        <select name="pid" class="admin-input" required aria-label="New package" style="grid-column: 1 / -1">
                            @foreach ($products as $pid => $name)
                                <option value="{{ $pid }}" @disabled($pid === (int) $row->whmcs_product_id)>{{ $name }}{{ $pid === (int) $row->whmcs_product_id ? ' (current)' : '' }}</option>
                            @endforeach
                        </select>
                        <input name="recurringamount" type="number" step="0.01" min="0" class="admin-input" placeholder="New renewal price (optional)" aria-label="New renewal price" style="grid-column: 1 / -1">
                        <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Change package</button>
                    </form>
                </details>
            @endif
            @if ($canFull && ! in_array($row->status, ['Terminated', 'Cancelled', 'Pending'], true))
                <form method="POST" action="{{ route('admin.queue.services.terminate', $row) }}" onsubmit="{{ $confirm('Terminate this service? The account is removed from the server and this cannot be undone.') }}">
                    @csrf
                    <button type="submit" class="admin-btn-danger">Terminate</button>
                </form>
            @endif
            @break

        @case('domains')
            @if (in_array($row->status, \App\Support\AdminWorkQueue::DOMAIN_PENDING, true))
                @php $transfer = $row->status === 'Pending Transfer'; @endphp
                <form method="POST" action="{{ route('admin.queue.domains.send', $row) }}" onsubmit="{{ $confirm($transfer ? 'Submit this transfer to the registrar?' : 'Register this domain now? Your registrar account will be charged.') }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary">{{ $transfer ? 'Submit transfer' : 'Register now' }}</button>
                </form>
            @endif
            @if (in_array($row->status, ['Active', 'Grace', 'Redemption', 'Expired'], true))
                <details class="admin-queue-pay">
                    <summary class="admin-btn-ghost">Renew</summary>
                    <form method="POST" action="{{ route('admin.queue.domains.renew', $row) }}" class="admin-queue-pay-form" onsubmit="{{ $confirm('Renew at the registrar? Your registrar account will be charged.') }}">
                        @csrf
                        <select name="years" class="admin-input" aria-label="Years">
                            @foreach (range(1, 10) as $years)
                                <option value="{{ $years }}">{{ $years }} {{ \Illuminate\Support\Str::plural('year', $years) }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="admin-btn-primary">Renew</button>
                    </form>
                </details>
                <details class="admin-queue-pay" data-ns-url="{{ route('admin.queue.domains.nameservers', $row) }}">
                    <summary class="admin-btn-ghost">Nameservers</summary>
                    <form method="POST" action="{{ route('admin.queue.domains.nameservers.update', $row) }}" class="admin-queue-pay-form">
                        @csrf
                        @method('PUT')
                        @foreach (range(1, 5) as $n)
                            <input name="ns{{ $n }}" type="text" class="admin-input" placeholder="ns{{ $n }}.example.com{{ $n > 2 ? ' (optional)' : '' }}" @required($n <= 2) aria-label="Nameserver {{ $n }}">
                        @endforeach
                        <button type="submit" class="admin-btn-primary">Save</button>
                    </form>
                </details>
                <details class="admin-queue-pay" data-details-url="{{ route('admin.queue.domains.details', $row) }}">
                    <summary class="admin-btn-ghost">Lock &amp; contacts</summary>
                    <div class="admin-queue-pay-form">
                        <p class="admin-mini-status" data-lock-state style="grid-column: 1 / -1">Loading registrar details…</p>
                        <form method="POST" action="{{ route('admin.queue.domains.lock', $row) }}" style="display: contents">
                            @csrf
                            <button type="submit" name="lock" value="1" class="admin-btn-ghost">Lock</button>
                            <button type="submit" name="lock" value="0" class="admin-btn-ghost" onclick="{{ $confirm('Unlock this domain? It can then be transferred away.') }}">Unlock</button>
                        </form>
                    </div>
                    <form method="POST" action="{{ route('admin.queue.domains.contacts', $row) }}" class="admin-queue-pay-form">
                        @csrf
                        @method('PUT')
                        @foreach (\App\Http\Controllers\AdminWhmcsToolsController::CONTACT_FIELDS as $key => $label)
                            <input name="contact[{{ $key }}]" type="{{ $key === 'Email' ? 'email' : 'text' }}" class="admin-input" placeholder="{{ $label }}" aria-label="{{ $label }}" @required(in_array($key, ['First_Name', 'Last_Name', 'Email', 'Country'], true)) @if ($key === 'Country') maxlength="2" @endif>
                        @endforeach
                        <label class="admin-mini-status" style="grid-column: 1 / -1"><input type="checkbox" name="all_contacts" value="1" checked> Use for admin, tech and billing contacts too</label>
                        <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Save contacts</button>
                    </form>
                </details>
                @if ($canFull)
                    <form method="POST" action="{{ route('admin.queue.domains.epp', $row) }}" onsubmit="{{ $confirm('Request the EPP (transfer) code? Only share it with the verified domain owner.') }}">
                        @csrf
                        <button type="submit" class="admin-btn-ghost">EPP code</button>
                    </form>
                @endif
            @endif
            @break

        @case('invoices')
            @if ($row->status === 'Unpaid')
                <details class="admin-queue-pay">
                    <summary class="admin-btn-ghost">Record payment</summary>
                    <form method="POST" action="{{ route('admin.queue.invoices.payment', $row) }}" class="admin-queue-pay-form">
                        @csrf
                        <input name="amount" type="number" step="0.01" min="0.01" value="{{ $row->total }}" class="admin-input" required aria-label="Amount">
                        <select name="gateway" class="admin-input" required aria-label="Payment method">
                            @forelse ($gateways as $module => $label)
                                <option value="{{ $module }}" @selected($module === $row->payment_method)>{{ $label }}</option>
                            @empty
                                <option value="{{ config('site.whmcs.payment_method') }}">{{ config('site.whmcs.payment_method') }}</option>
                            @endforelse
                        </select>
                        <input name="transid" type="text" placeholder="Bank / transaction reference" class="admin-input" required aria-label="Reference">
                        <input name="date" type="date" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="admin-input" required aria-label="Date paid">
                        <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Save payment</button>
                    </form>
                </details>
                <details class="admin-queue-pay">
                    <summary class="admin-btn-ghost">Apply credit</summary>
                    <form method="POST" action="{{ route('admin.queue.invoices.apply-credit', $row) }}" class="admin-queue-pay-form">
                        @csrf
                        <input name="amount" type="number" step="0.01" min="0.01" value="{{ $row->total }}" class="admin-input" required aria-label="Amount">
                        <button type="submit" class="admin-btn-primary">Apply</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('admin.queue.invoices.reminder', $row) }}" onsubmit="{{ $confirm('Email a payment reminder for this invoice?') }}">
                    @csrf
                    <button type="submit" class="admin-btn-ghost">Remind</button>
                </form>
                @if ($canFull)
                    <form method="POST" action="{{ route('admin.queue.invoices.cancel', $row) }}" onsubmit="{{ $confirm('Cancel this invoice in WHMCS?') }}">
                        @csrf
                        <button type="submit" class="admin-btn-danger">Cancel</button>
                    </form>
                @endif
            @endif
            @if ($row->status === 'Paid' && $canFull)
                <details class="admin-queue-pay">
                    <summary class="admin-btn-ghost">Refund</summary>
                    <form method="POST" action="{{ route('admin.queue.invoices.refund', $row) }}" class="admin-queue-pay-form" onsubmit="{{ $confirm('Record this refund in WHMCS?') }}">
                        @csrf
                        <input name="amount" type="number" step="0.01" min="0.01" max="{{ $row->total }}" value="{{ $row->total }}" class="admin-input" required aria-label="Amount">
                        <select name="method" class="admin-input" aria-label="Refund to">
                            <option value="gateway">Sent back to the client (bank / gateway)</option>
                            <option value="credit">Add to client credit balance</option>
                        </select>
                        <input name="transid" type="text" placeholder="Refund reference (for bank / gateway)" class="admin-input" aria-label="Refund reference" style="grid-column: 1 / -1">
                        <button type="submit" class="admin-btn-danger" style="grid-column: 1 / -1">Record refund</button>
                    </form>
                </details>
            @endif
            @break

        @case('tickets')
            <a href="{{ route('admin.queue.tickets.show', $row) }}" class="admin-btn-primary">{{ in_array($row->status, ['Open', 'Customer-Reply'], true) ? 'Reply' : 'Open' }}</a>
            @break

        @case('orders')
            @if ($row->status === 'Pending')
                <form method="POST" action="{{ route('admin.queue.orders.accept', $row) }}" onsubmit="{{ $confirm('Accept this order and set everything up now?') }}">
                    @csrf
                    <button type="submit" class="admin-btn-primary">Accept &amp; set up</button>
                </form>
                <form method="POST" action="{{ route('admin.queue.orders.cancel', $row) }}" onsubmit="{{ $confirm('Cancel this order in WHMCS?') }}">
                    @csrf
                    <button type="submit" class="admin-btn-danger">Cancel</button>
                </form>
            @endif
            @break
    @endswitch
</div>
