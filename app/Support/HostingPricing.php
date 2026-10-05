<?php

namespace App\Support;

class HostingPricing
{
    public static function usdToNgnRate(): float
    {
        return ExchangeRate::usdToNgn();
    }

    public static function billingCycles(): array
    {
        return Catalog::cycles();
    }

    public static function cycle(string $key): ?array
    {
        return self::billingCycles()[$key] ?? null;
    }

    /**
     * Monthly hosting price in NGN, as set in the admin catalog.
     */
    public static function monthlyNgnForSpec(string $planSlug, string $specKey): float
    {
        return (float) (Catalog::plan(strtolower($planSlug), $specKey)?->price_ngn ?? 0);
    }

    /**
     * Total for one billing period of a plan, honouring the plan's own cycle prices and discounts.
     */
    public static function periodNgnForSpec(string $planSlug, string $specKey, string $cycleKey): float
    {
        $plan = Catalog::plan(strtolower($planSlug), $specKey);

        return $plan ? Catalog::periodNgn((float) $plan->price_ngn, $cycleKey, $plan) : 0.0;
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
        return Catalog::periodNgn($monthlyNgn, $cycleKey);
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
}
