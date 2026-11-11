<?php

namespace App\Support;

use App\Models\DomainOrder;
use App\Models\EmailOrder;
use App\Models\SiteCheckout;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use Illuminate\Support\Collection;

/**
 * Everything a paid (or paying) customer is waiting on, so admins can finish it without opening WHMCS.
 */
class AdminWorkQueue
{
    public const DOMAIN_PENDING = ['Pending', 'Pending Registration', 'Pending Transfer'];

    public const TICKETS_WAITING = ['Open', 'Customer-Reply'];

    public const PAYMENT_PROBLEMS = ['amount_mismatch', 'received_after_cancel', 'unverified'];

    public static function sections(int $limit = 50): array
    {
        $orders = WhmcsOrder::query()->where('status', 'Pending')->latest('placed_at')->limit($limit)->get();
        $invoices = WhmcsInvoice::query()
            ->whereIn('whmcs_invoice_id', $orders->pluck('whmcs_invoice_id')->filter())
            ->get()->keyBy('whmcs_invoice_id');

        $domains = WhmcsDomain::query()->whereIn('status', self::DOMAIN_PENDING)->latest('registration_date')->limit($limit)->get();
        $services = WhmcsService::query()->where('status', 'Pending')->latest('registration_date')->limit($limit)->get();

        $handoff = DomainOrder::query()->with('user')
            ->where(fn ($q) => $q->where('payment_status', 'successful')->where(fn ($s) => $s->whereNull('whmcs_sync_status')->orWhere('whmcs_sync_status', '!=', 'payment_synced'))
                ->orWhereIn('payment_status', self::PAYMENT_PROBLEMS))
            ->where('status', '!=', 'cancelled')
            ->latest()->limit($limit)->get();

        $carts = SiteCheckout::query()->with('user')
            ->where(fn ($q) => $q->where('payment_status', 'successful')->whereIn('fulfilment_status', ['failed', 'partial'])
                ->orWhereIn('payment_status', self::PAYMENT_PROBLEMS))
            ->latest()->limit($limit)->get();

        $emails = EmailOrder::query()->with('user')->where('status', 'awaiting_manual_fulfilment')->latest()->limit($limit)->get()
            ->filter(fn (EmailOrder $order) => $order->isPaid())->values();

        $tickets = WhmcsTicket::query()->whereIn('status', self::TICKETS_WAITING)->latest('last_reply_at')->limit($limit)->get();

        $clientIds = $orders->pluck('whmcs_client_id')
            ->merge($domains->pluck('whmcs_client_id'))
            ->merge($services->pluck('whmcs_client_id'))
            ->merge($tickets->pluck('whmcs_client_id'))
            ->filter()->unique();

        return [
            'orders' => $orders,
            'invoices' => $invoices,
            'domains' => $domains,
            'services' => $services,
            'handoff' => $handoff,
            'carts' => $carts,
            'emails' => $emails,
            'tickets' => $tickets,
            'clients' => WhmcsCustomer::query()->whereIn('whmcs_client_id', $clientIds)->get()->keyBy('whmcs_client_id'),
        ];
    }

    public static function count(): int
    {
        return WhmcsOrder::query()->where('status', 'Pending')->count()
            + WhmcsDomain::query()->whereIn('status', self::DOMAIN_PENDING)->count()
            + WhmcsService::query()->where('status', 'Pending')->count()
            + WhmcsTicket::query()->whereIn('status', self::TICKETS_WAITING)->count()
            + DomainOrder::query()->where('status', '!=', 'cancelled')
                ->where(fn ($q) => $q->where('payment_status', 'successful')->where(fn ($s) => $s->whereNull('whmcs_sync_status')->orWhere('whmcs_sync_status', '!=', 'payment_synced'))
                    ->orWhereIn('payment_status', self::PAYMENT_PROBLEMS))->count()
            + SiteCheckout::query()->where(fn ($q) => $q->where('payment_status', 'successful')->whereIn('fulfilment_status', ['failed', 'partial'])
                ->orWhereIn('payment_status', self::PAYMENT_PROBLEMS))->count();
    }

    /**
     * Link to the admin page for a WHMCS client (site account page when linked).
     */
    public static function clientLink(Collection $clients, ?int $clientId): ?array
    {
        $client = $clientId ? $clients->get($clientId) : null;
        if (! $client) {
            return $clientId ? ['label' => 'Client #'.$clientId, 'href' => null] : null;
        }

        return [
            'label' => $client->displayName(),
            'href' => $client->user_id
                ? route('admin.customers.show', $client->user_id)
                : route('admin.customers.legacy.show', $client),
        ];
    }
}
