<?php

namespace App\Http\Middleware;

use App\Support\AdminAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs every admin request that changes something (anything but GET/HEAD/OPTIONS).
 */
class RecordAdminAudit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true) && $request->route()?->getName() !== 'admin.logout') {
            AdminAudit::recordRequest($request, $response->getStatusCode());
        }

        return $response;
    }
}
