<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminWhmcsClientActionsTest extends TestCase
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
        Http::fake(['https://billing.example.test/includes/api.php' => fn ($request) => Http::response($request['action'] === 'GetClientsDetails'
            ? ['result' => 'success', 'client' => ['id' => 77, 'fullname' => 'Name Placeholder', 'companyname' => 'Dillon Gas', 'email' => 'dillon@example.test', 'status' => $this->client->fresh()->status]]
            : ['result' => 'success', 'redirect_url' => 'https://billing.example.test/sso'])]);

        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);

        $this->client = WhmcsCustomer::query()->create([
            'whmcs_client_id' => 77, 'full_name' => 'Name Placeholder', 'company' => 'Dillon Gas',
            'email' => 'dillon@example.test', 'status' => 'Active',
        ]);
    }

    public function test_status_reset_and_sign_in_go_through_whmcs(): void
    {
        $this->get(route('admin.customers.legacy.show', $this->client))->assertOk()->assertSee('Account access')->assertSee('Deactivate');

        $this->post(route('admin.customers.legacy.status', $this->client), ['status' => 'Closed'])->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'UpdateClient' && (int) $r['clientid'] === 77 && $r['status'] === 'Closed');
        $this->assertSame('Closed', $this->client->fresh()->status);

        $this->post(route('admin.customers.legacy.password-reset', $this->client))->assertRedirect();
        Http::assertSent(fn ($r) => $r['action'] === 'ResetPassword' && $r['email'] === 'dillon@example.test');

        $this->post(route('admin.customers.legacy.sign-in', $this->client))->assertRedirect('https://billing.example.test/sso');
    }

    public function test_invite_creates_and_links_a_site_account(): void
    {
        Notification::fake();

        $this->post(route('admin.customers.legacy.invite', $this->client))->assertRedirect();

        $user = User::query()->where('email', 'dillon@example.test')->sole();
        $this->assertSame('Dillon Gas', $user->name);
        $this->assertSame($user->id, $this->client->fresh()->user_id);
    }
}
