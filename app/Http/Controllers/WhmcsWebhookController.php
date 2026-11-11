<?php

namespace App\Http\Controllers;

use App\Support\Whmcs\WhmcsMirror;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Receives change notifications from the WHMCS hook and refreshes that client's local mirror.
 */
class WhmcsWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('site.whmcs.webhook_secret');
        $signature = (string) $request->header('X-Lemonwares-Signature', '');

        if ($secret === '' || ! hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature)) {
            return response()->json(['ok' => false], 401);
        }

        $sentAt = (int) $request->input('ts', 0);
        if (abs(time() - $sentAt) > 300) {
            return response()->json(['ok' => false, 'error' => 'stale'], 401);
        }

        $clientId = (int) $request->input('client_id', 0);
        if ($clientId < 1) {
            return response()->json(['ok' => true, 'skipped' => true]);
        }

        // Several hooks fire for one action (e.g. order accepted + module create); sync once.
        if (Cache::add('whmcs-webhook:'.$clientId, true, 10)) {
            $event = (string) $request->input('event', '');
            dispatch(function () use ($clientId, $event): void {
                if (! WhmcsMirror::syncClient($clientId)) {
                    Log::warning('WHMCS webhook sync failed', ['client_id' => $clientId, 'event' => $event]);
                }
            })->afterResponse();
        }

        return response()->json(['ok' => true]);
    }
}
