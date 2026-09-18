<?php

namespace Tests\Feature;

use App\Models\AccountInvite;
use App\Models\DomainOrder;
use App\Models\EmailOrder;
use App\Models\User;
use App\Support\AccountActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountClientAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_shell_includes_mobile_drawer_hooks(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('data-account-nav-open', false)
            ->assertSee('data-account-drawer', false)
            ->assertSee('account-shell', false);
    }

    public function test_staff_without_permission_is_forbidden(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create([
            'role' => 'customer',
            'account_owner_id' => $owner->id,
            'account_permissions' => ['overview'],
        ]);

        $this->actingAs($staff)
            ->get(route('account.invoices.index'))
            ->assertForbidden();
    }

    public function test_staff_invite_can_be_accepted(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $invite = AccountInvite::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Teammate',
            'email' => 'teammate@example.com',
            'permissions' => ['overview', 'products'],
            'token' => AccountInvite::generateToken(),
            'expires_at' => now()->addDay(),
        ]);

        $this->post(route('account.invites.accept', $invite->token), [
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('account.show'));

        $this->assertAuthenticated();
        $staff = User::query()->where('email', 'teammate@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertSame($owner->id, $staff->account_owner_id);
        $this->assertTrue($staff->hasAccountPermission('products'));
        $this->assertFalse($staff->hasAccountPermission('invoices'));
    }

    public function test_products_list_includes_domain_orders(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        DomainOrder::query()->create([
            'user_id' => $user->id,
            'domain' => 'example-test.com',
            'option' => 'register',
            'reg_period' => 1,
            'amount_usd' => 12.00,
            'status' => 'paid',
            'payment_status' => 'successful',
        ]);

        $this->actingAs($user)
            ->get(route('account.products.index'))
            ->assertOk()
            ->assertSee('example-test.com');
    }

    public function test_invoices_merge_local_email_payments(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        EmailOrder::query()->create([
            'user_id' => $user->id,
            'domain' => 'mail.example.com',
            'plan_key' => 'starter',
            'plan_name' => 'Starter',
            'billing_cycle' => 'yearly',
            'amount_usd' => 48,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'payment_reference' => 'FLW-TEST-1',
            'mailbox_count' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('account.invoices.index'))
            ->assertOk()
            ->assertSee('FLW-TEST-1')
            ->assertSee('mail.example.com');
    }

    public function test_activity_logged_for_credentials_send_helper(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = EmailOrder::query()->create([
            'user_id' => $user->id,
            'domain' => 'creds.example.com',
            'plan_key' => 'starter',
            'plan_name' => 'Starter',
            'billing_cycle' => 'yearly',
            'amount_usd' => 48,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'mailbox_count' => 1,
        ]);

        AccountActivityLogger::log(
            $user,
            'credentials_sent',
            'Mailbox credentials sent',
            'Login details for '.$order->domain.' were emailed.',
            'admin',
            $order,
        );

        $this->actingAs($user)
            ->get(route('account.activity.index'))
            ->assertOk()
            ->assertSee('Mailbox credentials sent');
    }

    public function test_owner_can_change_password(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user)
            ->put(route('account.profile.password'), [
                'current_password' => 'old-password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect(route('account.profile'));

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_staff_can_see_owner_products_with_permission(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        DomainOrder::query()->create([
            'user_id' => $owner->id,
            'domain' => 'staff-visible.com',
            'option' => 'register',
            'reg_period' => 1,
            'amount_usd' => 10,
            'status' => 'paid',
            'payment_status' => 'successful',
        ]);
        $staff = User::factory()->create([
            'role' => 'customer',
            'account_owner_id' => $owner->id,
            'account_permissions' => ['products'],
        ]);

        $this->actingAs($staff)
            ->get(route('account.products.index'))
            ->assertOk()
            ->assertSee('staff-visible.com');
    }
}
