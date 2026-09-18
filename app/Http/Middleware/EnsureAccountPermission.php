<?php

namespace App\Http\Middleware;

use App\Support\AccountPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        AccountPermissions::abortUnlessRouteAllowed($request->route()?->getName());

        return $next($request);
    }
}
