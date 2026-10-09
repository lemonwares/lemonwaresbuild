<?php

namespace Tests\Feature;

use App\Filament\Resources\WhmcsOrders\Pages\ViewWhmcsOrder;
use App\Filament\Resources\WhmcsTickets\Pages\ViewWhmcsTicket;
use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsOrder;
use App\Models\WhmcsTicket;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ConsoleOrdersTicketsTest extends TestCase
{
    use RefreshDatabase;

    private WhmcsOrder $order;

    private WhmcsTicket $ticket;

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
            'GetTicket' => ['replies' => ['reply' => [
                ['name' => 'Ada Lovelace', 'admin' => '', 'date' => '2026-10-08 10:00', 'message' => "My site is down <script>alert(1)</script>"],
            ]]],
            'GetOrders' => ['totalresults' => 1, 'orders' => ['order' => [['id' => 61, 'userid' => 7, 'status' => 'Active', 'paymentstatus' => 'Paid']]]],
            'GetTickets' => ['totalresults' => 1, 'tickets' => ['ticket' => [['id' => 91, 'tid' => 'ABC-123', 'userid' => 7, 'subject' => 'Site down', 'status' => 'In Progress']]]],
            'GetSupportDepartments' => ['departments' => ['department' => [['id' => 1, 'name' => 'Technical']]]],
            default => [],
        })]);

        WhmcsCustomer::query()->create(['whmcs_client_id' => 7, 'full_name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        WhmcsInvoice::query()->create(['whmcs_invoice_id' => 501, 'whmcs_client_id' => 7, 'status' => 'Unpaid', 'total' => '40.00', 'due_date' => today()->subDay()]);
        $this->order = WhmcsOrder::query()->create([
            'whmcs_order_id' => 61, 'whmcs_client_id' => 7, 'status' => 'Pending', 'payment_status' => 'Paid', 'amount' => '25.00',
            'raw_payload' => ['lineitems' => ['lineitem' => ['product' => 'Starter', 'domain' => 'ada.test', 'billingcycle' => 'Annually', 'amount' => '25.00']]],
        ]);
        $this->ticket = WhmcsTicket::query()->create([
            'whmcs_ticket_id' => 91, 'tid' => 'ABC-123', 'whmcs_client_id' => 7, 'subject' => 'Site down', 'status' => 'Open', 'priority' => 'High', 'department_id' => 1,
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'is_super_admin' => true]), 'console');
        session(['admin_authenticated' => true, 'admin_user_id' => auth('console')->id()]);
    }

    public function test_dashboard_and_pages_render_with_escaped_ticket_messages(): void
    {
        $this->get('/console')->assertOk()->assertSee('Overdue invoices')->assertSee('Pending orders');
        $this->get('/console/whmcs-orders/'.$this->order->id)->assertOk()->assertSee('Starter — ada.test');
        $this->get('/console/whmcs-tickets')->assertOk()->assertSee('Site down');

        $this->get('/console/whmcs-tickets/'.$this->ticket->id)
            ->assertOk()
            ->assertSee('My site is down')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_accepting_an_order_and_replying_to_a_ticket(): void
    {
        Livewire::test(ViewWhmcsOrder::class, ['record' => $this->order->id])
            ->callAction('accept', data: ['autosetup' => true, 'sendemail' => false])
            ->assertNotified('Order accepted');

        Livewire::test(ViewWhmcsTicket::class, ['record' => $this->ticket->id])
            ->callAction('reply', data: ['message' => 'Looking into it now.', 'status' => 'In Progress'])
            ->assertNotified('Reply sent');

        Http::assertSent(fn ($r) => $r['action'] === 'AcceptOrder' && (int) $r['orderid'] === 61 && (bool) $r['autosetup']);
        Http::assertSent(fn ($r) => $r['action'] === 'AddTicketReply' && (int) $r['ticketid'] === 91 && $r['status'] === 'In Progress');
    }
}
