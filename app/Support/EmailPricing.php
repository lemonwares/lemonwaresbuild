<?php

namespace App\Support;

use App\Models\EmailBillingCycle;
use App\Models\EmailPlan;

class EmailPricing
{
    public static function normalizePlanKey(string $key): string
    {
        return $key === 'starter' ? 'solo' : $key;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function plans(): array
    {
        EmailCatalogSync::sync();

        return EmailPlan::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('plan_key')
            ->get()
            ->map(fn (EmailPlan $plan) => [
                'key' => $plan->plan_key,
                'provider' => $plan->provider,
                'fulfilment_mode' => $plan->fulfilment_mode,
                'mailboxes' => (int) $plan->mailbox_count,
                'monthly_usd' => (float) $plan->monthly_usd,
                'featured' => (bool) $plan->featured,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function plan(string $key): ?array
    {
        EmailCatalogSync::sync();

        $key = self::normalizePlanKey($key);

        $plan = EmailPlan::query()
            ->where('plan_key', $key)
            ->where('is_visible', true)
            ->first();

        if (! $plan) {
            return null;
        }

        return [
            'key' => $plan->plan_key,
            'provider' => $plan->provider,
            'fulfilment_mode' => $plan->fulfilment_mode,
            'mailboxes' => (int) $plan->mailbox_count,
            'monthly_usd' => (float) $plan->monthly_usd,
            'featured' => (bool) $plan->featured,
        ];
    }

    /**
     * @return list<string>
     */
    public static function billingCycles(): array
    {
        EmailCatalogSync::sync();

        return EmailBillingCycle::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('cycle_key')
            ->pluck('cycle_key')
            ->all();
    }

    public static function cycle(string $key): ?array
    {
        EmailCatalogSync::sync();

        $cycle = EmailBillingCycle::query()->where('cycle_key', $key)->first();

        if ($cycle) {
            return [
                'key' => $cycle->cycle_key,
                'months' => (int) $cycle->months,
                'discount_percent' => (int) $cycle->discount_percent,
            ];
        }

        foreach (config('email.billing_cycles', []) as $fallback) {
            if (($fallback['key'] ?? '') === $key) {
                return $fallback;
            }
        }

        return null;
    }

    public static function cycleLabel(string $key): string
    {
        return __('hosting.cycles.' . $key);
    }

    public static function periodTotalUsd(float $monthlyUsd, string $cycleKey): float
    {
        $cycle = self::cycle($cycleKey) ?? self::cycle('monthly');
        $months = (int) ($cycle['months'] ?? 1);
        $discount = (float) ($cycle['discount_percent'] ?? 0);
        $subtotal = $monthlyUsd * $months;

        return round($subtotal * (1 - ($discount / 100)), 2);
    }

    public static function perMailboxUsd(float $monthlyUsd, int $mailboxes, string $cycleKey): float
    {
        if ($mailboxes <= 0) {
            return 0;
        }

        $period = self::periodTotalUsd($monthlyUsd, $cycleKey);
        $cycle = self::cycle($cycleKey) ?? self::cycle('monthly');
        $months = max(1, (int) ($cycle['months'] ?? 1));

        return round($period / ($mailboxes * $months), 2);
    }

    /**
     * @return list<string>
     */
    public static function defaultLocalParts(int $count): array
    {
        $defaults = config('email.default_local_parts', ['hello']);

        return array_slice(array_values($defaults), 0, max(1, $count));
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    public static function presentPlan(array $plan, string $cycleKey = 'monthly'): array
    {
        $monthly = (float) $plan['monthly_usd'];
        $mailboxes = (int) $plan['mailboxes'];
        $total = self::periodTotalUsd($monthly, $cycleKey);
        $cycle = self::cycle($cycleKey) ?? self::cycle('monthly');
        $discount = (int) ($cycle['discount_percent'] ?? 0);

        return array_merge($plan, [
            'monthly_usd' => $monthly,
            'period_usd' => $total,
            'period_ngn' => $total * HostingPricing::usdToNgnRate(),
            'per_mailbox_usd' => self::perMailboxUsd($monthly, $mailboxes, $cycleKey),
            'price_display' => HostingPricing::dualPriceDisplay($monthly, HostingPricing::monthlySuffix()),
            'period_display' => HostingPricing::dualPriceDisplay($total),
            'per_mailbox_display' => HostingPricing::dualPriceDisplay(
                self::perMailboxUsd($monthly, $mailboxes, $cycleKey),
                HostingPricing::monthlySuffix(),
            ),
            'billing_cycle_label' => self::cycleLabel($cycleKey),
            'discount_percent' => $discount,
            'name' => __('email.plans.' . $plan['key'] . '.name'),
            'summary' => __('email.plans.' . $plan['key'] . '.summary'),
            'provider_label' => __('email.providers.' . ($plan['provider'] ?? 'lemonmail')),
            'is_manual' => ($plan['fulfilment_mode'] ?? 'auto') === 'manual',
        ]);
    }

    /**
     * @return list<array<string, string>>
     */
    public static function enterpriseProducts(): array
    {
        return collect(config('email.enterprise_products', []))
            ->map(fn (array $product) => [
                'key' => (string) $product['key'],
                'name' => (string) $product['name'],
                'summary' => __('email.enterprise.' . $product['key'] . '.summary'),
            ])
            ->all();
    }
}
