{{-- Payment actions for an order. Expects: $payable, $markPaidUrl, $verifyUrl, $cancelUrl, $reopenUrl, optional $scopeNote. --}}
@php
    $money = \App\Support\HostingPricing::class;
@endphp

<section class="admin-panel">
    <div class="admin-panel-toolbar compact">
        <div>
            <h2 class="admin-dash-panel-title">Payment</h2>
            <p class="admin-dash-panel-lede">
                {{ $money::formatMoney((float) $payable->amount_ngn) }}
                · {{ str_replace('_', ' ', (string) ($payable->payment_status ?: 'no payment yet')) }}
                · via {{ $payable->payment_provider ?: '—' }}
                @if (! empty($scopeNote))
                    <br>{{ $scopeNote }}
                @endif
            </p>
        </div>
        <div class="admin-pill-row">
            @if ($payable->isCancelled())
                <span class="admin-pill" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;">Cancelled</span>
            @elseif ($payable->isRefunded())
                <span class="admin-pill is-info">{{ str_replace('_', ' ', $payable->status) }}</span>
            @elseif ($payable->isPaid())
                <span class="admin-pill is-ok">Paid</span>
            @else
                <span class="admin-pill is-info">Unpaid</span>
            @endif
        </div>
    </div>

    <dl class="admin-dl">
        <div><dt>Reference</dt><dd class="font-mono text-xs">{{ $payable->payment_reference ?: '—' }}</dd></div>
        <div><dt>Flutterwave transaction</dt><dd class="font-mono text-xs">{{ $payable->flutterwave_transaction_id ?: '—' }}</dd></div>
        @if ($payable->isCancelled())
            <div class="admin-dl-span">
                <dt>Cancelled</dt>
                <dd>{{ $payable->cancelled_at?->format('d M Y H:i') ?: '—' }}{{ $payable->cancelled_reason ? ' · '.$payable->cancelled_reason : '' }}</dd>
            </div>
        @endif
    </dl>

    @if ($payable->isCancelled())
        <form method="POST" action="{{ $reopenUrl }}" class="mt-5" data-submit-form>
            @csrf
            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Reopen order</span>
                <span class="hidden" data-submit-loading>Reopening…</span>
            </button>
        </form>
    @elseif (! $payable->wasPaid())
        <div class="admin-customer-grid mt-6 border-t border-border pt-5">
            <form method="POST" action="{{ $markPaidUrl }}" class="space-y-3" data-submit-form>
                @csrf
                <p class="admin-dash-panel-title">Mark paid by hand</p>
                <p class="admin-dash-panel-lede">For bank transfers or cash. This sets up the order exactly as an online payment would (WHMCS, emails, mailboxes).</p>
                <label class="admin-field">
                    <span>Payment reference (bank transfer ID, receipt no.)</span>
                    <input type="text" name="reference" value="{{ old('reference') }}" class="admin-input" maxlength="120">
                </label>
                <label class="admin-field">
                    <span>Note</span>
                    <input type="text" name="note" value="{{ old('note') }}" class="admin-input" maxlength="1000">
                </label>
                <label class="admin-check">
                    <input type="checkbox" name="confirm" value="1">
                    <span>I confirm {{ $money::formatMoney((float) $payable->amount_ngn) }} has been received</span>
                </label>
                @error('confirm') <p class="text-sm text-rose">{{ $message }}</p> @enderror
                <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                    <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                    <span data-submit-label>Mark paid</span>
                    <span class="hidden" data-submit-loading>Processing…</span>
                </button>
            </form>

            <div class="space-y-6">
                <form method="POST" action="{{ $verifyUrl }}" class="space-y-3" data-submit-form>
                    @csrf
                    <p class="admin-dash-panel-title">Check a Flutterwave payment</p>
                    <p class="admin-dash-panel-lede">Customer says they paid but the order is still unpaid? Paste the transaction ID from Flutterwave.</p>
                    <input type="text" name="transaction_id" class="admin-input w-full" placeholder="e.g. 4829301" maxlength="80" required>
                    <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Check payment</span>
                        <span class="hidden" data-submit-loading>Checking…</span>
                    </button>
                </form>

                <form method="POST" action="{{ $cancelUrl }}" class="space-y-3 border-t border-border pt-5" data-submit-form>
                    @csrf
                    <p class="admin-dash-panel-title">Cancel order</p>
                    <input type="text" name="reason" class="admin-input w-full" placeholder="Reason (optional, internal)" maxlength="500">
                    <button type="submit" class="admin-btn-danger inline-flex items-center gap-2" data-submit-button onclick="return confirm('Cancel this order? The customer will no longer be able to pay for it.');">
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Cancel order</span>
                        <span class="hidden" data-submit-loading>Cancelling…</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</section>
