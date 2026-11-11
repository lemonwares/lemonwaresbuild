<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWhmcsToolsTest extends TestCase
{
    use RefreshDatabase;

    private WhmcsCustomer $client;

    private string $page;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);
        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response(match ($request['action']) {
            'GetClientsDetails' => ['result' => 'error', 'message' => 'skip mirror refresh in this test'],
            'GetProducts' => ['result' => 'success', 'products' => ['product' => [
                ['pid' => 1, 'name' => 'Starter', 'groupname' => 'Hosting'],
                ['pid' => 2, 'name' => 'Business', 'groupname' => 'Hosting'],
            ]]],
            'GetPaymentMethods' => ['result' => 'success', 'paymentmethods' => ['paymentmethod' => [['module' => 'banktransfer', 'displayname' => 'Bank Transfer']]]],
            'CreateInvoice' => ['result' => 'success', 'invoiceid' => 900],
            'AddOrder' => ['result' => 'success', 'orderid' => 77, 'invoiceid' => 901],
            'DomainGetWhoisInfo' => ['result' => 'success',
                'RegistrantContact' => ['First_Name' => 'Old', 'Last_Name' => 'Owner', 'Email' => 'old@example.test', 'City' => 'Abuja', 'ZIP_Code' => '900001', 'Country' => 'NG'],
                'AdministrativeContact' => ['First_Name' => 'Old', 'Last_Name' => 'Admin', 'Email' => 'old@example.test', 'City' => 'Abuja', 'Country' => 'NG'],
            ],
            'DomainGetLockingStatus' => ['result' => 'success', 'lockstatus' => 'locked'],
            'GetTicket' => ['result' => 'success', 'status' => 'Customer-Reply', 'replies' => ['reply' => [
                ['name' => 'Ada Buyer', 'admin' => '', 'date' => '2026-10-01 10:00', 'message' => 'My site is down'],
            ]]],
            default => ['result' => 'success'],
        })]);

        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true, 'name' => 'Grace Admin']);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);

        $this->client = WhmcsCustomer::query()->create(['whmcs_client_id' => 9, 'full_name' => 'Ada Buyer', 'email' => 'ada@example.test']);
        $this->page = route('admin.customers.legacy.show', $this->client);
    }

    public function test_admin_creates_orders_invoices_and_adjusts_credit(): void
    {
        $this->get($this->page)->assertOk()->assertSee('New order')->assertSee('New invoice')->assertSee('Credit balance')->assertSee('Hosting: Business');

        $this->from($this->page)->post(route('admin.queue.clients.invoice', $this->client), [
            'gateway' => 'banktransfer',
            'duedate' => today()->addWeek()->toDateString(),
            'items' => [['description' => 'Website design deposit', 'amount' => 250000], ['description' => '', 'amount' => '']],
            'sendinvoice' => 1,
        ])->assertRedirect($this->page)->assertSessionHas('status');
        Http::assertSent(fn ($r) => $r['action'] === 'CreateInvoice' && (int) $r['userid'] === 9
            && $r['itemdescription1'] === 'Website design deposit' && ! isset($r['itemdescription2']));

        $this->from($this->page)->post(route('admin.queue.clients.order', $this->client), [
            'gateway' => 'banktransfer', 'pid' => 2, 'billingcycle' => 'annually',
            'domain' => 'ada-shop.com', 'domaintype' => 'register', 'regperiod' => 1,
        ])->assertRedirect($this->page)->assertSessionHas('status');
        Http::assertSent(fn ($r) => $r['action'] === 'AddOrder' && (int) $r['clientid'] === 9
            && $r['pid'] == [2] && $r['domain'] == ['ada-shop.com'] && $r['domaintype'] == ['register']);

        $this->from($this->page)->post(route('admin.queue.clients.order', $this->client), ['gateway' => 'banktransfer', 'domaintype' => 'none'])
            ->assertSessionHasErrors('whmcs');

        $this->from($this->page)->post(route('admin.queue.clients.credit', $this->client), ['type' => 'add', 'amount' => 5000, 'description' => 'Goodwill'])
            ->assertRedirect($this->page);
        Http::assertSent(fn ($r) => $r['action'] === 'AddCredit' && $r['type'] === 'add' && (float) $r['amount'] === 5000.0);
    }

    public function test_refunds_credit_and_package_changes(): void
    {
        $paid = WhmcsInvoice::query()->create(['whmcs_invoice_id' => 81, 'whmcs_client_id' => 9, 'status' => 'Paid', 'total' => 10000, 'payment_method' => 'banktransfer']);
        $unpaid = WhmcsInvoice::query()->create(['whmcs_invoice_id' => 82, 'whmcs_client_id' => 9, 'status' => 'Unpaid', 'total' => 3000]);
        $service = WhmcsService::query()->create(['whmcs_customer_id' => $this->client->id, 'whmcs_service_id' => 51, 'whmcs_client_id' => 9, 'whmcs_product_id' => 1, 'status' => 'Active', 'product_name' => 'Starter']);

        $this->get($this->page.'?wtab=invoices')->assertOk()->assertSee('Refund')->assertSee('Apply credit');
        $this->get($this->page)->assertOk()->assertSee('Change package');

        $this->from($this->page)->post(route('admin.queue.invoices.refund', $paid), ['amount' => 10000, 'method' => 'gateway', 'transid' => 'RF-1'])
            ->assertRedirect($this->page)->assertSessionHas('status');
        Http::assertSent(fn ($r) => $r['action'] === 'AddTransaction' && (int) $r['invoiceid'] === 81 && (float) $r['amountout'] === 10000.0);
        Http::assertSent(fn ($r) => $r['action'] === 'UpdateInvoice' && (int) $r['invoiceid'] === 81 && $r['status'] === 'Refunded');

        $this->from($this->page)->post(route('admin.queue.invoices.refund', $paid), ['amount' => 20000, 'method' => 'credit'])->assertSessionHasErrors('amount');

        $this->from($this->page)->post(route('admin.queue.invoices.apply-credit', $unpaid), ['amount' => 3000])->assertRedirect($this->page);
        Http::assertSent(fn ($r) => $r['action'] === 'ApplyCredit' && (int) $r['invoiceid'] === 82);

        $this->from($this->page)->post(route('admin.queue.services.package', $service), ['pid' => 1])->assertSessionHasErrors('pid');
        $this->from($this->page)->post(route('admin.queue.services.package', $service), ['pid' => 2, 'recurringamount' => 45000])->assertRedirect($this->page);
        Http::assertSent(fn ($r) => $r['action'] === 'UpdateClientProduct' && (int) $r['pid'] === 2 && (int) $r['serviceid'] === 51);
        Http::assertSent(fn ($r) => $r['action'] === 'ModuleChangePackage' && (int) $r['serviceid'] === 51);
    }

    public function test_domain_lock_and_contacts(): void
    {
        $domain = WhmcsDomain::query()->create(['whmcs_domain_id' => 61, 'whmcs_client_id' => 9, 'domain' => 'live-site.com', 'status' => 'Active']);

        $this->get($this->page.'?wtab=domains')->assertOk()->assertSee('Lock &amp; contacts', false);
        $this->getJson(route('admin.queue.domains.details', $domain))->assertOk()
            ->assertJsonPath('lock', 'locked')->assertJsonPath('contact.First_Name', 'Old')->assertJsonPath('contact.ZIP_Code', '900001');

        $this->from($this->page)->post(route('admin.queue.domains.lock', $domain), ['lock' => 0])->assertRedirect($this->page);
        Http::assertSent(fn ($r) => $r['action'] === 'DomainUpdateLocking' && ! filter_var($r['lockstatus'], FILTER_VALIDATE_BOOLEAN));

        $this->from($this->page)->put(route('admin.queue.domains.contacts', $domain), ['contact' => [
            'First_Name' => 'Ada', 'Last_Name' => 'Buyer', 'Email' => 'ada@example.test', 'Country' => 'NG', 'City' => 'Lagos & Co', 'ZIP_Code' => '100001',
        ], 'all_contacts' => 1])->assertRedirect($this->page)->assertSessionHas('status');
        Http::assertSent(fn ($r) => $r['action'] === 'DomainUpdateWhoisInfo'
            && str_contains($r['xml'], '<RegistrantContact><First_Name>Ada</First_Name>')
            && str_contains($r['xml'], '<City>Lagos &amp; Co</City><ZIP_Code>100001</ZIP_Code>')
            && str_contains($r['xml'], '<AdministrativeContact><First_Name>Ada</First_Name>')
            && ! str_contains($r['xml'], '<Company_Name>'));
    }

    public function test_ticket_thread_reply_and_queue_listing(): void
    {
        $ticket = WhmcsTicket::query()->create(['whmcs_ticket_id' => 300, 'tid' => 'ABC-123', 'whmcs_client_id' => 9, 'subject' => 'Site down', 'status' => 'Customer-Reply']);

        $this->get(route('admin.queue.index'))->assertOk()->assertSee('Tickets waiting for a reply')->assertSee('#ABC-123');
        $this->get($this->page.'?wtab=tickets')->assertOk()->assertSee(route('admin.queue.tickets.show', $ticket));
        $this->get(route('admin.queue.tickets.show', $ticket))->assertOk()->assertSee('My site is down')->assertSee('Ada Buyer');

        $this->from(route('admin.queue.tickets.show', $ticket))
            ->post(route('admin.queue.tickets.reply', $ticket), ['message' => 'Fixed now.', 'status' => 'Answered'])
            ->assertRedirect(route('admin.queue.tickets.show', $ticket));
        Http::assertSent(fn ($r) => $r['action'] === 'AddTicketReply' && (int) $r['ticketid'] === 300
            && $r['message'] === 'Fixed now.' && $r['adminusername'] === 'Grace Admin' && $r['status'] === 'Answered');

        $this->post(route('admin.queue.tickets.status', $ticket), ['status' => 'Closed'])->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'UpdateTicket' && $r['status'] === 'Closed');
    }
}
