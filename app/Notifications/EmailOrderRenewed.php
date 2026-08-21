<?php

namespace App\Notifications;

use App\Models\EmailOrder;

class EmailOrderRenewed extends AccountNotification
{
    public function __construct(public EmailOrder $order) {}

    protected function payload(): array
    {
        return [
            'title' => __('account.notif_email_renewed_title'),
            'body' => __('account.notif_email_renewed_body', [
                'domain' => $this->order->domain,
                'date' => $this->order->period_ends_at?->format('d M Y') ?? '',
            ]),
            'url' => route('account.email.show', $this->order),
            'product' => 'email',
        ];
    }
}
