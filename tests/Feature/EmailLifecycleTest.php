<?php

namespace Tests\Feature;

use App\Models\EmailMailbox;
use App\Models\EmailOrder;
use App\Models\User;
use App\Support\EmailLifecycle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_expire_command_deactivates_due_orders(): void
    {
        $user = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'domain' => 'expire.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 4000,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'period_starts_at' => now()->subMonths(2),
            'period_ends_at' => now()->subDay(),
        ]);
        EmailMailbox::create([
            'email_order_id' => $order->id,
            'local_part' => 'hello',
            'address' => 'hello@expire.ng',
            'status' => 'created',
            'trekmail_mailbox_id' => 55,
        ]);

        Http::fake([
            '*/mailboxes/55:pause' => Http::response(['data' => ['ok' => true]], 200),
        ]);

        config(['services.trekmail.token' => 'tm_live_test']);

        $this->artisan('email:expire-orders')->assertSuccessful();

        $order->refresh();
        $this->assertSame('expired', $order->status);
        $this->assertNotNull($order->deactivated_at);
        $this->assertSame('expired', $order->deactivated_reason);
        $this->assertSame('deactivated', $order->mailboxes()->first()->status);
    }

    public function test_admin_can_deactivate_and_reactivate_email_order(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'team',
            'plan_name' => 'Team',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'domain' => 'lifecycle.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'annually',
            'amount_usd' => 50,
            'amount_ngn' => 50000,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'period_starts_at' => now(),
            'period_ends_at' => now()->addYear(),
        ]);
        EmailMailbox::create([
            'email_order_id' => $order->id,
            'local_part' => 'admin',
            'address' => 'admin@lifecycle.ng',
            'status' => 'created',
            'trekmail_mailbox_id' => 88,
        ]);

        Http::fake([
            '*/mailboxes/88:pause' => Http::response(['data' => ['ok' => true]], 200),
            '*/mailboxes/88:resume' => Http::response(['data' => ['ok' => true]], 200),
        ]);
        config(['services.trekmail.token' => 'tm_live_test']);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.email-orders.deactivate', $order))
            ->assertRedirect(route('admin.email-orders.show', $order));

        $order->refresh();
        $this->assertSame('deactivated', $order->status);
        $this->assertSame('admin', $order->deactivated_reason);

        $this->post(route('admin.email-orders.reactivate', $order))
            ->assertRedirect(route('admin.email-orders.show', $order));

        $order->refresh();
        $this->assertSame('provisioned', $order->status);
        $this->assertNull($order->deactivated_at);
        $this->assertSame('created', $order->mailboxes()->first()->status);
    }

    public function test_paid_period_is_applied_from_billing_cycle(): void
    {
        $order = EmailOrder::create([
            'user_id' => User::factory()->create()->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'domain' => 'period.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'quarterly',
            'amount_usd' => 10,
            'amount_ngn' => 10000,
            'status' => 'paid',
            'payment_status' => 'successful',
        ]);

        $order->applyPaidPeriod(now()->startOfDay());

        $this->assertNotNull($order->period_starts_at);
        $this->assertTrue($order->period_ends_at->equalTo($order->period_starts_at->copy()->addMonthsNoOverflow(3)));
    }

    public function test_renewal_payment_extends_period_and_reactivates(): void
    {
        $user = User::factory()->create();
        $ends = now()->subDays(5);
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'domain' => 'renew.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'expired',
            'payment_status' => 'successful',
            'payment_reference' => 'LW-MAIL-R-99-ABC',
            'period_starts_at' => $ends->copy()->subMonth(),
            'period_ends_at' => $ends,
            'deactivated_at' => $ends,
            'deactivated_reason' => 'expired',
            'trekmail_domain_id' => 12,
        ]);
        EmailMailbox::create([
            'email_order_id' => $order->id,
            'local_part' => 'hello',
            'address' => 'hello@renew.ng',
            'status' => 'deactivated',
            'trekmail_mailbox_id' => 77,
        ]);

        Http::fake([
            '*/mailboxes/77:resume' => Http::response(['data' => ['ok' => true]], 200),
        ]);
        config(['services.trekmail.token' => 'tm_live_test']);

        $result = \App\Support\FlutterwavePayment::confirmEmailOrderPayment($order, [
            'id' => 'tx-renew-1',
            'status' => 'successful',
            'amount' => 7500,
            'currency' => 'NGN',
            'meta' => ['payment_kind' => 'renewal'],
        ]);

        $this->assertTrue($result['ok']);
        $order->refresh();
        $this->assertSame('provisioned', $order->status);
        $this->assertNull($order->deactivated_at);
        $this->assertTrue($order->period_ends_at->greaterThan(now()->addDays(20)));
        $this->assertSame('created', $order->mailboxes()->first()->status);
    }

    public function test_active_renewal_stacks_from_current_period_end(): void
    {
        $ends = now()->addDays(10)->startOfDay();
        $order = EmailOrder::create([
            'user_id' => User::factory()->create()->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'domain' => 'stack.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'payment_reference' => 'LW-MAIL-R-1-ZZZ',
            'period_starts_at' => $ends->copy()->subMonth(),
            'period_ends_at' => $ends,
        ]);

        $result = \App\Support\FlutterwavePayment::confirmEmailOrderPayment($order, [
            'id' => 'tx-renew-2',
            'status' => 'successful',
            'amount' => 7500,
            'currency' => 'NGN',
            'meta' => ['payment_kind' => 'renewal'],
        ]);

        $this->assertTrue($result['ok']);
        $order->refresh();
        $this->assertTrue($order->period_ends_at->equalTo($ends->copy()->addMonthNoOverflow()));
    }

    public function test_admin_can_extend_period_without_payment(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = EmailOrder::create([
            'user_id' => User::factory()->create()->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'domain' => 'admin-extend.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7500,
            'status' => 'provisioned',
            'payment_status' => 'successful',
            'period_starts_at' => now()->subDays(5),
            'period_ends_at' => now()->addDays(10),
        ]);

        $expected = $order->period_ends_at->copy()->addMonthNoOverflow();

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.email-orders.extend', $order))
            ->assertRedirect(route('admin.email-orders.show', $order));

        $order->refresh();
        $this->assertTrue($order->period_ends_at->equalTo($expected));
    }
}
