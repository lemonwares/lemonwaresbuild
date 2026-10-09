<?php

namespace App\Http\Middleware;

use App\Support\AdminPermissions;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Auth;

/**
 * The console has no login of its own: it reuses the /admin session.
 * Access to the panel itself is decided by User::canAccessPanel().
 */
class AuthenticateConsoleFromAdminSession extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        if ($request->session()->get('admin_authenticated', false)) {
            $admin = AdminPermissions::currentUser();
            if ($admin?->isAdmin()) {
                Auth::guard('console')->setUser($admin);
            }
        }

        parent::authenticate($request, $guards);
    }

    protected function redirectTo($request): ?string
    {
        return route('admin.login');
    }
}
