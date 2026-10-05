<?php

namespace App\Http\Controllers;

use App\Models\WhmcsCustomer;
use App\Support\WhmcsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWhmcsConsoleController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.whmcs-console.clients');
    }

    public function clients(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $limit = 40;
        $start = ($page - 1) * $limit;

        $result = WhmcsClient::searchClients($search, $start, $limit);

        return view('admin.whmcs-console.clients', [
            'section' => 'clients',
            'clients' => $result['clients'],
            'total' => $result['total'],
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
            'apiError' => WhmcsClient::lastError(),
        ]);
    }

    public function showClient(int $clientId): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $client = WhmcsClient::findClientById($clientId);
        if (! $client) {
            return redirect()
                ->route('admin.whmcs-console.clients')
                ->withErrors(['whmcs' => WhmcsClient::lastError() ?: 'Client not found.']);
        }

        $local = WhmcsCustomer::query()->where('whmcs_client_id', $clientId)->first();
        $services = WhmcsClient::getClientProducts($clientId);
        $domains = WhmcsClient::getClientDomains($clientId);
        $invoices = WhmcsClient::getInvoices($clientId, 25);

        return view('admin.whmcs-console.client-show', [
            'section' => 'clients',
            'client' => $client,
            'clientId' => $clientId,
            'local' => $local,
            'services' => $services,
            'domains' => $domains,
            'invoices' => $invoices,
        ]);
    }

    public function updateClient(Request $request, int $clientId): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'companyname' => ['nullable', 'string', 'max:255'],
            'phonenumber' => ['nullable', 'string', 'max:50'],
        ]);

        $ok = WhmcsClient::updateClient(array_merge($data, [
            'clientid' => $clientId,
        ]));

        return redirect()
            ->route('admin.whmcs-console.clients.show', $clientId)
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Client updated in WHMCS.' : (WhmcsClient::lastError() ?: 'Failed to update client.'),
            );
    }

    public function closeClient(int $clientId): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $ok = WhmcsClient::closeClient($clientId);

        return redirect()
            ->route('admin.whmcs-console.clients')
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'WHMCS client #'.$clientId.' closed.' : (WhmcsClient::lastError() ?: 'Failed to close client.'),
            );
    }

    public function services(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $clientId = (int) $request->query('client_id', 0);
        $services = $clientId > 0 ? WhmcsClient::getClientProducts($clientId) : [];

        return view('admin.whmcs-console.services', [
            'section' => 'services',
            'clientId' => $clientId,
            'services' => $services,
            'apiError' => WhmcsClient::lastError(),
        ]);
    }

    public function suspendService(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'service_id' => ['required', 'integer', 'min:1'],
            'client_id' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $ok = WhmcsClient::moduleSuspend((int) $data['service_id'], (string) ($data['reason'] ?? ''));

        return $this->serviceActionRedirect($request, $ok, 'Service suspended.', 'Failed to suspend service.');
    }

    public function unsuspendService(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'service_id' => ['required', 'integer', 'min:1'],
            'client_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $ok = WhmcsClient::moduleUnsuspend((int) $data['service_id']);

        return $this->serviceActionRedirect($request, $ok, 'Service unsuspended.', 'Failed to unsuspend service.');
    }

    public function terminateService(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'service_id' => ['required', 'integer', 'min:1'],
            'client_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $ok = WhmcsClient::moduleTerminate((int) $data['service_id']);

        return $this->serviceActionRedirect($request, $ok, 'Service terminated.', 'Failed to terminate service.');
    }

    public function invoices(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $status = (string) $request->query('status', 'Unpaid');
        $page = max(1, (int) $request->query('page', 1));
        $limit = 40;
        $start = ($page - 1) * $limit;

        $result = WhmcsClient::getInvoicesFiltered($status === 'all' ? '' : $status, $start, $limit);

        return view('admin.whmcs-console.invoices', [
            'section' => 'invoices',
            'invoices' => $result['invoices'],
            'total' => $result['total'],
            'status' => $status,
            'page' => $page,
            'limit' => $limit,
            'apiError' => WhmcsClient::lastError(),
        ]);
    }

    public function showInvoice(int $invoiceId): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $invoice = WhmcsClient::getInvoice($invoiceId);
        if (! $invoice) {
            return redirect()
                ->route('admin.whmcs-console.invoices')
                ->withErrors(['whmcs' => WhmcsClient::lastError() ?: 'Invoice not found.']);
        }

        return view('admin.whmcs-console.invoice-show', [
            'section' => 'invoices',
            'invoice' => $invoice,
            'invoiceId' => $invoiceId,
        ]);
    }

    public function markInvoicePaid(Request $request, int $invoiceId): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $invoice = WhmcsClient::getInvoice($invoiceId);
        if (! $invoice) {
            return redirect()
                ->route('admin.whmcs-console.invoices')
                ->with('error', WhmcsClient::lastError() ?: 'Invoice not found.');
        }

        $amount = (float) ($invoice['total'] ?? $invoice['balance'] ?? 0);
        $transId = 'LW-ADMIN-'.now()->format('YmdHis').'-'.$invoiceId;

        $ok = WhmcsClient::addInvoicePayment($invoiceId, max($amount, 0.01), $transId);

        return redirect()
            ->route('admin.whmcs-console.invoices.show', $invoiceId)
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Payment recorded on invoice #'.$invoiceId.'.' : (WhmcsClient::lastError() ?: 'Failed to record payment.'),
            );
    }

    public function orders(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $status = (string) $request->query('status', 'Pending');
        $page = max(1, (int) $request->query('page', 1));
        $limit = 40;
        $start = ($page - 1) * $limit;

        $result = WhmcsClient::getOrders($status === 'all' ? '' : $status, $start, $limit);

        return view('admin.whmcs-console.orders', [
            'section' => 'orders',
            'orders' => $result['orders'],
            'total' => $result['total'],
            'status' => $status,
            'page' => $page,
            'limit' => $limit,
            'apiError' => WhmcsClient::lastError(),
        ]);
    }

    public function acceptOrder(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'autosetup' => ['nullable', 'boolean'],
        ]);

        $ok = WhmcsClient::acceptOrder((int) $data['order_id'], $request->boolean('autosetup'));

        return redirect()
            ->route('admin.whmcs-console.orders', ['status' => $request->query('status', 'Pending')])
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Order #'.$data['order_id'].' accepted.' : (WhmcsClient::lastError() ?: 'Failed to accept order.'),
            );
    }

    public function cancelOrder(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $ok = WhmcsClient::cancelOrder((int) $data['order_id']);

        return redirect()
            ->route('admin.whmcs-console.orders', ['status' => $request->query('status', 'Pending')])
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Order #'.$data['order_id'].' cancelled.' : (WhmcsClient::lastError() ?: 'Failed to cancel order.'),
            );
    }

    public function pendingOrder(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
        ]);

        $ok = WhmcsClient::pendingOrder((int) $data['order_id']);

        return redirect()
            ->route('admin.whmcs-console.orders', ['status' => $request->query('status', 'all')])
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Order #'.$data['order_id'].' set to pending.' : (WhmcsClient::lastError() ?: 'Failed to update order.'),
            );
    }

    public function tickets(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $status = (string) $request->query('status', 'Open');
        $page = max(1, (int) $request->query('page', 1));
        $limit = 40;
        $start = ($page - 1) * $limit;

        $result = WhmcsClient::getTickets($status === 'all' ? '' : $status, $start, $limit);

        return view('admin.whmcs-console.tickets', [
            'section' => 'tickets',
            'tickets' => $result['tickets'],
            'total' => $result['total'],
            'status' => $status,
            'page' => $page,
            'limit' => $limit,
            'apiError' => WhmcsClient::lastError(),
        ]);
    }

    public function showTicket(int $ticketId): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $ticket = WhmcsClient::getTicket($ticketId);
        if (! $ticket) {
            return redirect()
                ->route('admin.whmcs-console.tickets')
                ->withErrors(['whmcs' => WhmcsClient::lastError() ?: 'Ticket not found.']);
        }

        $replies = data_get($ticket, 'replies.reply', []);
        if (is_array($replies) && isset($replies['id'])) {
            $replies = [$replies];
        } elseif (! is_array($replies)) {
            $replies = [];
        } else {
            $replies = array_values($replies);
        }

        return view('admin.whmcs-console.ticket-show', [
            'section' => 'tickets',
            'ticket' => $ticket,
            'ticketId' => $ticketId,
            'replies' => $replies,
        ]);
    }

    public function replyTicket(Request $request, int $ticketId): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $adminName = trim((string) ($request->user()?->name ?? 'Admin'));
        $ok = WhmcsClient::addTicketReply($ticketId, $data['message'], $adminName !== '' ? $adminName : 'Admin');

        return redirect()
            ->route('admin.whmcs-console.tickets.show', $ticketId)
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Reply posted.' : (WhmcsClient::lastError() ?: 'Failed to post reply.'),
            );
    }

    public function closeTicket(int $ticketId): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $ok = WhmcsClient::closeTicket($ticketId);

        return redirect()
            ->route('admin.whmcs-console.tickets.show', $ticketId)
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Ticket closed.' : (WhmcsClient::lastError() ?: 'Failed to close ticket.'),
            );
    }

    public function domains(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $clientId = (int) $request->query('client_id', 0);
        $page = max(1, (int) $request->query('page', 1));
        $limit = 40;
        $start = ($page - 1) * $limit;

        if ($clientId > 0) {
            $domains = WhmcsClient::getClientDomains($clientId);
            $total = count($domains);
        } else {
            $result = WhmcsClient::getDomains($start, $limit);
            $domains = $result['domains'];
            $total = $result['total'];
        }

        return view('admin.whmcs-console.domains', [
            'section' => 'domains',
            'domains' => $domains,
            'total' => $total,
            'clientId' => $clientId,
            'page' => $page,
            'limit' => $limit,
            'apiError' => WhmcsClient::lastError(),
        ]);
    }

    public function lockDomain(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'domain_id' => ['required', 'integer', 'min:1'],
            'lock' => ['required', 'boolean'],
            'client_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $ok = WhmcsClient::domainUpdateLocking((int) $data['domain_id'], $request->boolean('lock'));

        return redirect()
            ->route('admin.whmcs-console.domains', array_filter([
                'client_id' => $data['client_id'] ?? null,
            ]))
            ->with(
                $ok ? 'status' : 'error',
                $ok
                    ? ('Domain '.($request->boolean('lock') ? 'locked' : 'unlocked').'.')
                    : (WhmcsClient::lastError() ?: 'Failed to update domain lock.'),
            );
    }

    public function renewDomain(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureConfigured()) {
            return $redirect;
        }

        $data = $request->validate([
            'domain_id' => ['required', 'integer', 'min:1'],
            'regperiod' => ['nullable', 'integer', 'min:1', 'max:10'],
            'client_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $ok = WhmcsClient::domainRenew((int) $data['domain_id'], (int) ($data['regperiod'] ?? 1));

        return redirect()
            ->route('admin.whmcs-console.domains', array_filter([
                'client_id' => $data['client_id'] ?? null,
            ]))
            ->with(
                $ok ? 'status' : 'error',
                $ok ? 'Domain renew requested.' : (WhmcsClient::lastError() ?: 'Failed to renew domain.'),
            );
    }

    private function ensureConfigured(): ?RedirectResponse
    {
        if (WhmcsClient::isConfigured()) {
            return null;
        }

        return redirect()
            ->route('admin.whmcs-settings.index')
            ->with('error', 'Configure WHMCS API credentials before using the console.');
    }

    private function serviceActionRedirect(Request $request, bool $ok, string $okMsg, string $failMsg): RedirectResponse
    {
        $clientId = (int) $request->input('client_id', 0);

        $route = $clientId > 0
            ? redirect()->route('admin.whmcs-console.clients.show', $clientId)
            : redirect()->route('admin.whmcs-console.services', array_filter([
                'client_id' => $clientId ?: null,
            ]));

        return $route->with(
            $ok ? 'status' : 'error',
            $ok ? $okMsg : (WhmcsClient::lastError() ?: $failMsg),
        );
    }
}
