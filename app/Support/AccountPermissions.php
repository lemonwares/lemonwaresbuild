<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AccountPermissions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        /** @var array<string, string> $permissions */
        $permissions = config('account.permissions', []);

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function forRoute(?string $routeName): ?string
    {
        if (! is_string($routeName) || (! str_starts_with($routeName, 'account.') && ! str_starts_with($routeName, 'email.'))) {
            return null;
        }

        /** @var array<string, string> $map */
        $map = config('account.route_permissions', []);

        foreach ($map as $prefix => $permission) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $permission;
            }
        }

        return 'overview';
    }

    public static function currentCan(string $permission): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAccountPermission($permission);
    }

    public static function abortUnlessCurrentCan(?string $permission): void
    {
        if ($permission === null) {
            return;
        }

        abort_unless(self::currentCan($permission), 403, 'You do not have access to this area.');
    }

    public static function abortUnlessRouteAllowed(?string $routeName = null): void
    {
        $routeName ??= Route::currentRouteName();
        self::abortUnlessCurrentCan(self::forRoute($routeName));
    }
}
