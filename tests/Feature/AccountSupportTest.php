<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountSupportTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response(['result' => 'success'] + match ($request['action']) {
            'GetSupportDepartments' => ['departments' => ['department' => [['id' => 1, 'name' => 'Technical']]]],
            'OpenTicket' => ['id' => 91, 'tid' => 'ABC-123'],
            'GetTicket' => ['status' => 'Answered', 'replies' => ['reply' => [
                ['name' => 'Ada', 'admin' => '', 'date' => '2026-10-08 10:00', 'message' => 'Site down <script>alert(1)</script>'],
                ['name' => '', 'admin' => 'Sam', 'date' => '2026-10-08 11:00', 'message' => 'Fixed now.'],
            ]]],
            default => [],
        })]);

        $this->customer = User::factory()->create();
        WhmcsCustomer::query()->create(['user_id' => $this->customer->id, 'whmcs_client_id' => 7, 'email' => $this->customer->email]);
    }

    public function test_customer_opens_a_ticket_and_reads_the_conversation_safely(): void
    {
        $this->actingAs($this->customer)
            ->post(route('account.support.store'), ['deptid' => 1, 'priority' => 'High', 'subject' => 'Site down', 'message' => 'Please help'])
            ->assertRedirect();

        $ticket = WhmcsTicket::query()->where('whmcs_ticket_id', 91)->firstOrFail();
        $this->assertSame(7, (int) $ticket->whmcs_client_id);
        Http::assertSent(fn ($r) => $r['action'] === 'OpenTicket' && (int) $r['clientid'] === 7 && $r['subject'] === 'Site down');

        $this->actingAs($this->customer)->get(route('account.support.show', $ticket))
            ->assertOk()
            ->assertSee('Fixed now.')
            ->assertSee('Sam (LemonWares)')
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame('Answered', $ticket->fresh()->status);

        $this->actingAs($this->customer)->post(route('account.support.reply', $ticket), ['message' => 'Thanks!'])->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'AddTicketReply' && (int) $r['clientid'] === 7 && $r['message'] === 'Thanks!');

        $this->actingAs($this->customer)->get(route('account.support.index'))->assertOk()->assertSee('Site down');
    }

    public function test_customers_cannot_see_other_clients_tickets(): void
    {
        $other = WhmcsTicket::query()->create(['whmcs_ticket_id' => 92, 'tid' => 'XYZ-999', 'whmcs_client_id' => 8, 'subject' => 'Private', 'status' => 'Open']);

        $this->actingAs($this->customer)->get(route('account.support.show', $other))->assertNotFound();
        $this->actingAs($this->customer)->post(route('account.support.reply', $other), ['message' => 'hi'])->assertNotFound();
        $this->actingAs($this->customer)->get(route('account.support.index'))->assertOk()->assertDontSee('Private');

        Http::assertNotSent(fn ($r) => in_array($r['action'], ['GetTicket', 'AddTicketReply'], true));
    }

    public function test_customer_without_billing_account_sees_guidance_and_cannot_open_tickets(): void
    {
        $newcomer = User::factory()->create();

        $this->actingAs($newcomer)->get(route('account.support.index'))->assertOk()->assertSee(__('account.support_no_account'));
        $this->actingAs($newcomer)->get(route('account.support.create'))->assertRedirect(route('account.support.index'));
        $this->actingAs($newcomer)->post(route('account.support.store'), ['deptid' => 1, 'priority' => 'Low', 'subject' => 'x', 'message' => 'y'])->assertNotFound();
    }
}
