@php
    $canFull = \App\Support\AdminPermissions::currentCan('orders', 'full');
    $gatewayOptions = $gateways !== [] ? $gateways : [config('site.whmcs.payment_method') => config('site.whmcs.payment_method')];
@endphp

<div class="flex flex-wrap items-start gap-2 px-4 pb-4">
    <details class="admin-queue-pay">
        <summary class="admin-btn-primary">New order</summary>
        <form method="POST" action="{{ route('admin.queue.clients.order', $customer) }}" class="admin-queue-pay-form">
            @csrf
            <select name="pid" class="admin-input" aria-label="Product" style="grid-column: 1 / -1">
                <option value="">No hosting product (domain only)</option>
                @foreach ($products as $pid => $name)
                    <option value="{{ $pid }}">{{ $name }}</option>
                @endforeach
            </select>
            <select name="billingcycle" class="admin-input" aria-label="Billing cycle">
                @foreach (\App\Http\Controllers\AdminWhmcsToolsController::CYCLES as $value => $label)
                    <option value="{{ $value }}" @selected($value === 'annually')>{{ $label }}</option>
                @endforeach
            </select>
            <input name="domain" type="text" class="admin-input" placeholder="example.com" aria-label="Domain">
            <select name="domaintype" class="admin-input" aria-label="Domain action">
                <option value="none">Domain: use existing</option>
                <option value="register">Domain: register</option>
                <option value="transfer">Domain: transfer in</option>
            </select>
            <select name="regperiod" class="admin-input" aria-label="Domain years">
                @foreach (range(1, 10) as $years)
                    <option value="{{ $years }}">{{ $years }} {{ \Illuminate\Support\Str::plural('year', $years) }}</option>
                @endforeach
            </select>
            <input name="eppcode" type="text" class="admin-input" placeholder="EPP code (transfers)" aria-label="EPP code">
            <select name="gateway" class="admin-input" required aria-label="Payment method">
                @foreach ($gatewayOptions as $module => $label)
                    <option value="{{ $module }}">{{ $label }}</option>
                @endforeach
            </select>
            <label class="admin-mini-status"><input type="checkbox" name="noinvoice" value="1"> No invoice (free / already paid)</label>
            <label class="admin-mini-status"><input type="checkbox" name="noemail" value="1"> Don't email the client</label>
            <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Create order</button>
        </form>
    </details>

    <details class="admin-queue-pay">
        <summary class="admin-btn-ghost">New invoice</summary>
        <form method="POST" action="{{ route('admin.queue.clients.invoice', $customer) }}" class="admin-queue-pay-form">
            @csrf
            @foreach (range(0, 2) as $i)
                <input name="items[{{ $i }}][description]" type="text" class="admin-input" placeholder="Line {{ $i + 1 }} description" aria-label="Line {{ $i + 1 }} description" @required($i === 0)>
                <input name="items[{{ $i }}][amount]" type="number" step="0.01" min="0" class="admin-input" placeholder="Amount" aria-label="Line {{ $i + 1 }} amount" @required($i === 0)>
            @endforeach
            <input name="duedate" type="date" value="{{ today()->addDays(7)->toDateString() }}" min="{{ today()->toDateString() }}" class="admin-input" required aria-label="Due date">
            <select name="gateway" class="admin-input" required aria-label="Payment method">
                @foreach ($gatewayOptions as $module => $label)
                    <option value="{{ $module }}">{{ $label }}</option>
                @endforeach
            </select>
            <label class="admin-mini-status" style="grid-column: 1 / -1"><input type="checkbox" name="sendinvoice" value="1" checked> Email the invoice to the client</label>
            <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Create invoice</button>
        </form>
    </details>

    @if ($canFull)
        <details class="admin-queue-pay">
            <summary class="admin-btn-ghost">Credit balance{{ $customer->credit !== null ? ' ('.number_format((float) $customer->credit, 2).')' : '' }}</summary>
            <form method="POST" action="{{ route('admin.queue.clients.credit', $customer) }}" class="admin-queue-pay-form">
                @csrf
                <select name="type" class="admin-input" aria-label="Add or remove">
                    <option value="add">Add credit</option>
                    <option value="remove">Remove credit</option>
                </select>
                <input name="amount" type="number" step="0.01" min="0.01" class="admin-input" placeholder="Amount" required aria-label="Amount">
                <input name="description" type="text" class="admin-input" placeholder="Reason (shown to the client)" required aria-label="Reason" style="grid-column: 1 / -1">
                <button type="submit" class="admin-btn-primary" style="grid-column: 1 / -1">Save</button>
            </form>
        </details>
    @endif
</div>
