<?php

namespace App\Http\Controllers;

use App\Models\HostingLead;
use App\Support\WhmcsLeadSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminHostingLeadController extends Controller
{
    public function index(): View
    {
        $leads = HostingLead::query()->latest()->paginate(20);
        $totalLeads = HostingLead::count();
        $pendingLeads = HostingLead::query()->where(function ($q) {
            $q->whereNull('status')->orWhereIn('status', ['pending', 'new', 'open', 'awaiting_payment']);
        })->count();
        $paidLeads = HostingLead::query()->where(function ($q) {
            $q->where('payment_status', 'paid')->orWhereIn('status', ['paid', 'provisioned']);
        })->count();
        $newWeek = HostingLead::query()->where('created_at', '>=', now()->subDays(7))->count();

        return view('admin.hosting-leads.index', compact(
            'leads',
            'totalLeads',
            'pendingLeads',
            'paidLeads',
            'newWeek',
        ));
    }

    public function show(HostingLead $hostingLead): View
    {
        return view('admin.hosting-leads.show', ['lead' => $hostingLead]);
    }

    public function retryWhmcsSync(HostingLead $hostingLead): RedirectResponse
    {
        WhmcsLeadSync::retry($hostingLead);

        return redirect()
            ->route('admin.hosting-leads.show', $hostingLead)
            ->with('status', 'WHMCS sync retried.');
    }
}
