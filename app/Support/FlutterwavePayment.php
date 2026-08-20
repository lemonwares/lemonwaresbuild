<?php

namespace App\Support;

use App\Models\HostingLead;
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

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $amountNgn,
                'currency' => 'NGN',
                'redirect_url' => route('hosting.flutterwave.callback'),
                'customer' => [
                    'email' => $lead->email,
                    'name' => $lead->full_name,
                    'phonenumber' => $lead->phone,
                ],
                'customizations' => self::hostingCustomization($lead),
                'meta' => [
                    'lead_id' => $lead->id,
                    'plan_slug' => $lead->plan_slug,
                    'billing_cycle' => $lead->billing_cycle,
                    'checkout_provider' => $lead->checkout_provider,
                    'whmcs_order_id' => $lead->whmcs_order_id,
                    'whmcs_invoice_id' => $lead->whmcs_invoice_id,
                ],
            ]);

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

    public static function createEmailPaymentLink(\App\Models\EmailOrder $order): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $txRef = $order->payment_reference ?: ('LW-MAIL-' . $order->id . '-' . Str::upper(Str::random(8)));
        $amountNgn = max(1, (int) round((float) ($order->amount_ngn ?? 0)));
        $order->loadMissing('user');

        $response = Http::timeout(20)
            ->withToken(FlutterwaveSettings::secretKey())
            ->acceptJson()
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $amountNgn,
                'currency' => 'NGN',
                'redirect_url' => route('email.flutterwave.callback'),
                'customer' => [
                    'email' => $order->user?->email,
                    'name' => $order->user?->name,
                ],
                'customizations' => [
                    'title' => config('site.short_name') . ' Lemon Mail',
                    'description' => trim($order->plan_name . ' — ' . $order->domain),
                    'logo' => asset('lemonwareslogo.webp'),
                ],
                'meta' => [
                    'email_order_id' => $order->id,
                    'plan_key' => $order->plan_key,
                    'domain' => $order->domain,
                    'billing_cycle' => $order->billing_cycle,
                ],
            ]);

        if (! $response->successful() || data_get($response->json(), 'status') !== 'success') {
            Log::warning('Flutterwave email payment init failed', [
                'order_id' => $order->id,
                'body' => $response->json(),
            ]);

            return null;
        }

        $link = data_get($response->json(), 'data.link');

        $order->update([
            'payment_reference' => $txRef,
            'payment_provider' => 'flutterwave',
            'checkout_url' => $link,
            'status' => 'awaiting_payment',
        ]);

        return is_string($link) ? $link : null;
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
     * @return array{title:string,description:string,logo:string}
     */
    protected static function hostingCustomization(HostingLead $lead): array
    {
        if ($lead->isShared()) {
            $description = trim(($lead->plan_name ?? 'Hosting') . ' — ' . ($lead->spec_label ?? ''));
            if ($lead->hostname) {
                $description .= ' · ' . $lead->hostname;
            }

            return [
                'title' => config('site.short_name') . ' Hosting',
                'description' => $description,
                'logo' => asset('lemonwareslogo.webp'),
            ];
        }

        return [
            'title' => config('site.short_name') . ' VPS Hosting',
            'description' => trim(($lead->plan_name ?? 'VPS') . ' — ' . ($lead->spec_label ?? '')),
            'logo' => asset('lemonwareslogo.webp'),
        ];
    }
}
