<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;

class TrekMailSettings
{
    public static function token(): string
    {
        return (string) IntegrationSetting::getValue(
            'trekmail.token',
            (string) config('services.trekmail.token', ''),
        );
    }

    public static function baseUrl(): string
    {
        return rtrim((string) IntegrationSetting::getValue(
            'trekmail.base_url',
            (string) config('services.trekmail.base_url', 'https://trekmail.net/api/v1'),
        ), '/');
    }

    public static function webmailUrl(): string
    {
        return rtrim((string) IntegrationSetting::getValue(
            'trekmail.webmail_url',
            (string) config('services.trekmail.webmail_url', config('email.webmail_url', 'https://trekmail.net/webmail')),
        ), '/');
    }

    public static function isConfigured(): bool
    {
        return filled(self::token());
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyConnection(): array
    {
        if (! self::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'TrekMail API token is missing.',
            ];
        }

        $response = Http::timeout(15)
            ->withToken(self::token())
            ->acceptJson()
            ->baseUrl(self::baseUrl())
            ->get('domains', ['page' => 1]);

        if ($response->successful()) {
            return [
                'ok' => true,
                'message' => 'TrekMail API credentials verified.',
            ];
        }

        $message = (string) data_get(
            $response->json(),
            'error.message',
            'TrekMail API rejected these credentials.',
        );

        return [
            'ok' => false,
            'message' => $message,
        ];
    }
}
