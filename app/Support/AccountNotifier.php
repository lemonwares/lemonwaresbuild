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

        $user->notify($notification);
    }
}
