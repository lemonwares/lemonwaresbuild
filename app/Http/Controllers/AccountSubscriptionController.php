<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();

        $whmcsServices = $owner->whmcsServices()->limit(50)->get();
        $emailOrders = $owner->emailOrders()
            ->whereIn('payment_status', ['successful', 'completed'])
            ->limit(50)
            ->get();
        $hostingLeads = $owner->hostingLeads()->limit(50)->get();

        $subscriptions = collect();

        foreach ($whmcsServices as $service) {
            $subscriptions->push([
                'source' => 'whmcs',
                'label' => $service->product_name ?: __('account.subscription_whmcs'),
                'domain' => $service->domain,
                'status' => $service->status,
                'billing_cycle' => $service->billing_cycle,
                'next_due' => $service->next_due_date,
                'url' => null,
                'renew_url' => null,
            ]);
        }

        foreach ($emailOrders as $order) {
            $subscriptions->push([
                'source' => 'email',
                'label' => __('account.product_email').': '.$order->domain,
                'domain' => $order->domain,
                'status' => $order->status,
                'billing_cycle' => $order->billing_cycle,
                'next_due' => $order->period_ends_at,
                'url' => route('account.email.show', $order),
                'renew_url' => $order->canBeRenewed() ? route('email.renew', $order) : null,
            ]);
        }

        foreach ($hostingLeads as $lead) {
            $subscriptions->push([
                'source' => $lead->isVps() ? 'vps' : 'hosting',
                'label' => $lead->displayName(),
                'domain' => $lead->domain,
                'status' => $lead->status ?: $lead->payment_status,
                'billing_cycle' => $lead->billing_cycle ?? null,
                'next_due' => null,
                'url' => $lead->isVps()
                    ? route('account.vps.show', $lead)
                    : route('account.hosting.show', $lead),
                'renew_url' => null,
            ]);
        }

        return view('pages.account-subscriptions', [
            'subscriptions' => $subscriptions->values(),
        ]);
    }
}
