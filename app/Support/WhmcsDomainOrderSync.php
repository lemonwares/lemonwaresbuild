<?php

namespace App\Support;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\User;
use App\Models\WhmcsCustomer;
use Illuminate\Support\Facades\Log;

class WhmcsDomainOrderSync
{
    public static function syncCheckoutBundle(DomainCheckout $checkout): DomainCheckout
    {
        if (! WhmcsClient::isConfigured()) {
            return self::markCheckout($checkout, 'failed', 'WHMCS API credentials are missing.');
        }

        if ($checkout->whmcs_order_id && (string) $checkout->whmcs_sync_status === 'checkout_synced') {
            return $checkout;
        }

        $checkout->loadMissing(['user.whmcsCustomer', 'orders']);
        $user = $checkout->user;
        $orders = $checkout->orders;

        if (! $user) {
            return self::markCheckout($checkout, 'failed', 'Domain checkout has no linked user.');
        }

        if ($orders->isEmpty()) {
            return self::markCheckout($checkout, 'failed', 'Domain checkout has no line items.');
        }

        $clientId = self::resolveClientId($user, $checkout);
        if ($clientId < 1) {
            return self::markCheckout($checkout, 'failed', WhmcsClient::lastError() ?: 'Unable to create or find WHMCS client.');
        }

        self::linkWhmcsCustomer($user, $clientId);

        $paymentMethod = WhmcsSettings::paymentMethod();
        if ($paymentMethod === '') {
            return self::markCheckout($checkout, 'failed', 'WHMCS payment method is not configured. Set it in Admin > WHMCS Settings.');
        }

        $domains = [];
        $domainTypes = [];
        $regPeriods = [];
        $eppCodes = [];
        $hasTransfer = false;

        foreach ($orders as $order) {
            $option = $order->isTransfer() ? 'transfer' : 'register';
            $domains[] = (string) $order->domain;
            $domainTypes[] = $option;
            $regPeriods[] = max(1, (int) ($order->reg_period ?: 1));

            if ($option === 'transfer') {
                $hasTransfer = true;
                $epp = trim((string) ($order->epp_code ?? ''));
                if ($epp === '') {
                    return self::markCheckout($checkout, 'failed', 'Transfer orders require an auth/EPP code for '.$order->domain.'.');
                }
                $eppCodes[] = $epp;
            } else {
                // Keep parallel array indexes aligned for WHMCS AddOrder.
                $eppCodes[] = '';
            }
        }

        $orderPayload = [
            'clientid' => $clientId,
            'paymentmethod' => $paymentMethod,
            'noinvoice' => false,
            'noemail' => true,
            // Indexed arrays become domain[0], domaintype[0], regperiod[0] over form-urlencoded.
            'domain' => array_values($domains),
            'domaintype' => array_values($domainTypes),
            'regperiod' => array_values($regPeriods),
        ];

        if ($hasTransfer) {
            $orderPayload['eppcode'] = array_values($eppCodes);
        }

        $currencyId = self::catalogCurrencyId();
        if ($currencyId > 0) {
            $orderPayload['currencyid'] = $currencyId;
        }

        Log::info('WHMCS domain cart AddOrder request', [
            'domain_checkout_id' => $checkout->id,
            'clientid' => $clientId,
            'domains' => $domains,
            'domaintype' => $domainTypes,
            'regperiod' => $regPeriods,
            'currencyid' => $currencyId > 0 ? $currencyId : null,
        ]);

        $whmcsOrder = WhmcsClient::createOrder($orderPayload);
        $orderId = (int) data_get($whmcsOrder, 'orderid', 0);
        $invoiceId = (int) data_get($whmcsOrder, 'invoiceid', 0);

        if ($orderId < 1) {
            return self::markCheckout($checkout, 'failed', WhmcsClient::lastError() ?: 'Unable to create WHMCS domain order.');
        }

        $checkout->update([
            'whmcs_client_id' => $clientId,
            'whmcs_order_id' => $orderId,
            'whmcs_invoice_id' => $invoiceId > 0 ? $invoiceId : null,
            'whmcs_sync_status' => 'checkout_synced',
            'whmcs_sync_error' => null,
            'whmcs_synced_at' => now(),
        ]);

        foreach ($orders as $order) {
            $order->update([
                'whmcs_client_id' => $clientId,
                'whmcs_order_id' => $orderId,
                'whmcs_invoice_id' => $invoiceId > 0 ? $invoiceId : null,
                'whmcs_sync_status' => 'checkout_synced',
                'whmcs_sync_error' => null,
                'whmcs_synced_at' => now(),
            ]);
        }

        return $checkout->fresh(['orders', 'user']);
    }

