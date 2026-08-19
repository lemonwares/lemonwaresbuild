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

        return view('admin.hosting-leads.index', compact('leads'));
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
