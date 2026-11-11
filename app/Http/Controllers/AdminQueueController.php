<?php

namespace App\Http\Controllers;

use App\Filament\Support\ConsoleOptions;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use App\Support\AdminPermissions;
use App\Support\AdminWorkQueue;
use App\Support\Whmcs\Whmcs;
use App\Support\WhmcsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminQueueController extends Controller
{
    public function index(): View
    {
        return view('admin.queue.index', AdminWorkQueue::sections() + [
            'gateways' => ConsoleOptions::gateways(),
        ]);
    }

    public function acceptOrder(Request $request, WhmcsOrder $order): RedirectResponse
    {
        abort_unless($order->status === 'Pending', 409);

        return $this->run('AcceptOrder', [
            'orderid' => $order->whmcs_order_id,
            'autosetup' => true,
            'sendemail' => $request->boolean('sendemail', true),
        ], 'Order #'.($order->order_num ?: $order->whmcs_order_id).' accepted. Services are being set up and domains sent to the registrar.');
    }

    public function cancelOrder(WhmcsOrder $order): RedirectResponse
    {
        abort_unless($order->status === 'Pending', 409);

        return $this->run('CancelOrder', ['orderid' => $order->whmcs_order_id, 'cancelsub' => true], 'Order cancelled.');
    }

    public function addPayment(Request $request, WhmcsInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->status === 'Unpaid', 409);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'gateway' => ['required', 'string', 'max:80'],
            'transid' => ['required', 'string', 'max:120'],
            'date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        return $this->run('AddInvoicePayment', [
            'invoiceid' => $invoice->whmcs_invoice_id,
            'amount' => $data['amount'],
            'gateway' => $data['gateway'],
            'transid' => $data['transid'],
            'date' => $data['date'],
        ], 'Payment recorded on invoice #'.($invoice->invoice_num ?: $invoice->whmcs_invoice_id).'.');
    }

    public function sendDomain(WhmcsDomain $domain): RedirectResponse
    {
        abort_unless(in_array($domain->status, AdminWorkQueue::DOMAIN_PENDING, true), 409);

        return $domain->status === 'Pending Transfer'
            ? $this->run('DomainTransfer', ['domainid' => $domain->whmcs_domain_id], 'Transfer of '.$domain->domain.' submitted to the registrar.')
            : $this->run('DomainRegister', ['domainid' => $domain->whmcs_domain_id], $domain->domain.' registered.');
    }

    public function provisionService(WhmcsService $service): RedirectResponse
    {
        abort_unless($service->status === 'Pending', 409);

        return $this->run('ModuleCreate', ['serviceid' => $service->whmcs_service_id], ($service->product_name ?: 'Service').' set up.');
    }

    public function suspendService(Request $request, WhmcsService $service): RedirectResponse
    {
        abort_unless($service->status === 'Active', 409);
        $reason = (string) ($request->validate(['reason' => ['nullable', 'string', 'max:190']])['reason'] ?? '');

        return $this->run('ModuleSuspend', [
            'serviceid' => $service->whmcs_service_id,
            'suspendreason' => $reason !== '' ? $reason : 'Suspended by LemonWares',
        ], ($service->product_name ?: 'Service').' suspended.');
    }

    public function unsuspendService(WhmcsService $service): RedirectResponse
    {
        abort_unless($service->status === 'Suspended', 409);

        return $this->run('ModuleUnsuspend', ['serviceid' => $service->whmcs_service_id], ($service->product_name ?: 'Service').' unsuspended.');
    }

    public function terminateService(WhmcsService $service): RedirectResponse
    {
        $this->requireFull();
        abort_if(in_array($service->status, ['Terminated', 'Cancelled'], true), 409);

        return $this->run('ModuleTerminate', ['serviceid' => $service->whmcs_service_id], ($service->product_name ?: 'Service').' terminated.');
    }

    public function cpanelLogin(WhmcsService $service): RedirectResponse
    {
        $url = WhmcsClient::moduleSingleSignOn((int) $service->whmcs_service_id, (int) $service->whmcs_client_id);

        return $url
            ? redirect()->away($url)
            : $this->back()->withErrors(['whmcs' => 'Could not open the control panel: '.(WhmcsClient::lastError() ?: 'no login link returned')]);
    }

    public function renewDomain(Request $request, WhmcsDomain $domain): RedirectResponse
    {
        $years = (int) $request->validate(['years' => ['required', 'integer', 'between:1,10']])['years'];

        return $this->run('DomainRenew', ['domainid' => $domain->whmcs_domain_id, 'regperiod' => $years], $domain->domain.' renewed for '.$years.' '.str('year')->plural($years).'.');
    }

    public function currentNameservers(WhmcsDomain $domain): JsonResponse
    {
        $result = Whmcs::call('DomainGetNameservers', ['domainid' => $domain->whmcs_domain_id]);

        return response()->json($result->ok
            ? collect(range(1, 5))->mapWithKeys(fn ($n) => ["ns{$n}" => (string) $result->get("ns{$n}", '')])->all()
            : ['error' => $result->error], $result->ok ? 200 : 422);
    }

    public function updateNameservers(Request $request, WhmcsDomain $domain): RedirectResponse
    {
        $rule = ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9.-]+$/i'];
        $data = $request->validate([
            'ns1' => ['required', ...array_slice($rule, 1)],
            'ns2' => ['required', ...array_slice($rule, 1)],
            'ns3' => $rule, 'ns4' => $rule, 'ns5' => $rule,
        ]);

        return $this->run('DomainUpdateNameservers', ['domainid' => $domain->whmcs_domain_id] + array_filter($data), 'Nameservers updated for '.$domain->domain.'.');
    }

    public function eppCode(WhmcsDomain $domain): RedirectResponse
    {
        $this->requireFull();
        $result = Whmcs::call('DomainRequestEPP', ['domainid' => $domain->whmcs_domain_id], AdminPermissions::currentUser());
        if (! $result->ok) {
            return $this->back()->withErrors(['whmcs' => 'WHMCS refused: '.$result->error]);
        }
        $code = (string) $result->get('eppcode', '');

        return $this->back()->with('status', $code !== ''
            ? 'EPP code for '.$domain->domain.': '.$code.' (share it only with the verified owner).'
            : 'The registrar emailed the EPP code for '.$domain->domain.' to the domain owner.');
    }

    public function invoiceReminder(WhmcsInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->status === 'Unpaid', 409);

        return $this->run('SendEmail', ['messagename' => 'Invoice Payment Reminder', 'id' => $invoice->whmcs_invoice_id], 'Payment reminder sent for invoice #'.($invoice->invoice_num ?: $invoice->whmcs_invoice_id).'.');
    }

    public function cancelInvoice(WhmcsInvoice $invoice): RedirectResponse
    {
        $this->requireFull();
        abort_unless($invoice->status === 'Unpaid', 409);

        return $this->run('UpdateInvoice', ['invoiceid' => $invoice->whmcs_invoice_id, 'status' => 'Cancelled'], 'Invoice #'.($invoice->invoice_num ?: $invoice->whmcs_invoice_id).' cancelled.');
    }

    protected function requireFull(): void
    {
        abort_unless(AdminPermissions::currentCan('orders', 'full'), 403, 'Your access does not include this action.');
    }

    protected function back(): RedirectResponse
    {
        return redirect()->to(url()->previous(route('admin.queue.index')));
    }

    protected function run(string $action, array $params, string $success): RedirectResponse
    {
        $result = Whmcs::call($action, $params, AdminPermissions::currentUser());

        return $result->ok
            ? $this->back()->with('status', $success)
            : $this->back()->withErrors(['whmcs' => 'WHMCS refused: '.$result->error]);
    }
}
