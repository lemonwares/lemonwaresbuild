<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out customers an admin has suspended, including sessions that were already open.
 * Admins viewing the account through "log in as customer" are let through.
 */
class BlockSuspendedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isSuspended() && ! $request->session()->has('impersonator_admin_id')) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('account.account_suspended')]);
        }

        return $next($request);
    }
}
