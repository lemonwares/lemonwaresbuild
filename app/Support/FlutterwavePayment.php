<?php

namespace App\Support;

use App\Models\HostingLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwavePayment
{
    public static function isConfigured(): bool
    {
        return filled(config('services.flutterwave.secret_key'));
    }

    public static function createPaymentLink(HostingLead $lead): ?string
    {
        if (! self::isConfigured()) {
            return null;
        }

        $txRef = $lead->payment_reference ?: ('LW-VPS-' . $lead->id . '-' . Str::upper(Str::random(8)));
        $amountNgn = max(1, (int) round((float) ($lead->amount_ngn ?? 0)));

        $response = Http::timeout(20)
            ->withToken((string) config('services.flutterwave.secret_key'))
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
                'customizations' => [
                    'title' => config('site.short_name') . ' VPS Hosting',
                    'description' => trim(($lead->plan_name ?? 'VPS') . ' — ' . ($lead->spec_label ?? '')),
                    'logo' => asset('lemonwareslogo.webp'),
                ],
                'meta' => [
                    'lead_id' => $lead->id,
                    'plan_slug' => $lead->plan_slug,
                    'billing_cycle' => $lead->billing_cycle,
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
            ->withToken((string) config('services.flutterwave.secret_key'))
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
}
