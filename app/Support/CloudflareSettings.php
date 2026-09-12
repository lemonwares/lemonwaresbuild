<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;

class CloudflareSettings
{
    public static function isEnabled(): bool
    {
        $stored = IntegrationSetting::getValue('cloudflare.enabled', null);
        if ($stored !== null && $stored !== '') {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return filled((string) config('services.cloudflare.api_token', ''));
    }

    public static function apiToken(): string
    {
        return trim((string) IntegrationSetting::getValue(
            'cloudflare.api_token',
            (string) config('services.cloudflare.api_token', ''),
        ));
    }

    public static function accountId(): string
    {
        return trim((string) IntegrationSetting::getValue(
            'cloudflare.account_id',
            (string) config('services.cloudflare.account_id', ''),
        ));
    }

    public static function isConfigured(): bool
    {
        return self::isEnabled() && filled(self::apiToken());
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyConnection(?string $tokenOverride = null): array
    {
        $token = trim((string) ($tokenOverride ?: self::apiToken()));

        if ($token === '') {
            return [
                'ok' => false,
                'message' => 'Cloudflare API token is missing.',
            ];
        }

        $response = Http::timeout(15)
            ->withToken($token)
            ->acceptJson()
            ->get('https://api.cloudflare.com/client/v4/user/tokens/verify');

        if ($response->status() === 401 || $response->status() === 403) {
            return [
                'ok' => false,
                'message' => 'Cloudflare rejected this API token.',
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => self::formatApiError($response->json(), $response->status()),
            ];
        }

        if (! (bool) data_get($response->json(), 'success', false)) {
            return [
                'ok' => false,
                'message' => self::formatApiError($response->json(), $response->status()),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Cloudflare API token accepted.',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    public static function formatApiError(?array $json, int $status): string
    {
        $errors = data_get($json, 'errors');
        if (is_array($errors) && $errors !== []) {
            $parts = [];
            foreach ($errors as $error) {
                $message = trim((string) data_get($error, 'message', ''));
                if ($message !== '') {
                    $parts[] = $message;
                }
            }
            if ($parts !== []) {
                return implode(' · ', $parts);
            }
        }

        return 'Cloudflare API error (HTTP '.$status.').';
    }
}
