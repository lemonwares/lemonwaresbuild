<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class Cart
{
    public const SESSION_KEY = 'cart.items';

    public const LEGACY_DOMAIN_KEY = 'domain_cart.items';

    public const MAX_ITEMS = 25;

    public const TYPE_DOMAIN = 'domain';

    public const TYPE_EMAIL = 'email';

    public const TYPE_HOSTING = 'hosting';

    /**
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        self::migrateLegacyDomainCart();

        $raw = Session::get(self::SESSION_KEY, []);
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter($raw, function ($item) {
            return is_array($item)
                && filled($item['id'] ?? null)
                && in_array(($item['type'] ?? ''), [self::TYPE_DOMAIN, self::TYPE_EMAIL, self::TYPE_HOSTING], true);
        }));
    }

    public static function count(): int
    {
        return count(self::items());
    }

    public static function isEmpty(): bool
    {
        return self::count() < 1;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function itemsOfType(string $type): array
    {
        return array_values(array_filter(
            self::items(),
            fn (array $item) => ($item['type'] ?? '') === $type,
        ));
    }

    /**
     * @return array{ok: bool, message?: string, item?: array<string, mixed>, count?: int}
     */
    public static function addDomain(string $domain, string $option, int $regPeriod = 1): array
    {
        $option = strtolower(trim($option));
        if (! in_array($option, ['register', 'transfer'], true)) {
            return ['ok' => false, 'message' => __('domain.cart_invalid_option')];
        }

        $normalized = DomainName::normalize($domain);
        if ($normalized === null) {
            return ['ok' => false, 'message' => __('domain.invalid_domain')];
        }

        $items = self::items();
        foreach ($items as $existing) {
            if (($existing['type'] ?? '') !== self::TYPE_DOMAIN) {
                continue;
            }
            if (strcasecmp((string) ($existing['domain'] ?? ''), $normalized) === 0) {
                return [
                    'ok' => false,
                    'message' => __('domain.cart_already_has_domain', ['domain' => $normalized]),
                    'count' => count($items),
                ];
            }
        }

        if (count($items) >= self::MAX_ITEMS) {
            return ['ok' => false, 'message' => __('cart.full', ['max' => self::MAX_ITEMS])];
        }

        $quote = WhmcsDomainPricing::quote($normalized, $option, $regPeriod);
        if (! ($quote['ok'] ?? false) || (float) ($quote['amount_ngn'] ?? 0) <= 0) {
            return [
                'ok' => false,
                'message' => (string) ($quote['message'] ?? __('domain.quote_unavailable')),
            ];
        }

        $item = [
            'id' => (string) Str::uuid(),
            'type' => self::TYPE_DOMAIN,
            'label' => $normalized,
            'domain' => $normalized,
            'option' => $option,
            'reg_period' => (int) ($quote['reg_period'] ?? 1),
            'amount_usd' => (float) ($quote['amount_usd'] ?? 0),
            'amount_ngn' => (float) ($quote['amount_ngn'] ?? 0),
            'display' => (string) ($quote['display'] ?? ''),
            'period_label' => $quote['period_label'] ?? null,
            'available_periods' => $quote['available_periods'] ?? [1],
        ];

        $items[] = $item;
        self::put($items);

        return [
            'ok' => true,
            'item' => $item,
            'count' => count($items),
            'message' => __('domain.cart_added', ['domain' => $normalized]),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, item?: array<string, mixed>, count?: int}
     */
    public static function addEmail(string $planKey, string $billingCycle): array
    {
        $planKey = strtolower(trim($planKey));
        $billingCycle = strtolower(trim($billingCycle));
        $plan = EmailPricing::plan($planKey);

        if (! $plan) {
            return ['ok' => false, 'message' => __('email.invalid_plan')];
        }

        $cycles = EmailPricing::billingCycles();
        if (! in_array($billingCycle, $cycles, true)) {
            $billingCycle = 'monthly';
        }

        $provider = (string) ($plan['provider'] ?? 'lemonmail');
        $fulfilmentMode = (string) ($plan['fulfilment_mode'] ?? 'auto');
        $isManual = $fulfilmentMode === 'manual';
        $requiresPayment = $provider === 'lemonmail' || ! $isManual;

        // Manual partner plans stay on the request flow, not the paid cart.
        if (! $requiresPayment) {
            return ['ok' => false, 'message' => __('cart.email_manual_use_checkout')];
        }

        $items = self::items();
        if (count($items) >= self::MAX_ITEMS) {
            return ['ok' => false, 'message' => __('cart.full', ['max' => self::MAX_ITEMS])];
        }

        $presented = EmailPricing::presentPlan($plan, $billingCycle);
        $amountUsd = EmailPricing::periodTotalUsd((float) $plan['monthly_usd'], $billingCycle);
        $amountNgn = round($amountUsd * HostingPricing::usdToNgnRate(), 2);

        $item = [
            'id' => (string) Str::uuid(),
            'type' => self::TYPE_EMAIL,
            'label' => (string) ($presented['name'] ?? $planKey),
            'plan_key' => $planKey,
            'plan_name' => (string) ($presented['name'] ?? $planKey),
            'provider' => $provider,
            'fulfilment_mode' => $fulfilmentMode,
            'mailbox_count' => (int) ($plan['mailboxes'] ?? 1),
            'billing_cycle' => $billingCycle,
            'billing_cycle_label' => (string) ($presented['billing_cycle_label'] ?? EmailPricing::cycleLabel($billingCycle)),
            'amount_usd' => $amountUsd,
            'amount_ngn' => $amountNgn,
            'display' => HostingPricing::ngnPriceDisplay($amountNgn),
            'domain' => null,
            'mailboxes' => EmailPricing::defaultLocalParts((int) ($plan['mailboxes'] ?? 1)),
        ];

        $items[] = $item;
        self::put($items);

        return [
            'ok' => true,
            'item' => $item,
            'count' => count($items),
            'message' => __('cart.email_added', ['plan' => $item['plan_name']]),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, item?: array<string, mixed>, count?: int}
     */
    public static function addHosting(string $planSlug, string $specKey, string $billingCycle): array
    {
        $planSlug = strtolower(trim($planSlug));
        $specKey = strtolower(trim($specKey));
        $billingCycle = strtolower(trim($billingCycle));

        $plans = config('site.hosting_plans', []);
        $plan = is_array($plans[$planSlug] ?? null) ? $plans[$planSlug] : null;
        if (! $plan) {
            return ['ok' => false, 'message' => __('cart.hosting_invalid_plan')];
        }

        $cycles = array_keys(config('site.billing_cycles', []) ?: []);
        if ($cycles === [] || ! in_array($billingCycle, $cycles, true)) {
            $billingCycle = 'monthly';
        }

        $specs = collect($plan['specifications'] ?? $plan['specs'] ?? []);
        $spec = $specs->first(fn ($row) => strtolower((string) ($row['key'] ?? '')) === $specKey);
        if (! is_array($spec)) {
            return ['ok' => false, 'message' => __('cart.hosting_invalid_spec')];
        }

        $items = self::items();
        if (count($items) >= self::MAX_ITEMS) {
            return ['ok' => false, 'message' => __('cart.full', ['max' => self::MAX_ITEMS])];
        }

        $monthlyNgn = HostingPricing::monthlyNgnForSpec($planSlug, $specKey);
        if ($monthlyNgn <= 0) {
            $monthlyNgn = HostingPricing::amountAsNgn(
                (float) ($spec['default_price'] ?? 0),
                (string) ($spec['default_currency'] ?? 'NGN'),
            );
        }

        $amountNgn = HostingPricing::periodTotalNgn($monthlyNgn, $billingCycle);
        $rate = max(1.0, HostingPricing::usdToNgnRate());
        $amountUsd = round($amountNgn / $rate, 2);
        $checkoutProvider = (string) ($plan['checkout_provider'] ?? 'whmcs');
        $whmcsPid = WhmcsSettings::resolvePid($planSlug, $specKey);

        $item = [
            'id' => (string) Str::uuid(),
            'type' => self::TYPE_HOSTING,
            'label' => (string) ($plan['title'] ?? $planSlug).' · '.(string) ($spec['label'] ?? $specKey),
            'plan_slug' => $planSlug,
            'plan_name' => (string) ($plan['title'] ?? $planSlug),
            'spec_key' => $specKey,
            'spec_label' => (string) ($spec['label'] ?? $specKey),
            'billing_cycle' => $billingCycle,
            'billing_cycle_label' => HostingPricing::cycleLabel($billingCycle),
            'checkout_provider' => $checkoutProvider,
            'whmcs_pid' => $whmcsPid,
            'amount_usd' => $amountUsd,
            'amount_ngn' => $amountNgn,
            'display' => HostingPricing::ngnPriceDisplay($amountNgn),
            'hostname' => null,
            'domain_option' => 'register',
        ];

        $items[] = $item;
        self::put($items);

        return [
            'ok' => true,
            'item' => $item,
            'count' => count($items),
            'message' => __('cart.hosting_added', ['plan' => $item['label']]),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, item?: array<string, mixed>, count?: int, totals?: array<string, mixed>}
     */
    public static function updateDomainPeriod(string $itemId, int $regPeriod): array
    {
        $items = self::items();

        foreach ($items as $index => $item) {
            if ((string) ($item['id'] ?? '') !== $itemId || ($item['type'] ?? '') !== self::TYPE_DOMAIN) {
                continue;
            }

            $quote = WhmcsDomainPricing::quote(
                (string) $item['domain'],
                (string) $item['option'],
                $regPeriod,
            );

            if (! ($quote['ok'] ?? false) || (float) ($quote['amount_ngn'] ?? 0) <= 0) {
                return [
                    'ok' => false,
                    'message' => (string) ($quote['message'] ?? __('domain.quote_unavailable')),
                    'count' => count($items),
                ];
            }

            $items[$index] = array_merge($item, [
                'reg_period' => (int) ($quote['reg_period'] ?? 1),
                'amount_usd' => (float) ($quote['amount_usd'] ?? 0),
                'amount_ngn' => (float) ($quote['amount_ngn'] ?? 0),
                'display' => (string) ($quote['display'] ?? ''),
                'period_label' => $quote['period_label'] ?? null,
                'available_periods' => $quote['available_periods'] ?? [1],
            ]);

            self::put($items);

            return [
                'ok' => true,
                'item' => $items[$index],
                'count' => count($items),
                'totals' => self::totals(),
            ];
        }

        return ['ok' => false, 'message' => __('domain.cart_item_missing')];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message?: string, item?: array<string, mixed>, count?: int, totals?: array<string, mixed>}
     */
    public static function updateItem(string $itemId, array $payload): array
    {
        $items = self::items();

        foreach ($items as $index => $item) {
            if ((string) ($item['id'] ?? '') !== $itemId) {
                continue;
            }

            $type = (string) ($item['type'] ?? '');

            if ($type === self::TYPE_DOMAIN && isset($payload['reg_period'])) {
                return self::updateDomainPeriod($itemId, (int) $payload['reg_period']);
            }

            if ($type === self::TYPE_EMAIL) {
                if (isset($payload['domain'])) {
                    $normalized = DomainName::normalize((string) $payload['domain']);
                    if ($normalized === null) {
                        return ['ok' => false, 'message' => __('email.invalid_domain')];
                    }
                    $items[$index]['domain'] = $normalized;
                }
                if (isset($payload['mailboxes']) && is_array($payload['mailboxes'])) {
                    $items[$index]['mailboxes'] = array_values(array_map(
                        fn ($part) => strtolower(trim((string) $part)),
                        $payload['mailboxes'],
                    ));
                }
            }

            if ($type === self::TYPE_HOSTING) {
                if (isset($payload['hostname'])) {
                    $normalized = DomainName::normalize((string) $payload['hostname']);
                    $items[$index]['hostname'] = $normalized;
                }
                if (isset($payload['domain_option'])) {
                    $opt = strtolower((string) $payload['domain_option']);
                    $items[$index]['domain_option'] = in_array($opt, ['register', 'transfer', 'owndomain'], true)
                        ? $opt
                        : 'register';
                }
            }

            self::put($items);

            return [
                'ok' => true,
                'item' => $items[$index],
                'count' => count($items),
                'totals' => self::totals(),
            ];
        }

        return ['ok' => false, 'message' => __('cart.item_missing')];
    }

    /**
     * @return array{ok: bool, message?: string, count?: int, totals?: array<string, mixed>}
     */
    public static function remove(string $itemId): array
    {
        $items = self::items();
        $filtered = array_values(array_filter(
            $items,
            fn (array $item) => (string) ($item['id'] ?? '') !== $itemId,
        ));

        if (count($filtered) === count($items)) {
            return ['ok' => false, 'message' => __('cart.item_missing'), 'count' => count($items)];
        }

        self::put($filtered);

        return [
            'ok' => true,
            'count' => count($filtered),
            'totals' => self::totals(),
            'message' => __('cart.removed'),
        ];
    }

    public static function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::LEGACY_DOMAIN_KEY);
    }

    /**
     * @return array{amount_usd: float, amount_ngn: float, display: string, count: int}
     */
    public static function totals(): array
    {
        $items = self::items();
        $usd = 0.0;
        $ngn = 0.0;

        foreach ($items as $item) {
            $usd += (float) ($item['amount_usd'] ?? 0);
            $ngn += (float) ($item['amount_ngn'] ?? 0);
        }

        return [
            'amount_usd' => round($usd, 2),
            'amount_ngn' => round($ngn, 2),
            'display' => HostingPricing::ngnPriceDisplay(round($ngn, 2)),
            'count' => count($items),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, items?: list<array<string, mixed>>, totals?: array<string, mixed>}
     */
    public static function refreshQuotes(): array
    {
        $items = self::items();
        $refreshed = [];

        foreach ($items as $item) {
            $type = (string) ($item['type'] ?? '');

            if ($type === self::TYPE_DOMAIN) {
                $quote = WhmcsDomainPricing::quote(
                    (string) $item['domain'],
                    (string) $item['option'],
                    (int) ($item['reg_period'] ?? 1),
                );

                if (! ($quote['ok'] ?? false) || (float) ($quote['amount_ngn'] ?? 0) <= 0) {
                    return [
                        'ok' => false,
                        'message' => __('domain.cart_requote_failed', [
                            'domain' => (string) $item['domain'],
                            'detail' => (string) ($quote['message'] ?? __('domain.quote_unavailable')),
                        ]),
                    ];
                }

                $refreshed[] = array_merge($item, [
                    'reg_period' => (int) ($quote['reg_period'] ?? 1),
                    'amount_usd' => (float) ($quote['amount_usd'] ?? 0),
                    'amount_ngn' => (float) ($quote['amount_ngn'] ?? 0),
                    'display' => (string) ($quote['display'] ?? ''),
                    'period_label' => $quote['period_label'] ?? null,
                    'available_periods' => $quote['available_periods'] ?? [1],
                ]);

                continue;
            }

            if ($type === self::TYPE_EMAIL) {
                $plan = EmailPricing::plan((string) $item['plan_key']);
                if (! $plan) {
                    return [
                        'ok' => false,
                        'message' => __('cart.email_requote_failed', ['plan' => (string) ($item['label'] ?? '')]),
                    ];
                }

                $cycle = (string) ($item['billing_cycle'] ?? 'monthly');
                $presented = EmailPricing::presentPlan($plan, $cycle);
                $amountUsd = EmailPricing::periodTotalUsd((float) $plan['monthly_usd'], $cycle);
                $amountNgn = round($amountUsd * HostingPricing::usdToNgnRate(), 2);

                $refreshed[] = array_merge($item, [
                    'plan_name' => (string) ($presented['name'] ?? $item['plan_name']),
                    'label' => (string) ($presented['name'] ?? $item['label']),
                    'mailbox_count' => (int) ($plan['mailboxes'] ?? $item['mailbox_count'] ?? 1),
                    'billing_cycle_label' => (string) ($presented['billing_cycle_label'] ?? ''),
                    'amount_usd' => $amountUsd,
                    'amount_ngn' => $amountNgn,
                    'display' => HostingPricing::ngnPriceDisplay($amountNgn),
                ]);

                continue;
            }

            if ($type === self::TYPE_HOSTING) {
                $plans = config('site.hosting_plans', []);
                $plan = is_array($plans[$item['plan_slug']] ?? null) ? $plans[$item['plan_slug']] : null;
                if (! $plan) {
                    return [
                        'ok' => false,
                        'message' => __('cart.hosting_requote_failed', ['plan' => (string) ($item['label'] ?? '')]),
                    ];
                }

                $monthlyNgn = HostingPricing::monthlyNgnForSpec((string) $item['plan_slug'], (string) $item['spec_key']);
                if ($monthlyNgn <= 0) {
                    return [
                        'ok' => false,
                        'message' => __('cart.hosting_requote_failed', ['plan' => (string) ($item['label'] ?? '')]),
                    ];
                }

                $cycle = (string) ($item['billing_cycle'] ?? 'monthly');
                $amountNgn = HostingPricing::periodTotalNgn($monthlyNgn, $cycle);
                $rate = max(1.0, HostingPricing::usdToNgnRate());
                $amountUsd = round($amountNgn / $rate, 2);

                $refreshed[] = array_merge($item, [
                    'amount_usd' => $amountUsd,
                    'amount_ngn' => $amountNgn,
                    'display' => HostingPricing::ngnPriceDisplay($amountNgn),
                    'whmcs_pid' => WhmcsSettings::resolvePid((string) $item['plan_slug'], (string) $item['spec_key']),
                ]);

                continue;
            }

            $refreshed[] = $item;
        }

        self::put($refreshed);

        return [
            'ok' => true,
            'items' => $refreshed,
            'totals' => self::totals(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected static function put(array $items): void
    {
        Session::put(self::SESSION_KEY, array_values($items));
        Session::forget(self::LEGACY_DOMAIN_KEY);
    }

    protected static function migrateLegacyDomainCart(): void
    {
        if (Session::has(self::SESSION_KEY)) {
            return;
        }

        $legacy = Session::get(self::LEGACY_DOMAIN_KEY, []);
        if (! is_array($legacy) || $legacy === []) {
            return;
        }

        $migrated = [];
        foreach ($legacy as $item) {
            if (! is_array($item) || ! filled($item['domain'] ?? null)) {
                continue;
            }

            $migrated[] = array_merge($item, [
                'id' => (string) ($item['id'] ?? Str::uuid()),
                'type' => self::TYPE_DOMAIN,
                'label' => (string) $item['domain'],
            ]);
        }

        if ($migrated !== []) {
            Session::put(self::SESSION_KEY, $migrated);
        }

        Session::forget(self::LEGACY_DOMAIN_KEY);
    }
}
