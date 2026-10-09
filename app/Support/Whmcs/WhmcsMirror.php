<?php

namespace App\Support\Whmcs;

use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use App\Support\WhmcsSyncService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the local whmcs_* tables in step with WHMCS so the console and
 * customer area can read locally instead of calling WHMCS on every page.
 */
class WhmcsMirror
{
    private const PAGE = 250;

    private const OPEN_INVOICE = ['Unpaid', 'Payment Pending'];

    private const OPEN_ORDER = ['Pending'];

    private const CLOSED_TICKET = ['Closed'];

    /** @var array<int, int|null> */
    private static array $userIds = [];

    /**
     * Scheduled run: client list, open records everywhere, then a rotating batch of full client refreshes.
     *
     * @return array{clients:int,open:int,refreshed:int}
     */
    public static function run(int $batch = 25): array
    {
        self::$userIds = [];

        $clients = self::syncClientList();
        [$open, $changedClients] = self::syncOpenRecords();

        $stale = WhmcsCustomer::query()
            ->whereNotIn('whmcs_client_id', $changedClients ?: [0])
            ->orderByRaw('details_synced_at IS NOT NULL')
            ->orderBy('details_synced_at')
            ->limit(max(0, $batch))
            ->pluck('whmcs_client_id')
            ->all();

        $refreshed = 0;
        foreach (array_unique([...$changedClients, ...$stale]) as $clientId) {
            $refreshed += self::syncClient((int) $clientId) ? 1 : 0;
        }

        Log::info('WHMCS mirror run completed', compact('clients', 'open', 'refreshed'));

        return compact('clients', 'open', 'refreshed');
    }

    public static function syncClientList(): int
    {
        return self::paged('GetClients', [], 'clients.client', function (array $row): void {
            WhmcsSyncService::upsertCustomer($row);
        });
    }

    /**
     * Pulls every open invoice, order and ticket. Local records that were open but are no longer
     * returned have changed state, so their clients are returned for a full refresh.
     *
     * @return array{0:int,1:list<int>}
     */
    public static function syncOpenRecords(): array
    {
        $count = 0;
        $changed = [];

        $sets = [
            [WhmcsInvoice::class, 'whmcs_invoice_id', 'GetInvoices', ['status' => 'Unpaid'], 'invoices.invoice', self::OPEN_INVOICE, false],
            [WhmcsOrder::class, 'whmcs_order_id', 'GetOrders', ['status' => 'Pending'], 'orders.order', self::OPEN_ORDER, false],
            [WhmcsTicket::class, 'whmcs_ticket_id', 'GetTickets', ['status' => 'All Active Tickets', 'ignore_dept_assignments' => true], 'tickets.ticket', self::CLOSED_TICKET, true],
        ];

        foreach ($sets as [$model, $key, $action, $params, $path, $statuses, $exclude]) {
            $seen = [];
            $pages = self::paged($action, $params, $path, function (array $row) use ($model, &$seen): void {
                if ($record = self::store($model, $row)) {
                    $seen[] = $record->getAttribute(self::key($model));
                }
            });

            if ($pages < 0) {
                continue;
            }
            $count += $pages;

            $query = $model::query()->whereNotIn($key, $seen ?: [0])->whereNotNull('whmcs_client_id');
            $exclude ? $query->whereNotIn('status', $statuses) : $query->whereIn('status', $statuses);
            $changed = [...$changed, ...$query->pluck('whmcs_client_id')->map(fn ($id) => (int) $id)->all()];
        }

        return [$count, array_values(array_unique(array_filter($changed)))];
    }

