<?php

namespace App\Http\Controllers;

use App\Support\Cart;
use App\Support\DomainName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(): View
    {
        $refresh = Cart::refreshQuotes();
        $items = Cart::items();
        $totals = Cart::totals();

        return view('pages.cart', [
            'items' => $items,
            'totals' => $totals,
            'refreshError' => ($refresh['ok'] ?? false) ? null : ($refresh['message'] ?? null),
        ]);
    }

    public function addDomain(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
            'option' => ['required', 'string', 'in:register,transfer'],
            'reg_period' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $result = Cart::addDomain(
            (string) $validated['domain'],
            (string) $validated['option'],
            (int) ($validated['reg_period'] ?? 1),
        );

        return $this->respondAdd($request, $result, 'domain');
    }

    public function addDomainRedirect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
            'option' => ['required', 'string', 'in:register,transfer'],
            'reg_period' => ['nullable', 'integer', 'min:1', 'max:10'],
            'buy' => ['nullable', 'boolean'],
        ]);

        $domain = DomainName::normalize((string) $validated['domain']);
        if ($domain === null) {
            return redirect()
                ->route('domain')
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => __('domain.invalid_domain'),
                ]);
        }

        $result = Cart::addDomain(
            $domain,
            (string) $validated['option'],
            (int) ($validated['reg_period'] ?? 1),
        );

        if (! ($result['ok'] ?? false)) {
            $already = collect(Cart::itemsOfType(Cart::TYPE_DOMAIN))->contains(
                fn (array $item) => strcasecmp((string) ($item['domain'] ?? ''), $domain) === 0
            );
            if (! $already) {
                return redirect()
                    ->route('domain', ['tab' => $validated['option']])
                    ->with('cart_feedback', [
                        'type' => 'error',
                        'message' => (string) ($result['message'] ?? __('domain.quote_unavailable')),
                    ]);
            }
        }

        if ($request->boolean('buy')) {
            return redirect()->route('checkout');
        }

        return redirect()
            ->route('cart')
            ->with('cart_feedback', [
                'type' => 'success',
                'message' => (string) ($result['message'] ?? __('domain.cart_added', ['domain' => $domain])),
            ]);
    }

    public function addEmail(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:80'],
            'billing_cycle' => ['nullable', 'string', 'max:40'],
        ]);

        $result = Cart::addEmail(
            (string) $validated['plan'],
            (string) ($validated['billing_cycle'] ?? 'monthly'),
        );

        if (! ($result['ok'] ?? false) && str_contains((string) ($result['message'] ?? ''), 'manual')) {
            return redirect()->route('email.checkout', [
                'plan' => $validated['plan'],
                'billing_cycle' => $validated['billing_cycle'] ?? 'monthly',
            ]);
        }

        return $this->respondAdd($request, $result, 'email');
    }

    public function addHosting(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:40'],
            'spec' => ['nullable'],
            'spec.*' => ['nullable', 'string', 'max:80'],
            'billing_cycle' => ['nullable', 'string', 'max:40'],
        ]);

        $specs = $request->input('spec', []);
        if (! is_array($specs)) {
            $specs = filled($specs) ? [$specs] : [];
        }
        $specs = array_values(array_filter(array_map(
            static fn ($value) => strtolower(trim((string) $value)),
            $specs,
        ), static fn (string $value) => $value !== ''));

        if ($specs === []) {
            return redirect()
                ->back()
                ->with('hosting_feedback', [
                    'type' => 'error',
                    'message' => __('cart.hosting_invalid_spec'),
                ]);
        }

        $last = ['ok' => false, 'message' => __('cart.add_failed')];
        foreach ($specs as $specKey) {
            $last = Cart::addHosting(
                (string) $validated['plan'],
                $specKey,
                (string) ($validated['billing_cycle'] ?? 'monthly'),
            );
            if (! ($last['ok'] ?? false) && $request->expectsJson()) {
                return response()->json($last, 422);
            }
        }

        return $this->respondAdd($request, $last, 'hosting');
    }

    public function update(Request $request, string $item): JsonResponse
    {
        $validated = $request->validate([
            'reg_period' => ['nullable', 'integer', 'min:1', 'max:10'],
            'domain' => ['nullable', 'string', 'max:253'],
            'mailboxes' => ['nullable', 'array'],
            'mailboxes.*' => ['string', 'max:64'],
            'hostname' => ['nullable', 'string', 'max:253'],
            'domain_option' => ['nullable', 'string', 'in:register,transfer,owndomain'],
        ]);

        $result = Cart::updateItem($item, $validated);

        return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
    }

    public function destroy(string $item): JsonResponse|RedirectResponse
    {
        $result = Cart::remove($item);

        if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
            return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
        }

        return redirect()
            ->route('cart')
            ->with('cart_feedback', [
                'type' => ($result['ok'] ?? false) ? 'success' : 'error',
                'message' => (string) ($result['message'] ?? __('cart.removed')),
            ]);
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'count' => Cart::count(),
            'totals' => Cart::totals(),
        ]);
    }

    /**
     * @param  array{ok?: bool, message?: string, count?: int}  $result
     */
    protected function respondAdd(Request $request, array $result, string $fallback): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json($result, ($result['ok'] ?? false) ? 200 : 422);
        }

        if (! ($result['ok'] ?? false)) {
            $route = match ($fallback) {
                'email' => 'email.plans',
                'hosting' => 'cloud-hosting',
                default => 'domain',
            };

            return redirect()
                ->route($route)
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => (string) ($result['message'] ?? __('cart.add_failed')),
                ]);
        }

        return redirect()
            ->route('cart')
            ->with('cart_feedback', [
                'type' => 'success',
                'message' => (string) ($result['message'] ?? __('cart.added')),
            ]);
    }
}
