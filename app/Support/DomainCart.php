<?php

namespace App\Support;

/**
 * @deprecated Prefer Cart — kept as a thin domain adapter for existing callers.
 */
class DomainCart
{
    public const SESSION_KEY = Cart::LEGACY_DOMAIN_KEY;

    public const MAX_ITEMS = Cart::MAX_ITEMS;

    public static function items(): array
    {
        return array_map(function (array $item) {
            unset($item['type'], $item['label']);

            return $item;
        }, Cart::itemsOfType(Cart::TYPE_DOMAIN));
    }

    public static function count(): int
    {
        return count(Cart::itemsOfType(Cart::TYPE_DOMAIN));
    }

    public static function isEmpty(): bool
    {
        return self::count() < 1;
    }

    public static function add(string $domain, string $option, int $regPeriod = 1): array
    {
        return Cart::addDomain($domain, $option, $regPeriod);
    }

    public static function updatePeriod(string $itemId, int $regPeriod): array
    {
        return Cart::updateDomainPeriod($itemId, $regPeriod);
    }

    public static function remove(string $itemId): array
    {
        return Cart::remove($itemId);
    }

    public static function clear(): void
    {
        foreach (Cart::itemsOfType(Cart::TYPE_DOMAIN) as $item) {
            Cart::remove((string) $item['id']);
        }
    }

    public static function totals(): array
    {
        $items = Cart::itemsOfType(Cart::TYPE_DOMAIN);
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

    public static function refreshQuotes(): array
    {
        return Cart::refreshQuotes();
    }
}
