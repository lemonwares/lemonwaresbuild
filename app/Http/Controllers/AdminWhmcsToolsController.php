<?php

namespace App\Http\Controllers;

use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use App\Support\AdminPermissions;
use App\Support\Whmcs\Whmcs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Billing, package, domain and ticket jobs that used to need the WHMCS admin area.
 */
class AdminWhmcsToolsController extends AdminQueueController
{
    public const CYCLES = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semiannually' => 'Semi-annually', 'annually' => 'Annually', 'biennially' => 'Biennially', 'triennially' => 'Triennially', 'onetime' => 'One time', 'free' => 'Free'];

    /** Form field => label. Registrars name these differently; see contactKey(). */
    public const CONTACT_FIELDS = [
        'First_Name' => 'First name', 'Last_Name' => 'Last name', 'Company_Name' => 'Company', 'Email' => 'Email',
        'Address_1' => 'Address line 1', 'Address_2' => 'Address line 2', 'City' => 'City', 'State' => 'State',
        'ZIP_Code' => 'Postcode', 'Country' => 'Country (2 letters, e.g. NG)', 'Phone_Country_Code' => 'Phone country code', 'Phone' => 'Phone',
    ];

    private const CONTACT_ALIASES = ['Postcode' => 'ZIP_Code', 'Phone_Number' => 'Phone'];

    public function createInvoice(Request $request, WhmcsCustomer $customer): RedirectResponse
    {
        $data = $request->validate([
            'gateway' => ['required', 'string', 'max:80'],
            'duedate' => ['required', 'date', 'after_or_equal:today'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'sendinvoice' => ['nullable', 'boolean'],
        ]);

        $params = [
            'userid' => $customer->whmcs_client_id,
            'status' => 'Unpaid',
            'paymentmethod' => $data['gateway'],
            'date' => today()->toDateString(),
            'duedate' => $data['duedate'],
            'sendinvoice' => $request->boolean('sendinvoice'),
        ];
        $line = 0;
        foreach ($data['items'] as $item) {
            if (trim((string) ($item['description'] ?? '')) === '' || ! isset($item['amount'])) {
                continue;
            }
            $line++;
            $params['itemdescription'.$line] = $item['description'];
            $params['itemamount'.$line] = $item['amount'];
            $params['itemtaxed'.$line] = false;
        }
        if ($line === 0) {
            return $this->back()->withErrors(['whmcs' => 'Add at least one invoice line with a description and an amount.']);
        }

        $result = Whmcs::call('CreateInvoice', $params, AdminPermissions::currentUser());

        return $result->ok
            ? $this->back()->with('status', 'Invoice #'.$result->get('invoiceid').' created'.($params['sendinvoice'] ? ' and emailed to the client.' : '.'))
            : $this->back()->withErrors(['whmcs' => 'WHMCS refused: '.$result->error]);
    }

    public function createOrder(Request $request, WhmcsCustomer $customer): RedirectResponse
    {
        $data = $request->validate([
            'gateway' => ['required', 'string', 'max:80'],
            'pid' => ['nullable', 'integer', 'min:1'],
            'billingcycle' => ['nullable', 'in:'.implode(',', array_keys(self::CYCLES))],
            'domain' => ['nullable', 'string', 'max:190', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'domaintype' => ['nullable', 'in:none,register,transfer'],
            'regperiod' => ['nullable', 'integer', 'between:1,10'],
            'eppcode' => ['nullable', 'string', 'max:190'],
            'noinvoice' => ['nullable', 'boolean'],
            'noemail' => ['nullable', 'boolean'],
        ]);
        $domainType = $data['domaintype'] ?? 'none';
        if (empty($data['pid']) && $domainType === 'none') {
            return $this->back()->withErrors(['whmcs' => 'Choose a product or a domain registration/transfer for the order.']);
        }
        if ($domainType !== 'none' && empty($data['domain'])) {
            return $this->back()->withErrors(['whmcs' => 'Enter the domain name to register or transfer.']);
        }

        $params = [
            'clientid' => $customer->whmcs_client_id,
            'paymentmethod' => $data['gateway'],
            'noinvoice' => $request->boolean('noinvoice'),
            'noemail' => $request->boolean('noemail'),
        ];
        if (! empty($data['pid'])) {
            $params['pid'] = [(int) $data['pid']];
            $params['billingcycle'] = [$data['billingcycle'] ?? 'annually'];
            if (! empty($data['domain'])) {
                $params['domain'] = [strtolower($data['domain'])];
            }
        }
        if ($domainType !== 'none') {
            $params['domain'] = [strtolower($data['domain'])];
            $params['domaintype'] = [$domainType];
            $params['regperiod'] = [(int) ($data['regperiod'] ?? 1)];
            if ($domainType === 'transfer' && ! empty($data['eppcode'])) {
                $params['eppcode'] = [$data['eppcode']];
            }
        }

        $result = Whmcs::call('AddOrder', $params, AdminPermissions::currentUser());

        return $result->ok
            ? $this->back()->with('status', 'Order #'.$result->get('orderid').' created'.($result->get('invoiceid') ? ' with invoice #'.$result->get('invoiceid') : '').'. Accept it from Needs Action once it is paid.')
            : $this->back()->withErrors(['whmcs' => 'WHMCS refused: '.$result->error]);
    }

    public function adjustCredit(Request $request, WhmcsCustomer $customer): RedirectResponse
    {
        $this->requireFull();
        $data = $request->validate([
            'type' => ['required', 'in:add,remove'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:190'],
        ]);

        return $this->run('AddCredit', [
            'clientid' => $customer->whmcs_client_id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'description' => $data['description'],
        ], $data['type'] === 'add' ? 'Credit added to the client balance.' : 'Credit removed from the client balance.');
    }

    public function applyCredit(Request $request, WhmcsInvoice $invoice): RedirectResponse
    {
        abort_unless($invoice->status === 'Unpaid', 409);
        $amount = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']])['amount'];

        return $this->run('ApplyCredit', ['invoiceid' => $invoice->whmcs_invoice_id, 'amount' => $amount], 'Credit applied to invoice #'.($invoice->invoice_num ?: $invoice->whmcs_invoice_id).'.');
    }

    public function refundInvoice(Request $request, WhmcsInvoice $invoice): RedirectResponse
    {
        $this->requireFull();
        abort_unless($invoice->status === 'Paid', 409);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $invoice->total)],
            'method' => ['required', 'in:gateway,credit'],
            'transid' => ['nullable', 'required_if:method,gateway', 'string', 'max:120'],
        ]);
        $label = '#'.($invoice->invoice_num ?: $invoice->whmcs_invoice_id);
        $admin = AdminPermissions::currentUser();

