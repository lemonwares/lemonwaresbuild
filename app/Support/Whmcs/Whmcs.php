<?php

namespace App\Support\Whmcs;

use App\Models\User;
use App\Support\AdminAudit;
use App\Support\AdminPermissions;
use App\Support\WhmcsClient;

/**
 * Single entry point for the WHMCS API. Actions must be listed in config/whmcs-actions.php.
 */
final class Whmcs
{
    /**
     * @return array{group:string,write:bool,required:list<string>,sensitive:bool}|null
     */
    public static function definition(string $action): ?array
    {
        $definition = config('whmcs-actions.'.$action);
        if (! is_array($definition)) {
            return null;
        }

        return [
            'group' => (string) ($definition['group'] ?? 'system'),
            'write' => (bool) ($definition['write'] ?? false),
            'required' => array_values((array) ($definition['required'] ?? [])),
            'sensitive' => (bool) ($definition['sensitive'] ?? false),
        ];
    }

    public static function allows(string $action): bool
    {
        return self::definition($action) !== null;
    }

    /**
     * @return list<string>
     */
    public static function actions(?string $group = null): array
    {
        return collect(array_keys((array) config('whmcs-actions', [])))
            ->filter(fn (string $action) => $group === null || self::definition($action)['group'] === $group)
            ->values()
            ->all();
    }

    /**
     * Write actions are recorded in the admin audit log against $admin (defaults to the signed-in admin).
     *
     * @param  array<string, mixed>  $params
     */
    public static function call(string $action, array $params = [], ?User $admin = null): WhmcsResult
    {
        $definition = self::definition($action);
        if ($definition === null) {
            return WhmcsResult::failure("WHMCS action {$action} is not enabled in config/whmcs-actions.php.");
        }

        $missing = array_values(array_filter(
            $definition['required'],
            fn (string $key) => ! array_key_exists($key, $params) || $params[$key] === '' || $params[$key] === null,
        ));
        if ($missing !== []) {
            return WhmcsResult::failure("WHMCS {$action} is missing: ".implode(', ', $missing).'.');
        }

        $json = WhmcsClient::send($action, $params);
        $result = is_array($json) && ($json['result'] ?? null) === 'success'
            ? WhmcsResult::success($json)
            : WhmcsResult::failure(WhmcsClient::lastError() ?: 'WHMCS request failed.', is_array($json) ? $json : []);

        if ($definition['write']) {
            self::audit($action, $definition, $params, $result, $admin ?? AdminPermissions::currentUser());
        }

        return $result;
    }

    /**
     * @param  array{group:string,write:bool,required:list<string>,sensitive:bool}  $definition
     * @param  array<string, mixed>  $params
     */
    private static function audit(string $action, array $definition, array $params, WhmcsResult $result, ?User $admin): void
    {
        if (! $admin) {
            return;
        }

        $subjectKey = collect(['clientid', 'serviceid', 'domainid', 'invoiceid', 'orderid', 'ticketid', 'quoteid', 'userid', 'contactid', 'transactionid'])
            ->first(fn (string $key) => isset($params[$key]));

        try {
            AdminAudit::record(
                'whmcs.'.$action,
                $admin,
                null,
                $definition['sensitive'] ? ['redacted' => true] : AdminAudit::redact($params) + ['error' => $result->error],
                $result->ok ? 200 : 422,
                $subjectKey ? 'whmcs_'.$definition['group'] : null,
                $subjectKey ? (string) $params[$subjectKey] : null,
            );
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
