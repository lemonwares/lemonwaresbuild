<?php

namespace App\Http\Controllers;

use App\Models\CareerOpening;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\HostingPlanPrice;
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
        $pricedSpecsCount = HostingPlanPrice::query()->where('is_visible', true)->where('price_amount', '>', 0)->count();
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
        ));
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
