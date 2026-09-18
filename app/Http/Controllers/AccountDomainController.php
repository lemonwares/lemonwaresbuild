<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountDomainController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();

        return view('pages.account-domains', [
            'orders' => $owner->domainOrders()->limit(50)->get(),
        ]);
    }

    public function show(Request $request, DomainOrder $order): View
    {
        $owner = $request->user()->accountOwner();
        abort_unless((int) $order->user_id === (int) $owner->id, 404);

        return view('pages.account-domain', [
            'order' => $order,
        ]);
    }
}
