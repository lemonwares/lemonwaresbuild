<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\SiteCheckout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * One list of every order type: email, hosting, domains and cart checkouts.
 */
class AdminOrderController extends Controller
{
    /**
     * Rows pulled per source before merging. Plenty for a combined, newest-first view.
     */
    private const PER_SOURCE = 500;

    public const TYPES = [
        'email' => 'Email',
        'hosting' => 'Hosting',
        'domain' => 'Domain',
        'cart' => 'Cart',
    ];

    public function index(Request $request): View
    {
        $type = (string) $request->query('type', '');
        $payment = (string) $request->query('payment', '');
        $search = trim((string) $request->query('q', ''));

        $rows = collect();
        foreach (array_keys(self::TYPES) as $key) {
            if ($type === '' || $type === $key) {
                $rows = $rows->merge($this->rowsFor($key, $search));
            }
        }

        if ($payment === 'paid') {
            $rows = $rows->where('paid', true);
        } elseif ($payment === 'unpaid') {
            $rows = $rows->where('paid', false)->where('cancelled', false);
        } elseif ($payment === 'cancelled') {
            $rows = $rows->where('cancelled', true);
        }

        $rows = $rows->sortByDesc('created_at')->values();

        $perPage = 25;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $orders = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.orders.index', [
            'orders' => $orders,
            'type' => $type,
            'payment' => $payment,
            'search' => $search,
            'types' => self::TYPES,
            'paidTotalNgn' => $rows->where('paid', true)->sum('amount_ngn'),
            'unpaidCount' => $rows->where('paid', false)->where('cancelled', false)->count(),
            'paidCount' => $rows->where('paid', true)->count(),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function rowsFor(string $type, string $search): Collection
    {
        return match ($type) {
            'email' => $this->search(EmailOrder::query()->with('user'), $search, ['domain', 'payment_reference'])
                ->latest()->limit(self::PER_SOURCE)->get()
                ->map(fn (EmailOrder $o) => $this->row('email', $o, $o->domain.' · '.$o->plan_name, $o->user?->email, $o->isPaid(), route('admin.email-orders.show', $o))),
            'hosting' => $this->search(HostingLead::query()->with('user'), $search, ['email', 'full_name', 'hostname', 'payment_reference'])
                ->latest()->limit(self::PER_SOURCE)->get()
                ->map(fn (HostingLead $o) => $this->row('hosting', $o, trim($o->plan_name.' · '.($o->hostname ?: $o->full_name), ' ·'), $o->user?->email ?: $o->email, $o->isPaid(), route('admin.hosting-leads.show', $o))),
            'domain' => $this->search(DomainOrder::query()->with(['user', 'checkout']), $search, ['domain', 'payment_reference'])
                ->latest()->limit(self::PER_SOURCE)->get()
                ->map(fn (DomainOrder $o) => $this->row('domain', $o, $o->domain.' · '.$o->option, $o->user?->email, $o->isPaid(), route('admin.domain-orders.show', $o))),
            'cart' => $this->search(SiteCheckout::query()->with(['user', 'items']), $search, ['payment_reference'], 'items.label')
                ->latest()->limit(self::PER_SOURCE)->get()
                ->map(fn (SiteCheckout $o) => $this->row('cart', $o, $o->items->pluck('label')->take(3)->implode(', ') ?: ($o->item_count.' items'), $o->user?->email, $o->isPaid(), route('admin.cart-orders.show', $o))),
            default => collect(),
        };
    }

    /**
     * @param  list<string>  $columns
     */
    protected function search(Builder $query, string $search, array $columns, ?string $relationColumn = null): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns, $relationColumn) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%'.$search.'%');
            }
            if ($relationColumn) {
                [$relation, $column] = explode('.', $relationColumn, 2);
                $q->orWhereHas($relation, fn (Builder $r) => $r->where($column, 'like', '%'.$search.'%'));
            }
            $q->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(string $type, $order, string $label, ?string $customer, bool $paid, string $href): array
    {
        return [
            'type' => $type,
            'id' => $order->id,
            'label' => $label,
            'customer' => $customer,
            'amount_ngn' => (float) ($order->amount_ngn ?? 0),
            'status' => (string) ($order->status ?: 'pending'),
            'paid' => $paid,
            'cancelled' => (string) $order->status === 'cancelled',
            'created_at' => $order->created_at,
            'href' => $href,
        ];
    }
}
