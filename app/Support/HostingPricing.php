<?php

namespace App\Support;

use App\Models\HostingPlanPrice;

class HostingPricing
{
    public static function usdToNgnRate(): float
    {
        return ExchangeRate::usdToNgn();
    }

    public static function billingCycles(): array
    {
        return config('site.billing_cycles', []);
    }

    public static function cycle(string $key): ?array
    {
        return self::billingCycles()[$key] ?? null;
    }

    /**
     * Monthly hosting price in NGN (admin amount, or config default).
     */
    public static function monthlyNgnForSpec(string $planSlug, string $specKey): float
    {
        $price = HostingPlanPrice::query()
            ->where('plan_slug', strtolower($planSlug))
            ->where('spec_key', strtolower($specKey))
            ->first();

        if ($price && (float) $price->price_amount > 0) {
            return self::amountAsNgn((float) $price->price_amount, (string) $price->currency);
        }

        $plans = config('site.hosting_plans', []);
        $plan = is_array($plans[$planSlug] ?? null) ? $plans[$planSlug] : null;
        $specs = collect($plan['specifications'] ?? $plan['specs'] ?? []);
        $spec = $specs->first(fn ($row) => strtolower((string) ($row['key'] ?? '')) === strtolower($specKey));

        if (! is_array($spec)) {
            return 0.0;
        }

        return self::amountAsNgn(
            (float) ($spec['default_price'] ?? 0),
            (string) ($spec['default_currency'] ?? 'NGN'),
        );
    }

    /**
     * @deprecated Use monthlyNgnForSpec — amounts are NGN.
     */
    public static function monthlyUsdForSpec(string $planSlug, string $specKey): float
    {
        $ngn = self::monthlyNgnForSpec($planSlug, $specKey);
        $rate = max(1.0, self::usdToNgnRate());

        return round($ngn / $rate, 2);
    }

    public static function amountAsNgn(float $amount, string $currency = 'NGN'): float
    {
        $currency = strtoupper(trim($currency));

        if ($currency === '' || $currency === 'NGN') {
            return round($amount, 2);
        }

        if ($currency === 'USD') {
            return round($amount * self::usdToNgnRate(), 2);
        }

        return round($amount, 2);
    }

    public static function cycleLabel(string $key): string
    {
        return __('hosting.cycles.' . $key);
    }

    public static function periodTotalNgn(float $monthlyNgn, string $cycleKey): float
    {
        $cycle = self::cycle($cycleKey) ?? self::cycle('monthly');
        $months = (int) ($cycle['months'] ?? 1);
        $discount = (float) ($cycle['discount_percent'] ?? 0);
        $subtotal = $monthlyNgn * $months;

        return round($subtotal * (1 - ($discount / 100)), 2);
    }

    /**
     * @deprecated Use periodTotalNgn — amounts are NGN.
     */
    public static function periodTotalUsd(float $monthlyAmount, string $cycleKey): float
    {
        return self::periodTotalNgn($monthlyAmount, $cycleKey);
    }

    public static function formatMoney(float $amount, string $currency = 'NGN'): string
    {
        $currency = strtoupper($currency);

        if ($currency === 'USD') {
            return '$' . number_format($amount, $amount >= 100 ? 0 : 2);
        }

        if ($currency === 'NGN') {
            return '₦' . number_format($amount, 0);
        }

        return $currency . ' ' . number_format($amount, 2);
    }

    public static function dualPriceDisplay(float $usdAmount, ?string $suffix = null): string
    {
        $ngn = $usdAmount * self::usdToNgnRate();
        $suffixText = $suffix ? ' ' . $suffix : '';

        return self::formatMoney($usdAmount, 'USD') . $suffixText . ' / ' . self::formatMoney($ngn, 'NGN') . $suffixText;
    }

    public static function ngnPriceDisplay(float $ngnAmount, ?string $suffix = null): string
    {
        $suffixText = $suffix ? ' ' . $suffix : '';

        return self::formatMoney($ngnAmount, 'NGN') . $suffixText;
    }

    public static function monthlySuffix(): string
    {
        return '/mo';
    }

    public static function pricePayload(HostingPlanPrice $price, string $cycleKey = 'monthly'): array
    {
        $monthlyNgn = self::amountAsNgn((float) $price->price_amount, (string) $price->currency);
        $cycle = self::cycle($cycleKey) ?? self::cycle('monthly');
        $totalNgn = self::periodTotalNgn($monthlyNgn, $cycleKey);
        $months = (int) ($cycle['months'] ?? 1);
        $discount = (int) ($cycle['discount_percent'] ?? 0);
        $rate = max(1.0, self::usdToNgnRate());

        return [
            'monthly_ngn' => $monthlyNgn,
            'monthly_usd' => round($monthlyNgn / $rate, 2),
            'period_ngn' => $totalNgn,
            'period_usd' => round($totalNgn / $rate, 2),
            'months' => $months,
            'discount_percent' => $discount,
            'cycle' => $cycleKey,
            'price_display' => self::ngnPriceDisplay($monthlyNgn, self::monthlySuffix()),
            'period_display' => self::ngnPriceDisplay($totalNgn),
            'billing_cycle_label' => self::cycleLabel($cycleKey),
        ];
    }
}
