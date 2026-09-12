<?php

namespace App\Http\Controllers;

use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\HostingPlanPrice;
use App\Models\NewsletterSubscriber;
use App\Models\TeamMember;
use App\Models\User;
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

        $recentCustomers = User::query()->customers()->latest()->limit(5)->get();
        $recentEmailOrders = EmailOrder::query()->with('user')->latest()->limit(6)->get();
        $recentHostingLeads = HostingLead::query()->latest()->limit(5)->get();

        return view('admin.dashboard', compact(
            'customersCount',
            'emailOrdersCount',
            'paidEmailOrdersCount',
            'hostingLeadsCount',
            'subscribersCount',
            'pendingEmailSetupCount',
            'teamMembersCount',
            'pricedSpecsCount',
            'recentCustomers',
            'recentEmailOrders',
            'recentHostingLeads',
        ));
    }
}
