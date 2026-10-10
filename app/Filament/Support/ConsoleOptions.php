<?php

namespace App\Filament\Support;

use App\Models\WhmcsCustomer;
use App\Support\Whmcs\Whmcs;
use Illuminate\Support\Facades\Cache;

class ConsoleOptions
{
    /**
     * @return array<string, string> gateway module => display name
     */
    public static function gateways(): array
    {
        return self::cached('console.whmcs.gateways', function (): array {
            $options = [];
            foreach (Whmcs::call('GetPaymentMethods')->rows('paymentmethods.paymentmethod') as $method) {
                if (! empty($method['module'])) {
                    $options[$method['module']] = (string) ($method['displayname'] ?? $method['module']);
                }
            }

            return $options;
        });
    }

    /**
     * @return array<int, string> department id => name
     */
    public static function departments(): array
    {
        return self::cached('console.whmcs.departments', function (): array {
            $options = [];
            foreach (Whmcs::call('GetSupportDepartments', ['ignore_dept_assignments' => true])->rows('departments.department') as $dept) {
                if (! empty($dept['id'])) {
                    $options[(int) $dept['id']] = (string) ($dept['name'] ?? "Department {$dept['id']}");
                }
            }

            return $options;
        });
    }

    /**
     * @return array<int, string> WHMCS client id => label
     */
    public static function searchClients(string $search): array
    {
        return WhmcsCustomer::query()
            ->where(fn ($q) => $q->whereLike('full_name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")
                ->orWhereLike('company', "%{$search}%"))
            ->limit(25)
            ->get()
            ->mapWithKeys(fn (WhmcsCustomer $c) => [$c->whmcs_client_id => "{$c->full_name} ({$c->email})"])
            ->all();
    }

    public static function clientLabel(mixed $clientId): ?string
    {
        $client = WhmcsCustomer::query()->where('whmcs_client_id', (int) $clientId)->first();

        return $client ? "{$client->full_name} ({$client->email})" : null;
    }

    /**
     * Caches non-empty lookups only, so a WHMCS outage is not remembered.
     *
     * @param  callable(): array<mixed>  $load
     * @return array<mixed>
     */
    private static function cached(string $key, callable $load): array
    {
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $options = $load();
        if ($options !== []) {
            Cache::put($key, $options, now()->addMinutes(10));
        }

        return $options;
    }
}