    /**
     * Full refresh of one client: profile, services, domains, invoices, orders and tickets.
     */
    public static function syncClient(int $clientId): bool
    {
        if ($clientId < 1) {
            return false;
        }

        $details = Whmcs::call('GetClientsDetails', ['clientid' => $clientId, 'stats' => false]);
        if (! $details->ok) {
            return false;
        }

        $profile = (array) ($details->get('client') ?: $details->data);
        $profile['id'] ??= $clientId;
        $customer = WhmcsSyncService::upsertCustomer($profile);
        $customer->forceFill([
            'credit' => is_numeric($profile['credit'] ?? null) ? $profile['credit'] : null,
            'currency_code' => (string) ($profile['currency_code'] ?? '') ?: null,
            'details_synced_at' => now(),
        ])->save();
        self::$userIds[$clientId] = $customer->user_id;

        WhmcsSyncService::syncServicesForCustomer($customer);

        $lists = [
            [WhmcsDomain::class, 'GetClientsDomains', ['clientid' => $clientId], 'domains.domain'],
            [WhmcsInvoice::class, 'GetInvoices', ['userid' => $clientId], 'invoices.invoice'],
            [WhmcsOrder::class, 'GetOrders', ['userid' => $clientId], 'orders.order'],
            [WhmcsTicket::class, 'GetTickets', ['clientid' => $clientId, 'ignore_dept_assignments' => true], 'tickets.ticket'],
        ];

        foreach ($lists as [$model, $action, $params, $path]) {
            $seen = [];
            $synced = self::paged($action, $params, $path, function (array $row) use ($model, &$seen): void {
                if ($record = self::store($model, $row)) {
                    $seen[] = $record->getAttribute(self::key($model));
                }
            });

            // Records WHMCS no longer returns for this client were deleted there.
            if ($synced >= 0) {
                $model::query()->where('whmcs_client_id', $clientId)->whereNotIn(self::key($model), $seen ?: [0])->delete();
            }
        }

        return true;
    }

