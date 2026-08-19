<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $customers = User::query()
            ->customers()
            ->withCount('emailOrders')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('company', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'search'));
    }

    public function show(User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $customer->load(['emailOrders.mailboxes', 'contacts']);
        $hostingLeads = $customer->hostingLeads;

        return view('admin.customers.show', compact('customer', 'hostingLeads'));
    }
}
