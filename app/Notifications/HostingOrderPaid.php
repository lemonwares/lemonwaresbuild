<?php

namespace App\Notifications;

use App\Models\HostingLead;

class HostingOrderPaid extends AccountNotification
{
    public function __construct(public HostingLead $lead) {}

    protected function payload(): array
    {
        $isVps = $this->lead->isVps();

        return [
            'title' => $isVps
                ? __('account.notif_hosting_paid_vps_title')
                : __('account.notif_hosting_paid_shared_title'),
            'body' => $isVps
                ? __('account.notif_hosting_paid_vps_body', [
                    'plan' => $this->lead->plan_name ?: 'VPS',
                ])
                : __('account.notif_hosting_paid_shared_body', [
                    'plan' => $this->lead->plan_name ?: 'Hosting',
                ]),
            'url' => $isVps
                ? route('account.vps.show', $this->lead)
                : route('account.hosting.show', $this->lead),
            'product' => $isVps ? 'vps' : 'hosting',
            'action' => __('account.notifications_open'),
        ];
    }
}
