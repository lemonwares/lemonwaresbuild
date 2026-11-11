<?php

namespace App\Support\Whmcs;

use App\Models\InvoicePayment;
use App\Models\WhmcsInvoice;
use Illuminate\Support\Facades\Log;

/**
 * Records Flutterwave payments for WHMCS invoices back into WHMCS, exactly once.
 */
class WhmcsInvoicePayments
{
    private const MAX_ATTEMPTS = 20;

    public static function record(InvoicePayment $payment): bool
    {
        if ($payment->whmcs_recorded_at) {
            return true;
        }
        if (! $payment->isPaid() || ! $payment->flutterwave_transaction_id) {
            return false;
        }

        $transId = 'FLW-'.$payment->flutterwave_transaction_id;

        // A previous attempt may have reached WHMCS even though we never saw the response.
        $invoice = Whmcs::call('GetInvoice', ['invoiceid' => $payment->whmcs_invoice_id]);
        $alreadyThere = $invoice->ok && collect($invoice->rows('transactions.transaction'))
            ->contains(fn ($txn) => (string) ($txn['transid'] ?? '') === $transId);

        if (! $alreadyThere) {
            $result = Whmcs::call('AddInvoicePayment', [
                'invoiceid' => $payment->whmcs_invoice_id,
                'transid' => $transId,
                'gateway' => (string) config('site.whmcs.payment_method', 'banktransfer'),
                'amount' => (string) $payment->amount_ngn,
                'date' => $payment->updated_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            ]);

            if (! $result->ok) {
                $payment->increment('whmcs_attempts', 1, ['whmcs_error' => (string) $result->error]);
                Log::error('Paid invoice could not be recorded in WHMCS', [
                    'invoice_payment_id' => $payment->id,
                    'whmcs_invoice_id' => $payment->whmcs_invoice_id,
                    'error' => $result->error,
                ]);

                return false;
            }
        }

        $payment->update(['whmcs_recorded_at' => now(), 'whmcs_error' => null]);
        self::refreshLocalInvoice($payment);

        return true;
    }

    /**
     * Scheduled retry for payments that landed while WHMCS was unreachable.
     */
    public static function recordPending(): int
    {
        $recorded = 0;

        InvoicePayment::query()
            ->where('status', 'paid')
            ->whereNull('whmcs_recorded_at')
            ->where('whmcs_attempts', '<', self::MAX_ATTEMPTS)
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(function (InvoicePayment $payment) use (&$recorded): void {
                $recorded += self::record($payment) ? 1 : 0;
            });

        return $recorded;
    }

    private static function refreshLocalInvoice(InvoicePayment $payment): void
    {
        $invoice = Whmcs::call('GetInvoice', ['invoiceid' => $payment->whmcs_invoice_id]);
        if (! $invoice->ok) {
            return;
        }

        WhmcsInvoice::query()->where('whmcs_invoice_id', $payment->whmcs_invoice_id)->update(array_filter([
            'status' => $invoice->get('status'),
            'date_paid' => str_starts_with((string) $invoice->get('datepaid'), '0000') ? null : $invoice->get('datepaid'),
            'last_synced_at' => now(),
        ]));
    }
}
