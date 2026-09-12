<?php

namespace App\Http\Controllers;

use App\Support\FlutterwavePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlutterwaveWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! FlutterwavePayment::verifyWebhookSignature($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $result = FlutterwavePayment::handleWebhookPayload($request->all());

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }
}