        $result = $data['method'] === 'credit'
            ? Whmcs::call('AddCredit', [
                'clientid' => $invoice->whmcs_client_id,
                'amount' => $data['amount'],
                'description' => 'Refund of invoice '.$label,
            ], $admin)
            : Whmcs::call('AddTransaction', [
                'paymentmethod' => $invoice->payment_method ?: config('site.whmcs.payment_method'),
                'userid' => $invoice->whmcs_client_id,
                'invoiceid' => $invoice->whmcs_invoice_id,
                'transid' => $data['transid'],
                'amountout' => $data['amount'],
                'description' => 'Refund of invoice '.$label,
            ], $admin);
        if (! $result->ok) {
            return $this->back()->withErrors(['whmcs' => 'WHMCS refused: '.$result->error]);
        }

        $full = (float) $data['amount'] >= (float) $invoice->total;
        if ($full) {
            Whmcs::call('UpdateInvoice', ['invoiceid' => $invoice->whmcs_invoice_id, 'status' => 'Refunded'], $admin);
        }

        return $this->back()->with('status', ($full ? 'Invoice ' : 'Part of invoice ').$label.' refunded '.($data['method'] === 'credit' ? 'to the client credit balance.' : 'and the refund recorded.'));
    }

    public function changePackage(Request $request, WhmcsService $service): RedirectResponse
    {
        abort_unless(in_array($service->status, ['Active', 'Suspended'], true), 409);
        $data = $request->validate([
            'pid' => ['required', 'integer', 'min:1', 'not_in:'.(int) $service->whmcs_product_id],
            'recurringamount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $admin = AdminPermissions::currentUser();

        $update = Whmcs::call('UpdateClientProduct', array_filter([
            'serviceid' => $service->whmcs_service_id,
            'pid' => (int) $data['pid'],
            'recurringamount' => $data['recurringamount'] ?? null,
        ], fn ($v) => $v !== null), $admin);
        if (! $update->ok) {
            return $this->back()->withErrors(['whmcs' => 'WHMCS refused: '.$update->error]);
        }

        return $this->run('ModuleChangePackage', ['serviceid' => $service->whmcs_service_id], 'Package changed and applied on the server.');
    }

    public function domainLock(Request $request, WhmcsDomain $domain): RedirectResponse
    {
        $lock = $request->validate(['lock' => ['required', 'boolean']])['lock'];

        return $this->run('DomainUpdateLocking', ['domainid' => $domain->whmcs_domain_id, 'lockstatus' => (bool) $lock], $domain->domain.($lock ? ' locked.' : ' unlocked. Lock it again after the transfer.'));
    }

    public function domainDetails(WhmcsDomain $domain): JsonResponse
    {
        $lock = Whmcs::call('DomainGetLockingStatus', ['domainid' => $domain->whmcs_domain_id]);
        $whois = Whmcs::call('DomainGetWhoisInfo', ['domainid' => $domain->whmcs_domain_id]);
        $sections = $this->contactSections($whois->data);
        $registrant = $sections[$this->registrantSection($sections)] ?? [];
        $contact = array_fill_keys(array_keys(self::CONTACT_FIELDS), '');
        foreach ($registrant as $key => $value) {
            $contact[$this->contactKey($key)] = (string) $value;
        }

        return response()->json([
            'lock' => $lock->ok ? (string) $lock->get('lockstatus', '') : '',
            'contact' => array_intersect_key($contact, self::CONTACT_FIELDS),
            'error' => ! $whois->ok ? $whois->error : ($sections === [] ? 'the registrar does not support contact changes for this domain.' : null),
        ]);
    }

    public function updateDomainContacts(Request $request, WhmcsDomain $domain): RedirectResponse
    {
        $rules = collect(self::CONTACT_FIELDS)->mapWithKeys(fn ($label, $key) => ['contact.'.$key => ['nullable', 'string', 'max:190']])->all();
        $rules['contact.First_Name'] = ['required', 'string', 'max:190'];
        $rules['contact.Last_Name'] = ['required', 'string', 'max:190'];
        $rules['contact.Email'] = ['required', 'email', 'max:190'];
        $rules['contact.Country'] = ['required', 'string', 'size:2'];
        $contact = array_map(fn ($v) => (string) $v, $request->validate($rules)['contact']);

        $current = Whmcs::call('DomainGetWhoisInfo', ['domainid' => $domain->whmcs_domain_id]);
        if (! $current->ok) {
            return $this->back()->withErrors(['whmcs' => 'Could not load the current contacts: '.$current->error]);
        }
        $sections = $this->contactSections($current->data);
        if ($sections === []) {
            return $this->back()->withErrors(['whmcs' => 'The registrar does not support contact changes for '.$domain->domain.'.']);
        }

        $registrant = $this->registrantSection($sections);
        $xml = new \SimpleXMLElement('<contactdetails/>');
        foreach ($sections as $section => $fields) {
            $node = $xml->addChild($section);
            $apply = $section === $registrant || $request->boolean('all_contacts');
            foreach ($fields as $field => $value) {
                $key = $this->contactKey($field);
                if ($apply && array_key_exists($key, $contact)) {
                    $value = $contact[$key];
                }
                $node->addChild(str_replace(' ', '_', (string) $field), htmlspecialchars((string) $value, ENT_XML1));
            }
        }

        return $this->run('DomainUpdateWhoisInfo', ['domainid' => $domain->whmcs_domain_id, 'xml' => $xml->asXML()], 'Contact details updated for '.$domain->domain.'.');
    }

    /** @return array<string, array<string, mixed>> contact section => fields, as the registrar names them */
    private function contactSections(array $data): array
    {
        return array_filter($data, fn ($value, $key) => is_array($value) && $value !== [] && $key !== 'result', ARRAY_FILTER_USE_BOTH);
    }

    private function registrantSection(array $sections): ?string
    {
        foreach (array_keys($sections) as $name) {
            if (stripos($name, 'registrant') !== false) {
                return $name;
            }
        }

        return array_key_first($sections);
    }

    private function contactKey(string $field): string
    {
        $key = str_replace(' ', '_', $field);

        return self::CONTACT_ALIASES[$key] ?? $key;
    }

    public function showTicket(WhmcsTicket $ticket): View
    {
        $result = Whmcs::call('GetTicket', ['ticketid' => $ticket->whmcs_ticket_id]);
        $customer = WhmcsCustomer::query()->where('whmcs_client_id', $ticket->whmcs_client_id)->first();

        return view('admin.queue.ticket', [
            'ticket' => $ticket,
            'customer' => $customer,
            'error' => $result->ok ? null : $result->error,
            'replies' => $result->ok ? $result->rows('replies.reply') : [],
            'status' => (string) $result->get('status', $ticket->status),
        ]);
    }

    public function replyTicket(Request $request, WhmcsTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
            'status' => ['required', 'in:Answered,On Hold,In Progress,Closed'],
        ]);
        $admin = AdminPermissions::currentUser();

        return $this->run('AddTicketReply', [
            'ticketid' => $ticket->whmcs_ticket_id,
            'message' => $data['message'],
            'adminusername' => $admin?->name ?: 'LemonWares Support',
            'status' => $data['status'],
        ], 'Reply sent to the client.');
    }

    public function updateTicketStatus(Request $request, WhmcsTicket $ticket): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:Open,Answered,On Hold,In Progress,Closed']])['status'];

        return $this->run('UpdateTicket', ['ticketid' => $ticket->whmcs_ticket_id, 'status' => $status], 'Ticket marked '.$status.'.');
    }
}
