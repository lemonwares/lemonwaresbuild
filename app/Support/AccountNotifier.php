<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;

class AccountNotifier
{
    public static function send(?User $user, Notification $notification): void
    {
        if (! $user) {
            return;
        }

        if (! $user->wantsInAppNotifications() && ! $user->wantsEmailNotifications()) {
            return;
        }

        try {
            // Queued notifications render later, outside this request's locale.
            $notification->locale ??= app()->getLocale();
            if (method_exists($notification, 'afterCommit')) {
                $notification->afterCommit();
            }
            $user->notify($notification);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
