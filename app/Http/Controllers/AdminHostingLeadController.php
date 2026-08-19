<?php

namespace App\Http\Controllers;

use App\Models\HostingLead;
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
}
