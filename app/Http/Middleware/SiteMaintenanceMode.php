<?php

namespace App\Http\Middleware;

use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-controlled maintenance mode. Visitors get a 503 page; the admin panel,
 * signed-in admins and payment webhooks keep working.
 */
class SiteMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteSettings::maintenanceEnabled()) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'webhooks/*', 'up') || $request->session()->get('admin_authenticated')) {
            return $next($request);
        }

        return response()->view('maintenance-mode', [
            'message' => SiteSettings::get('maintenance.message', 'We are making some improvements and will be back shortly.'),
        ], 503, ['Retry-After' => '1800']);
    }
}
