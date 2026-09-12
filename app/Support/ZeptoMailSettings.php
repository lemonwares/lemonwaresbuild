<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class ZeptoMailSettings
{
    public static function isEnabled(): bool
    {
        $stored = IntegrationSetting::getValue('zeptomail.enabled', null);
        if ($stored !== null && $stored !== '') {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (string) config('mail.default') === 'zeptomail'
            || filled((string) config('services.zeptomail.token', ''));
    }

    public static function token(): string
    {
        $token = (string) IntegrationSetting::getValue(
            'zeptomail.token',
            (string) config('services.zeptomail.token', ''),
        );

        return self::normalizeToken($token);
    }

    /**
     * Accept either the raw Send Mail Token or a full "Zoho-enczapikey …" value.
     */
    public static function normalizeToken(string $token): string
    {
        $token = trim($token);
        $token = preg_replace('/\s+/', ' ', $token) ?? $token;

        if (preg_match('/^zoho-enczapikey\s+/i', $token) === 1) {
            $token = trim((string) preg_replace('/^zoho-enczapikey\s+/i', '', $token));
        }

        return $token;
    }

    public static function authorizationHeader(): string
    {
        return 'Zoho-enczapikey '.self::token();
    }

    public static function endpoint(): string
    {
        return rtrim((string) IntegrationSetting::getValue(
            'zeptomail.endpoint',
            (string) config('services.zeptomail.endpoint', 'https://api.zeptomail.com/v1.1/email'),
        ), '/');
    }

    public static function fromAddress(): string
    {
        return (string) IntegrationSetting::getValue(
            'zeptomail.from_address',
            (string) config('services.zeptomail.from_address', config('mail.from.address', 'noreply@lemonwares.com')),
        );
    }

    public static function fromName(): string
    {
        return (string) IntegrationSetting::getValue(
            'zeptomail.from_name',
            (string) config('services.zeptomail.from_name', config('mail.from.name', config('site.short_name', 'Lemonwares'))),
        );
    }

    /**
     * Public absolute URL for the Lemonwares logo used in transactional emails.
     */
    public static function logoUrl(): string
    {
        $configured = trim((string) IntegrationSetting::getValue(
            'zeptomail.logo_url',
            (string) config('services.zeptomail.logo_url', ''),
        ));

        if ($configured !== '') {
            return $configured;
        }

        if (is_file(public_path('lemonwareslogo.png'))) {
            return asset('lemonwareslogo.png');
        }

        return asset('lemonwareslogo.webp');
    }

    public static function isConfigured(): bool
    {
        return self::isEnabled()
            && filled(self::token())
            && filled(self::fromAddress());
    }

    /**
     * Apply admin/env ZeptoMail settings to the live mail config.
     */
    public static function applyRuntimeConfig(): void
    {
        if (! self::isEnabled() || ! filled(self::token())) {
            return;
        }

        config([
            'mail.default' => 'zeptomail',
            'mail.from.address' => self::fromAddress(),
            'mail.from.name' => self::fromName(),
            'services.zeptomail.token' => self::token(),
            'services.zeptomail.endpoint' => self::endpoint(),
        ]);
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function verifyConnection(): array
    {
        if (! filled(self::token())) {
            return [
                'ok' => false,
                'message' => 'ZeptoMail send-mail token is missing.',
            ];
        }

        // Auth-only probe: empty payload should not succeed, but a 401/403 means bad token.
        $response = Http::timeout(15)
            ->withHeaders([
                'Authorization' => self::authorizationHeader(),
                'Accept' => 'application/json',
            ])
            ->asJson()
            ->post(self::endpoint(), []);

        $status = $response->status();

        if (in_array($status, [401, 403], true)) {
            return [
                'ok' => false,
                'message' => 'ZeptoMail rejected this send-mail token.',
            ];
        }

        if ($status === 0) {
            return [
                'ok' => false,
                'message' => 'Could not reach ZeptoMail. Check the API endpoint.',
            ];
        }

        // 400/422 = token accepted, payload rejected — expected for an empty body.
        return [
            'ok' => true,
            'message' => 'ZeptoMail API token accepted.',
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public static function sendTestEmail(string $to): array
    {
        if (! self::isConfigured()) {
            return [
                'ok' => false,
                'message' => 'Save a ZeptoMail token and from-address first, then enable sending.',
            ];
        }

        self::applyRuntimeConfig();

        try {
            Mail::mailer('zeptomail')->raw(
                'This is a Lemonwares ZeptoMail test. Password resets and account notices will use this connection.',
                function ($message) use ($to): void {
                    $message->to($to)
                        ->from(self::fromAddress(), self::fromName())
                        ->subject(config('site.short_name').' · ZeptoMail test');
                },
            );
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Test email sent to '.$to.'.',
        ];
    }
}
