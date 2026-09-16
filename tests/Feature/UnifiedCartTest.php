<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnifiedCartTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_domain_cart_redirects_to_unified_cart(): void
    {
        $this->get(route('domain.cart'))->assertRedirect(route('cart'));
    }

    public function test_adding_domain_to_unified_cart_shows_on_cart_page(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'currency' => ['id' => 1, 'code' => 'NGN'],
                'pricing' => [
                    'com' => [
                        'register' => ['1' => 15000],
                        'transfer' => ['1' => 15000],
                    ],
                ],
            ], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->post(route('cart.domain.add'), [
            'domain' => 'brightmedia.com',
            'option' => 'register',
            'reg_period' => 1,
        ]);

        $response->assertRedirect(route('cart'));
        $this->assertSame(1, Cart::count());
        $this->assertSame('domain', Cart::items()[0]['type'] ?? null);
    }

    public function test_checkout_requires_items(): void
    {
        $this->get(route('checkout'))->assertRedirect(route('cart'));
    }

    public function test_checkout_account_status_marks_existing_customers(): void
    {
        User::factory()->create([
            'email' => 'returning@example.com',
            'role' => 'customer',
        ]);

        $this->postJson(route('checkout.account-status'), [
            'email' => 'returning@example.com',
        ])->assertOk()->assertJson(['status' => 'existing']);

        $this->postJson(route('checkout.account-status'), [
            'email' => 'brand-new@example.com',
        ])->assertOk()->assertJson(['status' => 'new']);
    }

    public function test_guest_checkout_rejects_existing_email_without_login(): void
    {
        User::factory()->create([
            'email' => 'owner@example.com',
            'role' => 'customer',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'currency' => ['id' => 1, 'code' => 'NGN'],
                'pricing' => [
                    'com' => [
                        'register' => ['1' => 15000],
                    ],
                ],
            ], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $this->post(route('cart.domain.add'), [
            'domain' => 'checkout-test.com',
            'option' => 'register',
        ]);

        $this->post(route('checkout.store'), [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company' => 'Acme',
            'phone' => '+2348012345678',
            'billing_address_line_1' => '12 Broad Street',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
            'billing_country' => 'NG',
            'shipping_same_as_billing' => '1',
        ])->assertSessionHasErrors('email');
    }

    public function test_authenticated_checkout_saves_full_billing_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'role' => 'customer',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'currency' => ['id' => 1, 'code' => 'NGN'],
                'pricing' => [
                    'com' => [
                        'register' => ['1' => 15000],
                    ],
                ],
            ], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
            'https://api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => [
                    'link' => 'https://checkout.flutterwave.com/v3/hosted/pay/abc',
                ],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('cart.domain.add'), [
                'domain' => 'billing-full.com',
                'option' => 'register',
            ]);

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'name' => 'Ada Lovelace',
            'company' => 'Analytical Engines',
            'phone' => '+2348099990000',
            'billing_address_line_1' => '1 Computation Way',
            'billing_address_line_2' => 'Suite 2',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '101001',
            'billing_country' => 'NG',
            'shipping_same_as_billing' => '1',
        ]);

        $response->assertRedirect('https://checkout.flutterwave.com/v3/hosted/pay/abc');

        $user->refresh();
        $this->assertSame('Analytical Engines', $user->company);
        $this->assertSame('1 Computation Way', $user->billing_address_line_1);
        $this->assertSame('Lagos', $user->billing_state);
        $this->assertSame('101001', $user->billing_postcode);
        $this->assertTrue($user->hasCheckoutBillingProfile());
        $this->assertDatabaseHas('site_checkouts', [
            'user_id' => $user->id,
            'shipping_same_as_billing' => 1,
        ]);
    }
}
