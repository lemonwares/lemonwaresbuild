<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
