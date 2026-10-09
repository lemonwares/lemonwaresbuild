<?php

namespace Tests\Feature;

use App\Filament\Resources\WhmcsCustomers\Pages\ViewWhmcsCustomer;
use App\Filament\Resources\WhmcsCustomers\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\WhmcsDomains\Pages\ViewWhmcsDomain;
use App\Filament\Resources\WhmcsInvoices\Pages\ViewWhmcsInvoice;
use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsDomain;
use App\Models\WhmcsInvoice;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ConsoleInvoicesDomainsTest extends TestCase
{
    use RefreshDatabase;

    private WhmcsCustomer $customer;

    private WhmcsInvoice $invoice;

    private WhmcsDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);
        Filament::setCurrentPanel('console');

        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response(['result' => 'success'] + match ($request['action']) {
            'GetInvoice' => ['invoiceid' => 501, 'items' => ['item' => [['description' => 'Starter hosting', 'amount' => '25.00']]]],
            'GetPaymentMethods' => ['paymentmethods' => ['paymentmethod' => [['module' => 'banktransfer', 'displayname' => 'Bank transfer']]]],
            'DomainGetNameservers' => ['ns1' => 'ns1.lemonwares.test', 'ns2' => 'ns2.lemonwares.test'],
            'CreateInvoice' => ['invoiceid' => 502],
            default => [],
        })]);

        $this->customer = WhmcsCustomer::query()->create(['whmcs_client_id' => 7, 'full_name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        $this->invoice = WhmcsInvoice::query()->create(['whmcs_invoice_id' => 501, 'whmcs_client_id' => 7, 'status' => 'Unpaid', 'total' => '25.00', 'due_date' => today()]);
        $this->domain = WhmcsDomain::query()->create(['whmcs_domain_id' => 81, 'whmcs_client_id' => 7, 'domain' => 'ada.test', 'status' => 'Active']);
    }

    private function signIn(array $permissions = []): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'is_super_admin' => $permissions === [],
            'admin_permissions' => $permissions ?: null,
        ]), 'console');
    }

    public function test_pages_render_with_live_invoice_items(): void
    {
        $this->signIn();
        session(['admin_authenticated' => true, 'admin_user_id' => auth('console')->id()]);

        $this->get('/console/whmcs-invoices')->assertOk()->assertSee('Ada Lovelace');
        $this->get('/console/whmcs-invoices/'.$this->invoice->id)->assertOk()->assertSee('Starter hosting');
        $this->get('/console/whmcs-domains')->assertOk()->assertSee('ada.test');
        $this->get('/console/whmcs-domains/'.$this->domain->id)->assertOk();
    }

    public function test_recording_a_payment_and_creating_an_invoice_for_a_client(): void
    {
        $this->signIn();

        Livewire::test(ViewWhmcsInvoice::class, ['record' => $this->invoice->id])
            ->callAction('addPayment', data: ['amount' => 25, 'gateway' => 'banktransfer', 'transid' => 'TX-1', 'date' => today()->toDateString()])
            ->assertNotified('Payment recorded');

        Livewire::test(InvoicesRelationManager::class, ['ownerRecord' => $this->customer, 'pageClass' => ViewWhmcsCustomer::class])
            ->callAction(TestAction::make('createInvoice')->table(), data: [
                'duedate' => today()->addWeek()->toDateString(),
                'items' => [['description' => 'Setup fee', 'amount' => 10]],
            ])
            ->assertNotified('Invoice created');

        Http::assertSent(fn ($r) => $r['action'] === 'AddInvoicePayment' && $r['transid'] === 'TX-1' && $r['gateway'] === 'banktransfer');
        Http::assertSent(fn ($r) => $r['action'] === 'CreateInvoice' && (int) $r['userid'] === 7 && $r['itemdescription1'] === 'Setup fee');
    }

    public function test_nameservers_prefill_and_epp_code_needs_full_access(): void
    {
        $this->signIn(['whmcs:edit']);

        Livewire::test(ViewWhmcsDomain::class, ['record' => $this->domain->id])
            ->assertActionHidden('eppCode')
            ->assertActionVisible('renew')
            ->mountAction('nameservers')
            ->assertSchemaStateSet(['ns1' => 'ns1.lemonwares.test', 'ns2' => 'ns2.lemonwares.test']);
    }
}