    /**
     * After a console write, refresh the client it touched so screens show the new state.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $response
     */
    public static function refreshAfter(array $params, array $response = []): void
    {
        $clientId = (int) ($params['clientid'] ?? $response['clientid'] ?? 0);

        $lookups = [
            'serviceid' => [WhmcsService::class, 'whmcs_service_id'],
            'domainid' => [WhmcsDomain::class, 'whmcs_domain_id'],
            'invoiceid' => [WhmcsInvoice::class, 'whmcs_invoice_id'],
            'orderid' => [WhmcsOrder::class, 'whmcs_order_id'],
            'ticketid' => [WhmcsTicket::class, 'whmcs_ticket_id'],
        ];
        foreach ($lookups as $param => [$model, $column]) {
            if ($clientId > 0) {
                break;
            }
            $id = $params[$param] ?? $response[$param] ?? null;
            if ($id) {
                $clientId = (int) $model::query()->where($column, $id)->value('whmcs_client_id');
            }
        }

        $clientId = $clientId ?: (int) ($params['userid'] ?? 0);

        try {
            self::syncClient($clientId);
        } catch (\Throwable $e) {
            Log::warning('WHMCS mirror refresh failed', ['client_id' => $clientId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Removes credentials WHMCS includes in some responses (service passwords, EPP codes, security answers).
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    public static function sanitize(array $payload): array
    {
        $clean = [];
        foreach ($payload as $key => $value) {
            if (is_string($key) && preg_match('/(password|passwd|secret|token|apikey|eppcode|authcode|securityqans|cardnum)$/i', $key)) {
                continue;
            }
            $clean[$key] = is_array($value) ? self::sanitize($value) : $value;
        }

        return $clean;
    }

    /**
     * Walks every page of a WHMCS list action. Returns rows handled, or -1 when the first request fails.
     */
    private static function paged(string $action, array $params, string $path, callable $each): int
    {
        $start = 0;
        $handled = 0;

        do {
            $result = Whmcs::call($action, $params + ['limitstart' => $start, 'limitnum' => self::PAGE]);
            if (! $result->ok) {
                return $start === 0 ? -1 : $handled;
            }

            $rows = $result->rows($path);
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $each($row);
                    $handled++;
                }
            }

            $start += self::PAGE;
            $total = (int) ($result->get('totalresults') ?? 0);
        } while ($rows !== [] && $start < $total);

        return $handled;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function key(string $model): string
    {
        return match ($model) {
            WhmcsDomain::class => 'whmcs_domain_id',
            WhmcsInvoice::class => 'whmcs_invoice_id',
            WhmcsOrder::class => 'whmcs_order_id',
            WhmcsTicket::class => 'whmcs_ticket_id',
        };
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $row
     */
    private static function store(string $model, array $row): ?Model
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id < 1) {
            return null;
        }

        $clientId = (int) ($row['userid'] ?? $row['clientid'] ?? 0) ?: null;

        $attributes = match ($model) {
            WhmcsDomain::class => [
                'domain' => (string) ($row['domainname'] ?? $row['domain'] ?? ''),
                'registrar' => self::text($row['registrar'] ?? null),
                'status' => self::text($row['status'] ?? null),
                'registration_date' => self::date($row['regdate'] ?? null),
                'expiry_date' => self::date($row['expirydate'] ?? null),
                'next_due_date' => self::date($row['nextduedate'] ?? null),
                'recurring_amount' => self::money($row['recurringamount'] ?? null),
                'auto_renew' => ! filter_var($row['donotrenew'] ?? false, FILTER_VALIDATE_BOOL),
                'id_protection' => filter_var($row['idprotection'] ?? false, FILTER_VALIDATE_BOOL),
            ],
            WhmcsInvoice::class => [
                'invoice_num' => self::text($row['invoicenum'] ?? null),
                'status' => self::text($row['status'] ?? null),
                'date' => self::date($row['date'] ?? null),
                'due_date' => self::date($row['duedate'] ?? null),
                'date_paid' => self::date($row['datepaid'] ?? null),
                'subtotal' => self::money($row['subtotal'] ?? null),
                'credit' => self::money($row['credit'] ?? null),
                'tax' => self::money($row['tax'] ?? null),
                'total' => self::money($row['total'] ?? null),
                'currency_code' => self::text($row['currencycode'] ?? null),
                'payment_method' => self::text($row['paymentmethod'] ?? null),
            ],
            WhmcsOrder::class => [
                'order_num' => self::text($row['ordernum'] ?? null),
                'status' => self::text($row['status'] ?? null),
                'payment_status' => self::text($row['paymentstatus'] ?? null),
                'whmcs_invoice_id' => (int) ($row['invoiceid'] ?? 0) ?: null,
                'amount' => self::money($row['amount'] ?? null),
                'payment_method' => self::text($row['paymentmethod'] ?? null),
                'placed_at' => self::date($row['date'] ?? null),
            ],
            WhmcsTicket::class => [
                'tid' => self::text($row['tid'] ?? null),
                'department_id' => (int) ($row['deptid'] ?? 0) ?: null,
                'department' => self::text($row['deptname'] ?? null),
                'subject' => self::text($row['subject'] ?? null),
                'status' => self::text($row['status'] ?? null),
                'priority' => self::text($row['priority'] ?? null),
                'opened_at' => self::date($row['date'] ?? null),
                'last_reply_at' => self::date($row['lastreply'] ?? null),
            ],
        };

        if ($model !== WhmcsTicket::class && ! $clientId) {
            return null;
        }

        return $model::query()->updateOrCreate([self::key($model) => $id], $attributes + [
            'whmcs_client_id' => $clientId,
            'user_id' => $clientId ? self::userId($clientId) : null,
            'last_synced_at' => now(),
            'raw_payload' => self::sanitize($row),
        ]);
    }

    private static function userId(int $clientId): ?int
    {
        if (! array_key_exists($clientId, self::$userIds)) {
            self::$userIds[$clientId] = WhmcsCustomer::query()->where('whmcs_client_id', $clientId)->value('user_id');
        }

        return self::$userIds[$clientId];
    }

    private static function text(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }

    private static function money(mixed $value): ?string
    {
        return is_numeric($value) ? (string) $value : null;
    }

    private static function date(mixed $value): ?string
    {
        $value = self::text($value);

        return $value && ! str_starts_with($value, '0000-00-00') ? $value : null;
    }
}
