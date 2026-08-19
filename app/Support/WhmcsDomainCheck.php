<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class WhmcsDomainCheck
{
    /**
     * @return array{ok:bool,status:string,message:?string}
     */
    public static function validate(string $domain, string $domainOption): array
    {
        $domainOption = strtolower(trim($domainOption));

        if ($domainOption === 'owndomain') {
            return [
                'ok' => true,
                'status' => 'skipped',
                'message' => null,
            ];
        }

        if (! in_array($domainOption, ['register', 'transfer'], true)) {
            return [
                'ok' => false,
                'status' => 'invalid',
                'message' => __('hosting.domain_check_failed'),
            ];
        }

        if (! WhmcsClient::isConfigured()) {
            Log::warning('WHMCS domain check skipped because API credentials are missing.', [
                'domain' => $domain,
                'domain_option' => $domainOption,
            ]);

            return [
                'ok' => false,
                'status' => 'unconfigured',
                'message' => __('hosting.domain_check_unavailable'),
            ];
        }

        $whois = WhmcsClient::domainWhois($domain);
        if (! $whois) {
            return [
                'ok' => false,
                'status' => 'unknown',
                'message' => __('hosting.domain_check_failed'),
            ];
        }

        $status = strtolower((string) ($whois['status'] ?? ''));

        if ($domainOption === 'register') {
            if ($status === 'available') {
                return [
                    'ok' => true,
                    'status' => 'available',
                    'message' => __('hosting.domain_available'),
                ];
            }

            if ($status === 'unavailable') {
                return [
                    'ok' => false,
                    'status' => 'unavailable',
                    'message' => __('hosting.domain_taken'),
                ];
            }

            return [
                'ok' => false,
                'status' => 'unknown',
                'message' => __('hosting.domain_check_failed'),
            ];
        }

        if ($status === 'unavailable') {
            return [
                'ok' => true,
                'status' => 'unavailable',
                'message' => __('hosting.domain_transfer_ok'),
            ];
        }

        if ($status === 'available') {
            return [
                'ok' => false,
                'status' => 'available',
                'message' => __('hosting.domain_not_registered'),
            ];
        }

        return [
            'ok' => false,
            'status' => 'unknown',
            'message' => __('hosting.domain_check_failed'),
        ];
    }
}
