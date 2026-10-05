<?php

namespace App\Notifications;

use App\Models\EmailOrder;

class EmailRenewalReminder extends AccountNotification
{
    public function __construct(public EmailOrder $order, public int $days) {}

    protected function payload(): array
    {
        return [
            'title' => trans_choice('account.notif_email_renewal_title', $this->days, ['days' => $this->days]),
            'body' => __('account.notif_email_renewal_body', [
                'product' => __('email.providers.'.($this->order->provider ?: 'lemonmail')),
                'domain' => $this->order->domain,
                'date' => $this->order->period_ends_at?->toFormattedDateString(),
            ]),
            'url' => route('account.email.show', $this->order),
            'product' => 'email',
        ];
    }
}
