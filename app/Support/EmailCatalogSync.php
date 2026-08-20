<?php

namespace App\Support;

use App\Models\EmailBillingCycle;
use App\Models\EmailPlan;

class EmailCatalogSync
{
    public static function sync(bool $forceDefaults = false): void
    {
        foreach (config('email.plans', []) as $index => $plan) {
            $key = (string) ($plan['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $defaults = [
                'provider' => (string) ($plan['provider'] ?? 'lemonmail'),
                'fulfilment_mode' => (string) ($plan['fulfilment_mode'] ?? 'auto'),
                'mailbox_count' => (int) ($plan['mailboxes'] ?? 1),
                'monthly_usd' => (float) ($plan['monthly_usd'] ?? 0),
                'featured' => (bool) ($plan['featured'] ?? false),
                'is_visible' => true,
                'sort_order' => $index + 1,
            ];

            $row = EmailPlan::query()->firstOrCreate(['plan_key' => $key], $defaults);

            if ($forceDefaults) {
                $row->update($defaults);
            }
        }

        foreach (config('email.billing_cycles', []) as $index => $cycle) {
            $key = (string) ($cycle['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $defaults = [
                'months' => (int) ($cycle['months'] ?? 1),
                'discount_percent' => (int) ($cycle['discount_percent'] ?? 0),
                'is_visible' => true,
                'sort_order' => $index + 1,
            ];

            $row = EmailBillingCycle::query()->firstOrCreate(['cycle_key' => $key], $defaults);

            if ($forceDefaults) {
                $row->update($defaults);
            }
        }
    }
}
