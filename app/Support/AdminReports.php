<?php

namespace App\Support;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\SiteCheckout;
use App\Models\User;
use App\Models\WhmcsService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Sales figures for the admin reports page and dashboard.
 *
 * Revenue is counted on the product records (email orders, hosting requests, domain orders), which
 * also covers items bought through the cart. Cart discounts and recorded refunds are subtracted.
 * Orders are placed in the month they were created.
 */
class AdminReports
{
    public const PRODUCTS = [
        'email' => 'Email',
        'hosting' => 'Hosting',
        'domain' => 'Domains',
    ];

    /**
     * Paid sales in the range, one row per order.
     *
     * @return Collection<int, array{date:CarbonInterface,product:string,id:int,label:string,customer:?string,amount_ngn:float,refunded_ngn:float,reference:?string,provider:?string}>
     */
    public static function sales(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $range = [$from->startOfDay(), $to->endOfDay()];

        $email = EmailOrder::query()->with('user:id,email')->whereBetween('created_at', $range)->get()
            ->filter(fn (EmailOrder $o) => $o->isPaid())
            ->map(fn (EmailOrder $o) => self::row('email', $o, $o->domain.' · '.$o->plan_name, $o->user?->email, 0.0));

        $hosting = HostingLead::query()->with('user:id,email')->whereBetween('created_at', $range)->get()
            ->filter(fn (HostingLead $o) => $o->isPaid() || $o->status === 'refunded')
            ->map(fn (HostingLead $o) => self::row('hosting', $o, trim($o->plan_name.' · '.($o->spec_label ?: '')), $o->user?->email ?: $o->email, $o->status === 'refunded' ? (float) $o->amount_ngn : 0.0));

        $domain = DomainOrder::query()->with('user:id,email')->whereBetween('created_at', $range)->get()
            ->filter(fn (DomainOrder $o) => $o->wasPaid())
            ->map(fn (DomainOrder $o) => self::row('domain', $o, $o->domain.' · '.$o->option, $o->user?->email, (float) ($o->refunded_amount_ngn ?? 0)));

        return $email->concat($hosting)->concat($domain)->sortBy('date')->values();
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sales = self::sales($from, $to);
        $range = [$from->startOfDay(), $to->endOfDay()];

        $cartDiscounts = (float) SiteCheckout::query()->whereBetween('created_at', $range)->where('status', '!=', 'cancelled')->sum('discount_ngn');
        $cartRefunds = (float) SiteCheckout::query()->whereBetween('created_at', $range)->sum('refunded_amount_ngn')
            // Part-refunds recorded on a domain checkout (full ones are already on each domain order).
            + (float) DomainCheckout::query()->whereBetween('created_at', $range)->where('status', 'partially_refunded')->sum('refunded_amount_ngn');

        $months = [];
        for ($cursor = $from->startOfMonth(); $cursor <= $to; $cursor = $cursor->addMonth()) {
            $months[$cursor->format('Y-m')] = ['label' => $cursor->format('M Y'), 'email' => 0.0, 'hosting' => 0.0, 'domain' => 0.0, 'total' => 0.0, 'orders' => 0, 'customers' => 0];
        }

        foreach ($sales as $sale) {
            $key = $sale['date']->format('Y-m');
            if (! isset($months[$key])) {
                continue;
            }
            $net = $sale['amount_ngn'] - $sale['refunded_ngn'];
            $months[$key][$sale['product']] += $net;
            $months[$key]['total'] += $net;
            $months[$key]['orders']++;
        }

        User::query()->customers()->whereBetween('created_at', $range)->get(['created_at'])
            ->each(function (User $user) use (&$months) {
                $key = $user->created_at->format('Y-m');
                if (isset($months[$key])) {
                    $months[$key]['customers']++;
                }
            });

        $gross = (float) $sales->sum('amount_ngn');
        $refunds = (float) $sales->sum('refunded_ngn') + $cartRefunds;

        $checkouts = SiteCheckout::query()->whereBetween('created_at', $range)->get(['status', 'payment_status']);
        $paidCheckouts = $checkouts->filter(fn ($c) => $c->wasPaid())->count();

        return [
            'months' => $months,
            'gross' => $gross,
            'refunds' => $refunds,
            'discounts' => $cartDiscounts,
            'net' => $gross - $refunds - $cartDiscounts,
            'orders' => $sales->count(),
            'average' => $sales->count() > 0 ? $gross / $sales->count() : 0.0,
            'byProduct' => collect(self::PRODUCTS)->map(fn ($label, $key) => [
                'label' => $label,
                'amount' => (float) $sales->where('product', $key)->sum(fn ($s) => $s['amount_ngn'] - $s['refunded_ngn']),
                'orders' => $sales->where('product', $key)->count(),
            ])->all(),
            'bestSellers' => $sales
                ->groupBy(fn ($s) => self::PRODUCTS[$s['product']].' · '.self::bestSellerName($s))
                ->map(fn (Collection $group, string $name) => ['name' => $name, 'orders' => $group->count(), 'amount' => (float) $group->sum('amount_ngn')])
                ->sortByDesc('amount')
                ->take(10)
                ->values()
                ->all(),
            'funnel' => [
                'checkouts' => $checkouts->count(),
                'paid' => $paidCheckouts,
                'rate' => $checkouts->count() > 0 ? round($paidCheckouts / $checkouts->count() * 100, 1) : 0.0,
            ],
            'newCustomers' => array_sum(array_column($months, 'customers')),
        ];
    }

