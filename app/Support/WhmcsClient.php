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
    public static function updateClient(array $payload): ?array
    {
        $response = self::request('UpdateClient', $payload);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    public static function closeClient(int $clientId): bool
    {
        if ($clientId < 1) {
            return false;
        }

        $response = self::request('CloseClient', [
            'clientid' => $clientId,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
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

    public static function acceptOrder(int $orderId, bool $autoSetup = false): bool
    {
        $response = self::request('AcceptOrder', [
            'orderid' => $orderId,
            'autosetup' => $autoSetup,
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
    public static function getInvoice(int $invoiceId): ?array
    {
        $response = self::request('GetInvoice', [
            'invoiceid' => $invoiceId,
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getInvoices(int $clientId, int $limit = 50): array
    {
        if ($clientId <= 0) {
            return [];
        }

        $response = self::request('GetInvoices', [
            'userid' => $clientId,
            'limitnum' => $limit,
            'limitstart' => 0,
            'orderby' => 'date',
            'order' => 'desc',
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return [];
        }

        $invoices = $response['invoices']['invoice'] ?? [];

        if (! is_array($invoices)) {
            return [];
        }

        if (isset($invoices['id'])) {
            return [$invoices];
        }

        return array_values(array_filter($invoices, 'is_array'));
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
     * Validate a user email/password against WHMCS.
     *
     * Note: `userid` is the WHMCS *user* id (WHMCS 8+), not the billing client id.
     *
     * @return array{userid:int,passwordhash?:string,two_factor:bool}|null
     */
    public static function validateLogin(string $email, string $password): ?array
    {
        $response = self::request('ValidateLogin', [
            'email' => strtolower(trim($email)),
            'password2' => $password,
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        $userId = (int) ($response['userid'] ?? $response['clientid'] ?? 0);
        if ($userId < 1) {
            return null;
        }

        $twoFactor = filter_var($response['twoFactorEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'userid' => $userId,
            'passwordhash' => (string) ($response['passwordhash'] ?? ''),
            'two_factor' => $twoFactor,
        ];
    }

    /**
     * Resolve billing client details after a successful ValidateLogin.
     *
     * WHMCS 8+ separates users from clients — never treat ValidateLogin's userid
     * as a client id without verifying GetClientsDetails.
     *
     * @return array<string, mixed>|null
     */
    public static function resolveClientDetailsForLogin(string $email, int $whmcsUserId = 0): ?array
    {
        $email = strtolower(trim($email));

        // Prefer email — GetClientsDetails accepts client email directly.
        $byEmail = self::findClientByEmail($email);
        if ($byEmail) {
            return $byEmail;
        }

        // Map user → linked clients via GetUsers.
        $clientId = self::findOwnedClientIdForUser($email, $whmcsUserId);
        if ($clientId > 0) {
            $byId = self::findClientById($clientId);
            if ($byId) {
                return $byId;
            }
        }

        // Last resort: only if userid happens to equal a client id (older WHMCS).
        if ($whmcsUserId > 0) {
            return self::findClientById($whmcsUserId);
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getUsers(string $search, int $limit = 25): array
    {
        $response = self::request('GetUsers', [
            'search' => $search,
            'limitnum' => max(1, min(100, $limit)),
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return [];
        }

        $rows = data_get($response, 'users', []);
        if (! is_array($rows)) {
            return [];
        }

        // Some installs nest as users.user
        if (isset($rows['user']) && is_array($rows['user'])) {
            $rows = $rows['user'];
        }

        if ($rows !== [] && isset($rows['id'])) {
            return [$rows];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    public static function findOwnedClientIdForUser(string $email, int $whmcsUserId = 0): int
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        foreach (self::getUsers($email) as $user) {
            $id = (int) ($user['id'] ?? 0);
            $userEmail = strtolower(trim((string) ($user['email'] ?? '')));

            $matchesUser = $whmcsUserId > 0 && $id === $whmcsUserId;
            $matchesEmail = $userEmail === $email;

            if (! $matchesUser && ! $matchesEmail) {
                continue;
            }

            $clients = $user['clients'] ?? [];
            if (! is_array($clients)) {
                continue;
            }

            if (isset($clients['id'])) {
                $clients = [$clients];
            }

            $ownerId = 0;
            $anyId = 0;
            foreach ($clients as $client) {
                if (! is_array($client)) {
                    continue;
                }
                $cid = (int) ($client['id'] ?? 0);
                if ($cid < 1) {
                    continue;
                }
                if ($anyId < 1) {
                    $anyId = $cid;
                }
                if (filter_var($client['isOwner'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $ownerId = $cid;
                    break;
                }
            }

            return $ownerId > 0 ? $ownerId : $anyId;
        }

        return 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findClientById(int $clientId): ?array
    {
        if ($clientId < 1) {
            return null;
        }

        $response = self::request('GetClientsDetails', [
            'clientid' => $clientId,
            'stats' => true,
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    /**
     * @return array{clients:list<array<string,mixed>>,total:int}
     */
    public static function searchClients(string $search = '', int $start = 0, int $limit = 50): array
    {
        $payload = [
            'limitstart' => max(0, $start),
            'limitnum' => max(1, $limit),
        ];

        if (trim($search) !== '') {
            $payload['search'] = trim($search);
        }

        $response = self::request('GetClients', $payload);

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

    public static function moduleSuspend(int $serviceId, string $reason = ''): bool
    {
        $payload = ['accountid' => $serviceId];
        if ($reason !== '') {
            $payload['suspendreason'] = $reason;
        }

        $response = self::request('ModuleSuspend', $payload);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    public static function moduleUnsuspend(int $serviceId): bool
    {
        $response = self::request('ModuleUnsuspend', [
            'accountid' => $serviceId,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    public static function moduleTerminate(int $serviceId): bool
    {
        $response = self::request('ModuleTerminate', [
            'accountid' => $serviceId,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    /**
     * @return array{orders:list<array<string,mixed>>,total:int}
     */
    public static function getOrders(string $status = '', int $start = 0, int $limit = 50): array
    {
        $payload = [
            'limitstart' => max(0, $start),
            'limitnum' => max(1, $limit),
        ];

        if ($status !== '') {
            $payload['status'] = $status;
        }

        $response = self::request('GetOrders', $payload);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return ['orders' => [], 'total' => 0];
        }

        $rows = data_get($response, 'orders.order', []);
        if (is_array($rows) && isset($rows['id'])) {
            $orders = [$rows];
        } else {
            $orders = is_array($rows) ? array_values($rows) : [];
        }

        return [
            'orders' => $orders,
            'total' => (int) data_get($response, 'totalresults', count($orders)),
        ];
    }

    public static function cancelOrder(int $orderId): bool
    {
        $response = self::request('CancelOrder', [
            'orderid' => $orderId,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    public static function pendingOrder(int $orderId): bool
    {
        $response = self::request('PendingOrder', [
            'orderid' => $orderId,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    /**
     * @return array{invoices:list<array<string,mixed>>,total:int}
     */
    public static function getInvoicesFiltered(string $status = '', int $start = 0, int $limit = 50): array
    {
        $payload = [
            'limitstart' => max(0, $start),
            'limitnum' => max(1, $limit),
            'orderby' => 'date',
            'order' => 'desc',
        ];

        if ($status !== '') {
            $payload['status'] = $status;
        }

        $response = self::request('GetInvoices', $payload);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return ['invoices' => [], 'total' => 0];
        }

        $rows = data_get($response, 'invoices.invoice', []);
        if (is_array($rows) && isset($rows['id'])) {
            $invoices = [$rows];
        } else {
            $invoices = is_array($rows) ? array_values($rows) : [];
        }

        return [
            'invoices' => $invoices,
            'total' => (int) data_get($response, 'totalresults', count($invoices)),
        ];
    }

    public static function updateInvoiceStatus(int $invoiceId, string $status): bool
    {
        $response = self::request('UpdateInvoice', [
            'invoiceid' => $invoiceId,
            'status' => $status,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    /**
     * @return array{tickets:list<array<string,mixed>>,total:int}
     */
    public static function getTickets(string $status = 'Open', int $start = 0, int $limit = 50): array
    {
        $payload = [
            'limitstart' => max(0, $start),
            'limitnum' => max(1, $limit),
        ];

        if ($status !== '') {
            $payload['status'] = $status;
        }

        $response = self::request('GetTickets', $payload);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return ['tickets' => [], 'total' => 0];
        }

        $rows = data_get($response, 'tickets.ticket', []);
        if (is_array($rows) && isset($rows['id'])) {
            $tickets = [$rows];
        } else {
            $tickets = is_array($rows) ? array_values($rows) : [];
        }

        return [
            'tickets' => $tickets,
            'total' => (int) data_get($response, 'totalresults', count($tickets)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getTicket(int $ticketId): ?array
    {
        $response = self::request('GetTicket', [
            'ticketid' => $ticketId,
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return null;
        }

        return $response;
    }

    public static function addTicketReply(int $ticketId, string $message, string $adminName = 'Admin'): bool
    {
        $response = self::request('AddTicketReply', [
            'ticketid' => $ticketId,
            'message' => $message,
            'adminusername' => $adminName,
            'status' => 'Answered',
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    public static function closeTicket(int $ticketId): bool
    {
        $response = self::request('UpdateTicket', [
            'ticketid' => $ticketId,
            'status' => 'Closed',
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function getClientDomains(int $clientId): array
    {
        if ($clientId < 1) {
            return [];
        }

        $response = self::request('GetClientsDomains', [
            'clientid' => $clientId,
            'limitnum' => 200,
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return [];
        }

        $rows = data_get($response, 'domains.domain', []);
        if (is_array($rows) && isset($rows['id'])) {
            return [$rows];
        }

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return array{domains:list<array<string,mixed>>,total:int}
     */
    public static function getDomains(int $start = 0, int $limit = 50): array
    {
        $response = self::request('GetClientsDomains', [
            'limitstart' => max(0, $start),
            'limitnum' => max(1, $limit),
        ]);

        if (! $response || ($response['result'] ?? null) !== 'success') {
            return ['domains' => [], 'total' => 0];
        }

        $rows = data_get($response, 'domains.domain', []);
        if (is_array($rows) && isset($rows['id'])) {
            $domains = [$rows];
        } else {
            $domains = is_array($rows) ? array_values($rows) : [];
        }

        return [
            'domains' => $domains,
            'total' => (int) data_get($response, 'totalresults', count($domains)),
        ];
    }

    public static function domainUpdateLocking(int $domainId, bool $locked): bool
    {
        $response = self::request('DomainUpdateLocking', [
            'domainid' => $domainId,
            'lockstatus' => $locked,
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
    }

    public static function domainRenew(int $domainId, int $regPeriod = 1): bool
    {
        $response = self::request('DomainRenew', [
            'domainid' => $domainId,
            'regperiod' => max(1, $regPeriod),
        ]);

        return (bool) $response && ($response['result'] ?? null) === 'success';
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
