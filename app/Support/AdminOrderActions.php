<?php

namespace App\Support;

use App\Models\AdminOrderEvent;
use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\SiteCheckout;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin-side actions on domain orders, domain checkouts and cart checkouts.
 *
 * Payment confirmation reuses the same FlutterwavePayment::confirm* paths as a real
 * payment, so fulfilment, WHMCS sync and customer notifications behave identically.
 */
class AdminOrderActions
{
    /**
     * @return array{ok:bool,message:string}
     */
    public static function markPaid(SiteCheckout|DomainCheckout|DomainOrder $payable, ?User $admin, ?string $reference, ?string $note): array
    {
        if ($payable->isCancelled()) {
            return ['ok' => false, 'message' => 'This order is cancelled. Reopen it before marking it paid.'];
        }

        if ($payable->wasPaid()) {
            return ['ok' => false, 'message' => 'This order is already paid.'];
        }

        $target = self::paymentTarget($payable);
        if (blank($target->payment_reference)) {
            $target->forceFill([
                'payment_reference' => 'LW-MANUAL-'.class_basename($target).'-'.$target->getKey().'-'.Str::upper(Str::random(8)),
            ])->save();
        }

        $verified = [
            'id' => 'manual-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)),
            'status' => 'successful',
            'amount' => (float) $target->amount_ngn,
            'currency' => 'NGN',
            'tx_ref' => (string) $target->payment_reference,
        ];

        $result = self::confirm($payable, $verified);

        if ($result['ok'] ?? false) {
            $payable->forceFill(['payment_provider' => 'manual'])->save();
            if ($payable instanceof DomainCheckout) {
                $payable->orders()->update(['payment_provider' => 'manual']);
            }
        }

        self::log($payable, $admin, 'marked_paid', 'Marked paid by hand'.(filled($reference) ? ' (ref '.$reference.')' : '').'.', [
            'reference' => $reference,
            'note' => $note,
            'ok' => (bool) ($result['ok'] ?? false),
            'result' => $result['message'] ?? null,
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => ($result['ok'] ?? false)
                ? 'Marked paid. Fulfilment ran the same way as an online payment.'
                : 'Could not mark paid: '.($result['message'] ?? 'unknown error'),
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyFlutterwave(SiteCheckout|DomainCheckout|DomainOrder $payable, ?User $admin, string $transactionId): array
    {
        $transactionId = trim($transactionId);
        if ($transactionId === '') {
            return ['ok' => false, 'message' => 'Enter the Flutterwave transaction ID.'];
        }

        if ($payable->wasPaid()) {
            return ['ok' => false, 'message' => 'This order is already paid.'];
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);
        if (! $verified) {
            return ['ok' => false, 'message' => 'Flutterwave did not confirm that transaction. Check the ID and the Flutterwave settings.'];
        }

        $txRef = (string) data_get($verified, 'tx_ref', '');
        if ($txRef === '' || $txRef !== (string) self::paymentTarget($payable)->payment_reference) {
            self::log($payable, $admin, 'verify_rejected', 'Flutterwave transaction '.$transactionId.' belongs to a different order.', [
                'transaction_id' => $transactionId,
                'tx_ref' => $txRef,
            ]);

            return ['ok' => false, 'message' => 'That transaction belongs to a different order (reference '.($txRef ?: 'none').').'];
        }

        $result = self::confirm($payable, $verified);

        self::log($payable, $admin, 'verified_payment', 'Checked Flutterwave transaction '.$transactionId.': '.($result['message'] ?? ''), [
            'transaction_id' => $transactionId,
            'ok' => (bool) ($result['ok'] ?? false),
        ]);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? 'Verification finished.'),
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function cancel(SiteCheckout|DomainCheckout|DomainOrder $order, ?User $admin, ?string $reason): array
    {
        if ($order->isCancelled()) {
            return ['ok' => false, 'message' => 'This order is already cancelled.'];
        }

        if ($order->wasPaid()) {
            return ['ok' => false, 'message' => 'Paid orders cannot be cancelled. Record a refund instead.'];
        }

        $values = [
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_reason' => filled($reason) ? trim((string) $reason) : null,
        ];

        DB::transaction(function () use ($order, $values) {
            $order->update($values);
            if ($order instanceof DomainCheckout) {
                $order->orders()->update($values);
            }
        });

        self::log($order, $admin, 'cancelled', 'Order cancelled'.(filled($reason) ? ': '.$reason : '.'));

        return ['ok' => true, 'message' => 'Order cancelled. The customer can no longer pay for it.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function reopen(SiteCheckout|DomainCheckout|DomainOrder $order, ?User $admin): array
    {
        if (! $order->isCancelled()) {
            return ['ok' => false, 'message' => 'Only cancelled orders can be reopened.'];
        }

        $values = [
            'status' => 'awaiting_payment',
            'cancelled_at' => null,
            'cancelled_reason' => null,
        ];

        DB::transaction(function () use ($order, $values) {
            $order->update($values);
            if ($order instanceof DomainCheckout) {
                $order->orders()->where('status', 'cancelled')->update($values);
            }
        });

        self::log($order, $admin, 'reopened', 'Order reopened and waiting for payment again.');

        return ['ok' => true, 'message' => 'Order reopened.'];
    }

    /**
     * Records a refund made outside the app (for example in the Flutterwave dashboard).
     * It does not move money and does not cancel WHMCS services or mailboxes.
     *
     * @return array{ok:bool,message:string}
     */
    public static function recordRefund(SiteCheckout|DomainCheckout|DomainOrder $order, ?User $admin, float $amountNgn, ?string $reference, ?string $note): array
    {
        if (! $order->wasPaid()) {
            return ['ok' => false, 'message' => 'Only paid orders can be refunded.'];
        }

        $refundable = $order->refundableNgn();
        if ($amountNgn <= 0 || $amountNgn > $refundable + 0.001) {
            return ['ok' => false, 'message' => 'Refund must be more than ₦0 and no more than '.HostingPricing::formatMoney($refundable).'.'];
        }

        $total = round((float) ($order->refunded_amount_ngn ?? 0) + $amountNgn, 2);
        $full = $total >= (float) $order->amount_ngn - 0.001;

        DB::transaction(function () use ($order, $total, $full, $reference) {
            $order->update([
                'refunded_amount_ngn' => $total,
                'refund_reference' => filled($reference) ? trim((string) $reference) : $order->refund_reference,
                'refunded_at' => now(),
                'status' => $full ? 'refunded' : 'partially_refunded',
            ]);

            if ($full && $order instanceof DomainCheckout) {
                foreach ($order->orders as $domainOrder) {
                    $domainOrder->update([
                        'refunded_amount_ngn' => $domainOrder->amount_ngn,
                        'refund_reference' => $order->refund_reference,
                        'refunded_at' => now(),
                        'status' => 'refunded',
                    ]);
                }
            }
        });

        self::log($order, $admin, 'refund_recorded', 'Refund of '.HostingPricing::formatMoney($amountNgn).' recorded'.(filled($reference) ? ' (ref '.$reference.')' : '').'.', [
            'amount_ngn' => $amountNgn,
            'reference' => $reference,
            'note' => $note,
        ]);

        return [
            'ok' => true,
            'message' => $full
                ? 'Full refund recorded. Remember to cancel any WHMCS service or mailbox by hand if needed.'
                : 'Partial refund recorded.',
        ];
    }

    public static function updateNotes(SiteCheckout|DomainCheckout|DomainOrder $order, ?User $admin, ?string $notes): void
    {
        $order->update(['admin_notes' => filled($notes) ? trim((string) $notes) : null]);
        self::log($order, $admin, 'notes_updated', 'Internal notes updated.');
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function retryWhmcs(DomainCheckout|DomainOrder $payable, ?User $admin): array
    {
        if ($payable instanceof DomainCheckout) {
            $payable = $payable->fresh(['orders', 'user']);
            if ((string) $payable->whmcs_sync_status !== 'checkout_synced' && (string) $payable->whmcs_sync_status !== 'payment_synced') {
                $payable = WhmcsDomainOrderSync::syncCheckoutBundle($payable);
            }
            if ($payable->isPaid() && (string) $payable->whmcs_sync_status === 'checkout_synced') {
                $payable = WhmcsDomainOrderSync::syncPaymentBundle($payable->fresh(['orders', 'user']));
            }
        } else {
            if ((string) $payable->whmcs_sync_status !== 'checkout_synced' && (string) $payable->whmcs_sync_status !== 'payment_synced') {
                $payable = WhmcsDomainOrderSync::syncCheckout($payable);
            }
            if ($payable->isPaid() && (string) $payable->whmcs_sync_status === 'checkout_synced') {
                $payable = WhmcsDomainOrderSync::syncPayment($payable->fresh());
            }
        }

        $status = (string) $payable->whmcs_sync_status;
        $ok = in_array($status, ['checkout_synced', 'payment_synced'], true);

        self::log($payable, $admin, 'whmcs_retry', 'WHMCS sync retried: '.($status ?: 'no status').'.', [
            'status' => $status,
            'error' => $payable->whmcs_sync_error,
        ]);

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'WHMCS sync status: '.str_replace('_', ' ', $status).'.'
                : 'WHMCS sync failed: '.($payable->whmcs_sync_error ?: 'unknown error'),
        ];
    }

    public static function updateEppCode(DomainOrder $order, ?User $admin, string $eppCode): void
    {
        $order->update(['epp_code' => trim($eppCode)]);
        self::log($order, $admin, 'epp_updated', 'Transfer (EPP) code updated.');
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function log(Model $order, ?User $admin, string $action, string $summary, array $meta = []): void
    {
        try {
            AdminOrderEvent::query()->create([
                'orderable_type' => $order->getMorphClass(),
                'orderable_id' => $order->getKey(),
                'admin_user_id' => $admin?->id,
                'action' => $action,
                'summary' => $summary,
                'meta' => $meta === [] ? null : $meta,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Domain orders bought together are paid through their checkout bundle.
     */
    protected static function paymentTarget(SiteCheckout|DomainCheckout|DomainOrder $payable): SiteCheckout|DomainCheckout|DomainOrder
    {
        if ($payable instanceof DomainOrder && $payable->domain_checkout_id) {
            return DomainCheckout::query()->find($payable->domain_checkout_id) ?? $payable;
        }

        return $payable;
    }

    /**
     * @param  array<string, mixed>  $verified
     * @return array{ok:bool,message:string}
     */
    protected static function confirm(SiteCheckout|DomainCheckout|DomainOrder $payable, array $verified): array
    {
        return match (true) {
            $payable instanceof SiteCheckout => FlutterwavePayment::confirmSiteCheckoutPayment($payable->fresh(['items', 'user']), $verified),
            $payable instanceof DomainCheckout => FlutterwavePayment::confirmDomainCheckoutPayment($payable->fresh(['orders', 'user']), $verified),
            default => FlutterwavePayment::confirmDomainOrderPayment($payable->fresh(), $verified),
        };
    }
}
