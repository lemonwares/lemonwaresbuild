<?php

namespace App\Support;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\IntegrationSetting;
use App\Models\SiteCheckout;
use App\Notifications\DomainOrderPaid;
use App\Notifications\EmailOrderPaid;
use App\Notifications\EmailOrderRenewed;
use App\Notifications\HostingOrderPaid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwavePayment
{
    public static function isConfigured(): bool
    {
        return FlutterwaveSettings::isConfigured();
    }

    public static function createPaymentLink(HostingLead $lead): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $prefix = $lead->isShared() ? 'LW-HOST-' : 'LW-VPS-';
        $txRef = $lead->payment_reference ?: ($prefix . $lead->id . '-' . Str::upper(Str::random(8)));
        $amountNgn = max(1, (int) round((float) ($lead->amount_ngn ?? 0)));

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amountNgn,
            'currency' => 'NGN',
            'redirect_url' => route('hosting.flutterwave.callback'),
            'payment_options' => 'card,banktransfer,ussd,account',
            'customer' => array_filter([
                'email' => $lead->email,
                'name' => $lead->full_name,
                'phonenumber' => $lead->phone,
            ], fn ($value) => filled($value)),
            'customizations' => self::hostingCustomization($lead),
            'meta' => [
                'lead_id' => $lead->id,
                'plan_slug' => $lead->plan_slug,
                'billing_cycle' => $lead->billing_cycle,
                'checkout_provider' => $lead->checkout_provider,
                'whmcs_order_id' => $lead->whmcs_order_id,
                'whmcs_invoice_id' => $lead->whmcs_invoice_id,
            ],
        ];

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave payment init failed', [
                'lead_id' => $lead->id,
                'body' => $response->json(),
            ]);

            return null;
        }

        $link = data_get($response->json(), 'data.link');

        $lead->update([
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => is_string($link) ? $link : $lead->checkout_url,
            'status' => 'awaiting_payment',
        ]);

        return is_string($link) ? $link : null;
    }

    /**
     * @return array{ok:bool,already_paid?:bool,message:string}
     */
    public static function confirmHostingLeadPayment(HostingLead $lead, array $verified): array
    {
        if ($lead->isPaid()) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('hosting.payment_already_confirmed'),
            ];
        }

        if (! in_array(strtolower((string) data_get($verified, 'status')), ['successful', 'completed'], true)) {
            $lead->update([
                'payment_status' => strtolower((string) data_get($verified, 'status', 'failed')),
                'status' => 'payment_failed',
            ]);

            return [
                'ok' => false,
                'message' => __('hosting.payment_not_completed'),
            ];
        }

        $paidAmount = (float) data_get($verified, 'amount', 0);
        $expected = (float) ($lead->amount_ngn ?? 0);
        $currency = strtoupper((string) data_get($verified, 'currency', ''));

        if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
            $lead->update([
                'payment_status' => 'amount_mismatch',
                'status' => 'payment_failed',
                'flutterwave_transaction_id' => (string) data_get($verified, 'id', ''),
            ]);

            return [
                'ok' => false,
                'message' => __('hosting.payment_amount_mismatch'),
            ];
        }

        $lead->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => (string) data_get($verified, 'id', ''),
        ]);

        if ($lead->checkout_provider === 'whmcs') {
            WhmcsLeadSync::syncPayment($lead->fresh());
        }

        $lead = $lead->fresh(['user']);
        AccountNotifier::send($lead?->user, new HostingOrderPaid($lead));

        return [
            'ok' => true,
            'message' => $lead->isShared()
                ? __('hosting.payment_confirmed_shared')
                : __('hosting.payment_confirmed_vps'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok:bool,message:string}
     */
    public static function handleWebhookPayload(array $payload): array
    {
        $event = strtolower((string) data_get($payload, 'event', ''));
        if ($event !== '' && ! in_array($event, ['charge.completed', 'transfer.completed'], true)) {
            return [
                'ok' => true,
                'message' => 'Ignored unsupported event.',
            ];
        }

        $data = data_get($payload, 'data');
        if (! is_array($data)) {
            return [
                'ok' => false,
                'message' => 'Webhook payload missing data.',
            ];
        }

        $txRef = (string) data_get($data, 'tx_ref', data_get($data, 'txRef', ''));
        $transactionId = (string) data_get($data, 'id', '');

        if ($txRef === '' || $transactionId === '') {
            return [
                'ok' => false,
                'message' => 'Webhook payload missing transaction identifiers.',
            ];
        }

        if (str_starts_with($txRef, 'LW-CART-')) {
            $checkout = SiteCheckout::query()->where('payment_reference', $txRef)->first();
            if (! $checkout) {
                return [
                    'ok' => false,
                    'message' => 'No site checkout matched this payment reference.',
                ];
            }

            $verified = self::verifyTransaction($transactionId);
            if (! $verified) {
                return [
                    'ok' => false,
                    'message' => 'Unable to verify Flutterwave transaction.',
                ];
            }

            $result = self::confirmSiteCheckoutPayment($checkout->fresh(['items', 'user']), $verified);

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'message' => (string) ($result['message'] ?? 'Payment processed.'),
            ];
        }

        if (str_starts_with($txRef, 'LW-MAIL-')) {
            $order = EmailOrder::query()->where('payment_reference', $txRef)->first();
            if (! $order) {
                return [
                    'ok' => false,
                    'message' => 'No email order matched this payment reference.',
                ];
            }

            $verified = self::verifyTransaction($transactionId);
            if (! $verified) {
                return [
                    'ok' => false,
                    'message' => 'Unable to verify Flutterwave transaction.',
                ];
            }

            $result = self::confirmEmailOrderPayment($order->fresh(), $verified);

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'message' => (string) ($result['message'] ?? 'Payment processed.'),
            ];
        }

        if (str_starts_with($txRef, 'LW-DOM-') || str_starts_with($txRef, 'LW-DCART-')) {
            $checkout = DomainCheckout::query()->where('payment_reference', $txRef)->first();
            if ($checkout) {
                $verified = self::verifyTransaction($transactionId);
                if (! $verified) {
                    return [
                        'ok' => false,
                        'message' => 'Unable to verify Flutterwave transaction.',
                    ];
                }

                $result = self::confirmDomainCheckoutPayment($checkout->fresh(['orders', 'user']), $verified);

                return [
                    'ok' => (bool) ($result['ok'] ?? false),
                    'message' => (string) ($result['message'] ?? 'Payment processed.'),
                ];
            }

            $order = DomainOrder::query()->where('payment_reference', $txRef)->first();
            if (! $order) {
                return [
                    'ok' => false,
                    'message' => 'No domain order matched this payment reference.',
                ];
            }

            $verified = self::verifyTransaction($transactionId);
            if (! $verified) {
                return [
                    'ok' => false,
                    'message' => 'Unable to verify Flutterwave transaction.',
                ];
            }

            $result = self::confirmDomainOrderPayment($order->fresh(), $verified);

            return [
                'ok' => (bool) ($result['ok'] ?? false),
                'message' => (string) ($result['message'] ?? 'Payment processed.'),
            ];
        }

        $lead = HostingLead::query()->where('payment_reference', $txRef)->first();
        if (! $lead) {
            return [
                'ok' => false,
                'message' => 'No hosting lead matched this payment reference.',
            ];
        }

        if ($lead->isPaid()) {
            return [
                'ok' => true,
                'message' => 'Payment already processed.',
            ];
        }

        $verified = self::verifyTransaction($transactionId);
        if (! $verified) {
            return [
                'ok' => false,
                'message' => 'Unable to verify Flutterwave transaction.',
            ];
        }

        $result = self::confirmHostingLeadPayment($lead->fresh(), $verified);

        return [
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? 'Payment processed.'),
        ];
    }

    public static function verifyWebhookSignature(Request $request): bool
    {
        $secretHash = trim(FlutterwaveSettings::secretHash());
        if ($secretHash === '') {
            Log::warning('Flutterwave webhook rejected because the webhook secret hash is missing.');

            return false;
        }

        $signature = trim((string) $request->header('verif-hash', ''));

        return hash_equals($secretHash, $signature);
    }

    public static function createDomainPaymentLink(DomainOrder $order): ?string
    {
        if ($order->domain_checkout_id) {
            $checkout = DomainCheckout::query()->find($order->domain_checkout_id);
            if ($checkout) {
                return self::createDomainCheckoutPaymentLink($checkout);
            }
        }

        if (! self::isConfigured()) {
            return null;
        }

        $txRef = $order->payment_reference ?: ('LW-DOM-' . $order->id . '-' . Str::upper(Str::random(8)));
        $amountNgn = max(1, (int) round((float) ($order->amount_ngn ?? 0)));
        $order->loadMissing('user');

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amountNgn,
            'currency' => 'NGN',
            'redirect_url' => route('domain.flutterwave.callback'),
            'payment_options' => 'card,banktransfer,ussd,account',
            'customer' => array_filter([
                'email' => $order->user?->email,
                'name' => $order->user?->name,
                'phonenumber' => $order->user?->phone,
            ], fn ($value) => filled($value)),
            'customizations' => self::domainCustomization($order),
            'meta' => [
                'domain_order_id' => $order->id,
                'domain' => $order->domain,
                'domain_option' => $order->option,
                'whmcs_order_id' => $order->whmcs_order_id,
                'whmcs_invoice_id' => $order->whmcs_invoice_id,
            ],
        ];

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave domain payment init failed', [
                'domain_order_id' => $order->id,
                'body' => $response->json(),
            ]);

            return null;
        }

        $link = data_get($response->json(), 'data.link');

        if (! is_string($link) || ! preg_match('#/hosted/pay/[A-Za-z0-9_-]+#', $link)) {
            Log::warning('Flutterwave domain payment returned an invalid checkout link', [
                'domain_order_id' => $order->id,
                'link' => $link,
            ]);

            return null;
        }

        $order->update([
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => $link,
            'status' => 'awaiting_payment',
        ]);

        return $link;
    }

    public static function createSiteCheckoutPaymentLink(SiteCheckout $checkout): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $txRef = $checkout->payment_reference ?: ('LW-CART-'.$checkout->id.'-'.Str::upper(Str::random(8)));
        $amountNgn = max(1, (int) round((float) ($checkout->amount_ngn ?? 0)));
        $checkout->loadMissing(['user', 'items']);

        $labels = $checkout->items->pluck('label')->filter()->take(4)->implode(', ');

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amountNgn,
            'currency' => 'NGN',
            'redirect_url' => route('checkout.flutterwave.callback'),
            'payment_options' => 'card,banktransfer,ussd,account',
            'customer' => array_filter([
                'email' => $checkout->user?->email,
                'name' => $checkout->user?->name,
                'phonenumber' => $checkout->user?->phone,
            ], fn ($value) => filled($value)),
            'customizations' => self::checkoutCustomization(
                config('site.short_name').' Cart',
                trim(($checkout->item_count ?: $checkout->items->count()).' item(s) · '.$labels),
            ),
            'meta' => [
                'site_checkout_id' => $checkout->id,
                'item_count' => $checkout->item_count,
            ],
        ];

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave site cart payment init failed', [
                'site_checkout_id' => $checkout->id,
                'body' => $response->json(),
            ]);

            return null;
        }

        $link = data_get($response->json(), 'data.link');

        if (! is_string($link) || ! preg_match('#/hosted/pay/[A-Za-z0-9_-]+#', $link)) {
            Log::warning('Flutterwave site cart payment returned an invalid checkout link', [
                'site_checkout_id' => $checkout->id,
                'link' => $link,
            ]);

            return null;
        }

        $checkout->update([
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => $link,
            'status' => 'awaiting_payment',
        ]);

        return $link;
    }

    /**
     * @return array{ok:bool,already_paid?:bool,message:string}
     */
    public static function confirmSiteCheckoutPayment(SiteCheckout $checkout, array $verified): array
    {
        $transactionId = (string) data_get($verified, 'id', '');

        if ($transactionId !== '' && (string) $checkout->flutterwave_transaction_id === $transactionId) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('domain.payment_already_confirmed'),
            ];
        }

        if ($checkout->isPaid()) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('domain.payment_already_confirmed'),
            ];
        }

        if (! in_array(strtolower((string) data_get($verified, 'status')), ['successful', 'completed'], true)) {
            $checkout->update([
                'payment_status' => strtolower((string) data_get($verified, 'status', 'failed')),
                'status' => 'payment_failed',
            ]);

            return [
                'ok' => false,
                'message' => __('domain.payment_incomplete'),
            ];
        }

        $paidAmount = (float) data_get($verified, 'amount', 0);
        $expected = (float) ($checkout->amount_ngn ?? 0);
        $currency = strtoupper((string) data_get($verified, 'currency', ''));

        if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
            $checkout->update([
                'payment_status' => 'amount_mismatch',
                'status' => 'payment_failed',
                'flutterwave_transaction_id' => $transactionId,
            ]);

            return [
                'ok' => false,
                'message' => __('domain.payment_mismatch'),
            ];
        }

        $checkout->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => $transactionId,
        ]);

        CartFulfillment::fulfill($checkout->fresh(['items', 'user']));

        return [
            'ok' => true,
            'message' => __('cart.payment_confirmed', [
                'count' => $checkout->item_count ?: $checkout->items()->count(),
            ]),
        ];
    }

    public static function createDomainCheckoutPaymentLink(DomainCheckout $checkout): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $txRef = $checkout->payment_reference ?: ('LW-DCART-' . $checkout->id . '-' . Str::upper(Str::random(8)));
        $amountNgn = max(1, (int) round((float) ($checkout->amount_ngn ?? 0)));
        $checkout->loadMissing(['user', 'orders']);

        $domains = $checkout->orders->pluck('domain')->filter()->implode(', ');

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amountNgn,
            'currency' => 'NGN',
            'redirect_url' => route('domain.flutterwave.callback'),
            'payment_options' => 'card,banktransfer,ussd,account',
            'customer' => array_filter([
                'email' => $checkout->user?->email,
                'name' => $checkout->user?->name,
                'phonenumber' => $checkout->user?->phone,
            ], fn ($value) => filled($value)),
            'customizations' => self::checkoutCustomization(
                config('site.short_name') . ' Domains',
                trim(($checkout->item_count ?: $checkout->orders->count()) . ' domain(s) · ' . $domains),
            ),
            'meta' => [
                'domain_checkout_id' => $checkout->id,
                'item_count' => $checkout->item_count,
                'whmcs_order_id' => $checkout->whmcs_order_id,
                'whmcs_invoice_id' => $checkout->whmcs_invoice_id,
            ],
        ];

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave domain cart payment init failed', [
                'domain_checkout_id' => $checkout->id,
                'body' => $response->json(),
            ]);

            return null;
        }

        $link = data_get($response->json(), 'data.link');

        if (! is_string($link) || ! preg_match('#/hosted/pay/[A-Za-z0-9_-]+#', $link)) {
            Log::warning('Flutterwave domain cart payment returned an invalid checkout link', [
                'domain_checkout_id' => $checkout->id,
                'link' => $link,
            ]);

            return null;
        }

        $checkout->update([
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => $link,
            'status' => 'awaiting_payment',
        ]);

        $checkout->orders()->update([
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => $link,
            'status' => 'awaiting_payment',
        ]);

        return $link;
    }

    /**
     * @return array{ok:bool,already_paid?:bool,message:string}
     */
    public static function confirmDomainCheckoutPayment(DomainCheckout $checkout, array $verified): array
    {
        $transactionId = (string) data_get($verified, 'id', '');

        if ($transactionId !== '' && (string) $checkout->flutterwave_transaction_id === $transactionId) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('domain.payment_already_confirmed'),
            ];
        }

        if ($checkout->isPaid()) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('domain.payment_already_confirmed'),
            ];
        }

        if (! in_array(strtolower((string) data_get($verified, 'status')), ['successful', 'completed'], true)) {
            $checkout->update([
                'payment_status' => strtolower((string) data_get($verified, 'status', 'failed')),
                'status' => 'payment_failed',
            ]);
            $checkout->orders()->update([
                'payment_status' => strtolower((string) data_get($verified, 'status', 'failed')),
                'status' => 'payment_failed',
            ]);

            return [
                'ok' => false,
                'message' => __('domain.payment_incomplete'),
            ];
        }

        $paidAmount = (float) data_get($verified, 'amount', 0);
        $expected = (float) ($checkout->amount_ngn ?? 0);
        $currency = strtoupper((string) data_get($verified, 'currency', ''));

        if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
            $checkout->update([
                'payment_status' => 'amount_mismatch',
                'status' => 'payment_failed',
                'flutterwave_transaction_id' => $transactionId,
            ]);
            $checkout->orders()->update([
                'payment_status' => 'amount_mismatch',
                'status' => 'payment_failed',
                'flutterwave_transaction_id' => $transactionId,
            ]);

            return [
                'ok' => false,
                'message' => __('domain.payment_mismatch'),
            ];
        }

        $checkout->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => $transactionId,
        ]);

        $checkout->orders()->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => $transactionId,
        ]);

        WhmcsDomainOrderSync::syncPaymentBundle($checkout->fresh(['orders', 'user']));

        $checkout = $checkout->fresh(['user', 'orders']);
        AccountNotifier::send($checkout?->user, new DomainOrderPaid($checkout));

        return [
            'ok' => true,
            'message' => __('domain.payment_confirmed_cart', [
                'count' => $checkout->item_count ?: $checkout->orders->count(),
            ]),
        ];
    }

    /**
     * @return array{ok:bool,already_paid?:bool,message:string}
     */
    public static function confirmDomainOrderPayment(DomainOrder $order, array $verified): array
    {
        if ($order->domain_checkout_id) {
            $checkout = DomainCheckout::query()->with('orders')->find($order->domain_checkout_id);
            if ($checkout) {
                return self::confirmDomainCheckoutPayment($checkout, $verified);
            }
        }

        $transactionId = (string) data_get($verified, 'id', '');

        if ($transactionId !== '' && (string) $order->flutterwave_transaction_id === $transactionId) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('domain.payment_already_confirmed'),
            ];
        }

        if ($order->isPaid()) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('domain.payment_already_confirmed'),
            ];
        }

        if (! in_array(strtolower((string) data_get($verified, 'status')), ['successful', 'completed'], true)) {
            $order->update([
                'payment_status' => strtolower((string) data_get($verified, 'status', 'failed')),
                'status' => 'payment_failed',
            ]);

            return [
                'ok' => false,
                'message' => __('domain.payment_incomplete'),
            ];
        }

        $paidAmount = (float) data_get($verified, 'amount', 0);
        $expected = (float) ($order->amount_ngn ?? 0);
        $currency = strtoupper((string) data_get($verified, 'currency', ''));

        if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
            $order->update([
                'payment_status' => 'amount_mismatch',
                'status' => 'payment_failed',
                'flutterwave_transaction_id' => $transactionId,
            ]);

            return [
                'ok' => false,
                'message' => __('domain.payment_mismatch'),
            ];
        }

        $order->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => $transactionId,
        ]);

        WhmcsDomainOrderSync::syncPayment($order->fresh());

        $order = $order->fresh(['user']);
        AccountNotifier::send($order?->user, new DomainOrderPaid($order));

        return [
            'ok' => true,
            'message' => $order->isTransfer()
                ? __('domain.payment_confirmed_transfer')
                : __('domain.payment_confirmed_register'),
        ];
    }

    public static function createEmailPaymentLink(EmailOrder $order, string $kind = 'initial'): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $kind = $kind === 'renewal' ? 'renewal' : 'initial';

        if ($kind === 'renewal' && ! $order->canBeRenewed()) {
            return null;
        }

        $txRef = $kind === 'renewal'
            ? ('LW-MAIL-R-' . $order->id . '-' . Str::upper(Str::random(8)))
            : ($order->payment_reference ?: ('LW-MAIL-' . $order->id . '-' . Str::upper(Str::random(8))));
        $amountNgn = max(1, (int) round((float) ($order->amount_ngn ?? 0)));
        $order->loadMissing('user');

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amountNgn,
            'currency' => 'NGN',
            'redirect_url' => route('email.flutterwave.callback'),
            'payment_options' => 'card,banktransfer,ussd,account',
            'customer' => array_filter([
                'email' => $order->user?->email,
                'name' => $order->user?->name,
                'phonenumber' => $order->user?->phone,
            ], fn ($value) => filled($value)),
            'customizations' => self::emailCustomization($order, $kind),
            'meta' => [
                'email_order_id' => $order->id,
                'plan_key' => $order->plan_key,
                'domain' => $order->domain,
                'billing_cycle' => $order->billing_cycle,
                'payment_kind' => $kind,
            ],
        ];

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', $payload);

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave email payment init failed', [
                'order_id' => $order->id,
                'kind' => $kind,
                'body' => $response->json(),
            ]);

            return null;
        }

        $link = data_get($response->json(), 'data.link');

        if (! is_string($link) || ! preg_match('#/hosted/pay/[A-Za-z0-9_-]+#', $link)) {
            Log::warning('Flutterwave email payment returned an invalid checkout link', [
                'order_id' => $order->id,
                'kind' => $kind,
                'link' => $link,
            ]);

            return null;
        }

        $updates = [
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => $link,
        ];

        if ($kind === 'initial') {
            $updates['status'] = 'awaiting_payment';
        }

        $order->update($updates);

        return $link;
    }

    /**
     * @return array{ok:bool,already_paid?:bool,message:string}
     */
    public static function confirmEmailOrderPayment(EmailOrder $order, array $verified): array
    {
        $transactionId = (string) data_get($verified, 'id', '');
        $metaKind = strtolower((string) data_get($verified, 'meta.payment_kind', ''));
        $isRenewal = $metaKind === 'renewal' || $order->isPendingRenewal();

        if ($transactionId !== '' && (string) $order->flutterwave_transaction_id === $transactionId) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => $isRenewal
                    ? __('email.renewal_already_confirmed')
                    : __('email.payment_already_confirmed'),
            ];
        }

        if (! $isRenewal && $order->isPaid()) {
            return [
                'ok' => true,
                'already_paid' => true,
                'message' => __('email.payment_already_confirmed'),
            ];
        }

        if (! in_array(strtolower((string) data_get($verified, 'status')), ['successful', 'completed'], true)) {
            if (! $isRenewal) {
                $order->update([
                    'payment_status' => strtolower((string) data_get($verified, 'status', 'failed')),
                    'status' => 'payment_failed',
                ]);
            }

            return [
                'ok' => false,
                'message' => __('email.payment_incomplete'),
            ];
        }

        $paidAmount = (float) data_get($verified, 'amount', 0);
        $expected = (float) ($order->amount_ngn ?? 0);
        $currency = strtoupper((string) data_get($verified, 'currency', ''));

        if ($currency !== 'NGN' || abs($paidAmount - $expected) > 1) {
            if (! $isRenewal) {
                $order->update([
                    'payment_status' => 'amount_mismatch',
                    'status' => 'payment_failed',
                    'flutterwave_transaction_id' => $transactionId,
                ]);
            }

            return [
                'ok' => false,
                'message' => __('email.payment_mismatch'),
            ];
        }

        if ($isRenewal) {
            return self::confirmEmailOrderRenewal($order, $verified);
        }

        $order->update([
            'payment_status' => 'successful',
            'status' => $order->isManualFulfilment() ? 'awaiting_manual_fulfilment' : 'paid',
            'flutterwave_transaction_id' => $transactionId,
        ]);

        $order->refresh();
        $order->applyPaidPeriod();
        $order->loadMissing('user');
        AccountNotifier::send($order->user, new EmailOrderPaid($order));

        if (! $order->isManualFulfilment()) {
            EmailProvisioner::provision($order->fresh(['mailboxes', 'user']));
        }

        return [
            'ok' => true,
            'message' => $order->isManualFulfilment()
                ? __('email.manual_fulfilment_paid', ['hours' => 4])
                : __('email.payment_confirmed'),
        ];
    }

    /**
     * @return array{ok:bool,already_paid?:bool,message:string}
     */
    protected static function confirmEmailOrderRenewal(EmailOrder $order, array $verified): array
    {
        $wasDeactivated = $order->isDeactivated();

        $order->extendPaidPeriod();
        $order->update([
            'payment_status' => 'successful',
            'flutterwave_transaction_id' => (string) data_get($verified, 'id', ''),
            'checkout_url' => null,
        ]);

        $order->refresh();

        if ($wasDeactivated) {
            EmailLifecycle::reactivate($order, force: true);
        }

        $order->refresh();
        $order->loadMissing('user');
        AccountNotifier::send($order->user, new EmailOrderRenewed($order));

        return [
            'ok' => true,
            'message' => __('email.renewal_confirmed', [
                'date' => $order->period_ends_at?->format('d M Y') ?? '',
            ]),
        ];
    }

    public static function verifyTransaction(string|int $transactionId): ?array
    {
        if (! self::isConfigured()) {
            return null;
        }

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->get('https://api.flutterwave.com/v3/transactions/' . $transactionId . '/verify');

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave verify failed', [
                'transaction_id' => $transactionId,
                'body' => $response->json(),
            ]);

            return null;
        }

        return data_get($response->json(), 'data');
    }

    /**
     * @return array{title:string,description:string,logo?:string}
     */
    protected static function hostingCustomization(HostingLead $lead): array
    {
        if ($lead->isShared()) {
            $description = trim(($lead->plan_name ?? 'Hosting') . ' - ' . ($lead->spec_label ?? ''));
            if ($lead->hostname) {
                $description .= ' · ' . $lead->hostname;
            }

            return self::checkoutCustomization(
                config('site.short_name') . ' Hosting',
                $description,
            );
        }

        return self::checkoutCustomization(
            config('site.short_name') . ' VPS Hosting',
            trim(($lead->plan_name ?? 'VPS') . ' - ' . ($lead->spec_label ?? '')),
        );
    }

    /**
     * @return array{title:string,description:string,logo?:string}
     */
    protected static function emailCustomization(EmailOrder $order, string $kind = 'initial'): array
    {
        $title = $kind === 'renewal'
            ? config('site.short_name') . ' Lemon Mail renewal'
            : config('site.short_name') . ' Lemon Mail';

        return self::checkoutCustomization(
            $title,
            trim($order->plan_name . ' - ' . $order->domain),
        );
    }

    /**
     * @return array{title:string,description:string,logo?:string}
     */
    protected static function domainCustomization(DomainOrder $order): array
    {
        $title = config('site.short_name') . ' Domain';
        $label = $order->isTransfer()
            ? 'Transfer · ' . $order->domain
            : 'Register · ' . $order->domain;

        return self::checkoutCustomization($title, $label);
    }

    /**
     * @return array{title:string,description:string,logo?:string}
     */
    protected static function checkoutCustomization(string $title, string $description): array
    {
        $customization = [
            'title' => $title,
            'description' => $description,
        ];

        $logo = self::checkoutLogoUrl();
        if ($logo) {
            $customization['logo'] = $logo;
        }

        return $customization;
    }

    /**
     * Flutterwave hosted checkout can hang forever when the logo URL returns an error.
     * Only send a logo that is publicly reachable over HTTPS.
     */
    protected static function checkoutLogoUrl(): ?string
    {
        $configured = trim((string) IntegrationSetting::getValue('flutterwave.logo_url', ''));
        $url = $configured !== '' ? $configured : asset('lemonwareslogo.webp');

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return null;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')) {
            return null;
        }

        if ($configured === '' && ! is_file(public_path('lemonwareslogo.webp'))) {
            return null;
        }

        try {
            $response = Http::timeout(2)
                ->withHeaders(['Accept' => 'image/*,*/*'])
                ->head($url);

            if (! $response->successful()) {
                Log::warning('Flutterwave checkout logo skipped because it is unreachable', [
                    'logo' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }
        } catch (\Throwable $exception) {
            Log::warning('Flutterwave checkout logo skipped after reachability check failed', [
                'logo' => $url,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        return $url;
    }
}
