<?php

namespace App\Support;

use App\Models\EmailOrder;
use App\Notifications\EmailSetupNeeded;
use Illuminate\Support\Facades\Notification;

/**
 * Business email (Titan, Google Workspace, Microsoft 365) is set up by staff by hand,
 * so a paid order only needs to sit in the admin queue and alert the team inbox.
 */
class EmailFulfilment
{
    public static function queued(EmailOrder $order): void
    {
        if ($order->fulfilment_status === null) {
            $order->forceFill(['fulfilment_status' => 'queued', 'fulfilment_updated_at' => now()])->save();
        }

        self::alertTeam(new EmailSetupNeeded($order));
    }

    /**
     * The partner account keeps running until staff suspend it in the provider portal.
     */
    public static function expired(EmailOrder $order): void
    {
        self::alertTeam(new EmailSetupNeeded($order, expired: true));
    }

    private static function alertTeam(EmailSetupNeeded $notification): void
    {
        $inbox = trim(ContactFormSettings::inboxAddress());
        if ($inbox === '') {
            return;
        }

        try {
            Notification::route('mail', $inbox)->notify($notification->afterCommit());
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
