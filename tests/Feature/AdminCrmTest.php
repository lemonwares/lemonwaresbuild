<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhmcsCustomer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_and_demo_customer(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'amara@brightmedia.ng',
            'role' => 'customer',
            'company' => 'Bright Media',
        ]);

        $this->assertDatabaseHas('email_orders', [
            'domain' => 'brightmedia.ng',
        ]);
    }

    public function test_admin_can_sign_in_and_open_customer_crm(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Staff CRM', false)
            ->assertSee('Amara Okonkwo', false);

        $customer = User::query()->where('email', 'amara@brightmedia.ng')->firstOrFail();

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('brightmedia.ng', false)
            ->assertSee('hello@brightmedia.ng', false)
            ->assertSee('203.0.113.42', false)
            ->assertSee('cPanel', false)
            ->assertSee('ops@brightmedia.ng', false)
            ->assertSee('14 Admiralty Way', false);

        $whmcsCustomer = \App\Models\WhmcsCustomer::query()->create([
            'user_id' => $customer->id,
            'whmcs_client_id' => 42,
            'full_name' => $customer->name,
            'email' => $customer->email,
            'status' => 'Active',
        ]);

        \App\Models\WhmcsService::query()->create([
            'whmcs_customer_id' => $whmcsCustomer->id,
            'user_id' => $customer->id,
            'whmcs_service_id' => 9001,
            'whmcs_client_id' => 42,
            'product_name' => 'Shared Hosting',
            'domain' => 'brightmedia.ng',
            'status' => 'Active',
        ]);
        \App\Models\WhmcsService::query()->create([
            'whmcs_customer_id' => $whmcsCustomer->id,
            'user_id' => $customer->id,
            'whmcs_service_id' => 9002,
            'whmcs_client_id' => 42,
            'product_name' => 'Old Plan',
            'domain' => 'old.brightmedia.ng',
            'status' => 'Suspended',
        ]);

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Shared Hosting', false)
            ->assertSee('Suspended', false);
    }

    public function test_seeded_customer_sees_email_vps_and_hosting_on_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'amara@brightmedia.ng',
            'password' => 'password',
        ])->assertRedirect(route('account.show'));

        $this->get(route('account.show'))
            ->assertOk()
            ->assertSee('Amara Okonkwo', false)
            ->assertSee('hello@brightmedia.ng', false)
            ->assertSee('vps.brightmedia.ng', false)
            ->assertSee('203.0.113.42', false)
            ->assertSee('Cloud Hosting Powered by cPanel', false)
            ->assertSee(__('account.client_area'), false)
            ->assertSee(__('account.sign_out_confirm_title'), false);

        $this->get(route('account.email.index'))
            ->assertOk()
            ->assertSee('hello@brightmedia.ng', false)
            ->assertSee('info@brightmedia.ng', false);

        $this->get(route('account.vps.index'))
            ->assertOk()
            ->assertSee('vps.brightmedia.ng', false)
            ->assertSee('203.0.113.42', false);

        $this->get(route('account.hosting.index'))
            ->assertOk()
            ->assertSee('Cloud Hosting Powered by cPanel', false);

        $this->get(route('account.profile'))
            ->assertOk()
            ->assertSee('Bright Media', false)
            ->assertSee('14 Admiralty Way', false);

        $this->get(route('account.settings'))
            ->assertOk()
            ->assertSee('ops@brightmedia.ng', false)
            ->assertSee('accounts@brightmedia.ng', false);
    }

    public function test_staff_cannot_use_customer_login(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_filter_native_and_legacy_customers(): void
    {
        $this->seed(DatabaseSeeder::class);
        $customer = User::query()->where('email', 'amara@brightmedia.ng')->firstOrFail();

        WhmcsCustomer::create([
            'user_id' => $customer->id,
            'whmcs_client_id' => 5001,
            'full_name' => 'Legacy Amara',
            'email' => 'amara@brightmedia.ng',
            'company' => 'Bright Media',
            'last_synced_at' => now(),
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.customers.index', ['source' => 'native']))
            ->assertOk()
            ->assertSee('Native', false)
            ->assertSee('Amara Okonkwo', false);

        $this->get(route('admin.customers.index', ['source' => 'legacy']))
            ->assertOk()
            ->assertSee('Legacy WHMCS', false)
            ->assertSee('Legacy Amara', false);
    }

    public function test_admin_can_run_whmcs_sync_from_customers_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        Http::fake([
            'https://my.lemonwares.com/includes/api.php' => Http::sequence()
                ->push([
                    'result' => 'success',
                    'totalresults' => 1,
                    'clients' => ['client' => [[
                        'id' => 7001,
                        'firstname' => 'Legacy',
                        'lastname' => 'Customer',
                        'email' => 'legacy@example.com',
                        'companyname' => 'Legacy Co',
                        'phonenumber' => '+2348000000000',
                        'status' => 'Active',
                        'countrycode' => 'NG',
                    ]]],
                ], 200)
                ->push([
                    'result' => 'success',
                    'products' => ['product' => [[
                        'id' => 9001,
                        'productname' => 'cPanel Business',
                        'domain' => 'legacy.ng',
                        'billingcycle' => 'Monthly',
                        'nextduedate' => '2026-12-01',
                        'status' => 'Active',
                    ]]],
                ], 200),
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.customers.sync-whmcs'))
            ->assertRedirect(route('admin.customers.index', ['source' => 'legacy']));

        $this->assertDatabaseHas('whmcs_customers', [
            'whmcs_client_id' => 7001,
            'email' => 'legacy@example.com',
        ]);

        $this->assertDatabaseHas('whmcs_services', [
            'whmcs_service_id' => 9001,
            'domain' => 'legacy.ng',
        ]);

        $legacyCustomer = WhmcsCustomer::query()->where('whmcs_client_id', 7001)->firstOrFail();

        $this->get(route('admin.customers.legacy.show', $legacyCustomer))
            ->assertOk()
            ->assertSee('Legacy Customer', false)
            ->assertSee('legacy.ng', false);
    }
}
