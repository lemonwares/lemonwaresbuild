<?php

namespace App\Support;

use App\Models\CatalogGroup;
use App\Models\CatalogPlan;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed plans (hosting, VPS, business email) and the one place prices are worked out.
 *
 * Hosting groups are also published into config('site.hosting_plans') in the current language,
 * so pages, cart and checkout read the admin's data without each knowing about the database.
 */
class Catalog
{
    protected const CACHE_KEY = 'catalog.v1';

    protected static ?array $memo = null;

    public static function reset(): void
    {
        self::$memo = null;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        self::$memo = null;
        self::apply();
    }

    /**
     * @return array{groups: \Illuminate\Support\Collection<int, CatalogGroup>, plans: \Illuminate\Support\Collection<int, CatalogPlan>}|null
     */
    public static function data(): ?array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            if (! Schema::hasTable('catalog_plans')) {
                return null;
            }

            $rows = Cache::rememberForever(self::CACHE_KEY, fn () => [
                'groups' => CatalogGroup::query()->orderBy('sort_order')->orderBy('id')->get()->map->getAttributes()->all(),
                'plans' => CatalogPlan::query()->orderBy('sort_order')->orderBy('id')->get()->map->getAttributes()->all(),
            ]);

            return self::$memo = [
                'groups' => CatalogGroup::hydrate($rows['groups']),
                'plans' => CatalogPlan::hydrate($rows['plans']),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public static function group(string $key): ?CatalogGroup
    {
        return self::data()['groups']->firstWhere('key', $key) ?? null;
    }

    public static function plan(string $group, string $key): ?CatalogPlan
    {
        return self::data()['plans']
            ->first(fn (CatalogPlan $plan) => $plan->group === $group && strtolower($plan->key) === strtolower($key)) ?? null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, CatalogPlan>
     */
    public static function activePlans(string $group): \Illuminate\Support\Collection
    {
        return (self::data()['plans'] ?? collect())
            ->filter(fn (CatalogPlan $plan) => $plan->group === $group && $plan->is_active)
            ->values();
    }

    /**
     * Billing cycles with the admin's discount percentages applied.
     *
     * @return array<string, array{key:string,months:int,discount_percent:float,whmcs:string}>
     */
    public static function cycles(): array
    {
        $saved = json_decode((string) IntegrationSetting::getValue('catalog.cycle_discounts', ''), true);
        $saved = is_array($saved) ? $saved : [];

        return collect(config('site.billing_cycles', []))
            ->map(function (array $cycle, string $key) use ($saved) {
                if (isset($saved[$key]) && is_numeric($saved[$key])) {
                    $cycle['discount_percent'] = max(0, min(90, (float) $saved[$key]));
                }

                return $cycle;
            })
            ->all();
    }

    /**
     * Price in NGN for one billing period: an exact per-cycle price if the admin set one,
     * otherwise the monthly price x months, less the plan's (or the global) cycle discount.
     */
    public static function periodNgn(float $monthlyNgn, string $cycleKey, ?CatalogPlan $plan = null): float
    {
        $cycles = self::cycles();
        $cycle = $cycles[$cycleKey] ?? $cycles['monthly'] ?? ['key' => 'monthly', 'months' => 1, 'discount_percent' => 0];
        $cycleKey = (string) ($cycle['key'] ?? 'monthly');

        $exact = (float) ($plan?->cycle_prices[$cycleKey] ?? 0);
        if ($exact > 0) {
            return round($exact, 2);
        }

        $discount = $plan && is_numeric($plan->cycle_discounts[$cycleKey] ?? null)
            ? (float) $plan->cycle_discounts[$cycleKey]
            : (float) ($cycle['discount_percent'] ?? 0);

        return round($monthlyNgn * (int) ($cycle['months'] ?? 1) * (1 - max(0, min(90, $discount)) / 100), 2);
    }

    public static function discountFor(string $cycleKey, ?CatalogPlan $plan = null): float
    {
        if ($plan && is_numeric($plan->cycle_discounts[$cycleKey] ?? null)) {
            return (float) $plan->cycle_discounts[$cycleKey];
        }

        return (float) (self::cycles()[$cycleKey]['discount_percent'] ?? 0);
    }

    /**
     * Publishes the hosting groups into config('site.hosting_plans') for the current language.
     */
    public static function apply(): void
    {
        $data = self::data();
        if ($data === null) {
            return;
        }

        $envPids = (array) config('site.whmcs_pids', []);
        $plans = [];

        foreach ($data['groups'] as $group) {
            if ($group->key === 'email' || ! $group->is_active) {
                continue;
            }

            $plans[$group->key] = [
                'slug' => $group->key,
                'name' => (string) $group->text('name'),
                'title' => (string) $group->text('title'),
                'summary' => (string) $group->text('summary'),
                'checkout_provider' => $group->checkout_provider,
                'highlights' => $group->textList('highlights'),
                'ui_tone' => $group->ui_tone,
                'whmcs_pid' => $group->whmcs_pid ?: ($envPids[$group->key] ?? ''),
                'specifications' => self::activePlans($group->key)->map(fn (CatalogPlan $plan) => self::specPayload($plan))->all(),
            ];
        }

        config(['site.hosting_plans' => $plans]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function specPayload(CatalogPlan $plan): array
    {
        $rows = $plan->specRows();
        $payload = [
            'key' => $plan->key,
            'label' => (string) $plan->text('label'),
            'default_price' => (float) $plan->price_ngn,
            'default_currency' => 'NGN',
            'default_billing_cycle' => 'monthly',
            'default_suffix' => '/mo',
            'description' => (string) $plan->text('description'),
            'badge' => (string) $plan->text('badge'),
            'featured' => (bool) $plan->is_featured,
            'image_url' => $plan->image_url,
            'whmcs_pid' => $plan->whmcs_pid,
            'highlights' => $plan->textList('highlights'),
            'details' => [
                'best_for' => (string) $plan->text('best_for'),
                'includes' => $plan->textList('includes'),
            ],
            'spec_rows' => $rows,
        ];

        foreach ($rows as $row) {
            if ($row['key'] !== '' && ! array_key_exists($row['key'], $payload)) {
                $payload[$row['key']] = $row['value'];
            }
        }

        return $payload;
    }
}
