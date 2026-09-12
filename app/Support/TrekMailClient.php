<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrekMailClient
{
    public static function isConfigured(): bool
    {
        return TrekMailSettings::isConfigured();
    }

    public static function webmailUrl(): string
    {
        return TrekMailSettings::webmailUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public static function findDomain(string $domain): ?array
    {
        $domain = strtolower($domain);
        $page = 1;

        do {
            $payload = self::request('get', '/domains', [
                'query' => [
                    'search' => $domain,
                    'page' => $page,
                ],
            ]);

            $items = self::items($payload);

            foreach ($items as $item) {
                $name = strtolower((string) ($item['domain'] ?? $item['name'] ?? ''));
                if ($name === $domain) {
                    return $item;
                }
            }

            $lastPage = (int) data_get($payload, 'meta.last_page', 1);
            $page++;
        } while ($page <= $lastPage && $page <= 5);

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function createDomain(string $domain, string $idempotencyKey): array
    {
        $existing = self::findDomain($domain);
        if ($existing) {
            return $existing;
        }

        try {
            $payload = self::request('post', '/domains', [
                'json' => ['domain' => $domain],
                'idempotency' => $idempotencyKey,
            ]);

            return self::resource($payload);
        } catch (TrekMailException $exception) {
            if ($exception->status === 422 || $exception->status === 409) {
                $retry = self::findDomain($domain);
                if ($retry) {
                    return $retry;
                }

                $payload = self::request('post', '/domains', [
                    'json' => ['name' => $domain],
                    'idempotency' => $idempotencyKey . '-name',
                ]);

                return self::resource($payload);
            }

            throw $exception;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function dnsRequirements(int|string $domainId): array
    {
        $payload = self::request('get', '/domains/' . $domainId . '/dns-requirements');
        $data = data_get($payload, 'data', $payload);

        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return $data;
        }

        foreach (['records', 'requirements', 'dns'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_values($data[$key]);
            }
        }

        return [$data];
    }

    public static function recheckDns(int|string $domainId, string $idempotencyKey): void
    {
        self::request('post', '/domains/' . $domainId . '/dns-recheck', [
            'idempotency' => $idempotencyKey,
        ]);
    }

    public static function pauseMailbox(int|string $mailboxId, string $idempotencyKey): void
    {
        self::request('post', '/mailboxes/' . $mailboxId . ':pause', [
            'idempotency' => $idempotencyKey,
        ]);
    }

    public static function resumeMailbox(int|string $mailboxId, string $idempotencyKey): void
    {
        self::request('post', '/mailboxes/' . $mailboxId . ':resume', [
            'idempotency' => $idempotencyKey,
        ]);
    }

    /**
     * Apply Lemonwares (or admin-configured) branding so TrekMail invites use your name/colors/logo.
     *
     * @return array<string, mixed>|null
     */
    public static function applyConfiguredBranding(int|string $domainId, string $idempotencyKey): ?array
    {
        if (! TrekMailSettings::brandingEnabled()) {
            return null;
        }

        $body = array_filter([
            'mode' => 'custom',
            'name' => TrekMailSettings::brandName(),
            'primary_color' => TrekMailSettings::brandPrimaryColor(),
            'accent_color' => TrekMailSettings::brandAccentColor(),
            'support_email' => TrekMailSettings::brandSupportEmail() ?: null,
            'support_url' => TrekMailSettings::brandSupportUrl() ?: null,
            'sender_email' => TrekMailSettings::brandSenderEmail() ?: null,
            // Keep hosts off by default — email branding does not require white-label DNS.
            'dashboard_enabled' => false,
            'webmail_enabled' => false,
        ], fn ($value) => $value !== null && $value !== '');

        $payload = self::request('patch', '/domains/' . $domainId . '/branding', [
            'json' => $body,
            'idempotency' => $idempotencyKey,
        ]);

        $logoPath = TrekMailSettings::brandLogoPath() ?? self::defaultLogoPath();
        if ($logoPath) {
            try {
                self::uploadBrandLogo($domainId, 'light', $logoPath, $idempotencyKey . '-logo-light');
                self::uploadBrandLogo($domainId, 'dark', $logoPath, $idempotencyKey . '-logo-dark');
            } catch (TrekMailException $exception) {
                Log::warning('TrekMail brand logo upload failed', [
                    'domain_id' => $domainId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return self::resource($payload);
    }

    public static function uploadBrandLogo(
        int|string $domainId,
        string $slot,
        string $absolutePath,
        string $idempotencyKey,
    ): void {
        $base64 = self::logoToBase64PngOrJpeg($absolutePath);
        if ($base64 === null) {
            throw new TrekMailException('Unable to encode brand logo for TrekMail (use PNG or JPG, max 1MB).');
        }

        self::request('put', '/domains/' . $domainId . '/branding/logo/' . $slot, [
            'json' => [
                'content_base64' => $base64,
            ],
            'idempotency' => $idempotencyKey,
        ]);
    }

    protected static function defaultLogoPath(): ?string
    {
        foreach (['lemonwareslogo.png', 'lemonwareslogo.jpg', 'lemonwareslogo.jpeg', 'lemonwareslogo.webp'] as $file) {
            $path = public_path($file);
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    protected static function logoToBase64PngOrJpeg(string $absolutePath): ?string
    {
        if (! is_file($absolutePath) || filesize($absolutePath) > 1024 * 1024) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: '';
        if (in_array($mime, ['image/png', 'image/jpeg'], true)) {
            $raw = file_get_contents($absolutePath);

            return $raw === false ? null : base64_encode($raw);
        }

        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $raw = file_get_contents($absolutePath);
        if ($raw === false) {
            return null;
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            return null;
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);
        $png = ob_get_clean();

        if ($png === false || strlen($png) > 1024 * 1024) {
            return null;
        }

        return base64_encode($png);
    }

    /**
     * @return array<string, mixed>
     */
    public static function createMailbox(
        int|string $domainId,
        string $localPart,
        string $idempotencyKey,
        ?int $storageMb = null,
    ): array {
        $body = [
            'domain_id' => (int) $domainId,
            'local_part' => strtolower(trim($localPart)),
        ];

        if ($storageMb && $storageMb > 0) {
            $body['storage_allocation_mb'] = $storageMb;
        }

        $payload = self::request('post', '/mailboxes', [
            'json' => $body,
            'idempotency' => $idempotencyKey,
        ]);

        return self::resource($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public static function inviteMailbox(
        int|string $domainId,
        string $localPart,
        string $notifyEmail,
        string $idempotencyKey,
        ?int $storageMb = null,
    ): array {
        $body = [
            'domain_id' => (int) $domainId,
            'local_part' => strtolower(trim($localPart)),
            'recipient_email' => strtolower(trim($notifyEmail)),
        ];

        if ($storageMb && $storageMb > 0) {
            $body['storage_allocation_mb'] = $storageMb;
        }

        $payload = self::request('post', '/mailboxes/invites', [
            'json' => $body,
            'idempotency' => $idempotencyKey,
        ]);

        return self::resource($payload);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected static function request(string $method, string $path, array $options = []): array
    {
        if (! self::isConfigured()) {
            throw new TrekMailException('TrekMail is not configured.');
        }

        $headers = [
            'Accept' => 'application/json',
        ];

        if (isset($options['idempotency'])) {
            $headers['Idempotency-Key'] = (string) $options['idempotency'];
        }

        $pending = Http::timeout(25)
            ->withToken(TrekMailSettings::token())
            ->withHeaders($headers)
            ->acceptJson()
            ->baseUrl(TrekMailSettings::baseUrl());

        $method = strtolower($method);

        if ($method === 'get') {
            $response = $pending->get(ltrim($path, '/'), $options['query'] ?? []);
        } else {
            $response = $pending->asJson()->{$method}(ltrim($path, '/'), $options['json'] ?? []);
        }

        if ($response->status() === 429) {
            throw new TrekMailException(
                'TrekMail rate limit reached. Please try again shortly.',
                429,
                $response->json(),
            );
        }

        if ($response->failed()) {
            $json = $response->json();
            $message = self::errorMessage($json) ?: 'TrekMail request failed.';

            Log::warning('TrekMail API error', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $json,
            ]);

            throw new TrekMailException($message, $response->status(), $json);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    protected static function items(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        if (! is_array($data)) {
            return [];
        }

        return array_is_list($data) ? $data : [$data];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected static function resource(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        return is_array($data) ? $data : $payload;
    }

    protected static function errorMessage(mixed $json): string
    {
        if (! is_array($json)) {
            return '';
        }

        $message = (string) data_get($json, 'error.message', data_get($json, 'message', ''));
        $errors = data_get($json, 'errors', data_get($json, 'error.errors'));

        if (! is_array($errors) || $errors === []) {
            return $message;
        }

        $details = [];
        foreach ($errors as $field => $messages) {
            if (is_array($messages)) {
                $details[] = $field . ': ' . implode(', ', array_map('strval', $messages));
            } else {
                $details[] = $field . ': ' . (string) $messages;
            }
        }

        if ($details === []) {
            return $message;
        }

        return trim($message . ' (' . implode('; ', $details) . ')');
    }
}
