<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;

class FlutterwaveSettings
{
    public static function isEnabled(): bool
    {
        $stored = IntegrationSetting::getValue('flutterwave.enabled', null);
        if ($stored !== null && $stored !== '') {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        $configured = config('site.flutterwave.enabled');
        if ($configured !== null && $configured !== '') {
            return filter_var($configured, FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }

    public static function publicKey(): string
    {
        return (string) IntegrationSetting::getValue(
            'flutterwave.public_key',
            (string) config('services.flutterwave.public_key', ''),
        );
    }

    public static function secretKey(): string
    {
        return (string) IntegrationSetting::getValue(
            'flutterwave.secret_key',
            (string) config('services.flutterwave.secret_key', ''),
        );
    }

    public static function secretHash(): string
    {
        return (string) IntegrationSetting::getValue(
            'flutterwave.secret_hash',
            (string) config('services.flutterwave.secret_hash', ''),
        );
    }

    public static function isConfigured(): bool
    {
        return self::isEnabled() && filled(self::secretKey());
    }

    public static function isTestMode(): bool
    {
        $secret = self::secretKey();
        $public = self::publicKey();

        return str_contains(strtoupper($secret), '_TEST')
            || str_contains(strtoupper($public), '_TEST');
    }

    public static function webhookUrl(): string
    {
        return route('webhooks.flutterwave');
    }

    /**
     * @return array{ok:bool,message:string,mode?:string}
     */
    public static function verifyConnection(): array
    {
        if (! self::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Flutterwave secret key is missing or payments are disabled.',
            ];
        }

        $response = Http::timeout(15)
            ->withToken(self::secretKey())
            ->acceptJson()
            ->get('https://api.flutterwave.com/v3/banks/NG');

        if ($response->successful() && data_get($response->json(), 'status') === 'success') {
            return [
                'ok' => true,
                'message' => 'Flutterwave API credentials verified.',
                'mode' => self::isTestMode() ? 'test' : 'live',
            ];
        }

        $message = (string) data_get($response->json(), 'message', 'Flutterwave API rejected these credentials.');

        return [
            'ok' => false,
            'message' => $message,
        ];
    }
}