    public static function syncPaymentBundle(DomainCheckout $checkout): DomainCheckout
    {
        if (! WhmcsClient::isConfigured()) {
            return self::markCheckout($checkout, 'failed', 'WHMCS API credentials are missing.');
        }

        if ((string) $checkout->whmcs_sync_status === 'payment_synced') {
            return $checkout;
        }

        $orderId = (int) ($checkout->whmcs_order_id ?: 0);
        if ($orderId < 1) {
            return self::markCheckout($checkout, 'failed', 'Cannot sync payment because WHMCS order id is missing.');
        }

        $invoiceId = (int) ($checkout->whmcs_invoice_id ?: 0);
        // Unique per Flutterwave tx so WHMCS accepts retries / webhook+callback races.
        $transactionId = trim((string) ($checkout->flutterwave_transaction_id ?: $checkout->payment_reference ?: ''));
        if ($transactionId !== '' && $checkout->id) {
            $transactionId = $transactionId.'-C'.$checkout->id;
        }

        if ($invoiceId > 0 && $transactionId !== '') {
            $amount = self::invoicePaymentAmount($invoiceId, (float) ($checkout->amount_ngn ?? 0));

            $invoicePaid = WhmcsClient::addInvoicePayment(
                $invoiceId,
                $amount,
                $transactionId,
            );

            if (! $invoicePaid) {
                return self::markCheckout($checkout, 'failed', WhmcsClient::lastError() ?: 'WHMCS invoice payment recording failed.');
            }
        }

        // autosetup=true so registrar modules run for domain register/transfer lines.
        $accepted = WhmcsClient::acceptOrder($orderId, true);

        if (! $accepted) {
            return self::markCheckout($checkout, 'failed', WhmcsClient::lastError() ?: 'WHMCS order acceptance failed after payment.');
        }

        Log::info('WHMCS domain cart payment synced', [
            'domain_checkout_id' => $checkout->id,
            'whmcs_order_id' => $orderId,
            'whmcs_invoice_id' => $invoiceId > 0 ? $invoiceId : null,
            'domains' => $checkout->orders()->pluck('domain')->all(),
        ]);

        $checkout->update([
            'whmcs_sync_status' => 'payment_synced',
            'whmcs_sync_error' => null,
            'whmcs_synced_at' => now(),
            'status' => 'submitted',
        ]);

        $checkout->orders()->update([
            'whmcs_sync_status' => 'payment_synced',
            'whmcs_sync_error' => null,
            'whmcs_synced_at' => now(),
            'status' => 'submitted',
            'payment_status' => 'successful',
        ]);

        return $checkout->fresh(['orders', 'user']);
    }

    /** @deprecated Prefer syncCheckoutBundle for cart checkouts */
    public static function syncCheckout(DomainOrder $order): DomainOrder
    {
        if ($order->domain_checkout_id) {
            $checkout = DomainCheckout::query()->with('orders')->find($order->domain_checkout_id);
            if ($checkout) {
                self::syncCheckoutBundle($checkout);

                return $order->fresh();
            }
        }

        $checkout = DomainCheckout::create([
            'user_id' => $order->user_id,
            'item_count' => 1,
            'amount_usd' => $order->amount_usd,
            'amount_ngn' => $order->amount_ngn,
            'status' => $order->status,
            'ip_address' => $order->ip_address,
        ]);

        $order->update(['domain_checkout_id' => $checkout->id]);
        self::syncCheckoutBundle($checkout->fresh(['orders', 'user']));

        return $order->fresh();
    }

    public static function syncPayment(DomainOrder $order): DomainOrder
    {
        if ($order->domain_checkout_id) {
            $checkout = DomainCheckout::query()->find($order->domain_checkout_id);
            if ($checkout) {
                self::syncPaymentBundle($checkout);

                return $order->fresh();
            }
        }

        return self::markOrder($order, 'failed', 'Domain order is missing checkout bundle for payment sync.');
    }

