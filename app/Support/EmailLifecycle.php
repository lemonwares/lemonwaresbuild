<?php

namespace App\Support;

use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Notifications\EmailOrderDeactivated;
use App\Notifications\EmailOrderExpired;
use Illuminate\Support\Facades\Log;

class EmailLifecycle
{
    public static function deactivate(EmailOrder $order, string $reason = 'admin'): EmailOrder
    {
        if (! in_array($reason, EmailOrder::DEACTIVATION_REASONS, true)) {
            $reason = 'admin';
        }

        if ($order->isDeactivated()) {
            return $order->fresh(['mailboxes']) ?? $order;
        }

        $order->loadMissing('mailboxes');

        foreach ($order->mailboxes as $mailbox) {
            self::pauseMailbox($order, $mailbox);
        }

        $order->update([
            'status' => $reason === 'expired' ? 'expired' : 'deactivated',
            'deactivated_at' => now(),
            'deactivated_reason' => $reason,
            'provision_error' => $reason === 'expired'
                ? 'Service period ended. Mailboxes were deactivated.'
                : 'Deactivated by admin.',
        ]);

        $order = $order->fresh(['mailboxes', 'user']) ?? $order;
        AccountNotifier::send(
            $order->user,
            $reason === 'expired' ? new EmailOrderExpired($order) : new EmailOrderDeactivated($order),
        );

        return $order;
    }

    public static function reactivate(EmailOrder $order, bool $force = false): EmailOrder
    {
        abort_unless($force || $order->canBeReactivated(), 422);

        $order->loadMissing('mailboxes');

        foreach ($order->mailboxes as $mailbox) {
            self::resumeMailbox($order, $mailbox);
        }

        $hasReadyMailbox = $order->mailboxes->contains(
            fn ($mailbox) => in_array($mailbox->status, ['created', 'invited', 'deactivated'], true)
                && filled($mailbox->trekmail_mailbox_id)
        );

        $order->update([
            'status' => ($order->trekmail_domain_id || $order->fulfilment_status === 'completed' || $hasReadyMailbox)
                ? 'provisioned'
                : 'paid_pending_setup',
            'deactivated_at' => null,
            'deactivated_reason' => null,
            'provision_error' => null,
        ]);

        return $order->fresh(['mailboxes']) ?? $order;
    }

    public static function expireDueOrders(): int
    {
        $count = 0;

        EmailOrder::query()
            ->whereNull('deactivated_at')
            ->whereNotNull('period_ends_at')
            ->where('period_ends_at', '<=', now())
            ->whereIn('status', ['paid', 'provisioned', 'paid_pending_setup', 'awaiting_manual_fulfilment'])
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$count): void {
                foreach ($orders as $order) {
                    self::deactivate($order, 'expired');
                    $count++;
                }
            });

        return $count;
    }

    protected static function pauseMailbox(EmailOrder $order, EmailMailbox $mailbox): void
    {
        if (! $mailbox->trekmail_mailbox_id || ! TrekMailClient::isConfigured()) {
            $mailbox->update([
                'status' => 'deactivated',
                'error_message' => null,
            ]);

            return;
        }

        try {
            TrekMailClient::pauseMailbox(
                $mailbox->trekmail_mailbox_id,
                'email-order-' . $order->id . '-pause-' . $mailbox->id,
            );

            $mailbox->update([
                'status' => 'deactivated',
                'error_message' => null,
            ]);
        } catch (TrekMailException $exception) {
            Log::warning('TrekMail mailbox pause failed', [
                'order_id' => $order->id,
                'mailbox_id' => $mailbox->id,
                'error' => $exception->getMessage(),
            ]);

            $mailbox->update([
                'status' => 'deactivated',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }

    protected static function resumeMailbox(EmailOrder $order, EmailMailbox $mailbox): void
    {
        if (! $mailbox->trekmail_mailbox_id || ! TrekMailClient::isConfigured()) {
            $mailbox->update([
                'status' => $mailbox->trekmail_invite_id ? 'invited' : 'created',
                'error_message' => null,
            ]);

            return;
        }

        try {
            TrekMailClient::resumeMailbox(
                $mailbox->trekmail_mailbox_id,
                'email-order-' . $order->id . '-resume-' . $mailbox->id,
            );

            $mailbox->update([
                'status' => $mailbox->trekmail_invite_id ? 'invited' : 'created',
                'error_message' => null,
            ]);
        } catch (TrekMailException $exception) {
            Log::warning('TrekMail mailbox resume failed', [
                'order_id' => $order->id,
                'mailbox_id' => $mailbox->id,
                'error' => $exception->getMessage(),
            ]);

            $mailbox->update([
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
