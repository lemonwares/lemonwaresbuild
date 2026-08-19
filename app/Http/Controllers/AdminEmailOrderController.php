<?php

namespace App\Http\Controllers;

use App\Models\EmailOrder;
use App\Support\EmailProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminEmailOrderController extends Controller
{
    public function index(): View
    {
        $orders = EmailOrder::query()
            ->with(['user', 'mailboxes'])
            ->latest()
            ->paginate(20);

        return view('admin.email-orders.index', compact('orders'));
    }

    public function show(EmailOrder $emailOrder): View
    {
        $emailOrder->load(['user', 'mailboxes']);

        return view('admin.email-orders.show', ['order' => $emailOrder]);
    }

    public function provision(EmailOrder $emailOrder): RedirectResponse
    {
        abort_unless($emailOrder->isPaid(), 404);

        EmailProvisioner::provision($emailOrder);

        return redirect()
            ->route('admin.email-orders.show', $emailOrder)
            ->with('status', 'Provisioning ran. Check the order status below.');
    }
}
