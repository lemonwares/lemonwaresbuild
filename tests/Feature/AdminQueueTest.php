<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private WhmcsCustomer $client;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);
        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response(
            $request['action'] === 'GetClientsDetails'
                ? ['result' => 'error', 'message' => 'skip mirror refresh in this test']
                : ['result' => 'success']
        )]);

        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);

        $this->client = WhmcsCustomer::query()->create(['whmcs_client_id' => 9, 'full_name' => 'Ada Buyer', 'email' => 'ada@example.test']);
    }

    public function test_queue_lists_pending_work_and_runs_actions_in_whmcs(): void
    {
        $invoice = WhmcsInvoice::query()->create(['whmcs_invoice_id' => 500, 'whmcs_client_id' => 9, 'status' => 'Unpaid', 'total' => 15000, 'payment_method' => 'banktransfer']);
        $order = WhmcsOrder::query()->create(['whmcs_order_id' => 70, 'whmcs_client_id' => 9, 'order_num' => '123456', 'status' => 'Pending', 'payment_status' => 'Unpaid', 'whmcs_invoice_id' => 500, 'amount' => 15000]);
        $transfer = WhmcsDomain::query()->create(['whmcs_domain_id' => 31, 'whmcs_client_id' => 9, 'domain' => 'move-me.com', 'status' => 'Pending Transfer']);
        $register = WhmcsDomain::query()->create(['whmcs_domain_id' => 32, 'whmcs_client_id' => 9, 'domain' => 'new-one.ng', 'status' => 'Pending']);
        $service = WhmcsService::query()->create(['whmcs_customer_id' => $this->client->id, 'whmcs_service_id' => 41, 'whmcs_client_id' => 9, 'status' => 'Pending', 'product_name' => 'Starter Hosting']);

        $this->get(route('admin.queue.index'))->assertOk()
            ->assertSee('#123456')->assertSee('Ada Buyer')->assertSee('move-me.com')->assertSee('Submit transfer')
            ->assertSee('new-one.ng')->assertSee('Register now')->assertSee('Starter Hosting');

        $this->post(route('admin.queue.invoices.payment', $invoice), ['amount' => 15000, 'gateway' => 'banktransfer', 'transid' => 'GTB-1', 'date' => today()->toDateString()])
            ->assertRedirect(route('admin.queue.index'));
        Http::assertSent(fn ($r) => $r['action'] === 'AddInvoicePayment' && (int) $r['invoiceid'] === 500 && $r['transid'] === 'GTB-1');

        $this->post(route('admin.queue.orders.accept', $order))->assertRedirect(route('admin.queue.index'));
        Http::assertSent(fn ($r) => $r['action'] === 'AcceptOrder' && (int) $r['orderid'] === 70 && filter_var($r['autosetup'], FILTER_VALIDATE_BOOLEAN));

        $this->post(route('admin.queue.domains.send', $transfer))->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'DomainTransfer' && (int) $r['domainid'] === 31);

        $this->post(route('admin.queue.domains.send', $register))->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'DomainRegister' && (int) $r['domainid'] === 32);

        $this->post(route('admin.queue.services.provision', $service))->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'ModuleCreate' && (int) $r['serviceid'] === 41);
    }

    public function test_customer_page_tabs_offer_and_run_row_actions(): void
    {
        $service = WhmcsService::query()->create(['whmcs_customer_id' => $this->client->id, 'whmcs_service_id' => 51, 'whmcs_client_id' => 9, 'status' => 'Active', 'product_name' => 'Pro Hosting']);
        $domain = WhmcsDomain::query()->create(['whmcs_domain_id' => 61, 'whmcs_client_id' => 9, 'domain' => 'live-site.com', 'status' => 'Active']);
        $invoice = WhmcsInvoice::query()->create(['whmcs_invoice_id' => 71, 'whmcs_client_id' => 9, 'status' => 'Unpaid', 'total' => 5000]);
        $page = route('admin.customers.legacy.show', $this->client);

        $this->get($page)->assertOk()->assertSee('Control panel')->assertSee('Suspend')->assertSee('Terminate');
        $this->get($page.'?wtab=domains')->assertOk()->assertSee('Renew')->assertSee('Nameservers')->assertSee('EPP code');
        $this->get($page.'?wtab=invoices')->assertOk()->assertSee('Record payment')->assertSee('Remind');

        $this->from($page)->post(route('admin.queue.services.suspend', $service), ['reason' => 'Overdue'])->assertRedirect($page);
        Http::assertSent(fn ($r) => $r['action'] === 'ModuleSuspend' && (int) $r['serviceid'] === 51 && $r['suspendreason'] === 'Overdue');

        $this->from($page)->post(route('admin.queue.domains.renew', $domain), ['years' => 2])->assertRedirect($page);
        Http::assertSent(fn ($r) => $r['action'] === 'DomainRenew' && (int) $r['regperiod'] === 2);

        $this->from($page)->put(route('admin.queue.domains.nameservers.update', $domain), ['ns1' => 'ns1.host.ng', 'ns2' => 'ns2.host.ng'])->assertRedirect($page);
        Http::assertSent(fn ($r) => $r['action'] === 'DomainUpdateNameservers' && $r['ns1'] === 'ns1.host.ng' && ! isset($r['ns3']));

        $this->from($page)->post(route('admin.queue.invoices.reminder', $invoice))->assertRedirect($page);
        Http::assertSent(fn ($r) => $r['action'] === 'SendEmail' && $r['messagename'] === 'Invoice Payment Reminder' && (int) $r['id'] === 71);
    }

    public function test_actions_refuse_records_that_are_no_longer_pending(): void
    {
        $service = WhmcsService::query()->create(['whmcs_customer_id' => $this->client->id, 'whmcs_service_id' => 42, 'whmcs_client_id' => 9, 'status' => 'Active']);

        $this->post(route('admin.queue.services.provision', $service))->assertStatus(409);
        Http::assertNotSent(fn ($r) => $r['action'] === 'ModuleCreate');
    }
}
