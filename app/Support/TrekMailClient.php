<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            'local_part' => $localPart,
            'display_name' => Str::headline($localPart),
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
    ): array {
        $payload = self::request('post', '/mailboxes/invites', [
            'json' => [
                'domain_id' => (int) $domainId,
                'local_part' => $localPart,
                'email' => $notifyEmail,
                'display_name' => Str::headline($localPart),
            ],
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
            $message = (string) data_get($response->json(), 'error.message', 'TrekMail request failed.');

            Log::warning('TrekMail API error', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new TrekMailException($message, $response->status(), $response->json());
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
}
