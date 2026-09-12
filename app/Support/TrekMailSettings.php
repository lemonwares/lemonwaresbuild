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

    public static function brandingEnabled(): bool
    {
        return filter_var(
            IntegrationSetting::getValue('trekmail.branding_enabled', '1'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    public static function brandName(): string
    {
        $name = trim((string) IntegrationSetting::getValue(
            'trekmail.brand_name',
            (string) config('site.short_name', 'Lemonwares'),
        ));

        return $name !== '' ? $name : (string) config('site.short_name', 'Lemonwares');
    }

    public static function brandPrimaryColor(): string
    {
        return self::normalizeHex(
            (string) IntegrationSetting::getValue('trekmail.brand_primary_color', '#e04545'),
            '#e04545',
        );
    }

    public static function brandAccentColor(): string
    {
        return self::normalizeHex(
            (string) IntegrationSetting::getValue('trekmail.brand_accent_color', '#ffeded'),
            '#ffeded',
        );
    }

    public static function brandSupportEmail(): string
    {
        return trim((string) IntegrationSetting::getValue('trekmail.brand_support_email', ''));
    }

    public static function brandSupportUrl(): string
    {
        return rtrim(trim((string) IntegrationSetting::getValue('trekmail.brand_support_url', '')), '/');
    }

    public static function brandSenderEmail(): string
    {
        return trim((string) IntegrationSetting::getValue('trekmail.brand_sender_email', ''));
    }

    public static function brandLogoPath(): ?string
    {
        $relative = trim((string) IntegrationSetting::getValue('trekmail.brand_logo_path', ''));
        if ($relative === '') {
            return null;
        }

        $path = storage_path('app/' . ltrim($relative, '/'));

        return is_file($path) ? $path : null;
    }

    /**
     * @return array{
     *   enabled:bool,
     *   name:string,
     *   primary_color:string,
     *   accent_color:string,
     *   support_email:string,
     *   support_url:string,
     *   sender_email:string,
     *   logo_path:?string,
     *   has_logo:bool
     * }
     */
    public static function branding(): array
    {
        return [
            'enabled' => self::brandingEnabled(),
            'name' => self::brandName(),
            'primary_color' => self::brandPrimaryColor(),
            'accent_color' => self::brandAccentColor(),
            'support_email' => self::brandSupportEmail(),
            'support_url' => self::brandSupportUrl(),
            'sender_email' => self::brandSenderEmail(),
            'logo_path' => self::brandLogoPath(),
            'has_logo' => self::brandLogoPath() !== null,
        ];
    }

    protected static function normalizeHex(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^#?[0-9a-fA-F]{6}$/', $value) !== 1) {
            return $fallback;
        }

        return str_starts_with($value, '#') ? strtolower($value) : '#' . strtolower($value);
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
