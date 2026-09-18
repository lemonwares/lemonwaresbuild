<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountProductController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();

        $emailOrders = $owner->emailOrders()->with('mailboxes')->limit(50)->get();
        $hostingLeads = $owner->hostingLeads()->limit(50)->get();
        $domainOrders = $owner->domainOrders()->limit(50)->get();

        $products = collect();

        foreach ($emailOrders as $order) {
            $products->push([
                'type' => 'email',
                'label' => $order->domain ?: __('account.product_email'),
                'status' => $order->status,
                'meta' => $order->mailboxes->count().' '.__('account.mailboxes'),
                'date' => $order->created_at,
                'url' => route('account.email.show', $order),
            ]);
        }

        foreach ($hostingLeads as $lead) {
            $isVps = $lead->isVps();
            $products->push([
                'type' => $isVps ? 'vps' : 'hosting',
                'label' => $lead->displayName(),
                'status' => $lead->status ?: $lead->payment_status,
                'meta' => $lead->domain ?: ($lead->ipv4 ?: __('account.product_hosting')),
                'date' => $lead->created_at,
                'url' => $isVps
                    ? route('account.vps.show', $lead)
                    : route('account.hosting.show', $lead),
            ]);
        }

        foreach ($domainOrders as $order) {
            $products->push([
                'type' => 'domain',
                'label' => $order->domain,
                'status' => $order->payment_status ?: $order->status,
                'meta' => $order->isTransfer()
                    ? __('account.product_domain_transfer')
                    : __('account.product_domain_register'),
                'date' => $order->created_at,
                'url' => route('account.domains.show', $order),
            ]);
        }

        $products = $products->sortByDesc(fn ($row) => $row['date']?->timestamp ?? 0)->values();

        return view('pages.account-products', [
            'products' => $products,
        ]);
    }
}
