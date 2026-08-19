<?php

namespace App\Support;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsService;
use Illuminate\Support\Facades\Log;

class WhmcsSyncService
{
    /**
     * @return array{customers_synced:int,services_synced:int}
     */
    public static function syncCustomersAndServices(): array
    {
        $customersSynced = 0;
        $servicesSynced = 0;
        $start = 0;
        $limit = 50;
        $total = null;

        while ($total === null || $start < $total) {
            $page = WhmcsClient::getClients($start, $limit);
            $clients = $page['clients'];
            $total = $page['total'];

            if ($clients === []) {
                break;
            }

            foreach ($clients as $clientPayload) {
                $customer = self::upsertCustomer($clientPayload);
                $customersSynced++;

                $products = WhmcsClient::getClientProducts((int) $customer->whmcs_client_id);
                foreach ($products as $product) {
                    self::upsertService($customer, $product);
                    $servicesSynced++;
                }
            }

            $start += $limit;
        }

        Log::info('WHMCS sync completed', [
            'customers_synced' => $customersSynced,
            'services_synced' => $servicesSynced,
        ]);

        return [
            'customers_synced' => $customersSynced,
            'services_synced' => $servicesSynced,
        ];
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected static function upsertCustomer(array $payload): WhmcsCustomer
    {
        $email = strtolower(trim((string) data_get($payload, 'email', '')));
        $linkedUser = $email !== ''
            ? User::query()->customers()->where('email', $email)->first()
            : null;

        $firstName = (string) data_get($payload, 'firstname', '');
        $lastName = (string) data_get($payload, 'lastname', '');
        $fullName = trim($firstName . ' ' . $lastName);

        /** @var WhmcsCustomer $customer */
        $customer = WhmcsCustomer::query()->updateOrCreate(
            ['whmcs_client_id' => (int) data_get($payload, 'id')],
            [
                'user_id' => $linkedUser?->id,
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'full_name' => $fullName !== '' ? $fullName : ((string) data_get($payload, 'fullname', '') ?: null),
                'email' => $email !== '' ? $email : null,
                'company' => (string) data_get($payload, 'companyname', '') ?: null,
                'phone' => (string) data_get($payload, 'phonenumber', '') ?: null,
                'status' => (string) data_get($payload, 'status', '') ?: null,
                'country' => strtoupper((string) data_get($payload, 'countrycode', '')) ?: null,
                'last_synced_at' => now(),
                'raw_payload' => $payload,
            ]
        );

        return $customer;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    protected static function upsertService(WhmcsCustomer $customer, array $payload): void
    {
        $serviceId = (int) data_get($payload, 'id', 0);
        if ($serviceId < 1) {
            return;
        }

        $nextDueDate = (string) data_get($payload, 'nextduedate', '');
        if ($nextDueDate === '0000-00-00') {
            $nextDueDate = '';
        }

        WhmcsService::query()->updateOrCreate(
            ['whmcs_service_id' => $serviceId],
            [
                'whmcs_customer_id' => $customer->id,
                'user_id' => $customer->user_id,
                'whmcs_client_id' => (int) $customer->whmcs_client_id,
                'product_name' => (string) data_get($payload, 'productname', '') ?: null,
                'domain' => (string) data_get($payload, 'domain', '') ?: null,
                'username' => (string) data_get($payload, 'username', '') ?: null,
                'billing_cycle' => (string) data_get($payload, 'billingcycle', '') ?: null,
                'next_due_date' => $nextDueDate !== '' ? $nextDueDate : null,
                'status' => (string) data_get($payload, 'status', '') ?: null,
                'last_synced_at' => now(),
                'raw_payload' => $payload,
            ]
        );
    }
}
