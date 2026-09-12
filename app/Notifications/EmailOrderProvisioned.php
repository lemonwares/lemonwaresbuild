<?php

namespace App\Notifications;

use App\Models\EmailOrder;

class EmailOrderProvisioned extends AccountNotification
{
    public function __construct(public EmailOrder $order) {}

    protected function payload(): array
    {
        return [
            'title' => __('account.notif_email_provisioned_title'),
            'body' => __('account.notif_email_provisioned_body', [
                'domain' => $this->order->domain,
            ]),
            'url' => route('account.email.show', $this->order),
            'product' => 'email',
        ];
    }
}
