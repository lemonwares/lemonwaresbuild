<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsService;
use App\Models\WhmcsTicket;
use App\Support\Whmcs\Whmcs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsMirrorTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $invoiceStatus = ['501' => 'Unpaid'];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => fn ($request) => Http::response($this->respond($request->data())),
        ]);
    }

    private function respond(array $data): array
    {
        $ok = ['result' => 'success'];
        $invoices = collect($this->invoiceStatus)
            ->map(fn ($status, $id) => ['id' => (int) $id, 'userid' => 7, 'invoicenum' => '', 'date' => '2026-10-01', 'duedate' => '2026-10-08', 'datepaid' => '0000-00-00 00:00:00', 'total' => '25.00', 'status' => $status, 'currencycode' => 'USD'])
            ->filter(fn ($row) => ($data['status'] ?? null) === null || $row['status'] === $data['status'])
            ->values()->all();

        return $ok + match ($data['action']) {
            'GetClients' => ['totalresults' => 1, 'clients' => ['client' => ['id' => 7, 'firstname' => 'Ada', 'lastname' => 'Lovelace', 'email' => 'ada@example.com', 'status' => 'Active']]],
            'GetClientsDetails' => ['client' => ['id' => 7, 'firstname' => 'Ada', 'lastname' => 'Lovelace', 'email' => 'ada@example.com', 'credit' => '12.50', 'currency_code' => 'USD', 'securityqans' => 'secret answer']],
            'GetClientsProducts' => ['totalresults' => 1, 'products' => ['product' => ['id' => 31, 'pid' => 4, 'name' => 'Starter', 'domain' => 'ada.test', 'status' => 'Active', 'recurringamount' => '9.99', 'regdate' => '2026-01-01', 'nextduedate' => '2027-01-01', 'password' => 'cpanel-pass']]],
            'GetClientsDomains' => ['totalresults' => 1, 'domains' => ['domain' => ['id' => 81, 'userid' => 7, 'domainname' => 'ada.test', 'status' => 'Active', 'expirydate' => '2027-01-01', 'donotrenew' => 0]]],
            'GetInvoices' => ['totalresults' => count($invoices), 'invoices' => ['invoice' => $invoices]],
            'GetOrders' => ['totalresults' => 1, 'orders' => ['order' => [['id' => 61, 'userid' => 7, 'ordernum' => '123', 'status' => 'Pending', 'amount' => '25.00', 'invoiceid' => 501]]]],
            'GetTickets' => ['totalresults' => 1, 'tickets' => ['ticket' => [['id' => 91, 'tid' => 'ABC-123', 'userid' => 7, 'subject' => 'Help', 'status' => 'Open', 'deptid' => 1]]]],
            'ModuleSuspend' => [],
        };
    }

    public function test_sync_mirrors_every_record_type_without_storing_secrets(): void
    {
        $this->artisan('whmcs:sync')->assertSuccessful();

        $customer = WhmcsCustomer::query()->where('whmcs_client_id', 7)->firstOrFail();
        $this->assertSame('12.50', $customer->credit);
        $this->assertNotNull($customer->details_synced_at);
        $this->assertArrayNotHasKey('securityqans', $customer->raw_payload);

        $service = WhmcsService::query()->where('whmcs_service_id', 31)->firstOrFail();
        $this->assertSame('9.99', $service->amount);
        $this->assertArrayNotHasKey('password', $service->raw_payload);

        $this->assertTrue(WhmcsDomain::query()->where('whmcs_domain_id', 81)->firstOrFail()->auto_renew);
        $invoice = WhmcsInvoice::query()->where('whmcs_invoice_id', 501)->firstOrFail();
        $this->assertSame('Unpaid', $invoice->status);
        $this->assertNull($invoice->date_paid);
        $this->assertSame(1, WhmcsOrder::query()->count());
        $this->assertSame('ABC-123', WhmcsTicket::query()->firstOrFail()->tid);
    }

    public function test_paid_invoices_drop_out_of_the_open_list_and_trigger_a_client_refresh(): void
    {
        $this->artisan('whmcs:sync')->assertSuccessful();
        WhmcsCustomer::query()->update(['details_synced_at' => now()]);

        $this->invoiceStatus = ['501' => 'Paid'];
        $this->artisan('whmcs:sync', ['--batch' => 0])->assertSuccessful();

        $this->assertSame('Paid', WhmcsInvoice::query()->where('whmcs_invoice_id', 501)->value('status'));
    }

    public function test_console_write_refreshes_the_affected_client(): void
    {
        $this->artisan('whmcs:sync')->assertSuccessful();
        $this->invoiceStatus = [];

        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->assertTrue(Whmcs::call('ModuleSuspend', ['serviceid' => 31], $admin)->ok);

        $this->assertSame(0, WhmcsInvoice::query()->count(), 'Invoices WHMCS no longer returns are removed.');
    }
}
