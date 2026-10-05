<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminCustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function actingAsAdmin(array $permissions = []): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_super_admin' => $permissions === [],
            'admin_permissions' => $permissions === [] ? null : $permissions,
        ]);

        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);

        return $admin;
    }

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'email' => 'buyer@example.com',
            'password' => 'secret-password',
        ], $attributes));
    }

    public function test_admin_can_create_a_customer_and_send_a_set_password_link(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.customers.create'))->assertOk();

        $response = $this->post(route('admin.customers.store'), [
            'name' => 'Phone Buyer',
            'email' => 'Phone@Example.com',
            'company' => 'Acme',
            'send_invite' => '1',
        ]);

        $customer = User::query()->where('email', 'phone@example.com')->firstOrFail();
        $response->assertRedirect(route('admin.customers.show', $customer));
        $this->assertSame('customer', $customer->role);
        Notification::assertSentTo($customer, ResetPasswordNotification::class);
    }

    public function test_customer_page_shows_access_controls(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Account access')
            ->assertSee('Log in as customer')
            ->assertSee('Internal notes');
    }

    public function test_suspended_customer_cannot_log_in_and_open_sessions_are_ended(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post(route('admin.customers.suspend', $customer), ['reason' => 'Chargeback'])->assertSessionHasNoErrors();
        $this->assertNotNull($customer->fresh()->suspended_at);

        $this->flushSession();
        $this->post(route('login.store'), ['email' => 'buyer@example.com', 'password' => 'secret-password'])
            ->assertSessionHasErrors(['email' => __('account.account_suspended')]);
        $this->assertGuest();

        $this->actingAs($customer->fresh())->get(route('account.show'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_account_staff_are_blocked_when_the_owner_is_suspended(): void
    {
        $owner = $this->customer(['suspended_at' => now()]);
        $member = User::factory()->create(['role' => 'customer', 'account_owner_id' => $owner->id]);

        $this->assertTrue($member->isSuspended());
    }

    public function test_unsuspend_restores_login(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer(['suspended_at' => now(), 'suspended_reason' => 'x']);

        $this->post(route('admin.customers.unsuspend', $customer))->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->suspended_at);
    }

    public function test_suspend_requires_a_reason(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post(route('admin.customers.suspend', $customer), ['reason' => ''])->assertSessionHasErrorsIn('customerAction', 'reason');
        $this->assertNull($customer->fresh()->suspended_at);
    }

    public function test_admin_can_send_reset_link_and_set_password(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post(route('admin.customers.password-reset', $customer))->assertSessionHasNoErrors();
        Notification::assertSentTo($customer, ResetPasswordNotification::class);

        $this->put(route('admin.customers.password', $customer), [
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('brand-new-pass-1', $customer->fresh()->password));
    }

    public function test_admin_can_log_in_as_customer_and_return(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post(route('admin.customers.impersonate', $customer))->assertRedirect(route('account.show'));
        $this->assertAuthenticatedAs($customer);

        $this->get(route('account.show'))->assertOk()->assertSee('You are viewing this account as LemonWares support');
        $this->assertDatabaseHas('account_activities', ['user_id' => $customer->id, 'type' => 'admin_signed_in']);

        $this->post(route('admin.impersonation.stop'))->assertRedirect(route('admin.customers.show', $customer));
        $this->assertGuest();
        $this->get(route('admin.customers.index'))->assertOk();
    }

    public function test_customer_logout_while_impersonating_keeps_the_admin_signed_in(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post(route('admin.customers.impersonate', $customer));
        $this->post(route('logout'))->assertRedirect(route('admin.customers.show', $customer));
        $this->get(route('admin.customers.index'))->assertOk();
    }

    public function test_impersonation_needs_its_own_permission(): void
    {
        $this->actingAsAdmin(['customers']);
        $customer = $this->customer();

        $this->get(route('admin.customers.show', $customer))->assertOk()->assertDontSee('Log in as customer');
        $this->post(route('admin.customers.impersonate', $customer))->assertForbidden();
        $this->assertGuest();
    }

    public function test_notes_tags_filter_and_export(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $this->customer(['email' => 'other@example.com', 'name' => 'Other Person']);

        $this->put(route('admin.customers.notes', $customer), [
            'admin_notes' => 'Pays late',
            'admin_tags' => 'VIP, slow_payer, vip',
        ])->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('Pays late', $customer->admin_notes);
        $this->assertSame(['vip', 'slow_payer'], $customer->admin_tags);

        $this->get(route('admin.customers.index', ['tag' => 'slow_payer']))
            ->assertOk()
            ->assertSee('buyer@example.com')
            ->assertDontSee('other@example.com');

        $csv = $this->get(route('admin.customers.export', ['tag' => 'vip']))->assertOk()->streamedContent();
        $this->assertStringContainsString('buyer@example.com', $csv);
        $this->assertStringNotContainsString('other@example.com', $csv);
    }

    public function test_admin_can_remove_customer_staff_member(): void
    {
        $this->actingAsAdmin();
        $owner = $this->customer();
        $member = User::factory()->create(['role' => 'customer', 'account_owner_id' => $owner->id]);
        $stranger = User::factory()->create(['role' => 'customer']);

        $this->delete(route('admin.customers.staff.destroy', [$owner, $stranger]))->assertNotFound();
        $this->delete(route('admin.customers.staff.destroy', [$owner, $member]))->assertSessionHasNoErrors();
        $this->assertModelMissing($member);
    }
}
