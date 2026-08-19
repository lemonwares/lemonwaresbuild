<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmcsClient
{
    public static function isConfigured(): bool
    {
        return filled(config('site.whmcs.api_identifier'))
            && filled(config('site.whmcs.api_secret'))
            && filled(config('site.whmcs.base_url'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public static function findClientByEmail(string $email, array $payload = []): ?array
    {
        $response = self::request('GetClientsDetails', array_merge($payload, [
            'email' => strtolower($email),
            'stats' => false,
        ]));

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        $clientId = data_get($response, 'id');
        if (! $clientId) {
            return null;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public static function createClient(array $payload): ?array
    {
        $response = self::request('AddClient', $payload);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public static function createOrder(array $payload): ?array
    {
        $response = self::request('AddOrder', $payload);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    public static function acceptOrder(int $orderId): bool
    {
        $response = self::request('AcceptOrder', [
            'orderid' => $orderId,
            'autosetup' => false,
            'sendemail' => false,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    /**
     * @return array{clients:list<array<string,mixed>>,total:int}
     */
    public static function getClients(int $start = 0, int $limit = 50): array
    {
        $response = self::request('GetClients', [
            'limitstart' => max(0, $start),
            'limitnum' => max(1, $limit),
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return ['clients' => [], 'total' => 0];
        }

        $rows = data_get($response, 'clients.client', []);
        if (is_array($rows) && isset($rows['id'])) {
            $clients = [$rows];
        } else {
            $clients = is_array($rows) ? array_values($rows) : [];
        }

        return [
            'clients' => $clients,
            'total' => (int) data_get($response, 'totalresults', count($clients)),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function getClientProducts(int $clientId): array
    {
        $response = self::request('GetClientsProducts', [
            'clientid' => $clientId,
            'limitnum' => 200,
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return [];
        }

        $rows = data_get($response, 'products.product', []);
        if (is_array($rows) && isset($rows['id'])) {
            return [$rows];
        }

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected static function request(string $action, array $payload): ?array
    {
        if (! self::isConfigured()) {
            return null;
        }

        $url = rtrim((string) config('site.whmcs.base_url'), '/') . '/includes/api.php';

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->acceptJson()
                ->post($url, array_merge($payload, [
                    'action' => $action,
                    'identifier' => (string) config('site.whmcs.api_identifier'),
                    'secret' => (string) config('site.whmcs.api_secret'),
                    'responsetype' => 'json',
                ]));

            $json = $response->json();

            if ($response->failed() || ! is_array($json)) {
                Log::warning('WHMCS request failed', [
                    'action' => $action,
                    'status' => $response->status(),
                    'response' => $json,
                ]);

                return null;
            }

            if (($json['result'] ?? null) !== 'success') {
                Log::warning('WHMCS API returned non-success', [
                    'action' => $action,
                    'response' => $json,
                ]);
            }

            return $json;
        } catch (\Throwable $exception) {
            Log::warning('WHMCS request exception', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