    /**
     * Unpaid orders that are still open, and renewals coming up.
     *
     * @return array<string, mixed>
     */
    public static function attention(int $days = 30): array
    {
        $soon = now()->addDays($days);

        $unpaidEmail = EmailOrder::query()->where('status', 'awaiting_payment')->get(['amount_ngn']);
        $unpaidHosting = HostingLead::query()->whereIn('status', ['awaiting_payment', 'pending'])->get(['amount_ngn']);
        $unpaidCart = SiteCheckout::query()->whereIn('status', ['awaiting_payment', 'payment_failed'])->get(['amount_ngn']);
        $unpaidDomain = DomainOrder::query()->whereNull('domain_checkout_id')->whereIn('status', ['awaiting_payment', 'payment_failed'])->get(['amount_ngn']);

        return [
            'unpaidCount' => $unpaidEmail->count() + $unpaidHosting->count() + $unpaidCart->count() + $unpaidDomain->count(),
            'unpaidValue' => (float) ($unpaidEmail->sum('amount_ngn') + $unpaidHosting->sum('amount_ngn') + $unpaidCart->sum('amount_ngn') + $unpaidDomain->sum('amount_ngn')),
            'emailRenewals' => EmailOrder::query()->with('user:id,email')
                ->whereNotNull('period_ends_at')
                ->whereBetween('period_ends_at', [now(), $soon])
                ->whereNotIn('status', ['deactivated', 'expired', 'cancelled'])
                ->orderBy('period_ends_at')
                ->get(),
            'whmcsRenewals' => WhmcsService::query()
                ->whereNotNull('next_due_date')
                ->whereBetween('next_due_date', [now()->startOfDay(), $soon])
                ->whereRaw('LOWER(status) = ?', ['active'])
                ->orderBy('next_due_date')
                ->get(),
        ];
    }

    /**
     * @param  EmailOrder|HostingLead|DomainOrder  $order
     * @return array<string, mixed>
     */
    private static function row(string $product, $order, string $label, ?string $customer, float $refunded): array
    {
        return [
            'date' => $order->created_at,
            'product' => $product,
            'id' => (int) $order->id,
            'label' => $label,
            'customer' => $customer,
            'amount_ngn' => (float) $order->amount_ngn,
            'refunded_ngn' => $refunded,
            'reference' => $order->payment_reference,
            'provider' => $order->payment_provider,
            'plan' => $product === 'domain' ? '.'.ltrim((string) strrchr((string) $order->domain, '.'), '.') : (string) $order->plan_name,
        ];
    }

    /**
     * @param  array<string, mixed>  $sale
     */
    private static function bestSellerName(array $sale): string
    {
        return $sale['product'] === 'domain' ? $sale['plan'].' '.(str_contains($sale['label'], 'transfer') ? 'transfers' : 'registrations') : $sale['plan'];
    }
}
