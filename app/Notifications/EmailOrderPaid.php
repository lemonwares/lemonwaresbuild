<?php

namespace App\Notifications;

use App\Models\EmailOrder;

class EmailOrderPaid extends AccountNotification
{
    public function __construct(public EmailOrder $order) {}

    protected function payload(): array
    {
        return [
            'title' => __('account.notif_email_paid_title'),
            'body' => __('account.notif_email_paid_body', [
                'domain' => $this->order->domain,
                'plan' => $this->order->plan_name,
            ]),
            'url' => route('account.email.show', $this->order),
            'product' => 'email',
        ];
    }
}
