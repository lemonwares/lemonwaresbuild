<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class WhmcsDomainCheck
{
    /**
     * @return array{ok:bool,status:string,message:?string,details?:array<string,mixed>|null}
     */
    public static function validate(string $domain, string $domainOption, bool $includeDetails = false): array
    {
        $domainOption = strtolower(trim($domainOption));

        if ($domainOption === 'owndomain') {
            return self::result(true, 'skipped', null, $includeDetails);
        }

        if (! in_array($domainOption, ['register', 'transfer'], true)) {
            return self::result(false, 'invalid', __('hosting.domain_check_failed'), $includeDetails);
        }

        if (! WhmcsClient::isConfigured()) {
            Log::warning('WHMCS domain check skipped because API credentials are missing.', [
                'domain' => $domain,
                'domain_option' => $domainOption,
            ]);

            return self::result(false, 'unconfigured', __('hosting.domain_check_unconfigured'), $includeDetails);
        }

        $whois = WhmcsClient::domainWhois($domain);
        if (! $whois) {
            $reason = trim((string) (WhmcsClient::lastError() ?: __('hosting.domain_check_failed')));

            return self::result(false, 'unknown', $reason, $includeDetails, [
                'whmcs_error' => WhmcsClient::lastError(),
            ]);
        }

        if (($whois['result'] ?? null) !== 'success') {
            $reason = trim((string) ($whois['message'] ?? WhmcsClient::lastError() ?? __('hosting.domain_check_failed')));

            Log::warning('WHMCS DomainWhois returned non-success', [
                'domain' => $domain,
                'domain_option' => $domainOption,
                'response' => $whois,
            ]);

            return self::result(false, 'unknown', $reason, $includeDetails, [
                'whmcs_response' => $whois,
            ]);
        }

        $registrationStatus = self::normalizeRegistrationStatus($whois);

        if ($registrationStatus === null) {
            Log::warning('WHMCS DomainWhois returned an unknown registration status', [
                'domain' => $domain,
                'domain_option' => $domainOption,
                'response' => $whois,
            ]);

            return self::result(false, 'unknown', __('hosting.domain_check_unknown_status'), $includeDetails, [
                'whmcs_response' => $whois,
            ]);
        }

        if ($domainOption === 'register') {
            if ($registrationStatus === 'available') {
                return self::result(true, 'available', __('hosting.domain_available'), $includeDetails, [
                    'whmcs_response' => $whois,
                ]);
            }

            return self::result(false, 'unavailable', __('hosting.domain_taken'), $includeDetails, [
                'whmcs_response' => $whois,
            ]);
        }

        if ($registrationStatus === 'unavailable') {
            return self::result(true, 'unavailable', __('hosting.domain_transfer_ok'), $includeDetails, [
                'whmcs_response' => $whois,
            ]);
        }

        return self::result(false, 'available', __('hosting.domain_not_registered'), $includeDetails, [
            'whmcs_response' => $whois,
        ]);
    }

    /**
     * @param  array<string, mixed>  $whois
     */
    protected static function normalizeRegistrationStatus(array $whois): ?string
    {
        $status = strtolower(trim((string) ($whois['status'] ?? '')));

        if (in_array($status, ['available', 'free', 'not registered', 'notregistered'], true)) {
            return 'available';
        }

        if (in_array($status, ['unavailable', 'registered', 'taken', 'not available', 'notavailable'], true)) {
            return 'unavailable';
        }

        $whoisText = strtolower((string) ($whois['whois'] ?? ''));

        if ($whoisText !== '') {
            if (preg_match('/\b(no match|not found|available for registration|no data found|no entries found|status:\s*free)\b/', $whoisText)) {
                return 'available';
            }

            if (preg_match('/\b(domain name:|registrar:|creation date:|registered on|status:\s*(ok|clienttransferprohibited|active))\b/', $whoisText)) {
                return 'unavailable';
            }
        }

        return $status !== '' ? null : null;
    }

    /**
     * @param  array<string, mixed>|null  $details
     * @return array{ok:bool,status:string,message:?string,details?:array<string,mixed>|null}
     */
    protected static function result(
        bool $ok,
        string $status,
        ?string $message,
        bool $includeDetails,
        ?array $details = null
    ): array {
        $payload = [
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
        ];

        if ($includeDetails) {
            $payload['details'] = $details;
        }

        return $payload;
    }
}
