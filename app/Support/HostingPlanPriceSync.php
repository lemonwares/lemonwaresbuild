<?php

namespace App\Support;

use App\Models\HostingPlanPrice;

class HostingPlanPriceSync
{
    public static function sync(bool $forceDefaults = false): void
    {
        $plans = config('site.hosting_plans', []);

        foreach ($plans as $planSlug => $plan) {
            foreach (($plan['specifications'] ?? []) as $spec) {
                $specKey = (string) ($spec['key'] ?? '');
                if ($specKey === '') {
                    continue;
                }

                $defaults = [
                    'price_amount' => (float) ($spec['default_price'] ?? 0),
                    'currency' => (string) ($spec['default_currency'] ?? 'USD'),
                    'billing_cycle' => (string) ($spec['default_billing_cycle'] ?? 'monthly'),
                    'display_suffix' => (string) ($spec['default_suffix'] ?? '/mo'),
                    'is_visible' => true,
                ];

                $price = HostingPlanPrice::query()->firstOrCreate(
                    [
                        'plan_slug' => $planSlug,
                        'spec_key' => $specKey,
                    ],
                    $defaults
                );

                if ($forceDefaults) {
                    $price->update($defaults);
                }
            }
        }
    }
}
