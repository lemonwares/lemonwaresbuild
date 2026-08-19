<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use App\Models\WhmcsProductMapping;

class WhmcsSettings
{
    public static function baseUrl(): string
    {
        return rtrim((string) IntegrationSetting::getValue('whmcs.base_url', (string) config('site.whmcs.base_url')), '/');
    }

    public static function clientLoginUrl(): string
    {
        return (string) IntegrationSetting::getValue('whmcs.client_login_url', (string) config('site.whmcs.client_login_url'));
    }

    public static function orderRoute(): string
    {
        return (string) IntegrationSetting::getValue('whmcs.order_route', (string) config('site.whmcs.order_route', '/cart.php'));
    }

    public static function apiIdentifier(): string
    {
        return (string) IntegrationSetting::getValue('whmcs.api_identifier', (string) config('site.whmcs.api_identifier'));
    }

    public static function apiSecret(): string
    {
        return (string) IntegrationSetting::getValue('whmcs.api_secret', (string) config('site.whmcs.api_secret'));
    }

    public static function apiAccessKey(): string
    {
        return (string) IntegrationSetting::getValue('whmcs.api_access_key', (string) config('site.whmcs.api_access_key', ''));
    }

    public static function paymentMethod(): string
    {
        return strtolower(trim((string) IntegrationSetting::getValue(
            'whmcs.payment_method',
            (string) config('site.whmcs.payment_method', ''),
        )));
    }

    public static function deferPaymentRedirect(): bool
    {
        $stored = IntegrationSetting::getValue('whmcs.defer_payment_redirect', null);
        if ($stored !== null && $stored !== '') {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        $configured = config('site.whmcs.defer_payment_redirect');
        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        return app()->environment('local');
    }

    public static function resolvePid(string $planSlug, string $specKey): ?int
    {
        $mapping = WhmcsProductMapping::query()
            ->where('plan_slug', strtolower($planSlug))
            ->where('spec_key', strtolower($specKey))
            ->where('is_active', true)
            ->first();

        if ($mapping) {
            return (int) $mapping->whmcs_pid;
        }

        $envFallback = match (strtolower($planSlug)) {
            'cpanel' => config('site.hosting_plans.cpanel.whmcs_pid'),
            'plesk' => config('site.hosting_plans.plesk.whmcs_pid'),
            'vps' => config('site.hosting_plans.vps.whmcs_pid'),
            default => null,
        };

        $pid = (int) ($envFallback ?: 0);

        return $pid > 0 ? $pid : null;
    }
}
