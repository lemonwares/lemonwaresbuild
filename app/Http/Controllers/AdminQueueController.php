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

    private function run(string $action, array $params, string $success): RedirectResponse
    {
        $result = Whmcs::call($action, $params, AdminPermissions::currentUser());

        return $result->ok
            ? redirect()->route('admin.queue.index')->with('status', $success)
            : redirect()->route('admin.queue.index')->withErrors(['whmcs' => 'WHMCS refused: '.$result->error]);
    }
}
