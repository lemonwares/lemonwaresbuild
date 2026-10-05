<?php

namespace App\Http\Controllers;

use App\Models\CareerOpening;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\CatalogPlan;
use App\Models\NewsletterSubscriber;
use App\Models\SupportTicket;
use App\Models\TeamMember;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $customersCount = User::query()->customers()->count();
        $emailOrdersCount = EmailOrder::count();
        $paidEmailOrdersCount = EmailOrder::query()->whereIn('status', ['paid', 'provisioned', 'paid_pending_setup'])->count();
        $hostingLeadsCount = HostingLead::count();
        $subscribersCount = NewsletterSubscriber::count();
        $pendingEmailSetupCount = EmailOrder::query()->where('status', 'paid_pending_setup')->count();
        $teamMembersCount = TeamMember::count();
        $pricedSpecsCount = CatalogPlan::query()->where('is_active', true)->where('price_ngn', '>', 0)->count();
        $openTicketsCount = SupportTicket::query()->whereIn('status', ['open', 'in_progress'])->count();
        $careerOpeningsCount = CareerOpening::query()->where('is_active', true)->count();
        $newCustomersWeek = User::query()->customers()->where('created_at', '>=', now()->subDays(7))->count();
        $newOrdersWeek = EmailOrder::query()->where('created_at', '>=', now()->subDays(7))->count();
        $newLeadsWeek = HostingLead::query()->where('created_at', '>=', now()->subDays(7))->count();

        $recentCustomers = User::query()->customers()->latest()->limit(4)->get();
        $recentEmailOrders = EmailOrder::query()->with('user')->latest()->limit(5)->get();
        $recentHostingLeads = HostingLead::query()->latest()->limit(4)->get();
        $recentTickets = SupportTicket::query()->latest()->limit(4)->get();

        $chartDays = collect(range(13, 0))->map(fn (int $offset) => now()->subDays($offset)->startOfDay());
        $orderSeries = $this->dailyCounts(EmailOrder::class, $chartDays);
        $leadSeries = $this->dailyCounts(HostingLead::class, $chartDays);
        $customerSeries = $this->dailyCounts(User::class, $chartDays, fn ($q) => $q->customers());
        $chartMax = max(1, ...$orderSeries, ...$leadSeries, ...$customerSeries);

        $activity = collect()
            ->merge($recentEmailOrders->map(fn (EmailOrder $order) => [
                'label' => 'Email order · ' . $order->domain,
                'meta' => str_replace('_', ' ', (string) $order->status),
                'href' => route('admin.email-orders.show', $order),
                'at' => $order->created_at,
            ]))
            ->merge($recentHostingLeads->map(fn (HostingLead $lead) => [
                'label' => 'Hosting lead · ' . $lead->full_name,
                'meta' => $lead->plan_name,
                'href' => route('admin.hosting-leads.show', $lead),
                'at' => $lead->created_at,
            ]))
            ->merge($recentTickets->map(fn (SupportTicket $ticket) => [
                'label' => 'Ticket · ' . ($ticket->subject ?: $ticket->reference),
                'meta' => str_replace('_', ' ', (string) $ticket->status),
                'href' => route('admin.support-tickets.show', $ticket),
                'at' => $ticket->created_at,
            ]))
            ->sortByDesc('at')
            ->take(8)
            ->values();

        return view('admin.dashboard', compact(
            'customersCount',
            'emailOrdersCount',
            'paidEmailOrdersCount',
            'hostingLeadsCount',
            'subscribersCount',
            'pendingEmailSetupCount',
            'teamMembersCount',
            'pricedSpecsCount',
            'openTicketsCount',
            'careerOpeningsCount',
            'newCustomersWeek',
            'newOrdersWeek',
            'newLeadsWeek',
            'recentCustomers',
            'recentEmailOrders',
            'recentHostingLeads',
            'recentTickets',
            'chartDays',
            'orderSeries',
            'leadSeries',
            'customerSeries',
            'chartMax',
            'activity',
        ) + $this->salesSnapshot());
    }

    /**
     * Money and to-do figures for the top of the dashboard, shown to staff who can see reports or orders.
     *
     * @return array<string, mixed>
     */
    private function salesSnapshot(): array
    {
        if (! \App\Support\AdminPermissions::currentCan('reports') && ! \App\Support\AdminPermissions::currentCan('orders')) {
            return ['sales' => null];
        }

        $monthStart = \Carbon\CarbonImmutable::now()->startOfMonth();
        $thisMonth = \App\Support\AdminReports::summary($monthStart, \Carbon\CarbonImmutable::now());
        $attention = \App\Support\AdminReports::attention();

        return ['sales' => [
            'monthNet' => $thisMonth['net'],
            'monthOrders' => $thisMonth['orders'],
            'unpaidCount' => $attention['unpaidCount'],
            'unpaidValue' => $attention['unpaidValue'],
            'renewals' => $attention['emailRenewals']->count() + $attention['whmcsRenewals']->count(),
            'domainOrdersWeek' => \App\Models\DomainOrder::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'cartOrdersWeek' => \App\Models\SiteCheckout::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'whmcsFailed' => \App\Models\DomainOrder::query()->where('whmcs_sync_status', 'failed')->count(),
            'partialCarts' => \App\Models\SiteCheckout::query()->where('fulfilment_status', 'partial')->count(),
        ]];
    }

    /**
     * @param  class-string  $model
     * @param  \Illuminate\Support\Collection<int, Carbon>  $days
     * @param  (callable(\Illuminate\Database\Eloquent\Builder): mixed)|null  $scope
     * @return list<int>
     */
    private function dailyCounts(string $model, $days, ?callable $scope = null): array
    {
        $start = $days->first()->copy();
        $end = $days->last()->copy()->endOfDay();

        $query = $model::query()->whereBetween('created_at', [$start, $end]);
        if ($scope) {
            $scope($query);
        }

        $rows = $query
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as aggregate'))
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        return $days->map(function (Carbon $day) use ($rows) {
            $key = $day->toDateString();

            return (int) ($rows[$key] ?? 0);
        })->all();
    }
}
