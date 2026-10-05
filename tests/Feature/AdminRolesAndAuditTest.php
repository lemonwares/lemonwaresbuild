<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolesAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(User $admin): void
    {
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);
    }

    private function staff(array $permissions, ?AdminRole $role = null): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_super_admin' => false,
            'admin_permissions' => $permissions,
            'admin_role_id' => $role?->id,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_view_only_staff_can_read_but_not_change(): void
    {
        $this->signIn($this->staff(['customers:view']));
        $customer = $this->customer();

        $this->get(route('admin.customers.show', $customer))->assertOk();
        $this->put(route('admin.customers.notes', $customer), ['admin_notes' => 'x'])->assertForbidden();
        $this->delete(route('admin.customers.destroy', $customer))->assertForbidden();
    }

    public function test_edit_level_can_change_but_not_delete(): void
    {
        $this->signIn($this->staff(['customers:edit']));
        $customer = $this->customer();

        $this->put(route('admin.customers.notes', $customer), ['admin_notes' => 'ok'])->assertRedirect();
        $this->assertSame('ok', $customer->fresh()->admin_notes);
        $this->delete(route('admin.customers.destroy', $customer))->assertForbidden();
        $this->assertModelExists($customer);
    }

    public function test_plain_permission_still_means_full_access(): void
    {
        $admin = $this->staff(['customers']);

        $this->assertTrue($admin->hasAdminPermission('customers', 'full'));
        $this->assertFalse($admin->hasAdminPermission('support'));
    }

    public function test_role_access_is_combined_with_personal_access(): void
    {
        $role = AdminRole::create(['name' => 'Support', 'permissions' => ['support:edit', 'customers:view']]);
        $admin = $this->staff(['customers:edit'], $role);

        $this->assertTrue($admin->hasAdminPermission('support', 'edit'));
        $this->assertFalse($admin->hasAdminPermission('support', 'full'));
        $this->assertTrue($admin->hasAdminPermission('customers', 'edit'));
        $this->assertFalse($admin->hasAdminPermission('orders'));

        $this->signIn($admin);
        $this->get(route('admin.support-tickets.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_super_admin_manages_roles_and_assigns_them_with_levels(): void
    {
        $this->signIn(User::factory()->create(['role' => 'admin', 'is_super_admin' => true]));

        $this->get(route('admin.roles.create'))->assertOk();
        $this->post(route('admin.roles.store'), [
            'name' => 'Billing',
            'permission_levels' => ['orders' => 'edit', 'customers' => 'view', 'blog' => 'none'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = AdminRole::query()->where('name', 'Billing')->firstOrFail();
        $this->assertSame(['customers:view', 'orders:edit'], collect($role->permissions)->sort()->values()->all());
        $this->get(route('admin.roles.index'))->assertOk()->assertSee('Billing');

        $this->post(route('admin.staff.store'), [
            'name' => 'Bea Biller',
            'email' => 'bea@example.com',
            'password' => 'Password-123!',
            'password_confirmation' => 'Password-123!',
            'admin_role_id' => $role->id,
            'permission_levels' => ['support' => 'view'],
        ])->assertRedirect(route('admin.staff.index'));

        $bea = User::query()->where('email', 'bea@example.com')->firstOrFail();
        $this->assertSame($role->id, $bea->admin_role_id);
        $this->assertSame(['support:view'], $bea->admin_permissions);
        $this->assertTrue($bea->hasAdminPermission('orders', 'edit'));

        $this->get(route('admin.staff.edit', $bea))->assertOk()->assertSee('Billing');
    }

    public function test_staff_needs_a_role_or_a_permission(): void
    {
        $this->signIn(User::factory()->create(['role' => 'admin', 'is_super_admin' => true]));

        $this->post(route('admin.staff.store'), [
            'name' => 'Nobody',
            'email' => 'nobody@example.com',
            'password' => 'Password-123!',
            'password_confirmation' => 'Password-123!',
            'permission_levels' => ['support' => 'none'],
        ])->assertSessionHasErrors('permissions');
    }

    public function test_changes_are_audited_with_secrets_hidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->signIn($admin);
        $customer = $this->customer();

        $this->put(route('admin.customers.password', $customer), [
            'password' => 'brand-new-pass-1',
            'password_confirmation' => 'brand-new-pass-1',
        ]);
        $this->get(route('admin.customers.show', $customer));

        $log = AdminAuditLog::query()->where('action', 'customers.password')->firstOrFail();
        $this->assertSame($admin->id, $log->admin_user_id);
        $this->assertSame('User', $log->subject_type);
        $this->assertSame((string) $customer->id, $log->subject_id);
        $this->assertSame('[hidden]', $log->payload['password']);
        $this->assertSame(1, AdminAuditLog::count(), 'GET requests are not logged');

        $this->get(route('admin.audit-log.index'))->assertOk()->assertSee('customers password');
    }

    public function test_admin_sign_ins_and_failures_are_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true, 'email' => 'boss@example.com', 'password' => 'right-password']);

        $this->post(route('admin.login.submit'), ['email' => 'boss@example.com', 'password' => 'wrong']);
        $this->post(route('admin.login.submit'), ['email' => 'boss@example.com', 'password' => 'right-password'])->assertRedirect();

        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'login_failed', 'admin_email' => 'boss@example.com']);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'login', 'admin_user_id' => $admin->id]);
        $this->assertDatabaseMissing('admin_audit_logs', ['action' => 'login', 'payload' => null, 'admin_user_id' => null]);
    }

    public function test_audit_log_needs_its_permission(): void
    {
        $this->signIn($this->staff(['customers']));

        $this->get(route('admin.audit-log.index'))->assertForbidden();
    }
}
