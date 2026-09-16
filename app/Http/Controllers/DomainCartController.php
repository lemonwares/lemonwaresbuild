<?php

namespace App\Http\Controllers;

use App\Support\Cart;
use App\Support\DomainName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Legacy domain cart routes — redirect / delegate to unified Cart.
 */
class DomainCartController extends Controller
{
    public function show(): RedirectResponse
    {
        return redirect()->route('cart');
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        return app(CartController::class)->addDomain($request);
    }

    public function addRedirect(Request $request): RedirectResponse
    {
        return app(CartController::class)->addDomainRedirect($request);
    }

    public function update(Request $request, string $item): JsonResponse
    {
        return app(CartController::class)->update($request, $item);
    }

    public function destroy(string $item): JsonResponse|RedirectResponse
    {
        return app(CartController::class)->destroy($item);
    }

    public function count(): JsonResponse
    {
        return app(CartController::class)->count();
    }
}
