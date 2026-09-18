<?php

namespace App\Http\Controllers;

use App\Support\WhmcsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AccountInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();
        $invoices = collect();

        $whmcsCustomer = $owner->whmcsCustomer;
        if ($whmcsCustomer?->whmcs_client_id) {
            $remote = WhmcsClient::getInvoices((int) $whmcsCustomer->whmcs_client_id);
            foreach ($remote as $row) {
                $invoiceId = (int) ($row['id'] ?? 0);
                $invoices->push([
                    'source' => 'whmcs',
                    'id' => 'whmcs-'.$invoiceId,
                    'reference' => '#'.$invoiceId,
                    'label' => (string) ($row['itemdescription'] ?? __('account.invoice_whmcs')),
                    'amount' => (float) ($row['total'] ?? 0),
                    'currency' => (string) ($row['currencycode'] ?? 'USD'),
                    'status' => (string) ($row['status'] ?? ''),
                    'date' => $this->parseDate($row['date'] ?? null),
                    'pay_url' => $this->whmcsPayUrl((int) $whmcsCustomer->whmcs_client_id, $invoiceId, (string) ($row['status'] ?? '')),
                    'url' => $invoiceId > 0 ? route('account.invoices.show', ['invoice' => 'whmcs-'.$invoiceId]) : null,
                ]);
            }
        }

        foreach ($owner->emailOrders()->whereIn('payment_status', ['successful', 'completed'])->limit(50)->get() as $order) {
            $invoices->push([
                'source' => 'email',
                'id' => 'email-'.$order->id,
                'reference' => $order->payment_reference ?: 'EM-'.$order->id,
                'label' => __('account.product_email').': '.$order->domain,
                'amount' => (float) ($order->amount_usd ?? 0),
                'currency' => 'USD',
                'status' => 'Paid',
                'date' => $order->updated_at ?? $order->created_at,
                'pay_url' => null,
                'url' => route('account.email.show', $order),
            ]);
        }

        foreach ($owner->hostingLeads()->get()->filter->isPaid() as $lead) {
            $invoices->push([
                'source' => 'hosting',
                'id' => 'hosting-'.$lead->id,
                'reference' => $lead->payment_reference ?: 'HL-'.$lead->id,
                'label' => $lead->displayName(),
                'amount' => (float) ($lead->amount_usd ?? $lead->hosting_amount_usd ?? 0),
                'currency' => 'USD',
                'status' => 'Paid',
                'date' => $lead->updated_at ?? $lead->created_at,
                'pay_url' => null,
                'url' => $lead->isVps()
                    ? route('account.vps.show', $lead)
                    : route('account.hosting.show', $lead),
            ]);
        }

        foreach ($owner->domainOrders()->whereIn('payment_status', ['successful', 'completed'])->limit(50)->get() as $order) {
            $invoices->push([
                'source' => 'domain',
                'id' => 'domain-'.$order->id,
                'reference' => $order->payment_reference ?: 'DO-'.$order->id,
                'label' => __('account.product_domain').': '.$order->domain,
                'amount' => (float) ($order->amount_usd ?? 0),
                'currency' => 'USD',
                'status' => 'Paid',
                'date' => $order->updated_at ?? $order->created_at,
                'pay_url' => null,
                'url' => route('account.domains.show', $order),
            ]);
        }

        $invoices = $invoices
            ->sortByDesc(fn ($row) => optional($row['date'])->timestamp ?? 0)
            ->values();

        return view('pages.account-invoices', [
            'invoices' => $invoices,
        ]);
    }

    public function show(Request $request, string $invoice): View
    {
        $owner = $request->user()->accountOwner();
        abort_unless(str_starts_with($invoice, 'whmcs-'), 404);

        $invoiceId = (int) substr($invoice, 6);
        abort_unless($invoiceId > 0, 404);

        $whmcsCustomer = $owner->whmcsCustomer;
        abort_unless($whmcsCustomer?->whmcs_client_id, 404);

        $detail = WhmcsClient::getInvoice($invoiceId);
        abort_unless(is_array($detail), 404);

        $clientId = (int) ($detail['userid'] ?? 0);
        abort_unless($clientId === (int) $whmcsCustomer->whmcs_client_id, 404);

        return view('pages.account-invoice', [
            'invoice' => $detail,
            'invoiceId' => $invoiceId,
            'payUrl' => $this->whmcsPayUrl(
                (int) $whmcsCustomer->whmcs_client_id,
                $invoiceId,
                (string) ($detail['status'] ?? ''),
            ),
        ]);
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function whmcsPayUrl(int $clientId, int $invoiceId, string $status): ?string
    {
        if ($invoiceId <= 0 || ! in_array(strtolower($status), ['unpaid', 'overdue'], true)) {
            return null;
        }

        $sso = WhmcsClient::createSsoToken($clientId, 'viewinvoice.php?id='.$invoiceId);

        return is_array($sso) ? ($sso['redirect_url'] ?? null) : null;
    }
}
