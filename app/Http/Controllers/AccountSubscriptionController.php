<?php

namespace App\Http\Controllers;

use App\Models\WhmcsService;
use App\Support\AccountListQuery;
use App\Support\WhmcsCheckout;
use App\Support\WhmcsClient;
use App\Support\WhmcsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();

        $whmcsCustomer = $owner->whmcsCustomer;
        if ($whmcsCustomer?->whmcs_client_id) {
            WhmcsSyncService::syncServicesForCustomer($whmcsCustomer);
            $whmcsCustomer->refresh();
        }

        $whmcsServices = $owner->whmcsServices()->limit(200)->get();
        $emailOrders = $owner->emailOrders()
            ->whereIn('payment_status', ['successful', 'completed'])
            ->limit(100)
            ->get();
        $hostingLeads = $owner->hostingLeads()->limit(100)->get();

        $clientId = (int) ($whmcsCustomer?->whmcs_client_id ?? 0);
        $subscriptions = collect();

        foreach ($whmcsServices as $service) {
            $serviceId = (int) $service->whmcs_service_id;
            $status = strtolower(trim((string) $service->status));
            $isActive = in_array($status, ['active', 'pending'], true);

            $subscriptions->push([
                'source' => 'whmcs',
                'label' => $service->product_name ?: __('account.subscription_whmcs'),
                'domain' => $service->domain,
                'status' => $service->status,
                'billing_cycle' => $service->billing_cycle,
                'next_due' => $service->next_due_date,
                'site_url' => WhmcsCheckout::websiteUrl($service->domain),
                'cpanel_url' => ($clientId > 0 && $serviceId > 0 && $isActive)
                    ? route('account.subscriptions.cpanel', $service)
                    : null,
                'url' => route('account.subscriptions.show', $service),
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
                'site_url' => WhmcsCheckout::websiteUrl($order->domain),
                'cpanel_url' => null,
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
                'site_url' => WhmcsCheckout::websiteUrl($lead->domain),
                'cpanel_url' => null,
                'url' => $lead->isVps()
                    ? route('account.vps.show', $lead)
                    : route('account.hosting.show', $lead),
                'renew_url' => null,
            ]);
        }

        $search = trim((string) $request->query('q', ''));
        $filtered = AccountListQuery::filter(
            $subscriptions->values(),
            $search,
            ['label', 'domain', 'status', 'billing_cycle', 'source'],
        );

        return view('pages.account-subscriptions', [
            'subscriptions' => AccountListQuery::paginate($filtered, 15),
            'search' => $search,
            'totalCount' => $subscriptions->count(),
        ]);
    }

    public function show(Request $request, WhmcsService $service): View
    {
        $owner = $request->user()->accountOwner();
        abort_unless($this->ownsService($owner->id, $service), 404);

        $clientId = (int) ($owner->whmcsCustomer?->whmcs_client_id ?? $service->whmcs_client_id ?? 0);
        $serviceId = (int) $service->whmcs_service_id;
        $status = strtolower(trim((string) $service->status));
        $isActive = in_array($status, ['active', 'pending'], true);

        $relatedInvoices = collect();
        if ($clientId > 0) {
            foreach (WhmcsClient::getInvoices($clientId, 50) as $row) {
                $invoiceId = (int) ($row['id'] ?? 0);
                if ($invoiceId < 1) {
                    continue;
                }

                $relatedInvoices->push([
                    'id' => $invoiceId,
                    'reference' => '#'.$invoiceId,
                    'label' => (string) ($row['itemdescription'] ?? __('account.invoice_whmcs')),
                    'amount' => (float) ($row['total'] ?? 0),
                    'currency' => (string) ($row['currencycode'] ?? 'USD'),
                    'status' => (string) ($row['status'] ?? ''),
                    'date' => (string) ($row['date'] ?? ''),
                    'url' => route('account.invoices.show', ['invoice' => 'whmcs-'.$invoiceId]),
                ]);
            }
        }

        return view('pages.account-subscription', [
            'service' => $service,
            'siteUrl' => WhmcsCheckout::websiteUrl($service->domain),
            'cpanelUrl' => ($clientId > 0 && $serviceId > 0 && $isActive)
                ? route('account.subscriptions.cpanel', $service)
                : null,
            'relatedInvoices' => $relatedInvoices->take(8)->values(),
        ]);
    }

    public function manage(Request $request, WhmcsService $service): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        abort_unless($this->ownsService($owner->id, $service), 404);

        return redirect()->route('account.subscriptions.show', $service);
    }

    public function cpanel(Request $request, WhmcsService $service): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        abort_unless($this->ownsService($owner->id, $service), 404);

        $serviceId = (int) $service->whmcs_service_id;
        $clientId = (int) ($owner->whmcsCustomer?->whmcs_client_id ?? $service->whmcs_client_id ?? 0);
        $url = WhmcsClient::moduleSingleSignOn($serviceId, $clientId);

        if (! $url) {
            $detail = trim((string) WhmcsClient::lastError());

            return redirect()
                ->route('account.subscriptions.show', $service)
                ->with('email_feedback', [
                    'type' => 'error',
                    'message' => $detail !== ''
                        ? __('account.subscription_cpanel_failed').' ('.$detail.')'
                        : __('account.subscription_cpanel_failed'),
                ]);
        }

        return redirect()->away($url);
    }

    protected function ownsService(int $ownerId, WhmcsService $service): bool
    {
        if ((int) $service->user_id === $ownerId) {
            return true;
        }

        $customer = $service->whmcsCustomer;

        return $customer !== null && (int) $customer->user_id === $ownerId;
    }
}
