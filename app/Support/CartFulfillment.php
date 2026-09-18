<?php

namespace App\Support;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\SiteCheckout;
use App\Models\SiteCheckoutItem;
use App\Models\User;
use App\Notifications\DomainOrderPaid;
use App\Notifications\EmailOrderPaid;
use App\Notifications\HostingOrderPaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartFulfillment
{
    public static function fulfill(SiteCheckout $checkout): SiteCheckout
    {
        $checkout->loadMissing(['items', 'user']);

        if ((string) $checkout->fulfilment_status === 'fulfilled') {
            return $checkout;
        }

        $errors = [];

        try {
            self::fulfillDomains($checkout);
        } catch (\Throwable $e) {
            $errors[] = 'domains: '.$e->getMessage();
            Log::warning('Cart domain fulfilment failed', [
                'site_checkout_id' => $checkout->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            self::fulfillEmails($checkout);
        } catch (\Throwable $e) {
            $errors[] = 'email: '.$e->getMessage();
            Log::warning('Cart email fulfilment failed', [
                'site_checkout_id' => $checkout->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            self::fulfillHosting($checkout);
        } catch (\Throwable $e) {
            $errors[] = 'hosting: '.$e->getMessage();
            Log::warning('Cart hosting fulfilment failed', [
                'site_checkout_id' => $checkout->id,
                'error' => $e->getMessage(),
            ]);
        }

        $checkout->update([
            'fulfilment_status' => $errors === [] ? 'fulfilled' : 'partial',
            'fulfilment_error' => $errors === [] ? null : implode(' | ', $errors),
            'fulfilled_at' => now(),
            'status' => $errors === [] ? 'fulfilled' : 'paid',
        ]);

        return $checkout->fresh(['items', 'user']);
    }

    protected static function fulfillDomains(SiteCheckout $checkout): void
    {
        $lines = $checkout->items->where('type', Cart::TYPE_DOMAIN)->values();
        if ($lines->isEmpty()) {
            return;
        }

        $user = $checkout->user;
        if (! $user) {
            throw new \RuntimeException('Missing user for domain fulfilment.');
        }

        $domainCheckout = DB::transaction(function () use ($checkout, $lines, $user) {
            $amountUsd = $lines->sum(fn (SiteCheckoutItem $item) => (float) $item->amount_usd);
            $amountNgn = $lines->sum(fn (SiteCheckoutItem $item) => (float) $item->amount_ngn);

            $domainCheckout = DomainCheckout::create([
                'user_id' => $user->id,
                'item_count' => $lines->count(),
                'amount_usd' => $amountUsd,
                'amount_ngn' => $amountNgn,
                'status' => 'paid',
                'payment_provider' => 'flutterwave',
                'payment_status' => 'successful',
                'payment_reference' => $checkout->payment_reference,
                'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                'ip_address' => $checkout->ip_address,
            ]);

            foreach ($lines as $line) {
                $payload = is_array($line->payload) ? $line->payload : [];
                $option = (string) ($payload['option'] ?? 'register');

                $order = DomainOrder::create([
                    'user_id' => $user->id,
                    'domain_checkout_id' => $domainCheckout->id,
                    'domain' => (string) ($payload['domain'] ?? $line->label),
                    'option' => $option,
                    'epp_code' => $option === 'transfer' ? trim((string) ($payload['epp_code'] ?? '')) : null,
                    'reg_period' => (int) ($payload['reg_period'] ?? 1),
                    'amount_usd' => (float) $line->amount_usd,
                    'amount_ngn' => (float) $line->amount_ngn,
                    'status' => 'paid',
                    'payment_provider' => 'flutterwave',
                    'payment_status' => 'successful',
                    'payment_reference' => $checkout->payment_reference,
                    'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                    'ip_address' => $checkout->ip_address,
                ]);

                $line->update([
                    'domain_order_id' => $order->id,
                    'domain_checkout_id' => $domainCheckout->id,
                    'fulfilment_status' => 'created',
                ]);
            }

            return $domainCheckout->fresh(['orders', 'user']);
        });

        $synced = WhmcsDomainOrderSync::syncCheckoutBundle($domainCheckout);
        if ((string) $synced->whmcs_sync_status !== 'checkout_synced') {
            throw new \RuntimeException($synced->whmcs_sync_error ?: 'WHMCS domain checkout sync failed.');
        }

        // Mark as paid again so payment sync can run after AddOrder.
        $synced->update([
            'payment_status' => 'successful',
            'status' => 'paid',
            'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
            'payment_reference' => $checkout->payment_reference,
        ]);

        $paid = WhmcsDomainOrderSync::syncPaymentBundle($synced->fresh(['orders', 'user']));
        if ((string) $paid->whmcs_sync_status !== 'payment_synced') {
            throw new \RuntimeException($paid->whmcs_sync_error ?: 'WHMCS domain payment sync failed.');
        }

        AccountNotifier::send($user, new DomainOrderPaid($paid));

        $checkout->items()->where('type', Cart::TYPE_DOMAIN)->update([
            'fulfilment_status' => 'synced',
        ]);
    }

    protected static function fulfillEmails(SiteCheckout $checkout): void
    {
        $lines = $checkout->items->where('type', Cart::TYPE_EMAIL)->values();
        if ($lines->isEmpty()) {
            return;
        }

        $user = $checkout->user;
        if (! $user) {
            throw new \RuntimeException('Missing user for email fulfilment.');
        }

        foreach ($lines as $line) {
            $payload = is_array($line->payload) ? $line->payload : [];
            $planKey = (string) ($payload['plan_key'] ?? '');
            $plan = EmailPricing::plan($planKey);
            if (! $plan) {
                $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => 'Invalid email plan.']);

                continue;
            }

            $domain = DomainName::normalize((string) ($payload['domain'] ?? ''));
            if ($domain === null) {
                $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => 'Missing email domain.']);

                continue;
            }

            $localParts = collect($payload['mailboxes'] ?? [])
                ->map(fn ($part) => strtolower(trim((string) $part)))
                ->filter()
                ->unique()
                ->values();

            $expected = (int) ($payload['mailbox_count'] ?? $plan['mailboxes'] ?? 0);
            if ($localParts->count() !== $expected) {
                $localParts = collect(EmailPricing::defaultLocalParts($expected));
            }

            $cycle = (string) ($payload['billing_cycle'] ?? 'monthly');
            $provider = (string) ($plan['provider'] ?? 'lemonmail');
            $fulfilmentMode = (string) ($plan['fulfilment_mode'] ?? 'auto');
            $isManual = $fulfilmentMode === 'manual';

            $order = DB::transaction(function () use ($checkout, $user, $planKey, $domain, $localParts, $cycle, $provider, $fulfilmentMode, $isManual, $line, $payload) {
                $order = EmailOrder::create([
                    'user_id' => $user->id,
                    'plan_key' => $planKey,
                    'plan_name' => (string) ($payload['plan_name'] ?? __('email.plans.'.$planKey.'.name')),
                    'provider' => $provider,
                    'fulfilment_mode' => $fulfilmentMode,
                    'fulfilment_status' => $isManual ? 'queued' : null,
                    'fulfilment_updated_at' => $isManual ? now() : null,
                    'domain' => $domain,
                    'mailbox_count' => $localParts->count(),
                    'billing_cycle' => $cycle,
                    'amount_usd' => (float) $line->amount_usd,
                    'amount_ngn' => (float) $line->amount_ngn,
                    'status' => $isManual ? 'awaiting_manual_fulfilment' : 'paid',
                    'payment_provider' => 'flutterwave',
                    'payment_status' => 'successful',
                    'payment_reference' => $checkout->payment_reference,
                    'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                    'ip_address' => $checkout->ip_address,
                ]);

                foreach ($localParts as $localPart) {
                    EmailMailbox::create([
                        'email_order_id' => $order->id,
                        'local_part' => $localPart,
                        'address' => $localPart.'@'.$domain,
                        'status' => 'pending',
                    ]);
                }

                return $order;
            });

            $order->refresh();
            $order->applyPaidPeriod();
            $order->loadMissing('user');
            AccountNotifier::send($order->user, new EmailOrderPaid($order));

            if (! $order->isManualFulfilment()) {
                EmailProvisioner::provision($order->fresh(['mailboxes', 'user']));
            }

            $line->update([
                'email_order_id' => $order->id,
                'fulfilment_status' => 'synced',
            ]);
        }
    }

    protected static function fulfillHosting(SiteCheckout $checkout): void
    {
        $lines = $checkout->items->where('type', Cart::TYPE_HOSTING)->values();
        if ($lines->isEmpty()) {
            return;
        }

        $user = $checkout->user;
        if (! $user instanceof User) {
            throw new \RuntimeException('Missing user for hosting fulfilment.');
        }

        foreach ($lines as $line) {
            $payload = is_array($line->payload) ? $line->payload : [];
            $planSlug = (string) ($payload['plan_slug'] ?? '');
            $specKey = (string) ($payload['spec_key'] ?? '');
            $checkoutProvider = (string) ($payload['checkout_provider'] ?? 'whmcs');
            $hostname = DomainName::normalize((string) ($payload['hostname'] ?? ''));

            if ($checkoutProvider === 'whmcs' && $hostname === null) {
                $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => 'Hosting requires a domain.']);

                continue;
            }

            $lead = HostingLead::create([
                'user_id' => $user->id,
                'full_name' => (string) $user->name,
                'email' => strtolower((string) $user->email),
                'phone' => (string) ($user->phone ?? ''),
                'company' => (string) ($user->company ?? '') ?: null,
                'billing_address_line_1' => (string) ($user->billing_address_line_1 ?? 'N/A'),
                'billing_address_line_2' => (string) ($user->billing_address_line_2 ?? '') ?: null,
                'billing_city' => (string) ($user->billing_city ?? 'Lagos'),
                'billing_state' => (string) ($user->billing_state ?? '') ?: null,
                'billing_postcode' => (string) ($user->billing_postcode ?? '') ?: null,
                'billing_country' => strtoupper((string) ($user->billing_country ?? 'NG')),
                'plan_slug' => $planSlug,
                'plan_name' => (string) ($payload['plan_name'] ?? $planSlug),
                'spec_key' => $specKey,
                'spec_label' => (string) ($payload['spec_label'] ?? $specKey),
                'spec_summary' => (string) ($payload['spec_label'] ?? $specKey),
                'hostname' => $hostname,
                'billing_cycle' => (string) ($payload['billing_cycle'] ?? 'monthly'),
                'amount_usd' => (float) $line->amount_usd,
                'amount_ngn' => (float) $line->amount_ngn,
                'hosting_amount_usd' => (float) $line->amount_usd,
                'hosting_amount_ngn' => (float) $line->amount_ngn,
                'domain_amount_usd' => 0,
                'domain_amount_ngn' => 0,
                'checkout_provider' => $checkoutProvider,
                'payment_provider' => 'flutterwave',
                'payment_status' => 'successful',
                'payment_reference' => $checkout->payment_reference,
                'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                'status' => 'paid',
                'whmcs_pid' => $payload['whmcs_pid'] ?? null,
                'ip_address' => $checkout->ip_address,
            ]);

            if ($checkoutProvider === 'whmcs') {
                $synced = WhmcsLeadSync::syncCheckout($lead->fresh());
                if ((string) $synced->whmcs_sync_status !== 'checkout_synced') {
                    $line->update([
                        'hosting_lead_id' => $lead->id,
                        'fulfilment_status' => 'failed',
                        'fulfilment_error' => $synced->whmcs_sync_error ?: 'WHMCS hosting sync failed.',
                    ]);

                    continue;
                }

                $synced->update([
                    'payment_status' => 'successful',
                    'status' => 'paid',
                    'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                    'payment_reference' => $checkout->payment_reference,
                ]);

                WhmcsLeadSync::syncPayment($synced->fresh());
            }

            AccountNotifier::send($user, new HostingOrderPaid($lead->fresh()));

            $line->update([
                'hosting_lead_id' => $lead->id,
                'fulfilment_status' => 'synced',
            ]);
        }
    }
}
