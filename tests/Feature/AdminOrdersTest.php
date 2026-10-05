<?php

namespace Tests\Feature;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\SiteCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->customer = User::factory()->create(['role' => 'customer']);
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

    private function domainBundle(string $status = 'awaiting_payment'): DomainCheckout
    {
        $checkout = DomainCheckout::create([
            'user_id' => $this->customer->id,
            'item_count' => 2,
            'amount_usd' => 30,
            'amount_ngn' => 45000,
            'status' => $status,
            'payment_reference' => 'LW-DOM-'.uniqid(),
        ]);

        foreach (['alpha.com' => 20000, 'beta.ng' => 25000] as $domain => $amount) {
            DomainOrder::create([
                'user_id' => $this->customer->id,
                'domain_checkout_id' => $checkout->id,
                'domain' => $domain,
                'option' => 'register',
                'reg_period' => 1,
                'amount_usd' => 15,
                'amount_ngn' => $amount,
                'status' => $status,
            ]);
        }

        return $checkout->fresh('orders');
    }

    private function cartOrder(string $status = 'awaiting_payment'): SiteCheckout
    {
        return SiteCheckout::create([
            'user_id' => $this->customer->id,
            'item_count' => 0,
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'status' => $status,
            'payment_reference' => 'LW-CART-'.uniqid(),
        ]);
    }

    public function test_admin_can_open_every_order_page(): void
    {
        $this->actingAsAdmin();
        $bundle = $this->domainBundle();
        $cart = $this->cartOrder();

        $this->get(route('admin.orders.index'))->assertOk()->assertSee('alpha.com')->assertSee('#'.$cart->id);
        $this->get(route('admin.orders.index', ['type' => 'cart']))->assertOk()->assertDontSee('alpha.com');
        $this->get(route('admin.domain-orders.index'))->assertOk()->assertSee('beta.ng');
        $this->get(route('admin.domain-orders.index', ['q' => 'beta']))->assertOk()->assertDontSee('alpha.com');
        $this->get(route('admin.domain-orders.show', $bundle->orders->first()))->assertOk()->assertSee('Mark paid by hand');
        $this->get(route('admin.cart-orders.index'))->assertOk();
        $this->get(route('admin.cart-orders.show', $cart))->assertOk()->assertSee('Mark paid by hand');
    }

    public function test_staff_without_orders_permission_is_blocked(): void
    {
        $this->actingAsAdmin(['support']);

        $this->get(route('admin.orders.index'))->assertForbidden();
        $this->get(route('admin.domain-orders.index'))->assertForbidden();
        $this->get(route('admin.cart-orders.index'))->assertForbidden();
    }

    public function test_mark_paid_requires_confirmation(): void
    {
        $this->actingAsAdmin();
        $bundle = $this->domainBundle();
        $order = $bundle->orders->first();

        $this->post(route('admin.domain-orders.mark-paid', $order), ['reference' => 'BANK-1'])
            ->assertSessionHasErrors('confirm');

        $this->assertSame('awaiting_payment', $bundle->fresh()->status);
    }

    public function test_mark_paid_pays_the_whole_domain_bundle_and_logs_it(): void
    {
        $admin = $this->actingAsAdmin();
        $bundle = $this->domainBundle();
        $order = $bundle->orders->first();

        $this->post(route('admin.domain-orders.mark-paid', $order), ['reference' => 'BANK-1', 'confirm' => '1'])
            ->assertRedirect(route('admin.domain-orders.show', $order));

        $bundle->refresh();
        // WHMCS is not configured in tests, so the existing sync marks status "sync_failed"; payment still lands.
        $this->assertTrue($bundle->isPaid());
        $this->assertSame('successful', $bundle->payment_status);
        $this->assertSame('manual', $bundle->payment_provider);
        $this->assertSame(2, DomainOrder::query()->where('domain_checkout_id', $bundle->id)->where('payment_status', 'successful')->where('payment_provider', 'manual')->count());
        $this->assertDatabaseHas('admin_order_events', [
            'orderable_type' => $bundle->getMorphClass(),
            'orderable_id' => $bundle->id,
            'admin_user_id' => $admin->id,
            'action' => 'marked_paid',
        ]);
    }

    public function test_mark_paid_on_cart_runs_fulfilment(): void
    {
        $this->actingAsAdmin();
        $cart = $this->cartOrder();

        $this->post(route('admin.cart-orders.mark-paid', $cart), ['confirm' => '1'])
            ->assertSessionHasNoErrors();

        $cart->refresh();
        $this->assertSame('fulfilled', $cart->status);
        $this->assertSame('fulfilled', $cart->fulfilment_status);
        $this->assertSame('manual', $cart->payment_provider);
    }

    public function test_cancelled_order_cannot_be_paid_by_customer_and_can_be_reopened(): void
    {
        $this->actingAsAdmin();
        $cart = $this->cartOrder();

        $this->post(route('admin.cart-orders.cancel', $cart), ['reason' => 'Duplicate'])->assertSessionHasNoErrors();
        $cart->refresh();
        $this->assertSame('cancelled', $cart->status);
        $this->assertSame('Duplicate', $cart->cancelled_reason);

        $this->post(\App\Support\OrderLinks::url('checkout.pay', $cart))
            ->assertRedirectContains('/checkout/received/'.$cart->id.'?')
            ->assertSessionHas('cart_feedback.message', __('domain.order_cancelled'));

        $this->post(route('admin.cart-orders.reopen', $cart))->assertSessionHasNoErrors();
        $this->assertSame('awaiting_payment', $cart->fresh()->status);
    }

    public function test_cancelling_a_domain_order_cancels_its_bundle(): void
    {
        $this->actingAsAdmin();
        $bundle = $this->domainBundle();

        $this->post(route('admin.domain-orders.cancel', $bundle->orders->first()))->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $bundle->fresh()->status);
        $this->assertSame(2, DomainOrder::query()->where('domain_checkout_id', $bundle->id)->where('status', 'cancelled')->count());
    }

    public function test_paid_order_cannot_be_cancelled(): void
    {
        $this->actingAsAdmin();
        $cart = $this->cartOrder('paid');

        $this->post(route('admin.cart-orders.cancel', $cart))->assertSessionHasErrors('order');
        $this->assertSame('paid', $cart->fresh()->status);
    }

    public function test_refunds_are_recorded_partially_then_fully_and_capped(): void
    {
        $this->actingAsAdmin();
        $cart = $this->cartOrder('paid');

        $this->post(route('admin.cart-orders.refund', $cart), ['amount_ngn' => 5000, 'reference' => 'RF-1'])
            ->assertSessionHasNoErrors();
        $cart->refresh();
        $this->assertSame('partially_refunded', $cart->status);
        $this->assertEquals(5000, (float) $cart->refunded_amount_ngn);

        $this->post(route('admin.cart-orders.refund', $cart), ['amount_ngn' => 20000])
            ->assertSessionHasErrors('order');

        $this->post(route('admin.cart-orders.refund', $cart), ['amount_ngn' => 10000])
            ->assertSessionHasNoErrors();
        $cart->refresh();
        $this->assertSame('refunded', $cart->status);
        $this->assertEquals(15000, (float) $cart->refunded_amount_ngn);
    }

    public function test_unpaid_order_cannot_be_refunded(): void
    {
        $this->actingAsAdmin();
        $cart = $this->cartOrder();

        $this->post(route('admin.cart-orders.refund', $cart), ['amount_ngn' => 100])->assertSessionHasErrors('order');
    }

    public function test_verify_payment_rejects_a_transaction_from_another_order(): void
    {
        config(['services.flutterwave.secret_key' => 'flw_test_key']);
        $this->actingAsAdmin();
        $cart = $this->cartOrder();

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => ['id' => 777, 'tx_ref' => 'SOMEONE-ELSE', 'status' => 'successful', 'amount' => 15000, 'currency' => 'NGN'],
            ]),
        ]);

        $this->post(route('admin.cart-orders.verify-payment', $cart), ['transaction_id' => '777'])
            ->assertSessionHasErrors('order');

        $this->assertSame('awaiting_payment', $cart->fresh()->status);
    }

    public function test_verify_payment_confirms_a_matching_transaction(): void
    {
        config(['services.flutterwave.secret_key' => 'flw_test_key']);
        $this->actingAsAdmin();
        $cart = $this->cartOrder();

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => ['id' => 778, 'tx_ref' => $cart->payment_reference, 'status' => 'successful', 'amount' => 15000, 'currency' => 'NGN'],
            ]),
        ]);

        $this->post(route('admin.cart-orders.verify-payment', $cart), ['transaction_id' => '778'])
            ->assertSessionHasNoErrors();

        $cart->refresh();
        $this->assertTrue($cart->isPaid());
        $this->assertSame('778', (string) $cart->flutterwave_transaction_id);
    }

    public function test_notes_and_epp_code_can_be_updated(): void
    {
        $this->actingAsAdmin();
        $order = DomainOrder::create([
            'user_id' => $this->customer->id,
            'domain' => 'moving.com',
            'option' => 'transfer',
            'epp_code' => 'OLD',
            'amount_ngn' => 9000,
            'status' => 'awaiting_payment',
        ]);

        $this->put(route('admin.domain-orders.notes', $order), ['admin_notes' => 'Called customer'])->assertSessionHasNoErrors();
        $this->put(route('admin.domain-orders.epp', $order), ['epp_code' => 'NEW-CODE'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('Called customer', $order->admin_notes);
        $this->assertSame('NEW-CODE', $order->epp_code);
        $this->get(route('admin.domain-orders.show', $order))->assertOk()->assertSee('Transfer (EPP) code updated.');
    }
}
