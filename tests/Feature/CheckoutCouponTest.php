<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\SiteCheckout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutCouponTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.payment_method' => 'banktransfer',
            'services.flutterwave.secret_key' => 'flw_test_key',
            'services.flutterwave.public_key' => 'flw_pub',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'currency' => ['id' => 1, 'code' => 'NGN'],
                'pricing' => ['com' => ['register' => ['1' => 15000]]],
            ], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
            'https://api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => ['link' => 'https://checkout.flutterwave.com/v3/hosted/pay/abc'],
            ], 200),
        ]);

        $this->user = User::factory()->create(['name' => 'Ada Lovelace', 'role' => 'customer']);
        $this->actingAs($this->user)->post(route('cart.domain.add'), ['domain' => 'coupon-test.com', 'option' => 'register']);
    }

    /**
     * @return array<string, string>
     */
    private function checkoutForm(string $code): array
    {
        return [
            'name' => 'Ada Lovelace',
            'company' => 'Analytical Engines',
            'phone' => '+2348099990000',
            'billing_address_line_1' => '1 Computation Way',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '101001',
            'billing_country' => 'NG',
            'shipping_same_as_billing' => '1',
            'coupon_code' => $code,
        ];
    }

    public function test_valid_code_lowers_the_amount_charged(): void
    {
        $coupon = Coupon::create(['code' => 'TENOFF', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $this->actingAs($this->user)->post(route('checkout.store'), $this->checkoutForm('tenoff'))
            ->assertRedirect('https://checkout.flutterwave.com/v3/hosted/pay/abc');

        $checkout = SiteCheckout::query()->firstOrFail();
        $this->assertSame('TENOFF', $checkout->coupon_code);
        $full = (float) $checkout->items()->sum('amount_ngn');
        $this->assertEqualsWithDelta($full * 0.1, (float) $checkout->discount_ngn, 0.01);
        $this->assertEqualsWithDelta($full * 0.9, (float) $checkout->amount_ngn, 0.01);
        $this->assertSame(0, $coupon->fresh()->used_count, 'A use is only counted once the order is paid.');
        \App\Support\Coupons::redeem($checkout);
        $this->assertSame(1, $coupon->fresh()->used_count);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.flutterwave.com/v3/payments'
            && (int) $request['amount'] === (int) round($full * 0.9));
    }

    public function test_invalid_code_stops_checkout_with_a_message(): void
    {
        $this->actingAs($this->user)->post(route('checkout.store'), $this->checkoutForm('NOPE'))
            ->assertSessionHasErrors(['coupon_code' => __('cart.coupon_invalid')]);

        $this->assertSame(0, SiteCheckout::count());
    }
}
