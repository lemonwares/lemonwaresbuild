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
                    'currency' => strtoupper((string) ($spec['default_currency'] ?? 'NGN')),
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
                    continue;
                }

                self::migrateLegacyUsdAmount($price, $defaults);
            }
        }
    }

    /**
     * Older rows stored small USD amounts. Convert once to NGN defaults/admin scale.
     */
    protected static function migrateLegacyUsdAmount(HostingPlanPrice $price, array $defaults): void
    {
        $currency = strtoupper((string) $price->currency);
        $amount = (float) $price->price_amount;

        if ($currency === 'NGN' && $amount > 0) {
            $defaultAmount = (float) ($defaults['price_amount'] ?? 0);
            // Catch leftover micro-amounts from an earlier USD→NGN conversion.
            if ($amount < 10000 && $defaultAmount >= 10000) {
                $price->update($defaults);
            }

            return;
        }

        if ($currency === 'USD' && $amount > 0) {
            // Prefer the new NGN catalog defaults for legacy USD rows.
            if ((float) ($defaults['price_amount'] ?? 0) > 0 && strtoupper((string) ($defaults['currency'] ?? 'NGN')) === 'NGN') {
                $price->update($defaults);

                return;
            }

            if ($amount < 500) {
                $price->update([
                    'price_amount' => round($amount * HostingPricing::usdToNgnRate(), 0),
                    'currency' => 'NGN',
                ]);

                return;
            }

            $price->update(['currency' => 'NGN']);

            return;
        }

        // Missing/zero amount: seed from config defaults (NGN).
        if ($amount <= 0 && (float) ($defaults['price_amount'] ?? 0) > 0) {
            $price->update($defaults);
        }
    }
}