    protected static function resolveClientId(User $user, DomainCheckout $checkout): int
    {
        $nameParts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $firstName = (string) ($nameParts[0] ?? '');
        $lastName = trim(implode(' ', array_slice($nameParts, 1)));
        if ($lastName === '') {
            $lastName = $firstName !== '' ? $firstName : 'Customer';
        }
        if ($firstName === '') {
            $firstName = 'Customer';
        }

        $clientId = (int) ($user->whmcsCustomer?->whmcs_client_id ?? 0);

        if ($clientId < 1) {
            $client = WhmcsClient::findClientByEmail((string) $user->email);
            $clientId = (int) data_get($client, 'id', 0);
        }

        $clientPayload = [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => strtolower((string) $user->email),
            'phonenumber' => (string) ($user->phone ?? ''),
            'companyname' => (string) ($user->company ?? ''),
            'address1' => (string) ($user->billing_address_line_1 ?: 'N/A'),
            'address2' => (string) ($user->billing_address_line_2 ?? ''),
            'city' => (string) ($user->billing_city ?: 'Lagos'),
            'state' => (string) ($user->billing_state ?? ''),
            'postcode' => (string) ($user->billing_postcode ?? ''),
            'country' => strtoupper((string) ($user->billing_country ?: 'NG')),
        ];

        if ($clientId < 1) {
            $created = WhmcsClient::createClient(array_merge($clientPayload, [
                'password2' => 'LW-DCART-'.$checkout->id.'-Temp#'.random_int(1000, 9999),
                'skipvalidation' => true,
            ]));

            $clientId = (int) data_get($created, 'clientid', 0);
        } else {
            WhmcsClient::updateClient(array_merge($clientPayload, [
                'clientid' => $clientId,
            ]));
        }

        return $clientId;
    }

    protected static function linkWhmcsCustomer(User $user, int $clientId): void
    {
        if ($clientId < 1) {
            return;
        }

        $nameParts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
        $firstName = (string) ($nameParts[0] ?? '');
        $lastName = trim(implode(' ', array_slice($nameParts, 1)));

        WhmcsCustomer::query()->updateOrCreate(
            ['whmcs_client_id' => $clientId],
            [
                'user_id' => $user->id,
                'first_name' => $firstName !== '' ? $firstName : null,
                'last_name' => $lastName !== '' ? $lastName : null,
                'full_name' => trim((string) $user->name) !== '' ? trim((string) $user->name) : null,
                'email' => strtolower((string) $user->email),
                'company' => (string) ($user->company ?? '') ?: null,
                'phone' => (string) ($user->phone ?? '') ?: null,
                'country' => strtoupper((string) ($user->billing_country ?? '')) ?: null,
                'status' => 'Active',
                'last_synced_at' => now(),
            ],
        );
    }

    protected static function catalogCurrencyId(): int
    {
        $catalog = WhmcsDomainPricing::catalog();
        $id = (int) data_get($catalog, 'currency.id', 0);

        return $id > 0 ? $id : 0;
    }

    /**
     * Prefer WHMCS invoice balance so AddInvoicePayment clears the invoice in WHMCS currency.
     */
    protected static function invoicePaymentAmount(int $invoiceId, float $fallbackNgn): float
    {
        $invoice = WhmcsClient::getInvoice($invoiceId);
        if (! $invoice) {
            return round(max(0.01, $fallbackNgn), 2);
        }

        $balance = (float) data_get($invoice, 'balance', 0);
        if ($balance > 0) {
            return round($balance, 2);
        }

        $total = (float) data_get($invoice, 'total', 0);
        if ($total > 0) {
            return round($total, 2);
        }

        return round(max(0.01, $fallbackNgn), 2);
    }

    protected static function markCheckout(DomainCheckout $checkout, string $status, string $error): DomainCheckout
    {
        Log::warning('WHMCS domain checkout sync issue', [
            'domain_checkout_id' => $checkout->id,
            'status' => $status,
            'error' => $error,
        ]);

        $checkout->update([
            'whmcs_sync_status' => $status,
            'whmcs_sync_error' => $error,
            'whmcs_synced_at' => now(),
            'status' => $status === 'failed' ? 'sync_failed' : $checkout->status,
        ]);

        $checkout->orders()->update([
            'whmcs_sync_status' => $status,
            'whmcs_sync_error' => $error,
            'whmcs_synced_at' => now(),
            'status' => $status === 'failed' ? 'sync_failed' : $checkout->status,
        ]);

        return $checkout->fresh(['orders', 'user']);
    }

    protected static function markOrder(DomainOrder $order, string $status, string $error): DomainOrder
    {
        Log::warning('WHMCS domain order sync issue', [
            'domain_order_id' => $order->id,
            'status' => $status,
            'error' => $error,
        ]);

        $order->update([
            'whmcs_sync_status' => $status,
            'whmcs_sync_error' => $error,
            'whmcs_synced_at' => now(),
            'status' => $status === 'failed' ? 'sync_failed' : $order->status,
        ]);

        return $order->fresh();
    }
}
