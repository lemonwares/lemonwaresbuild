<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Models\WhmcsInvoice;
use App\Models\WhmcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountSelfServiceTest extends TestCase
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

        Http::fake(['https://billing.example.test/includes/api.php' => Http::response(['result' => 'success'])]);

        $this->customer = User::factory()->create(['name' => 'Ada Lovelace']);
        WhmcsCustomer::query()->create(['user_id' => $this->customer->id, 'whmcs_client_id' => 7, 'email' => $this->customer->email, 'details_synced_at' => now()]);
    }

    public function test_customer_requests_cancellation_of_own_active_service_only(): void
    {
        $mine = WhmcsCustomer::query()->where('whmcs_client_id', 7)->sole();
        $theirs = WhmcsCustomer::query()->create(['user_id' => User::factory()->create()->id, 'whmcs_client_id' => 8, 'email' => 'other@example.test']);
        $own = WhmcsService::query()->create(['whmcs_customer_id' => $mine->id, 'user_id' => $this->customer->id, 'whmcs_service_id' => 31, 'whmcs_client_id' => 7, 'status' => 'Active', 'product_name' => 'Starter']);
        $other = WhmcsService::query()->create(['whmcs_customer_id' => $theirs->id, 'user_id' => $theirs->user_id, 'whmcs_service_id' => 32, 'whmcs_client_id' => 8, 'status' => 'Active', 'product_name' => 'Starter']);
        $this->actingAs($this->customer);

        $this->get(route('account.subscriptions.show', $own))->assertOk()->assertSee(__('account.service_cancel_submit'));

        $this->post(route('account.subscriptions.cancel', $own), ['type' => 'End of Billing Period', 'reason' => 'Moving on'])
            ->assertRedirect(route('account.subscriptions.show', $own))
            ->assertSessionHas('status', __('account.service_cancel_requested'));
        Http::assertSent(fn ($r) => $r['action'] === 'AddCancelRequest' && (int) $r['serviceid'] === 31 && $r['type'] === 'End of Billing Period');

        $this->post(route('account.subscriptions.cancel', $other), ['type' => 'Immediate', 'reason' => 'x'])->assertNotFound();
        $this->post(route('account.subscriptions.cancel', $own), ['type' => 'Whenever', 'reason' => 'x'])->assertSessionHasErrors('type');
    }

    public function test_profile_changes_are_pushed_to_the_whmcs_client_record(): void
    {
        $this->actingAs($this->customer)->put(route('account.profile.update'), [
            'name' => 'Ada King Lovelace',
            'company' => 'Analytical Ltd',
            'billing_city' => 'Lagos',
            'billing_country' => 'NG',
        ])->assertRedirect(route('account.profile'));

        Http::assertSent(fn ($r) => $r['action'] === 'UpdateClient' && (int) $r['clientid'] === 7
            && $r['firstname'] === 'Ada' && $r['lastname'] === 'King Lovelace'
            && $r['companyname'] === 'Analytical Ltd' && $r['city'] === 'Lagos' && $r['country'] === 'NG');
    }

    public function test_invoice_list_reads_the_local_copy_without_calling_whmcs(): void
    {
        WhmcsInvoice::query()->create(['whmcs_invoice_id' => 501, 'whmcs_client_id' => 7, 'status' => 'Unpaid', 'total' => 15000, 'currency_code' => 'NGN', 'date' => now()]);
        WhmcsInvoice::query()->create(['whmcs_invoice_id' => 777, 'whmcs_client_id' => 8, 'status' => 'Unpaid', 'total' => 99, 'date' => now()]);

        $this->actingAs($this->customer)->get(route('account.invoices.index'))
            ->assertOk()
            ->assertSee('#501')
            ->assertDontSee('#777');

        Http::assertNothingSent();
    }
}
