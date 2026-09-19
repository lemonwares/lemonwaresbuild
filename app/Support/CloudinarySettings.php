<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;

class CloudinarySettings
{
    public static function isEnabled(): bool
    {
        $stored = IntegrationSetting::getValue('cloudinary.enabled', null);
        if ($stored !== null && $stored !== '') {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return filled(self::cloudName())
            && filled(self::apiKey())
            && filled(self::apiSecret());
    }

    public static function cloudName(): string
    {
        return trim((string) IntegrationSetting::getValue(
            'cloudinary.cloud_name',
            (string) config('services.cloudinary.cloud_name', ''),
        ));
    }

    public static function apiKey(): string
    {
        return trim((string) IntegrationSetting::getValue(
            'cloudinary.api_key',
            (string) config('services.cloudinary.api_key', ''),
        ));
    }

    public static function apiSecret(): string
    {
        return trim((string) IntegrationSetting::getValue(
            'cloudinary.api_secret',
            (string) config('services.cloudinary.api_secret', ''),
        ));
    }

    public static function isConfigured(): bool
    {
        return self::isEnabled()
            && filled(self::cloudName())
            && filled(self::apiKey())
            && filled(self::apiSecret());
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyConnection(): array
    {
        $cloudName = self::cloudName();
        $apiKey = self::apiKey();
        $apiSecret = self::apiSecret();

        if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
            return [
                'ok' => false,
                'message' => 'Cloudinary cloud name, API key, and API secret are required.',
            ];
        }

        $response = Http::timeout(15)
            ->withBasicAuth($apiKey, $apiSecret)
            ->acceptJson()
            ->get('https://api.cloudinary.com/v1_1/'.$cloudName.'/ping');

        if ($response->status() === 401 || $response->status() === 403) {
            return [
                'ok' => false,
                'message' => 'Cloudinary rejected these credentials.',
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => 'Cloudinary API error (HTTP '.$response->status().').',
            ];
        }

        $status = strtolower((string) data_get($response->json(), 'status', ''));

        if ($status !== '' && $status !== 'ok') {
            return [
                'ok' => false,
                'message' => 'Cloudinary ping returned an unexpected status.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'Cloudinary credentials accepted.',
        ];
    }
}
