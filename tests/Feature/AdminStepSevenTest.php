<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\DomainOrder;
use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\NewsletterSubscriber;
use App\Models\SavedReply;
use App\Models\SiteCheckout;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\AdminReports;
use App\Support\Coupons;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Reports, discount codes, dashboard figures, subscribers, ticket tools and the system page.
 */
class AdminStepSevenTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $this->admin->id]);
    }

    private function coupon(array $attributes = []): Coupon
    {
        return Coupon::create(array_merge(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10, 'is_active' => true], $attributes));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cartItems(): array
    {
        return [
            ['type' => 'domain', 'amount_ngn' => 20000, 'amount_usd' => 13],
            ['type' => 'hosting', 'amount_ngn' => 80000, 'amount_usd' => 53],
        ];
    }

    public function test_admin_can_create_edit_and_delete_discount_codes(): void
    {
        $this->get(route('admin.coupons.create'))->assertOk();

        $this->post(route('admin.coupons.store'), [
            'code' => 'launch-25',
            'type' => 'percent',
            'value' => 25,
            'applies_to' => ['hosting'],
            'max_uses' => 50,
            'is_active' => '1',
        ])->assertRedirect(route('admin.coupons.index'));

        $coupon = Coupon::query()->where('code', 'LAUNCH-25')->firstOrFail();
        $this->assertSame(['hosting'], $coupon->applies_to);

        $this->post(route('admin.coupons.store'), ['code' => 'LAUNCH-25', 'type' => 'percent', 'value' => 10])->assertSessionHasErrors('code');
        $this->post(route('admin.coupons.store'), ['code' => 'TOO-MUCH', 'type' => 'percent', 'value' => 150])->assertSessionHasErrors('value');

        $this->get(route('admin.coupons.index'))->assertOk()->assertSee('LAUNCH-25')->assertSee('25% off');

        $this->put(route('admin.coupons.update', $coupon), ['code' => 'LAUNCH-25', 'type' => 'fixed', 'value' => 5000, 'is_active' => '0'])->assertRedirect();
        $this->assertSame('Paused', $coupon->fresh()->statusLabel());

        $this->delete(route('admin.coupons.destroy', $coupon))->assertRedirect();
        $this->assertModelMissing($coupon);
    }

    public function test_percent_code_only_discounts_eligible_items(): void
    {
        $this->coupon(['applies_to' => ['hosting']]);

        $result = Coupons::evaluate('welcome10', $this->cartItems(), null);

        $this->assertTrue($result['ok']);
        $this->assertSame(8000.0, $result['discount_ngn']);
    }

    public function test_fixed_code_never_takes_the_total_below_one_naira(): void
    {
        $this->coupon(['code' => 'BIG', 'type' => 'fixed', 'value' => 999999]);

        $result = Coupons::evaluate('BIG', $this->cartItems(), null);

        $this->assertSame(99999.0, $result['discount_ngn']);
    }

    public function test_code_limits_are_enforced(): void
    {
        $this->coupon(['code' => 'OLD', 'expires_at' => now()->subDay()]);
        $this->coupon(['code' => 'USED', 'max_uses' => 1, 'used_count' => 1]);
        $this->coupon(['code' => 'MIN', 'min_order_ngn' => 500000]);
        $this->coupon(['code' => 'EMAILONLY', 'applies_to' => ['email']]);
        $once = $this->coupon(['code' => 'ONCE', 'max_uses_per_customer' => 1]);
        $customer = User::factory()->create(['role' => 'customer']);
        SiteCheckout::create(['user_id' => $customer->id, 'amount_ngn' => 1, 'status' => 'paid', 'coupon_id' => $once->id]);

        $this->assertFalse(Coupons::evaluate('OLD', $this->cartItems(), null)['ok']);
        $this->assertFalse(Coupons::evaluate('USED', $this->cartItems(), null)['ok']);
        $this->assertFalse(Coupons::evaluate('MIN', $this->cartItems(), null)['ok']);
        $this->assertFalse(Coupons::evaluate('EMAILONLY', $this->cartItems(), null)['ok']);
        $this->assertFalse(Coupons::evaluate('ONCE', $this->cartItems(), $customer)['ok']);
        $this->assertTrue(Coupons::evaluate('ONCE', $this->cartItems(), null)['ok']);
        $this->assertFalse(Coupons::evaluate('NOPE', $this->cartItems(), null)['ok']);
    }

    public function test_reports_count_paid_sales_minus_refunds_and_discounts(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        EmailOrder::create(['user_id' => $customer->id, 'domain' => 'a.com', 'plan_key' => 'starter', 'mailbox_count' => 1, 'billing_cycle' => 'monthly', 'plan_name' => 'Starter', 'amount_ngn' => 10000, 'status' => 'provisioned', 'payment_status' => 'successful']);
        EmailOrder::create(['user_id' => $customer->id, 'domain' => 'b.com', 'plan_key' => 'starter', 'mailbox_count' => 1, 'billing_cycle' => 'monthly', 'plan_name' => 'Starter', 'amount_ngn' => 99999, 'status' => 'awaiting_payment']);
        HostingLead::create(['full_name' => 'X', 'email' => 'x@example.com', 'phone' => '1', 'plan_slug' => 'vps', 'plan_name' => 'VPS', 'amount_ngn' => 50000, 'status' => 'paid', 'payment_status' => 'successful']);
        DomainOrder::create(['user_id' => $customer->id, 'domain' => 'c.ng', 'option' => 'register', 'amount_ngn' => 5000, 'status' => 'partially_refunded', 'payment_status' => 'successful', 'refunded_amount_ngn' => 2000]);
        SiteCheckout::create(['user_id' => $customer->id, 'amount_ngn' => 1, 'status' => 'paid', 'payment_status' => 'successful', 'discount_ngn' => 3000]);

        $summary = AdminReports::summary(CarbonImmutable::now()->subMonth(), CarbonImmutable::now());

        $this->assertSame(3, $summary['orders']);
        $this->assertSame(65000.0, $summary['gross']);
        $this->assertSame(2000.0, $summary['refunds']);
        $this->assertSame(3000.0, $summary['discounts']);
        $this->assertSame(60000.0, $summary['net']);
        $attention = AdminReports::attention();
        $this->assertSame(1, $attention['unpaidCount']);
        $this->assertSame(99999.0, $attention['unpaidValue']);

        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Net sales')->assertSee('VPS');
        $csv = $this->get(route('admin.reports.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('c.ng · register', $csv);
        $this->assertStringNotContainsString('b.com', $csv);
    }

    public function test_dashboard_shows_sales_figures(): void
    {
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Sales this month')->assertSee('Unpaid orders');
    }

    public function test_dashboard_hides_sales_without_permission(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'admin_permissions' => ['dashboard']]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $staff->id]);

        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Sales this month');
        $this->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_subscribers_can_be_added_imported_exported_and_removed(): void
    {
        $this->post(route('admin.subscribers.store'), ['email' => 'New@Example.com', 'full_name' => 'New'])->assertSessionHasNoErrors();
        $this->post(route('admin.subscribers.store'), ['email' => 'new@example.com'])->assertSessionHasErrors('email');

        $csv = UploadedFile::fake()->createWithContent('list.csv', "Name,Email\nAda,ada@example.com\nDup,new@example.com\nBad,not-an-email\n");
        $this->post(route('admin.subscribers.import'), ['file' => $csv])->assertSessionHas('status', '1 added, 2 skipped (invalid or already on the list).');

        $this->get(route('admin.subscribers.index', ['q' => 'ada']))->assertOk()->assertSee('ada@example.com')->assertDontSee('new@example.com');
        $this->assertStringContainsString('ada@example.com', $this->get(route('admin.subscribers.export'))->streamedContent());

        $sub = NewsletterSubscriber::query()->where('email', 'ada@example.com')->firstOrFail();
        $this->delete(route('admin.subscribers.destroy', $sub))->assertRedirect();
        $this->assertModelMissing($sub);
    }

    public function test_tickets_can_be_assigned_prioritised_filtered_and_use_saved_replies(): void
    {
        $ticket = SupportTicket::create([
            'reference' => 'LW-T-1', 'full_name' => 'Cus', 'email' => 'cus@example.com', 'category' => 'billing',
            'priority' => 'normal', 'subject' => 'Invoice', 'message' => 'Help', 'status' => 'open',
        ]);

        $this->put(route('admin.support-tickets.update', $ticket), [
            'status' => 'in_progress', 'priority' => 'high', 'assigned_admin_id' => $this->admin->id,
        ])->assertSessionHasNoErrors();

        $ticket->refresh();
        $this->assertSame('high', $ticket->priority);
        $this->assertSame($this->admin->id, $ticket->assigned_admin_id);

        $this->get(route('admin.support-tickets.index', ['assigned' => 'me', 'priority' => 'high']))->assertOk()->assertSee('LW-T-1');
        $this->get(route('admin.support-tickets.index', ['assigned' => 'none']))->assertOk()->assertDontSee('LW-T-1');

        $this->post(route('admin.saved-replies.store'), ['title' => 'DNS wait', 'body' => 'DNS can take up to 24 hours.'])->assertRedirect();
        $this->assertSame(1, SavedReply::count());
        $this->get(route('admin.support-tickets.show', $ticket))->assertOk()->assertSee('DNS wait');
    }

    public function test_system_page_runs_tasks_and_guards_backups(): void
    {
        $this->get(route('admin.system.index'))->assertOk()->assertSee('Connected services')->assertSee('email:expire-orders');

        $this->post(route('admin.system.run-task'), ['task' => 'email:expire-orders'])
            ->assertSessionHas('task_result.ok', true);
        $this->post(route('admin.system.run-task'), ['task' => 'migrate:fresh'])->assertSessionHasErrors('task');

        // Tests use an in-memory database, so there is no file to back up.
        $this->post(route('admin.system.backup'))->assertSessionHasErrors('backup');

        $staff = User::factory()->create(['role' => 'admin', 'admin_permissions' => ['system']]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $staff->id]);
        $this->post(route('admin.system.backup'))->assertForbidden();
    }
}
