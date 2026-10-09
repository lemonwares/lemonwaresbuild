<?php

namespace App\Http\Controllers;

use App\Models\InvoicePayment;
use App\Models\WhmcsInvoice;
use App\Support\AccountListQuery;
use App\Support\FlutterwavePayment;
use App\Support\Whmcs\WhmcsMirror;
use App\Support\WhmcsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $request->user()->accountOwner();
        $invoices = collect();

        $whmcsCustomer = $owner->whmcsCustomer;
        if ($whmcsCustomer?->whmcs_client_id) {
            foreach (self::localInvoices((int) $whmcsCustomer->whmcs_client_id, ! $whmcsCustomer->details_synced_at) as $row) {
                $invoices->push([
                    'source' => 'whmcs',
                    'id' => 'whmcs-'.$row['id'],
                    'reference' => $row['reference'],
                    'label' => $row['label'],
                    'amount' => $row['amount'],
                    'currency' => $row['currency'],
                    'status' => $row['status'],
                    'date' => $row['date'],
                    'pay_url' => strcasecmp($row['status'], 'Unpaid') === 0 ? $row['url'] : null,
                    'url' => $row['url'],
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

        $search = trim((string) $request->query('q', ''));
        $filtered = AccountListQuery::filter(
            $invoices,
            $search,
            ['reference', 'label', 'status', 'source', 'currency'],
        );

        return view('pages.account-invoices', [
            'invoices' => AccountListQuery::paginate($filtered, 15),
            'search' => $search,
            'totalCount' => $invoices->count(),
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

        $items = $this->normalizeList(data_get($detail, 'items.item', []));
        $transactions = $this->normalizeList(data_get($detail, 'transactions.transaction', []));

        return view('pages.account-invoice', [
            'invoice' => $detail,
            'invoiceId' => $invoiceId,
            'items' => $items,
            'transactions' => $transactions,
            'canPay' => strcasecmp((string) ($detail['status'] ?? ''), 'Unpaid') === 0
                && (float) ($detail['balance'] ?? 0) > 0
                && FlutterwavePayment::isConfigured(),
        ]);
    }

    public function pay(Request $request, int $invoice): RedirectResponse
    {
        $owner = $request->user()->accountOwner();
        $customer = $owner->whmcsCustomer;
        abort_unless($customer?->whmcs_client_id, 404);

        $detail = WhmcsClient::getInvoice($invoice);
        abort_unless(is_array($detail) && (int) ($detail['userid'] ?? 0) === (int) $customer->whmcs_client_id, 404);

        $back = redirect()->route('account.invoices.show', ['invoice' => 'whmcs-'.$invoice]);
        $balance = round((float) ($detail['balance'] ?? 0), 2);

        if (strcasecmp((string) ($detail['status'] ?? ''), 'Unpaid') !== 0 || $balance <= 0) {
            return $back->with('error', __('account.invoice_not_payable'));
        }
        if ($customer->currency_code && strtoupper($customer->currency_code) !== 'NGN') {
            return $back->with('error', __('account.invoice_currency_unsupported'));
        }
        if (InvoicePayment::query()->where('whmcs_invoice_id', $invoice)->where('status', 'paid')->whereNull('whmcs_recorded_at')->exists()) {
            return $back->with('status', __('account.invoice_payment_processing'));
        }

        $payment = InvoicePayment::query()->create([
            'user_id' => $owner->id,
            'whmcs_client_id' => $customer->whmcs_client_id,
            'whmcs_invoice_id' => $invoice,
            'amount_ngn' => $balance,
            'payment_reference' => 'LW-INV-'.$invoice.'-'.Str::upper(Str::random(10)),
        ]);

        $link = FlutterwavePayment::createInvoicePaymentLink($payment);
        if (! $link) {
            $payment->update(['status' => 'payment_failed', 'payment_status' => 'init_failed']);

            return $back->with('error', __('account.invoice_payment_unavailable'));
        }

        return redirect()->away($link);
    }

    public function callback(Request $request): RedirectResponse
    {
        $payment = InvoicePayment::query()
            ->where('payment_reference', (string) $request->query('tx_ref', ''))
            ->where('user_id', $request->user()->accountOwner()->id)
            ->first();
        abort_unless($payment, 404);

        $back = redirect()->route('account.invoices.show', ['invoice' => 'whmcs-'.$payment->whmcs_invoice_id]);
        $transactionId = (string) $request->query('transaction_id', '');

        if ($transactionId === '' || in_array(strtolower((string) $request->query('status')), ['cancelled', 'failed'], true)) {
            return $back->with('error', __('account.invoice_payment_incomplete'));
        }

        $verified = FlutterwavePayment::verifyTransaction($transactionId);
        if (! $verified) {
            return $back->with('error', __('account.invoice_payment_unverified'));
        }

        $result = FlutterwavePayment::confirmInvoicePayment($payment, $verified);

        return $back->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    /**
     * WHMCS invoices from our local copy, pulled from WHMCS first if this client was never synced.
     *
     * @return Collection<int, array{id:int,reference:string,label:string,amount:float,currency:string,status:string,date:?Carbon,url:string}>
     */
    public static function localInvoices(int $clientId, bool $syncIfMissing = false): Collection
    {
        $query = fn () => WhmcsInvoice::query()->where('whmcs_client_id', $clientId)->orderByDesc('date')->orderByDesc('whmcs_invoice_id')->limit(100)->get();

        $rows = $query();
        if ($rows->isEmpty() && $syncIfMissing && WhmcsMirror::syncClientInvoices($clientId)) {
            $rows = $query();
        }

        return $rows->map(fn (WhmcsInvoice $invoice) => [
            'id' => (int) $invoice->whmcs_invoice_id,
            'reference' => '#'.($invoice->invoice_num ?: $invoice->whmcs_invoice_id),
            'label' => (string) (data_get($invoice->raw_payload, 'itemdescription') ?: __('account.invoice_whmcs')),
            'amount' => (float) $invoice->total,
            'currency' => (string) ($invoice->currency_code ?: 'NGN'),
            'status' => (string) $invoice->status,
            'date' => $invoice->date,
            'url' => route('account.invoices.show', ['invoice' => 'whmcs-'.$invoice->whmcs_invoice_id]),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function normalizeList(mixed $rows): array
    {
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        if (isset($rows['id']) || isset($rows['description']) || isset($rows['amount'])) {
            return [$rows];
        }

        return array_values(array_filter($rows, 'is_array'));
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
}
