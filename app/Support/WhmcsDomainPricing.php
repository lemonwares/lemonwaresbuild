<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhmcsDomainPricing
{
    /**
     * @return array{
     *     ok: bool,
     *     domain?: string,
     *     domain_option?: string,
     *     label?: string,
     *     amount_usd?: float,
     *     amount_ngn?: float,
     *     display?: string,
     *     period_label?: string|null,
     *     message?: string|null
     * }
     */
    public static function quote(string $domain, string $domainOption): array
    {
        $domainOption = strtolower(trim($domainOption));
        $normalized = DomainName::normalize($domain);

        if ($normalized === null) {
            return [
                'ok' => false,
                'message' => __('hosting.domain_invalid'),
            ];
        }

        if ($domainOption === 'owndomain') {
            return self::includedQuote($normalized, $domainOption);
        }

        if (! in_array($domainOption, ['register', 'transfer'], true)) {
            return [
                'ok' => false,
                'message' => __('hosting.domain_check_failed'),
            ];
        }

        if (! WhmcsClient::isConfigured()) {
            return [
                'ok' => false,
                'message' => __('hosting.domain_check_unconfigured'),
            ];
        }

        $parts = DomainName::split($normalized);
        if ($parts === null) {
            return [
                'ok' => false,
                'message' => __('hosting.domain_invalid'),
            ];
        }

        $tldKey = ltrim($parts['tld'], '.');
        $catalog = self::catalog();

        if ($catalog === null) {
            return [
                'ok' => false,
                'message' => WhmcsClient::lastError() ?: __('hosting.domain_quote_unavailable'),
            ];
        }

        $tldPricing = $catalog['pricing'][$tldKey] ?? null;
        if (! is_array($tldPricing)) {
            return [
                'ok' => false,
                'message' => __('hosting.domain_quote_tld_unavailable', ['tld' => $parts['tld']]),
            ];
        }

        $priceType = $domainOption === 'transfer' ? 'transfer' : 'register';
        $rawAmount = (float) ($tldPricing[$priceType]['1'] ?? 0);

        if ($rawAmount <= 0) {
            return [
                'ok' => false,
                'message' => __('hosting.domain_quote_tld_unavailable', ['tld' => $parts['tld']]),
            ];
        }

        $amounts = self::normalizeAmount($rawAmount, (string) ($catalog['currency']['code'] ?? 'USD'));
        $labelKey = $domainOption === 'transfer'
            ? 'hosting.order_summary_domain_transfer'
            : 'hosting.order_summary_domain_register';

        return [
            'ok' => true,
            'domain' => $normalized,
            'domain_option' => $domainOption,
            'label' => __($labelKey, ['domain' => $normalized]),
            'amount_usd' => $amounts['amount_usd'],
            'amount_ngn' => $amounts['amount_ngn'],
            'display' => HostingPricing::dualPriceDisplay($amounts['amount_usd']),
            'period_label' => $domainOption === 'transfer'
                ? __('hosting.order_summary_transfer_period')
                : __('hosting.order_summary_register_period'),
        ];
    }

    /**
     * @return array{currency: array<string, mixed>, pricing: array<string, array<string, mixed>>}|null
     */
    public static function catalog(): ?array
    {
        return Cache::remember('whmcs.tld_pricing', now()->addHours(6), function () {
            $response = WhmcsClient::getTldPricing();

            if (! $response || ($response['result'] ?? null) !== 'success') {
                Log::warning('WHMCS GetTLDPricing failed', [
                    'error' => WhmcsClient::lastError(),
                    'response' => $response,
                ]);

                return null;
            }

            $pricing = $response['pricing'] ?? [];
            if (! is_array($pricing)) {
                return null;
            }

            return [
                'currency' => is_array($response['currency'] ?? null) ? $response['currency'] : [],
                'pricing' => $pricing,
            ];
        });
    }

    /**
     * @return array{amount_usd: float, amount_ngn: float}
     */
    protected static function normalizeAmount(float $amount, string $currencyCode): array
    {
        $currencyCode = strtoupper(trim($currencyCode));
        $rate = max(1.0, HostingPricing::usdToNgnRate());

        if ($currencyCode === 'NGN') {
            return [
                'amount_usd' => round($amount / $rate, 2),
                'amount_ngn' => round($amount, 2),
            ];
        }

        return [
            'amount_usd' => round($amount, 2),
            'amount_ngn' => round($amount * $rate, 2),
        ];
    }

    /**
     * @return array{
     *     ok: true,
     *     domain: string,
     *     domain_option: string,
     *     label: string,
     *     amount_usd: float,
     *     amount_ngn: float,
     *     display: string,
     *     period_label: null
     * }
     */
    protected static function includedQuote(string $domain, string $domainOption): array
    {
        return [
            'ok' => true,
            'domain' => $domain,
            'domain_option' => $domainOption,
            'label' => __('hosting.order_summary_domain_existing', ['domain' => $domain]),
            'amount_usd' => 0.0,
            'amount_ngn' => 0.0,
            'display' => __('hosting.order_summary_included'),
            'period_label' => null,
        ];
    }
}
