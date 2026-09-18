<?php

namespace App\Http\Controllers;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\HostingLead;
use App\Models\User;
use App\Support\DomainCart;
use App\Support\FlutterwavePayment;
use App\Support\HostingPricing;
use App\Support\WhmcsDomainOrderSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DomainOrderController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        // Unified checkout owns the cart payment flow.
        if ($request->filled('domain')) {
            return redirect()->route('cart.domain.add-redirect', [
                'domain' => $request->query('domain'),
                'option' => $request->query('option', 'register'),
                'reg_period' => $request->query('reg_period', 1),
                'buy' => 1,
            ]);
        }

        return redirect()->route('checkout');
    }

    public function accountStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        $email = strtolower((string) $validated['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user || $user->isAdmin()) {
            return response()->json(['status' => 'new']);
        }

        return response()->json([
            'status' => $user->hasLeanBusinessProfile()
                ? 'existing_complete'
                : 'existing_incomplete',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $refresh = DomainCart::refreshQuotes();
        if (! ($refresh['ok'] ?? false) || DomainCart::isEmpty()) {
            return redirect()
                ->route('domain.cart')
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => (string) ($refresh['message'] ?? __('domain.cart_empty_checkout')),
                ]);
        }

        $items = DomainCart::items();
        $eppRules = [];
        foreach ($items as $item) {
            if (($item['option'] ?? '') !== 'transfer') {
                continue;
            }
            $key = 'epp.'.$item['id'];
            $eppRules[$key] = ['required', 'string', 'max:120'];
        }

        $guestRules = $request->user()
            ? []
            : [
                'name' => [
                    Rule::requiredIf(function () use ($request) {
                        $email = strtolower((string) $request->input('email', ''));
                        if ($email === '') {
                            return true;
                        }

                        $existing = User::query()->where('email', $email)->first();

                        return ! $existing || $existing->isAdmin();
                    }),
                    'nullable',
                    'string',
                    'max:120',
                ],
                'email' => ['required', 'email', 'max:160'],
                'password' => ['required', 'string', Password::min(8)],
            ];

        $payload = $request->validate(array_merge([
            'epp' => ['nullable', 'array'],
            'company' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'billing_country' => ['nullable', 'string', 'max:2'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_address_line_1' => ['nullable', 'string', 'max:190'],
        ], $guestRules, $eppRules));

        if ($this->checkoutNeedsBusinessFields($request, $payload)) {
            $request->validate([
                'company' => ['required', 'string', 'max:160'],
                'phone' => ['required', 'string', 'max:40'],
                'billing_country' => ['required', 'string', 'size:2'],
            ]);
        }

        $user = $request->user() ?? $this->resolveGuestCheckoutUser($request, $payload);
        $user->fillLeanBusinessFromCheckout($payload);

        if (! $user->fresh()->hasLeanBusinessProfile()) {
            throw ValidationException::withMessages([
                'company' => __('domain.checkout_business_required'),
            ]);
        }

        $totals = DomainCart::totals();
        $eppMap = is_array($payload['epp'] ?? null) ? $payload['epp'] : [];

        $checkout = DB::transaction(function () use ($request, $user, $items, $totals, $eppMap) {
            $checkout = DomainCheckout::create([
                'user_id' => $user->id,
                'item_count' => count($items),
                'amount_usd' => $totals['amount_usd'],
                'amount_ngn' => $totals['amount_ngn'],
                'status' => 'awaiting_payment',
                'ip_address' => $request->ip(),
            ]);

            foreach ($items as $item) {
                $option = (string) $item['option'];
                DomainOrder::create([
                    'user_id' => $user->id,
                    'domain_checkout_id' => $checkout->id,
                    'domain' => (string) $item['domain'],
                    'option' => $option,
                    'epp_code' => $option === 'transfer'
                        ? trim((string) ($eppMap[$item['id']] ?? ''))
                        : null,
                    'reg_period' => (int) ($item['reg_period'] ?? 1),
                    'amount_usd' => (float) ($item['amount_usd'] ?? 0),
                    'amount_ngn' => (float) ($item['amount_ngn'] ?? 0),
                    'status' => 'awaiting_payment',
                    'ip_address' => $request->ip(),
                ]);
            }

            return $checkout->fresh(['orders', 'user']);
        });

        $checkout = WhmcsDomainOrderSync::syncCheckoutBundle($checkout);

        if ((string) $checkout->whmcs_sync_status !== 'checkout_synced' || ! $checkout->whmcs_order_id) {
            return redirect()
                ->route('domain.checkout')
                ->withInput()
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.sync_failed'),
                ]);
        }

        DomainCart::clear();

        $link = FlutterwavePayment::createDomainCheckoutPaymentLink($checkout);

        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->route('domain.checkout-received', $checkout)
            ->with('domain_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }

    public function receivedCheckout(Request $request, DomainCheckout $checkout): View
    {
        $checkout->load(['orders', 'user']);

        return view('pages.domain-order-received', [
            'checkout' => $checkout,
            'order' => $checkout->orders->first(),
        ]);
    }

    public function received(Request $request, DomainOrder $order): View|RedirectResponse
    {
        if ($order->domain_checkout_id) {
            return redirect()->route('domain.checkout-received', $order->domain_checkout_id);
        }

        return view('pages.domain-order-received', [
            'checkout' => null,
            'order' => $order,
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $txRef = (string) $request->query('tx_ref', '');
        $transactionId = (string) $request->query('transaction_id', $request->query('id', ''));

        $checkout = DomainCheckout::query()->where('payment_reference', $txRef)->first();
        $order = $checkout ? null : DomainOrder::query()->where('payment_reference', $txRef)->first();

        if (! $checkout && ! $order) {
            return redirect()
                ->route('domain')
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_not_found'),
                ]);
        }

        $receivedRoute = $checkout
            ? route('domain.checkout-received', $checkout)
            : route('domain.order-received', $order);

        if (($checkout && $checkout->isPaid()) || ($order && $order->isPaid())) {
            return redirect()
                ->to($receivedRoute)
                ->with('domain_feedback', [
                    'type' => 'success',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        if ($transactionId === '') {
            return redirect()
                ->to($receivedRoute)
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_incomplete'),
                ]);
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);
        if (! $verified) {
            return redirect()
                ->to($receivedRoute)
                ->with('domain_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_verify_failed'),
                ]);
        }

        $result = $checkout
            ? FlutterwavePayment::confirmDomainCheckoutPayment($checkout->fresh(['orders', 'user']), $verified)
            : FlutterwavePayment::confirmDomainOrderPayment($order->fresh(), $verified);

        return redirect()
            ->to($receivedRoute)
            ->with('domain_feedback', [
                'type' => ($result['ok'] ?? false) ? 'success' : 'error',
                'message' => (string) ($result['message'] ?? __('domain.payment_incomplete')),
            ]);
    }

    public function payCheckout(Request $request, DomainCheckout $checkout): RedirectResponse
    {
        if ($checkout->isPaid()) {
            return redirect()
                ->route('domain.checkout-received', $checkout)
                ->with('domain_feedback', [
                    'type' => 'info',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        if ((string) $checkout->whmcs_sync_status !== 'checkout_synced' || ! $checkout->whmcs_order_id) {
            $checkout = WhmcsDomainOrderSync::syncCheckoutBundle($checkout->fresh(['orders', 'user']));
            if ((string) $checkout->whmcs_sync_status !== 'checkout_synced') {
                return redirect()
                    ->route('domain.checkout-received', $checkout)
                    ->with('domain_feedback', [
                        'type' => 'error',
                        'message' => __('domain.sync_failed'),
                    ]);
            }
        }

        $link = FlutterwavePayment::createDomainCheckoutPaymentLink($checkout);
        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->route('domain.checkout-received', $checkout)
            ->with('domain_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }

    public function pay(Request $request, DomainOrder $order): RedirectResponse
    {
        if ($order->domain_checkout_id) {
            return $this->payCheckout($request, DomainCheckout::query()->findOrFail($order->domain_checkout_id));
        }

        if ($order->isPaid()) {
            return redirect()
                ->route('domain.order-received', $order)
                ->with('domain_feedback', [
                    'type' => 'info',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        $order = WhmcsDomainOrderSync::syncCheckout($order);
        $link = FlutterwavePayment::createDomainPaymentLink($order);
        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->route('domain.order-received', $order)
            ->with('domain_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function checkoutNeedsBusinessFields(Request $request, array $payload): bool
    {
        $user = $request->user();
        if ($user instanceof User) {
            return ! $user->hasLeanBusinessProfile();
        }

        $email = strtolower((string) ($payload['email'] ?? ''));
        if ($email === '') {
            return true;
        }

        $existing = User::query()->where('email', $email)->first();
        if (! $existing || $existing->isAdmin()) {
            return true;
        }

        return ! $existing->hasLeanBusinessProfile();
    }

    /**
     * @param  array{name?:string,email:string,password:string}  $payload
     */
    private function resolveGuestCheckoutUser(Request $request, array $payload): User
    {
        $email = strtolower((string) $payload['email']);
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if ($existing->isAdmin()) {
                throw ValidationException::withMessages([
                    'email' => __('domain.checkout_use_customer_account'),
                ]);
            }

            if (! Auth::attempt(['email' => $email, 'password' => $payload['password']], true)) {
                throw ValidationException::withMessages([
                    'password' => __('domain.checkout_existing_account'),
                ]);
            }

            $request->session()->regenerate();

            return $existing->fresh() ?? $existing;
        }

        $user = User::create([
            'name' => $payload['name'],
            'email' => $email,
            'role' => 'customer',
            'password' => $payload['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        HostingLead::claimFor($user);

        return $user;
    }
}
