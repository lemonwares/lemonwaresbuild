<?php

namespace App\Support;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Binds a verified provider transaction to exactly one order, exactly once.
 *
 * The ledger row (unique per provider + transaction id) and the conditional status
 * update run in one transaction, so a webhook and a browser redirect racing each
 * other cannot both fulfil the same order, and one transaction cannot pay two orders.
 */
class PaymentClaim
{
    /**
     * Statuses that mean money already landed (or the order is closed) and must not be re-claimed.
     */
    public const SETTLED_STATUSES = [
        'paid',
        'fulfilled',
        'submitted',
        'provisioned',
        'paid_pending_setup',
        'awaiting_manual_fulfilment',
        'deactivated',
        'expired',
        'refunded',
        'partially_refunded',
        'cancelled',
    ];

    /**
     * Returns null when the verified transaction may be applied to the payable, otherwise a reason code.
     *
     * @param  array<string, mixed>  $verified
     * @param  list<string>  $acceptedReferences
     */
    public static function problem(Model $payable, array $verified, array $acceptedReferences): ?string
    {
        $transactionId = (string) data_get($verified, 'id', '');
        if ($transactionId === '') {
            return 'missing_transaction';
        }

        $txRef = (string) data_get($verified, 'tx_ref', '');
        $accepted = array_values(array_filter(array_map('strval', $acceptedReferences), fn (string $ref) => $ref !== ''));

        $matches = false;
        foreach ($accepted as $reference) {
            if ($txRef !== '' && hash_equals($reference, $txRef)) {
                $matches = true;
                break;
            }
        }

        if (! $matches) {
            Log::warning('Payment rejected: transaction reference does not belong to this order', [
                'payable' => $payable->getMorphClass().'#'.$payable->getKey(),
                'tx_ref' => $txRef,
                'transaction_id' => $transactionId,
            ]);

            return 'reference_mismatch';
        }

        $existing = self::ledgerEntry($transactionId);
        if ($existing && ! self::belongsTo($existing, $payable)) {
            Log::warning('Payment rejected: transaction already used for another order', [
                'payable' => $payable->getMorphClass().'#'.$payable->getKey(),
                'transaction_id' => $transactionId,
                'used_by' => $existing->payable_type.'#'.$existing->payable_id,
            ]);

            return 'transaction_reused';
        }

        return null;
    }

    public static function alreadyRecorded(Model $payable, string $transactionId, string $provider = 'flutterwave'): bool
    {
        if ($transactionId === '') {
            return false;
        }

        $existing = self::ledgerEntry($transactionId, $provider);

        return $existing !== null && self::belongsTo($existing, $payable);
    }

    /**
     * Atomically records the payment and applies the status updates.
     * Returns false when another request already claimed this order or transaction.
     *
     * @param  array<string, mixed>  $verified
     * @param  array<string, mixed>  $updates
     */
    public static function claim(
        Model $payable,
        array $verified,
        string $kind,
        array $updates,
        bool $requireUnsettled = true,
        string $provider = 'flutterwave',
    ): bool {
        try {
            DB::transaction(function () use ($payable, $verified, $kind, $updates, $requireUnsettled, $provider) {
                if ($updates !== []) {
                    $query = $payable->newQuery()->whereKey($payable->getKey());

                    if ($requireUnsettled) {
                        $query->whereNotIn('status', self::SETTLED_STATUSES)
                            ->where(function ($inner) {
                                $inner->whereNull('payment_status')
                                    ->orWhereNotIn('payment_status', ['successful', 'completed']);
                            });
                    }

                    if ($query->update($updates + ['updated_at' => now()]) === 0) {
                        throw new PaymentAlreadyClaimed;
                    }
                }

                Payment::query()->create([
                    'payable_type' => $payable->getMorphClass(),
                    'payable_id' => $payable->getKey(),
                    'provider' => $provider,
                    'kind' => $kind,
                    'reference' => (string) data_get($verified, 'tx_ref', '') ?: null,
                    'transaction_id' => (string) data_get($verified, 'id', ''),
                    'amount_ngn' => round((float) data_get($verified, 'amount', 0), 2),
                    'currency' => strtoupper((string) data_get($verified, 'currency', 'NGN')) ?: 'NGN',
                    'status' => 'successful',
                    'meta' => array_filter([
                        'flw_ref' => data_get($verified, 'flw_ref'),
                        'payment_type' => data_get($verified, 'payment_type'),
                    ]) ?: null,
                ]);
            });
        } catch (PaymentAlreadyClaimed|UniqueConstraintViolationException) {
            return false;
        }

        $payable->refresh();

        return true;
    }

    protected static function ledgerEntry(string $transactionId, string $provider = 'flutterwave'): ?Payment
    {
        return Payment::query()
            ->where('provider', $provider)
            ->where('transaction_id', $transactionId)
            ->first();
    }

    protected static function belongsTo(Payment $payment, Model $payable): bool
    {
        return $payment->payable_type === $payable->getMorphClass()
            && (string) $payment->payable_id === (string) $payable->getKey();
    }
}
