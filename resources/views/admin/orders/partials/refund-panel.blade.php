{{-- Refund recording. Expects: $order, $refundUrl, optional $refundLabel. --}}
@php
    $money = \App\Support\HostingPricing::class;
    $refundable = $order->refundableNgn();
@endphp

@if ($order->wasPaid())
    <section class="admin-panel">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">{{ $refundLabel ?? 'Refunds' }}</h2>
                <p class="admin-dash-panel-lede">
                    Record a refund you have already sent (for example from the Flutterwave dashboard or by bank transfer).
                    This does not send money and does not cancel WHMCS services or mailboxes.
                </p>
            </div>
        </div>

        <dl class="admin-dl">
            <div><dt>Refunded so far</dt><dd>{{ $money::formatMoney((float) ($order->refunded_amount_ngn ?? 0)) }}</dd></div>
            <div><dt>Can still refund</dt><dd>{{ $money::formatMoney($refundable) }}</dd></div>
            @if ($order->refund_reference)
                <div><dt>Last refund ref</dt><dd class="font-mono text-xs">{{ $order->refund_reference }}</dd></div>
            @endif
            @if ($order->refunded_at)
                <div><dt>Last refund</dt><dd>{{ $order->refunded_at->format('d M Y H:i') }}</dd></div>
            @endif
        </dl>

        @if ($refundable > 0)
            <form method="POST" action="{{ $refundUrl }}" class="mt-5 grid gap-3 md:grid-cols-3" data-submit-form>
                @csrf
                <label class="admin-field">
                    <span>Amount (₦)</span>
                    <input type="number" name="amount_ngn" step="0.01" min="0.01" max="{{ $refundable }}" value="{{ old('amount_ngn') }}" placeholder="Up to {{ number_format($refundable, 2) }}" class="admin-input" required>
                    @error('amount_ngn') <em>{{ $message }}</em> @enderror
                </label>
                <label class="admin-field">
                    <span>Refund reference</span>
                    <input type="text" name="reference" class="admin-input" maxlength="120">
                </label>
                <label class="admin-field">
                    <span>Note</span>
                    <input type="text" name="note" class="admin-input" maxlength="1000">
                </label>
                <div class="md:col-span-3">
                    <button type="submit" class="admin-btn-danger inline-flex items-center gap-2" data-submit-button onclick="return confirm('Record this refund?');">
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Record refund</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                </div>
            </form>
        @endif
    </section>
@endif
