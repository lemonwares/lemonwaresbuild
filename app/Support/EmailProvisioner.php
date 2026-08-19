<?php

namespace App\Support;

use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use Illuminate\Support\Facades\Log;

class EmailProvisioner
{
    public static function provision(EmailOrder $order): EmailOrder
    {
        $order->loadMissing('mailboxes', 'user');

        if ($order->status === 'provisioned' && $order->trekmail_domain_id) {
            return $order;
        }

        if (! TrekMailClient::isConfigured()) {
            $order->update([
                'status' => 'paid_pending_setup',
                'provision_error' => 'TrekMail API is not configured yet.',
            ]);

            return $order->fresh(['mailboxes']);
        }

        try {
            $domain = TrekMailClient::createDomain(
                $order->domain,
                'email-order-' . $order->id . '-domain',
            );

            $domainId = $domain['id'] ?? null;
            if (! $domainId) {
                throw new TrekMailException('TrekMail did not return a domain id.');
            }

            $dns = TrekMailClient::dnsRequirements($domainId);

            try {
                TrekMailClient::recheckDns($domainId, 'email-order-' . $order->id . '-dns');
            } catch (TrekMailException) {
                // DNS may still be pending at the registrar; records are enough for the customer.
            }

            $notifyEmail = (string) ($order->user?->email ?? '');

            foreach ($order->mailboxes as $mailbox) {
                self::provisionMailbox($order, $mailbox, $domainId, $notifyEmail);
            }

            $failed = $order->mailboxes()->where('status', 'failed')->exists();

            $order->update([
                'trekmail_domain_id' => $domainId,
                'dns_records' => $dns,
                'provision_error' => $failed ? 'Some mailboxes could not be created automatically.' : null,
                'status' => $failed ? 'paid_pending_setup' : 'provisioned',
                'provisioned_at' => $failed ? null : now(),
            ]);
        } catch (TrekMailException $exception) {
            Log::warning('Email provisioning failed', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
                'payload' => $exception->payload,
            ]);

            $order->update([
                'status' => 'paid_pending_setup',
                'provision_error' => $exception->getMessage(),
            ]);
        }

        return $order->fresh(['mailboxes']);
    }

    protected static function provisionMailbox(
        EmailOrder $order,
        EmailMailbox $mailbox,
        int|string $domainId,
        string $notifyEmail,
    ): void {
        if (in_array($mailbox->status, ['created', 'invited'], true) && $mailbox->trekmail_mailbox_id) {
            return;
        }

        $key = 'email-order-' . $order->id . '-mailbox-' . $mailbox->local_part;

        try {
            if ($notifyEmail !== '') {
                try {
                    $invite = TrekMailClient::inviteMailbox(
                        $domainId,
                        $mailbox->local_part,
                        $notifyEmail,
                        $key . '-invite',
                    );

                    $mailbox->update([
                        'status' => 'invited',
                        'trekmail_invite_id' => $invite['id'] ?? null,
                        'trekmail_mailbox_id' => $invite['mailbox_id'] ?? $invite['id'] ?? null,
                        'error_message' => null,
                    ]);

                    return;
                } catch (TrekMailException $inviteException) {
                    Log::info('TrekMail invite fallback to mailbox create', [
                        'order_id' => $order->id,
                        'local_part' => $mailbox->local_part,
                        'error' => $inviteException->getMessage(),
                    ]);
                }
            }

            $created = TrekMailClient::createMailbox(
                $domainId,
                $mailbox->local_part,
                $key . '-create',
            );

            $mailbox->update([
                'status' => 'created',
                'trekmail_mailbox_id' => $created['id'] ?? null,
                'error_message' => null,
            ]);
        } catch (TrekMailException $exception) {
            $mailbox->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
