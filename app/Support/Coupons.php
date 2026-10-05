<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\SiteCheckout;
use App\Models\User;

/**
 * Discount codes on the cart checkout.
 */
class Coupons
{
    /**
     * @param  list<array<string, mixed>>  $items  cart items (type, amount_ngn, amount_usd)
     * @return array{ok:bool,message:string,coupon?:Coupon,discount_ngn?:float,discount_usd?:float}
     */
    public static function evaluate(string $code, array $items, ?User $user): array
    {
        $coupon = Coupon::query()->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();

        if (! $coupon || $coupon->statusLabel() !== 'Active') {
            return ['ok' => false, 'message' => __('cart.coupon_invalid')];
        }

        if ($user && $coupon->max_uses_per_customer !== null) {
            $used = SiteCheckout::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->accountOwner()->id)
                ->where(fn ($query) => $query
                    ->whereIn('status', ['paid', 'fulfilled', 'submitted'])
                    ->orWhere('payment_status', 'successful'))
                ->count();
            if ($used >= $coupon->max_uses_per_customer) {
                return ['ok' => false, 'message' => __('cart.coupon_already_used')];
            }
        }

        $eligibleNgn = 0.0;
        $eligibleUsd = 0.0;
        $totalNgn = 0.0;
        foreach ($items as $item) {
            $totalNgn += (float) ($item['amount_ngn'] ?? 0);
            if ($coupon->appliesTo((string) ($item['type'] ?? ''))) {
                $eligibleNgn += (float) ($item['amount_ngn'] ?? 0);
                $eligibleUsd += (float) ($item['amount_usd'] ?? 0);
            }
        }

        if ($eligibleNgn <= 0) {
            return ['ok' => false, 'message' => __('cart.coupon_not_for_items')];
        }

        if ($coupon->min_order_ngn !== null && $totalNgn < (float) $coupon->min_order_ngn) {
            return ['ok' => false, 'message' => __('cart.coupon_min_order', ['amount' => HostingPricing::formatMoney((float) $coupon->min_order_ngn)])];
        }

        if ($coupon->type === 'percent') {
            $ratio = min(100, (float) $coupon->value) / 100;
            $discountNgn = round($eligibleNgn * $ratio, 2);
            $discountUsd = round($eligibleUsd * $ratio, 2);
        } else {
            $discountNgn = round(min((float) $coupon->value, $eligibleNgn), 2);
            $discountUsd = $eligibleNgn > 0 ? round($eligibleUsd * ($discountNgn / $eligibleNgn), 2) : 0.0;
        }

        // Never discount the whole order to zero: Flutterwave needs at least ₦1.
        $discountNgn = min($discountNgn, max(0, $totalNgn - 1));

        return [
            'ok' => true,
            'message' => __('cart.coupon_applied', ['code' => $coupon->code, 'amount' => HostingPricing::formatMoney($discountNgn)]),
            'coupon' => $coupon,
            'discount_ngn' => $discountNgn,
            'discount_usd' => $discountUsd,
        ];
    }

    /**
     * Counts a use once the order is paid. A customer who already paid keeps the discount
     * even if the last use was taken by someone else in the meantime.
     */
    public static function redeem(SiteCheckout $checkout): void
    {
        if (! $checkout->coupon_id) {
            return;
        }

        Coupon::query()->whereKey($checkout->coupon_id)->increment('used_count');
    }
}
