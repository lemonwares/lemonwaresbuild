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

    public static function formatMoney(float $amount, string $currency = 'USD'): string
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

    public static function monthlySuffix(): string
    {
        return '/mo';
    }

    public static function pricePayload(HostingPlanPrice $price, string $cycleKey = 'monthly'): array
    {
        $monthly = (float) $price->price_amount;
        $cycle = self::cycle($cycleKey) ?? self::cycle('monthly');
        $total = self::periodTotalUsd($monthly, $cycleKey);
        $months = (int) ($cycle['months'] ?? 1);
        $discount = (int) ($cycle['discount_percent'] ?? 0);

        return [
            'monthly_usd' => $monthly,
            'period_usd' => $total,
            'period_ngn' => $total * self::usdToNgnRate(),
            'months' => $months,
            'discount_percent' => $discount,
            'cycle' => $cycleKey,
            'price_display' => self::dualPriceDisplay($monthly, self::monthlySuffix()),
            'period_display' => self::dualPriceDisplay($total),
            'billing_cycle_label' => self::cycleLabel($cycleKey),
        ];
    }
}
