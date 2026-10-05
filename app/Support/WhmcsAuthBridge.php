<?php

namespace App\Support;

use App\Models\User;
use App\Models\WhmcsCustomer;

class WhmcsAuthBridge
{
    public const FAIL_NOT_CONFIGURED = 'not_configured';

    public const FAIL_ADMIN = 'admin';

    public const FAIL_CREDENTIALS = 'credentials';

    public const FAIL_TWO_FACTOR = 'two_factor';

    public const FAIL_CLIENT = 'client';

    public const FAIL_API = 'api';

    protected static ?string $lastFailure = null;

    protected static ?string $lastFailureDetail = null;

    public static function lastFailure(): ?string
    {
        return self::$lastFailure;
    }

    public static function lastFailureDetail(): ?string
    {
        return self::$lastFailureDetail;
    }

    /**
     * Login error for the form. Kept generic so the form never reveals whether a password was right,
     * an email belongs to staff, or how WHMCS is configured; the real reason goes to the log.
     */
    public static function failureMessage(): string
    {
        if (self::$lastFailure && self::$lastFailure !== self::FAIL_CREDENTIALS) {
            logger()->info('WHMCS login bridge failed', [
                'reason' => self::$lastFailure,
                'detail' => self::$lastFailureDetail,
            ]);
        }

        return self::$lastFailure === self::FAIL_API
            ? __('account.login_whmcs_api_error')
            : __('account.invalid_credentials');
    }

    /**
     * Validate WHMCS credentials and ensure a local customer User exists.
     */
    public static function attempt(string $email, string $password): ?User
    {
        self::$lastFailure = null;
        self::$lastFailureDetail = null;

        if (! WhmcsClient::isConfigured()) {
            self::fail(self::FAIL_NOT_CONFIGURED, 'WHMCS API base URL, identifier, or secret is missing.');

            return null;
        }

        $email = strtolower(trim($email));

        $existing = User::query()->where('email', $email)->first();
        if ($existing && $existing->isAdmin()) {
            // Never bridge staff accounts through WHMCS client login.
            self::fail(self::FAIL_ADMIN, 'This email belongs to a staff account — use the admin login.');

            return null;
        }

        $validated = WhmcsClient::validateLogin($email, $password);
        if (! $validated) {
            $apiError = trim((string) WhmcsClient::lastError());
            if ($apiError !== '' && self::looksLikeTransportOrConfigError($apiError)) {
                self::fail(self::FAIL_API, $apiError);
            } else {
                self::fail(
                    self::FAIL_CREDENTIALS,
                    $apiError !== '' ? $apiError : 'ValidateLogin did not accept these credentials.',
                );
            }

            return null;
        }

        if (! empty($validated['two_factor'])) {
            self::fail(self::FAIL_TWO_FACTOR, 'WHMCS reported twoFactorEnabled=true for this account.');

            return null;
        }

        $whmcsUserId = (int) $validated['userid'];
        $details = WhmcsClient::resolveClientDetailsForLogin($email, $whmcsUserId);

        if (! $details) {
            $apiError = trim((string) WhmcsClient::lastError());
            self::fail(
                self::FAIL_CLIENT,
                $apiError !== ''
                    ? $apiError
                    : 'ValidateLogin succeeded (user #'.$whmcsUserId.') but no billing client was found for this email.',
            );

            return null;
        }

        $clientId = (int) (data_get($details, 'client_id')
            ?: data_get($details, 'id')
            ?: 0);

        if ($clientId < 1) {
            self::fail(self::FAIL_CLIENT, 'Client details response did not include a client id.');

            return null;
        }

        $firstName = trim((string) data_get($details, 'firstname', ''));
        $lastName = trim((string) data_get($details, 'lastname', ''));
        $fullName = trim($firstName.' '.$lastName) ?: $email;
        $company = trim((string) data_get($details, 'companyname', ''));
        $phone = trim((string) (data_get($details, 'phonenumber') ?: data_get($details, 'telephoneNumber', '')));
        $country = trim((string) (data_get($details, 'countrycode') ?: data_get($details, 'country', '')));
        $status = trim((string) data_get($details, 'status', 'Active'));

        $user = $existing;

        if (! $user) {
            $user = User::query()->create([
                'name' => $fullName,
                'email' => $email,
                'role' => 'customer',
                'company' => $company !== '' ? $company : null,
                'phone' => $phone !== '' ? $phone : null,
                'billing_country' => $country !== '' ? $country : null,
                'password' => $password,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill([
                'password' => $password,
                'name' => $user->name ?: $fullName,
                'company' => $user->company ?: ($company !== '' ? $company : null),
                'phone' => $user->phone ?: ($phone !== '' ? $phone : null),
            ])->save();
        }

        /** @var WhmcsCustomer $customer */
        $customer = WhmcsCustomer::query()->updateOrCreate(
            ['whmcs_client_id' => $clientId],
            [
                'user_id' => $user->id,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'full_name' => $fullName,
                'email' => $email,
                'company' => $company !== '' ? $company : null,
                'phone' => $phone !== '' ? $phone : null,
                'status' => $status !== '' ? $status : 'Active',
                'country' => $country !== '' ? $country : null,
                'last_synced_at' => now(),
                'raw_payload' => $details,
            ],
        );

        // Pull running products immediately so Subscriptions is not empty until cron.
        WhmcsSyncService::syncServicesForCustomer($customer);

        return $user->fresh();
    }

    protected static function fail(string $code, string $detail): void
    {
        self::$lastFailure = $code;
        self::$lastFailureDetail = $detail;
    }

    protected static function looksLikeTransportOrConfigError(string $message): bool
    {
        $needle = mb_strtolower($message);

        return str_contains($needle, 'invalid ip')
            || str_contains($needle, 'blocked this server ip')
            || str_contains($needle, 'allowed api ip')
            || str_contains($needle, 'authentication')
            || str_contains($needle, 'access denied')
            || str_contains($needle, 'permission')
            || str_contains($needle, 'credentials are missing')
            || str_contains($needle, 'could not resolve')
            || str_contains($needle, 'timed out')
            || str_contains($needle, 'connection')
            || str_contains($needle, 'ssl')
            || str_contains($needle, 'http ');
    }
}
