<?php

namespace App\Http\Controllers;

use App\Models\HostingLead;
use App\Models\SiteCheckout;
use App\Models\SiteCheckoutItem;
use App\Models\User;
use App\Support\Cart;
use App\Support\DomainName;
use App\Support\FlutterwavePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $refresh = Cart::refreshQuotes();
        if (! ($refresh['ok'] ?? false) || Cart::isEmpty()) {
            return redirect()
                ->route('cart')
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => (string) ($refresh['message'] ?? __('cart.empty')),
                ]);
        }

        $items = Cart::items();
        $totals = Cart::totals();
        $user = $request->user();

        $guestAccountStatus = 'pending';
        if (! $user && old('email')) {
            $guestAccountStatus = $this->guestStatusForEmail((string) old('email'));
        }

        return view('pages.checkout', [
            'items' => $items,
            'totals' => $totals,
            'authUser' => $user,
            'countryOptions' => config('site.country_options', []),
            'guestAccountStatus' => $guestAccountStatus,
            'loginUrl' => route('login', ['redirect' => '/checkout']),
            'priceDisplay' => $totals['display'],
            'hasTransfer' => collect($items)->contains(
                fn (array $item) => ($item['type'] ?? '') === Cart::TYPE_DOMAIN && ($item['option'] ?? '') === 'transfer'
            ),
            'hasEmail' => collect($items)->contains(fn (array $item) => ($item['type'] ?? '') === Cart::TYPE_EMAIL),
            'hasHosting' => collect($items)->contains(fn (array $item) => ($item['type'] ?? '') === Cart::TYPE_HOSTING),
        ]);
    }

    public function accountStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ]);

        return response()->json([
            'status' => $this->guestStatusForEmail((string) $validated['email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $refresh = Cart::refreshQuotes();
        if (! ($refresh['ok'] ?? false) || Cart::isEmpty()) {
            return redirect()
                ->route('cart')
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => (string) ($refresh['message'] ?? __('cart.empty')),
                ]);
        }

        $items = Cart::items();
        $extraRules = [];

        foreach ($items as $item) {
            $id = (string) $item['id'];
            $type = (string) ($item['type'] ?? '');

            if ($type === Cart::TYPE_DOMAIN && ($item['option'] ?? '') === 'transfer') {
                $extraRules['epp.'.$id] = ['required', 'string', 'max:120'];
            }

            if ($type === Cart::TYPE_EMAIL) {
                $extraRules['email_domain.'.$id] = ['required', 'string', 'max:190'];
                $count = max(1, (int) ($item['mailbox_count'] ?? 1));
                $extraRules['mailboxes.'.$id] = ['required', 'array', 'size:'.$count];
                $extraRules['mailboxes.'.$id.'.*'] = ['required', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/i'];
            }

            if ($type === Cart::TYPE_HOSTING && ($item['checkout_provider'] ?? '') === 'whmcs') {
                $extraRules['hostname.'.$id] = ['required', 'string', 'max:253'];
                $extraRules['domain_option.'.$id] = ['nullable', 'string', 'in:register,transfer,owndomain'];
            }
        }

        $countries = array_keys(config('site.country_options', []));
        $isGuest = ! $request->user();
        $guestStatus = $isGuest
            ? $this->guestStatusForEmail((string) $request->input('email', ''))
            : 'authenticated';

        if ($isGuest && $guestStatus === 'existing') {
            throw ValidationException::withMessages([
                'email' => __('cart.checkout_sign_in_required'),
            ]);
        }

        $guestRules = $isGuest
            ? [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:160'],
                'password' => ['required', 'string', 'confirmed', Password::min(8)],
            ]
            : [
                'name' => ['required', 'string', 'max:120'],
            ];

        $billingRules = [
            'company' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'billing_address_line_1' => ['required', 'string', 'max:190'],
            'billing_address_line_2' => ['nullable', 'string', 'max:190'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_state' => ['required', 'string', 'max:120'],
            'billing_postcode' => ['required', 'string', 'max:40'],
            'billing_country' => ['required', 'string', 'size:2', Rule::in($countries)],
            'shipping_same_as_billing' => ['nullable', 'boolean'],
            'shipping_address_line_1' => ['nullable', 'string', 'max:190'],
            'shipping_address_line_2' => ['nullable', 'string', 'max:190'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:120'],
            'shipping_postcode' => ['nullable', 'string', 'max:40'],
            'shipping_country' => ['nullable', 'string', 'size:2', Rule::in($countries)],
        ];

        $payload = $request->validate(array_merge([
            'epp' => ['nullable', 'array'],
            'email_domain' => ['nullable', 'array'],
            'mailboxes' => ['nullable', 'array'],
            'hostname' => ['nullable', 'array'],
            'domain_option' => ['nullable', 'array'],
        ], $guestRules, $billingRules, $extraRules));

        $shippingSame = $request->boolean('shipping_same_as_billing', true);
        $payload['shipping_same_as_billing'] = $shippingSame;

        if (! $shippingSame) {
            $request->validate([
                'shipping_address_line_1' => ['required', 'string', 'max:190'],
                'shipping_city' => ['required', 'string', 'max:120'],
                'shipping_state' => ['required', 'string', 'max:120'],
                'shipping_postcode' => ['required', 'string', 'max:40'],
                'shipping_country' => ['required', 'string', 'size:2', Rule::in($countries)],
            ]);
            $payload['shipping_address_line_1'] = (string) $request->input('shipping_address_line_1');
            $payload['shipping_address_line_2'] = (string) $request->input('shipping_address_line_2', '');
            $payload['shipping_city'] = (string) $request->input('shipping_city');
            $payload['shipping_state'] = (string) $request->input('shipping_state');
            $payload['shipping_postcode'] = (string) $request->input('shipping_postcode');
            $payload['shipping_country'] = strtoupper((string) $request->input('shipping_country'));
        }

        foreach ($items as $item) {
            $id = (string) $item['id'];
            if (($item['type'] ?? '') === Cart::TYPE_EMAIL) {
                $normalized = DomainName::normalize((string) ($payload['email_domain'][$id] ?? ''));
                if ($normalized === null) {
                    throw ValidationException::withMessages([
                        'email_domain.'.$id => __('email.invalid_domain'),
                    ]);
                }
                $payload['email_domain'][$id] = $normalized;
            }
            if (($item['type'] ?? '') === Cart::TYPE_HOSTING && ($item['checkout_provider'] ?? '') === 'whmcs') {
                $normalized = DomainName::normalize((string) ($payload['hostname'][$id] ?? ''));
                if ($normalized === null) {
                    throw ValidationException::withMessages([
                        'hostname.'.$id => __('hosting.domain_invalid'),
                    ]);
                }
                $payload['hostname'][$id] = $normalized;
            }
        }

        $user = $request->user() ?? $this->createGuestCheckoutUser($request, $payload);
        $user->fillBillingFromCheckout($payload);

        if (! $user->fresh()->hasCheckoutBillingProfile()) {
            throw ValidationException::withMessages([
                'billing_address_line_1' => __('cart.checkout_billing_required'),
            ]);
        }

        $shippingAddress = $shippingSame
            ? null
            : [
                'address_line_1' => (string) ($payload['shipping_address_line_1'] ?? ''),
                'address_line_2' => (string) ($payload['shipping_address_line_2'] ?? ''),
                'city' => (string) ($payload['shipping_city'] ?? ''),
                'state' => (string) ($payload['shipping_state'] ?? ''),
                'postcode' => (string) ($payload['shipping_postcode'] ?? ''),
                'country' => strtoupper((string) ($payload['shipping_country'] ?? '')),
            ];

        $totals = Cart::totals();
        $eppMap = is_array($payload['epp'] ?? null) ? $payload['epp'] : [];
        $emailDomains = is_array($payload['email_domain'] ?? null) ? $payload['email_domain'] : [];
        $mailboxes = is_array($payload['mailboxes'] ?? null) ? $payload['mailboxes'] : [];
        $hostnames = is_array($payload['hostname'] ?? null) ? $payload['hostname'] : [];
        $domainOptions = is_array($payload['domain_option'] ?? null) ? $payload['domain_option'] : [];

        $checkout = DB::transaction(function () use (
            $request,
            $user,
            $items,
            $totals,
            $eppMap,
            $emailDomains,
            $mailboxes,
            $hostnames,
            $domainOptions,
            $shippingSame,
            $shippingAddress
        ) {
            $checkout = SiteCheckout::create([
                'user_id' => $user->id,
                'item_count' => count($items),
                'amount_usd' => $totals['amount_usd'],
                'amount_ngn' => $totals['amount_ngn'],
                'status' => 'awaiting_payment',
                'ip_address' => $request->ip(),
                'shipping_same_as_billing' => $shippingSame,
                'shipping_address' => $shippingAddress,
                'billing_snapshot' => $user->billingSnapshot(),
            ]);

            foreach ($items as $item) {
                $type = (string) ($item['type'] ?? Cart::TYPE_DOMAIN);
                $id = (string) $item['id'];
                $linePayload = $item;

                if ($type === Cart::TYPE_DOMAIN) {
                    $linePayload['epp_code'] = (($item['option'] ?? '') === 'transfer')
                        ? trim((string) ($eppMap[$id] ?? ''))
                        : null;
                }

                if ($type === Cart::TYPE_EMAIL) {
                    $linePayload['domain'] = (string) ($emailDomains[$id] ?? '');
                    $linePayload['mailboxes'] = array_values(array_map(
                        fn ($part) => strtolower(trim((string) $part)),
                        is_array($mailboxes[$id] ?? null) ? $mailboxes[$id] : [],
                    ));
                }

                if ($type === Cart::TYPE_HOSTING) {
                    $linePayload['hostname'] = (string) ($hostnames[$id] ?? '');
                    $linePayload['domain_option'] = (string) ($domainOptions[$id] ?? 'register');
                }

                unset($linePayload['id']);

                SiteCheckoutItem::create([
                    'site_checkout_id' => $checkout->id,
                    'type' => $type,
                    'label' => (string) ($item['label'] ?? $item['domain'] ?? 'Item'),
                    'amount_usd' => (float) ($item['amount_usd'] ?? 0),
                    'amount_ngn' => (float) ($item['amount_ngn'] ?? 0),
                    'payload' => $linePayload,
                ]);
            }

            return $checkout->fresh(['items', 'user']);
        });

        Cart::clear();

        $link = FlutterwavePayment::createSiteCheckoutPaymentLink($checkout);

        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->route('checkout.received', $checkout)
            ->with('cart_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }

    public function received(Request $request, SiteCheckout $checkout): View
    {
        $checkout->load(['items', 'user']);

        return view('pages.checkout-received', [
            'checkout' => $checkout,
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $txRef = (string) $request->query('tx_ref', '');
        $transactionId = (string) $request->query('transaction_id', $request->query('id', ''));

        $checkout = SiteCheckout::query()->where('payment_reference', $txRef)->first();

        if (! $checkout) {
            return redirect()
                ->route('cart')
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_not_found'),
                ]);
        }

        $receivedRoute = route('checkout.received', $checkout);

        if ($checkout->isPaid()) {
            return redirect()
                ->to($receivedRoute)
                ->with('cart_feedback', [
                    'type' => 'success',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        if ($transactionId === '') {
            return redirect()
                ->to($receivedRoute)
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_incomplete'),
                ]);
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);
        if (! $verified) {
            return redirect()
                ->to($receivedRoute)
                ->with('cart_feedback', [
                    'type' => 'error',
                    'message' => __('domain.payment_verify_failed'),
                ]);
        }

        $result = FlutterwavePayment::confirmSiteCheckoutPayment($checkout->fresh(['items', 'user']), $verified);

        return redirect()
            ->to($receivedRoute)
            ->with('cart_feedback', [
                'type' => ($result['ok'] ?? false) ? 'success' : 'error',
                'message' => (string) ($result['message'] ?? __('domain.payment_incomplete')),
            ]);
    }

    public function pay(Request $request, SiteCheckout $checkout): RedirectResponse
    {
        if ($checkout->isPaid()) {
            return redirect()
                ->route('checkout.received', $checkout)
                ->with('cart_feedback', [
                    'type' => 'info',
                    'message' => __('domain.payment_already_confirmed'),
                ]);
        }

        $link = FlutterwavePayment::createSiteCheckoutPaymentLink($checkout);
        if ($link) {
            return redirect()->away($link);
        }

        return redirect()
            ->route('checkout.received', $checkout)
            ->with('cart_feedback', [
                'type' => 'error',
                'message' => __('domain.payment_unavailable'),
            ]);
    }

    private function guestStatusForEmail(string $email): string
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'pending';
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user || $user->isAdmin()) {
            return 'new';
        }

        return 'existing';
    }

    /**
     * @param  array{name:string,email:string,password:string}  $payload
     */
    private function createGuestCheckoutUser(Request $request, array $payload): User
    {
        $email = strtolower((string) $payload['email']);
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if ($existing->isAdmin()) {
                throw ValidationException::withMessages([
                    'email' => __('domain.checkout_use_customer_account'),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => __('cart.checkout_sign_in_required'),
            ]);
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
