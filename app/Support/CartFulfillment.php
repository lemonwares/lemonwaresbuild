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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a paid cart into domain, email and hosting orders.
 *
 * Safe to run more than once: lines that already produced an order are resumed instead of
 * duplicated, so staff can retry a partially fulfilled cart.
 */
class CartFulfillment
{
    public static function fulfill(SiteCheckout $checkout): SiteCheckout
    {
        $checkout->loadMissing(['items', 'user']);

        if ((string) $checkout->fulfilment_status === 'fulfilled') {
            return $checkout;
        }

        $errors = [];

        foreach ([
            'domains' => fn () => self::fulfillDomains($checkout),
            'email' => fn () => self::fulfillEmails($checkout),
            'hosting' => fn () => self::fulfillHosting($checkout),
        ] as $label => $step) {
            try {
                foreach ($step() as $lineError) {
                    $errors[] = $label.': '.$lineError;
                }
            } catch (\Throwable $e) {
                $errors[] = $label.': '.$e->getMessage();
                Log::warning('Cart '.$label.' fulfilment failed', [
                    'site_checkout_id' => $checkout->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $checkout->update([
            'fulfilment_status' => $errors === [] ? 'fulfilled' : 'partial',
            'fulfilment_error' => $errors === [] ? null : implode(' | ', $errors),
            'fulfilled_at' => now(),
            'status' => $errors === [] ? 'fulfilled' : 'paid',
        ]);

        return $checkout->fresh(['items', 'user']);
    }

    /**
     * Transfer codes are stored encrypted in the cart line payload; older lines hold plain text.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function eppFromPayload(array $payload): ?string
    {
        $encrypted = (string) ($payload['epp_code_encrypted'] ?? '');
        if ($encrypted !== '') {
            try {
                return trim(Crypt::decryptString($encrypted)) ?: null;
            } catch (\Throwable) {
                return null;
            }
        }

        $plain = trim((string) ($payload['epp_code'] ?? ''));

        return $plain !== '' ? $plain : null;
    }

    public static function childReference(SiteCheckout $checkout, string $suffix): ?string
    {
        $base = (string) $checkout->payment_reference;

        return $base !== '' ? substr($base.'-'.$suffix, 0, 80) : null;
    }

    /**
     * @return list<string>
     */
    protected static function fulfillDomains(SiteCheckout $checkout): array
    {
        $lines = $checkout->items->where('type', Cart::TYPE_DOMAIN)->values();
        if ($lines->isEmpty()) {
            return [];
        }

        if ($lines->every(fn (SiteCheckoutItem $line) => $line->fulfilment_status === 'synced')) {
            return [];
        }

        $user = $checkout->user;
        if (! $user) {
            throw new \RuntimeException('Missing user for domain fulfilment.');
        }

        $existingId = $lines->pluck('domain_checkout_id')->filter()->first();
        $domainCheckout = $existingId ? DomainCheckout::query()->with(['orders', 'user'])->find($existingId) : null;

        if (! $domainCheckout) {
            $domainCheckout = DB::transaction(function () use ($checkout, $lines, $user) {
                $domainCheckout = DomainCheckout::create([
                    'user_id' => $user->id,
                    'item_count' => $lines->count(),
                    'amount_usd' => $lines->sum(fn (SiteCheckoutItem $item) => (float) $item->amount_usd),
                    'amount_ngn' => $lines->sum(fn (SiteCheckoutItem $item) => (float) $item->amount_ngn),
                    'status' => 'paid',
                    'payment_provider' => $checkout->payment_provider ?: 'flutterwave',
                    'payment_status' => 'successful',
                    'payment_reference' => self::childReference($checkout, 'D'),
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
                        'epp_code' => $option === 'transfer' ? self::eppFromPayload($payload) : null,
                        'reg_period' => (int) ($payload['reg_period'] ?? 1),
                        'amount_usd' => (float) $line->amount_usd,
                        'amount_ngn' => (float) $line->amount_ngn,
                        'status' => 'paid',
                        'payment_provider' => $checkout->payment_provider ?: 'flutterwave',
                        'payment_status' => 'successful',
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
        }

        if (! in_array((string) $domainCheckout->whmcs_sync_status, ['checkout_synced', 'payment_synced'], true)) {
            $domainCheckout = WhmcsDomainOrderSync::syncCheckoutBundle($domainCheckout);
            if ((string) $domainCheckout->whmcs_sync_status !== 'checkout_synced') {
                throw new \RuntimeException($domainCheckout->whmcs_sync_error ?: 'WHMCS domain checkout sync failed.');
            }

            // AddOrder can reset the local status; the money is already in, so restore it before syncing payment.
            $domainCheckout->update([
                'payment_status' => 'successful',
                'status' => 'paid',
                'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
            ]);
        }

        if ((string) $domainCheckout->whmcs_sync_status !== 'payment_synced') {
            $domainCheckout = WhmcsDomainOrderSync::syncPaymentBundle($domainCheckout->fresh(['orders', 'user']));
            if ((string) $domainCheckout->whmcs_sync_status !== 'payment_synced') {
                throw new \RuntimeException($domainCheckout->whmcs_sync_error ?: 'WHMCS domain payment sync failed.');
            }

            AccountNotifier::send($user, new DomainOrderPaid($domainCheckout));
        }

        $checkout->items()->where('type', Cart::TYPE_DOMAIN)->update([
            'fulfilment_status' => 'synced',
            'fulfilment_error' => null,
        ]);

        return [];
    }

    /**
     * @return list<string>
     */
    protected static function fulfillEmails(SiteCheckout $checkout): array
    {
        $lines = $checkout->items->where('type', Cart::TYPE_EMAIL)->values();
        if ($lines->isEmpty()) {
            return [];
        }

        $user = $checkout->user;
        if (! $user) {
            throw new \RuntimeException('Missing user for email fulfilment.');
        }

        $errors = [];

        foreach ($lines as $line) {
            if ($line->email_order_id) {
                continue;
            }

            $payload = is_array($line->payload) ? $line->payload : [];
            $planKey = (string) ($payload['plan_key'] ?? '');
            $plan = EmailPricing::plan($planKey);
            if (! $plan) {
                $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => 'Invalid email plan.']);
                $errors[] = $line->label.': invalid email plan';

                continue;
            }

            $domain = DomainName::normalize((string) ($payload['domain'] ?? ''));
            if ($domain === null) {
                $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => 'Missing email domain.']);
                $errors[] = $line->label.': missing email domain';

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
            $provider = (string) ($plan['provider'] ?? 'titan');
            $fulfilmentMode = (string) ($plan['fulfilment_mode'] ?? 'manual');
            $isManual = $fulfilmentMode === 'manual';

            $order = DB::transaction(function () use ($checkout, $user, $planKey, $domain, $localParts, $cycle, $provider, $fulfilmentMode, $isManual, $line, $payload) {
                $order = EmailOrder::create([
                    'user_id' => $user->id,
                    'plan_key' => $planKey,
                    'plan_name' => (string) ($payload['plan_name'] ?? $planKey),
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
                    'payment_provider' => $checkout->payment_provider ?: 'flutterwave',
                    'payment_status' => 'successful',
                    'payment_reference' => self::childReference($checkout, 'E'.$line->id),
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

                $line->update([
                    'email_order_id' => $order->id,
                    'fulfilment_status' => 'synced',
                    'fulfilment_error' => null,
                ]);

                return $order;
            });

            $order->refresh();
            $order->applyPaidPeriod();
            $order->loadMissing('user');
            AccountNotifier::send($order->user, new EmailOrderPaid($order));
            EmailFulfilment::queued($order);
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    protected static function fulfillHosting(SiteCheckout $checkout): array
    {
        $lines = $checkout->items->where('type', Cart::TYPE_HOSTING)->values();
        if ($lines->isEmpty()) {
            return [];
        }

        $user = $checkout->user;
        if (! $user instanceof User) {
            throw new \RuntimeException('Missing user for hosting fulfilment.');
        }

        $errors = [];

        foreach ($lines as $line) {
            if ($line->fulfilment_status === 'synced') {
                continue;
            }

            $payload = is_array($line->payload) ? $line->payload : [];
            $planSlug = (string) ($payload['plan_slug'] ?? '');
            $specKey = (string) ($payload['spec_key'] ?? '');
            $checkoutProvider = (string) ($payload['checkout_provider'] ?? 'whmcs');
            $hostname = DomainName::normalize((string) ($payload['hostname'] ?? ''));
            $domainOption = in_array(($payload['domain_option'] ?? null), ['register', 'transfer', 'owndomain'], true)
                ? (string) $payload['domain_option']
                : 'owndomain';

            if ($checkoutProvider === 'whmcs' && $hostname === null) {
                $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => 'Hosting requires a domain.']);
                $errors[] = $line->label.': hosting requires a domain';

                continue;
            }

            $lead = $line->hosting_lead_id ? HostingLead::query()->find($line->hosting_lead_id) : null;

            if (! $lead) {
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
                    'spec_summary' => (string) ($payload['spec_summary'] ?? $payload['spec_label'] ?? $specKey),
                    'hostname' => $hostname,
                    'domain_option' => $checkoutProvider === 'whmcs' ? $domainOption : null,
                    'billing_cycle' => (string) ($payload['billing_cycle'] ?? 'monthly'),
                    'amount_usd' => (float) $line->amount_usd,
                    'amount_ngn' => (float) $line->amount_ngn,
                    'hosting_amount_usd' => (float) $line->amount_usd,
                    'hosting_amount_ngn' => (float) $line->amount_ngn,
                    'domain_amount_usd' => 0,
                    'domain_amount_ngn' => 0,
                    'checkout_provider' => $checkoutProvider,
                    'payment_provider' => $checkout->payment_provider ?: 'flutterwave',
                    'payment_status' => 'successful',
                    'payment_reference' => self::childReference($checkout, 'H'.$line->id),
                    'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                    'status' => 'paid',
                    'whmcs_pid' => $payload['whmcs_pid'] ?? null,
                    'ip_address' => $checkout->ip_address,
                ]);

                $line->update(['hosting_lead_id' => $lead->id, 'fulfilment_status' => 'created']);
            }

            if ($checkoutProvider === 'whmcs') {
                if (! in_array((string) $lead->whmcs_sync_status, ['checkout_synced', 'payment_synced'], true)) {
                    $lead = WhmcsLeadSync::syncCheckout($lead->fresh());
                    if ((string) $lead->whmcs_sync_status !== 'checkout_synced') {
                        $message = $lead->whmcs_sync_error ?: 'WHMCS hosting sync failed.';
                        $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => $message]);
                        $errors[] = $line->label.': '.$message;

                        continue;
                    }

                    $lead->update([
                        'payment_status' => 'successful',
                        'status' => 'paid',
                        'flutterwave_transaction_id' => $checkout->flutterwave_transaction_id,
                    ]);
                }

                if ((string) $lead->whmcs_sync_status !== 'payment_synced') {
                    $lead = WhmcsLeadSync::syncPayment($lead->fresh());
                    if ((string) $lead->whmcs_sync_status !== 'payment_synced') {
                        $message = $lead->whmcs_sync_error ?: 'WHMCS hosting payment sync failed.';
                        $line->update(['fulfilment_status' => 'failed', 'fulfilment_error' => $message]);
                        $errors[] = $line->label.': '.$message;

                        continue;
                    }
                }
            }

            AccountNotifier::send($user, new HostingOrderPaid($lead->fresh()));

            $line->update([
                'hosting_lead_id' => $lead->id,
                'fulfilment_status' => 'synced',
                'fulfilment_error' => null,
            ]);
        }

        return $errors;
    }
}
