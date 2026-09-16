<?php

namespace App\Notifications;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;

class DomainOrderPaid extends AccountNotification
{
    public function __construct(
        public DomainOrder|DomainCheckout $order,
    ) {}

    protected function payload(): array
    {
        if ($this->order instanceof DomainCheckout) {
            $count = (int) ($this->order->item_count ?: $this->order->orders()->count());

            return [
                'title' => __('account.notif_domain_paid_title'),
                'body' => __('account.notif_domain_cart_paid_body', ['count' => $count]),
                'url' => route('domain.checkout-received', $this->order),
                'product' => 'domain',
                'action' => __('account.notifications_open'),
            ];
        }

        return [
            'title' => __('account.notif_domain_paid_title'),
            'body' => __('account.notif_domain_paid_body', [
                'domain' => $this->order->domain,
                'option' => $this->order->optionLabel(),
            ]),
            'url' => $this->order->domain_checkout_id
                ? route('domain.checkout-received', $this->order->domain_checkout_id)
                : route('domain.order-received', $this->order),
            'product' => 'domain',
            'action' => __('account.notifications_open'),
        ];
    }
}
