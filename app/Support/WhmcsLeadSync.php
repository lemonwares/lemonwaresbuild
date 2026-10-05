<?php

namespace App\Support;

use App\Models\HostingLead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhmcsLeadSync
{
    public static function syncCheckout(HostingLead $lead): HostingLead
    {
        if ($lead->checkout_provider !== 'whmcs') {
            return $lead;
        }

        if (! WhmcsClient::isConfigured()) {
            return self::mark($lead, 'skipped', 'WHMCS API credentials are missing.');
        }

        if ($lead->whmcs_order_id && $lead->whmcs_sync_status === 'checkout_synced') {
            return $lead;
        }

        $nameParts = preg_split('/\s+/', trim((string) $lead->full_name)) ?: [];
        $firstName = (string) ($nameParts[0] ?? '');
        $lastName = trim(implode(' ', array_slice($nameParts, 1)));
        if ($lastName === '') {
            $lastName = $firstName;
        }

        $client = WhmcsClient::findClientByEmail((string) $lead->email);
        $clientId = (int) data_get($client, 'id', 0);

        $clientPayload = [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => strtolower((string) $lead->email),
            'phonenumber' => (string) $lead->phone,
            'companyname' => (string) ($lead->company ?? ''),
            'address1' => (string) ($lead->billing_address_line_1 ?? ''),
            'address2' => (string) ($lead->billing_address_line_2 ?? ''),
            'city' => (string) ($lead->billing_city ?? ''),
            'state' => (string) ($lead->billing_state ?? ''),
            'postcode' => (string) ($lead->billing_postcode ?? ''),
            'country' => strtoupper((string) ($lead->billing_country ?? '')),
        ];

        // Existing clients are never updated from order details: the order form is public, so an
        // email match alone must not let anyone rewrite another customer's WHMCS record.
        if ($clientId < 1) {
            $created = WhmcsClient::createClient(array_merge($clientPayload, [
                'password2' => Str::password(32),
                'skipvalidation' => true,
            ]));

            $clientId = (int) data_get($created, 'clientid', 0);
        }

        if ($clientId < 1) {
            return self::mark($lead, 'failed', 'Unable to create or find WHMCS client.');
        }

        $pid = (int) ($lead->whmcs_pid ?: 0);
        if ($pid < 1) {
            return self::mark($lead, 'failed', 'Missing WHMCS product id mapping for selected plan.');
        }

        $paymentMethod = WhmcsSettings::paymentMethod();
        if ($paymentMethod === '') {
            return self::mark($lead, 'failed', 'WHMCS payment method is not configured. Set it in Admin > WHMCS Settings.');
        }

        $orderPayload = [
            'clientid' => $clientId,
            'pid' => [$pid],
            'billingcycle' => [self::whmcsCycle((string) $lead->billing_cycle)],
            'paymentmethod' => $paymentMethod,
            'noinvoice' => false,
            'noemail' => true,
        ];

        $domain = filled($lead->hostname) ? (string) $lead->hostname : null;
        $domainOption = (string) ($lead->domain_option ?? '');

        if ($domainOption === '' && $lead->checkout_url) {
            parse_str((string) parse_url((string) $lead->checkout_url, PHP_URL_QUERY), $checkoutQuery);
            $domainOption = (string) ($checkoutQuery['domainoption'] ?? '');
        }

        if ($domainOption === '') {
            $domainOption = 'owndomain';
        }

        if ($domain) {
            $orderPayload['domain'] = [$domain];

            if (in_array($domainOption, ['register', 'transfer'], true)) {
                $orderPayload['domaintype'] = [$domainOption];
                $orderPayload['regperiod'] = [1];
            }
        }

        $order = WhmcsClient::createOrder($orderPayload);

        $orderId = (int) data_get($order, 'orderid', 0);
        $invoiceId = (int) data_get($order, 'invoiceid', 0);

        if ($orderId < 1) {
            return self::mark($lead, 'failed', WhmcsClient::lastError() ?: 'Unable to create WHMCS order.');
        }

        $lead->update([
            'whmcs_client_id' => $clientId,
            'whmcs_order_id' => $orderId,
            'whmcs_invoice_id' => $invoiceId > 0 ? $invoiceId : null,
            'whmcs_sync_status' => 'checkout_synced',
            'whmcs_sync_error' => null,
            'whmcs_synced_at' => now(),
        ]);

        return $lead->fresh();
    }

    public static function syncPayment(HostingLead $lead): HostingLead
    {
        if ($lead->checkout_provider !== 'whmcs') {
            return $lead;
        }

        if (! WhmcsClient::isConfigured()) {
            return self::mark($lead, 'skipped', 'WHMCS API credentials are missing.');
        }

        if ((string) $lead->whmcs_sync_status === 'payment_synced') {
            return $lead;
        }

        $orderId = (int) ($lead->whmcs_order_id ?: 0);
        if ($orderId < 1) {
            return self::mark($lead, 'failed', 'Cannot sync payment because WHMCS order id is missing.');
        }

        $invoiceId = (int) ($lead->whmcs_invoice_id ?: 0);
        $transactionId = trim((string) ($lead->flutterwave_transaction_id ?: $lead->payment_reference ?: ''));

        if ($invoiceId > 0 && $transactionId !== '') {
            $outstanding = WhmcsClient::invoiceOutstanding($invoiceId);
            if ($outstanding === null) {
                return self::mark($lead, 'failed', WhmcsClient::lastError() ?: 'Could not read the WHMCS invoice balance.');
            }

            if ($outstanding > 0 && ! WhmcsClient::addInvoicePayment($invoiceId, $outstanding, $transactionId)) {
                return self::mark($lead, 'failed', WhmcsClient::lastError() ?: 'WHMCS invoice payment recording failed.');
            }
        }

        $accepted = WhmcsClient::acceptOrder($orderId, autoSetup: true);

        if (! $accepted) {
            return self::mark($lead, 'failed', 'WHMCS order acceptance failed after payment.');
        }

        $lead->update([
            'whmcs_sync_status' => 'payment_synced',
            'whmcs_sync_error' => null,
            'whmcs_synced_at' => now(),
        ]);

        return $lead->fresh();
    }

    public static function retry(HostingLead $lead): HostingLead
    {
        $lead = self::syncCheckout($lead->fresh());

        if ($lead->isPaid()) {
            return self::syncPayment($lead);
        }

        return $lead;
    }

    /**
     * Maps a storefront billing cycle key to the WHMCS cycle name.
     */
    public static function whmcsCycle(string $cycle): string
    {
        $mapped = (string) (HostingPricing::cycle($cycle)['whmcs'] ?? '');

        return in_array($mapped, ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'], true)
            ? $mapped
            : 'monthly';
    }

    protected static function mark(HostingLead $lead, string $status, string $error): HostingLead
    {
        Log::warning('WHMCS lead sync issue', [
            'lead_id' => $lead->id,
            'status' => $status,
            'error' => $error,
        ]);

        $lead->update([
            'whmcs_sync_status' => $status,
            'whmcs_sync_error' => $error,
            'whmcs_synced_at' => now(),
        ]);

        return $lead->fresh();
    }
}
