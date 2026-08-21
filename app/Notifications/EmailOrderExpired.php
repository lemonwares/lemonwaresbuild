<?php

namespace App\Notifications;

use App\Models\EmailOrder;

class EmailOrderExpired extends AccountNotification
{
    public function __construct(public EmailOrder $order) {}

    protected function payload(): array
    {
        return [
            'title' => __('account.notif_email_expired_title'),
            'body' => __('account.notif_email_expired_body', [
                'domain' => $this->order->domain,
            ]),
            'url' => route('account.email.show', $this->order),
            'product' => 'email',
        ];
    }
}
