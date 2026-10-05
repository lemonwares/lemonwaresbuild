<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AdminPermissions
{
    /**
     * Access levels. A bare permission key in a staff list means "full".
     */
    public const LEVELS = [
        'view' => 1,
        'edit' => 2,
        'full' => 3,
    ];

    public const LEVEL_LABELS = [
        'view' => 'View only',
        'edit' => 'View + edit',
        'full' => 'Full (incl. delete)',
    ];
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

    public static function currentCan(string $permission, string $level = 'view'): bool
    {
        $user = self::currentUser();

        return $user?->hasAdminPermission($permission, $level) ?? false;
    }

    public static function abortUnlessCurrentCan(?string $permission, string $level = 'view'): void
    {
        if ($permission === null) {
            return;
        }

        if (! self::currentCan($permission, 'view')) {
            abort(403, 'You do not have access to this area.');
        }

        abort_unless(
            self::currentCan($permission, $level),
            403,
            $level === 'full'
                ? 'Your access to this area does not include deleting.'
                : 'Your access to this area is view only.'
        );
    }

    public static function abortUnlessRouteAllowed(?string $routeName = null, string $method = 'GET'): void
    {
        $routeName ??= Route::currentRouteName();
        self::abortUnlessCurrentCan(self::forRoute($routeName), self::levelForMethod($method));
    }

    /**
     * Reading needs "view", changing needs "edit", deleting needs "full".
     */
    public static function levelForMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'GET', 'HEAD', 'OPTIONS' => 'view',
            'DELETE' => 'full',
            default => 'edit',
        };
    }

    /**
     * Turns form input [key => none|view|edit|full] into stored entries.
     *
     * @param  array<string, mixed>  $levels
     * @return list<string>
     */
    public static function entriesFromLevels(array $levels): array
    {
        $entries = [];
        foreach (self::keys() as $key) {
            $level = (string) ($levels[$key] ?? 'none');
            if ($level === 'full') {
                $entries[] = $key;
            } elseif (in_array($level, ['view', 'edit'], true)) {
                $entries[] = $key.':'.$level;
            }
        }

        return $entries;
    }

    /**
     * @param  list<string>|null  $entries
     * @return array<string, string>
     */
    public static function levelsFromEntries(?array $entries): array
    {
        $levels = [];
        foreach ((array) $entries as $entry) {
            [$key, $level] = array_pad(explode(':', (string) $entry, 2), 2, 'full');
            if (! isset($levels[$key]) || (self::LEVELS[$level] ?? 0) > (self::LEVELS[$levels[$key]] ?? 0)) {
                $levels[$key] = $level;
            }
        }

        return $levels;
    }
}
