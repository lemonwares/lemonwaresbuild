<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmcsClient
{
    protected static ?string $lastError = null;

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    public static function isConfigured(): bool
    {
        return filled(WhmcsSettings::apiIdentifier())
            && filled(WhmcsSettings::apiSecret())
            && filled(WhmcsSettings::baseUrl());
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

    public static function addInvoicePayment(int $invoiceId, float $amount, string $transactionId): bool
    {
        $response = self::request('AddInvoicePayment', [
            'invoiceid' => $invoiceId,
            'transid' => $transactionId,
            'amount' => round($amount, 2),
            'date' => now()->format('Y-m-d'),
            'paymentmethod' => WhmcsSettings::paymentMethod(),
            'noemail' => true,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function createSsoToken(int $clientId, string $redirectPath): ?array
    {
        $response = self::request('CreateSsoToken', [
            'client_id' => $clientId,
            'destination' => 'sso:custom_redirect',
            'sso_redirect_path' => ltrim($redirectPath, '/'),
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    /**
     * @return array{ok:bool,action:string,message:string,response?:array<string,mixed>|null}
     */
    public static function verifyConnection(): array
    {
        $response = self::request('GetClients', [
            'limitnum' => 1,
            'limitstart' => 0,
        ]);

        if (! $response) {
            return [
                'ok' => false,
                'action' => 'GetClients',
                'message' => self::lastError() ?: 'Could not reach WHMCS API.',
                'response' => null,
            ];
        }

        if (($response['result'] ?? null) !== 'success') {
            return [
                'ok' => false,
                'action' => 'GetClients',
                'message' => trim((string) ($response['message'] ?? self::lastError() ?: 'WHMCS API rejected the request.')),
                'response' => $response,
            ];
        }

        return [
            'ok' => true,
            'action' => 'GetClients',
            'message' => 'WHMCS API credentials are working.',
            'response' => $response,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function domainWhois(string $domain): ?array
    {
        $response = self::request('DomainWhois', [
            'domain' => strtolower(trim($domain)),
        ]);

        if (! $response) {
            return null;
        }

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getTldPricing(): ?array
    {
        $response = self::request('GetTLDPricing', []);

        if (! $response) {
            return null;
        }

        return $response;
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
        self::$lastError = null;

        if (! self::isConfigured()) {
            self::$lastError = 'WHMCS API credentials are missing.';

            return null;
        }

        $url = WhmcsSettings::baseUrl() . '/includes/api.php';

        $auth = [
            'action' => $action,
            'identifier' => WhmcsSettings::apiIdentifier(),
            'secret' => WhmcsSettings::apiSecret(),
            'responsetype' => 'json',
        ];

        if ($accessKey = WhmcsSettings::apiAccessKey()) {
            $auth['accesskey'] = $accessKey;
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->acceptJson()
                ->post($url, array_merge($payload, $auth));

            $json = $response->json();

            if ($response->failed() || ! is_array($json)) {
                $body = trim(substr((string) $response->body(), 0, 240));
                self::$lastError = self::formatRequestFailure($response->status(), is_array($json) ? $json : null, $body);

                Log::warning('WHMCS request failed', [
                    'action' => $action,
                    'status' => $response->status(),
                    'response' => $json,
                    'body' => $body,
                ]);

                return null;
            }

            if (($json['result'] ?? null) !== 'success') {
                self::$lastError = self::formatApiError($json);

                Log::warning('WHMCS API returned non-success', [
                    'action' => $action,
                    'response' => $json,
                ]);
            }

            return $json;
        } catch (\Throwable $exception) {
            self::$lastError = $exception->getMessage();

            Log::warning('WHMCS request exception', [
                'action' => $action,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    protected static function formatRequestFailure(int $status, ?array $json, string $body): string
    {
        $message = trim((string) data_get($json, 'message', ''));

        if ($message !== '' && preg_match('/invalid ip\s+([0-9a-f:.]+)/i', $message, $matches)) {
            return __('hosting.domain_check_invalid_ip', ['ip' => $matches[1]]);
        }

        if ($message !== '') {
            return $message;
        }

        return 'WHMCS API request failed with HTTP ' . $status . ($body !== '' ? ': ' . $body : '.');
    }

    /**
     * @param  array<string, mixed>  $json
     */
    protected static function formatApiError(array $json): string
    {
        $message = trim((string) ($json['message'] ?? 'WHMCS API returned an error.'));

        if (preg_match('/invalid ip\s+([0-9a-f:.]+)/i', $message, $matches)) {
            return __('hosting.domain_check_invalid_ip', ['ip' => $matches[1]]);
        }

        return $message;
    }
}
