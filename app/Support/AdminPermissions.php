<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminPermissions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        /** @var array<string, string> $permissions */
        $permissions = config('admin.permissions', []);

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
        if (! is_string($routeName) || ! str_starts_with($routeName, 'admin.')) {
            return null;
        }

        if (in_array($routeName, ['admin.login', 'admin.login.submit', 'admin.logout'], true)) {
            return null;
        }

        /** @var array<string, string> $map */
        $map = config('admin.route_permissions', []);

        foreach ($map as $prefix => $permission) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return $permission;
            }
        }

        return 'dashboard';
    }

    public static function currentUser(): ?User
    {
        $id = (int) session('admin_user_id');
        if ($id < 1) {
            return null;
        }

        return User::query()->find($id);
    }

    public static function currentCan(string $permission): bool
    {
        $user = self::currentUser();

        return $user?->hasAdminPermission($permission) ?? false;
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
