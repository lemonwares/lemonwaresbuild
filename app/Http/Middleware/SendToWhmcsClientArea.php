<?php

namespace App\Http\Middleware;

use App\Support\WhmcsClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Customers linked to a WHMCS client manage services, invoices and tickets in the
 * (LemonWares-themed) WHMCS client area, signed in automatically. Others keep the site pages.
 *
 * Usage: ->middleware('whmcs.client-area:invoices') or 'whmcs.client-area:invoice,invoice'
 * (second argument = route parameter holding the WHMCS id).
 */
class SendToWhmcsClientArea
{
    public const PATHS = [
        'home' => 'clientarea.php',
        'services' => 'clientarea.php?action=services',
        'service' => 'clientarea.php?action=productdetails&id=',
        'domains' => 'clientarea.php?action=domains',
        'domain' => 'clientarea.php?action=domaindetails&id=',
        'invoices' => 'clientarea.php?action=invoices',
        'invoice' => 'viewinvoice.php?id=',
        'tickets' => 'supporttickets.php',
        'new-ticket' => 'submitticket.php',
    ];

    public function handle(Request $request, Closure $next, string $section, ?string $parameter = null): Response
    {
        if (! config('site.whmcs.client_area_redirect') || $request->isMethod('post') || ! isset(self::PATHS[$section])) {
            return $next($request);
        }

        $clientId = (int) ($request->user()?->accountOwner()->whmcsCustomer?->whmcs_client_id ?? 0);
        if ($clientId < 1) {
            return $next($request);
        }

        $path = self::PATHS[$section];
        if ($parameter !== null) {
            $value = $request->route($parameter);
            $id = (int) (is_object($value) ? ($value->whmcs_service_id ?? $value->getKey()) : preg_replace('/\D+/', '', (string) $value));
            if ($id < 1) {
                return $next($request);
            }
            $path .= $id;
        }

        $sso = WhmcsClient::createSsoToken($clientId, $path);
        $url = (string) ($sso['redirect_url'] ?? '');

        return $url !== '' ? redirect()->away($url) : $next($request);
    }
}
