<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Support\FlutterwaveSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlutterwaveSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_flutterwave_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->put(route('admin.flutterwave-settings.update'), [
            'enabled' => '1',
            'public_key' => 'FLWPUBK_TEST-public',
            'secret_key' => 'FLWSECK_TEST-secret',
            'secret_hash' => 'hash-from-dashboard',
        ])->assertRedirect(route('admin.flutterwave-settings.index'));

        $this->assertDatabaseHas('integration_settings', [
            'key' => 'flutterwave.secret_key',
            'value' => 'FLWSECK_TEST-secret',
        ]);

        $this->assertSame('FLWSECK_TEST-secret', FlutterwaveSettings::secretKey());
        $this->assertTrue(FlutterwaveSettings::isConfigured());
        $this->assertTrue(FlutterwaveSettings::isTestMode());
    }

    public function test_flutterwave_payment_uses_admin_saved_secret_key(): void
    {
        IntegrationSetting::putMany([
            'flutterwave.enabled' => '1',
            'flutterwave.secret_key' => 'FLWSECK_TEST-from-db',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/payments' => function ($request) {
                $this->assertStringContainsString(
                    'FLWSECK_TEST-from-db',
                    (string) ($request->header('Authorization')[0] ?? ''),
                );

                $payload = $request->data();
                $this->assertArrayNotHasKey('logo', $payload['customizations'] ?? []);

                return Http::response([
                    'status' => 'success',
                    'data' => ['link' => 'https://checkout.flutterwave.com/v3/hosted/pay/db-key'],
                ], 200);
            },
        ]);

        $lead = \App\Models\HostingLead::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+2348012345678',
            'plan_slug' => 'vps',
            'plan_name' => 'VPS',
            'spec_key' => 'starter',
            'spec_label' => 'Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'checkout_provider' => 'internal',
            'status' => 'pending',
        ]);

        $link = \App\Support\FlutterwavePayment::createPaymentLink($lead);

        $this->assertSame('https://checkout.flutterwave.com/v3/hosted/pay/db-key', $link);
    }

    public function test_email_payment_omits_unreachable_checkout_logo(): void
    {
        IntegrationSetting::putMany([
            'flutterwave.enabled' => '1',
            'flutterwave.secret_key' => 'FLWSECK_TEST-email',
        ]);

        config(['app.url' => 'https://gadgets.lemonwares.com']);

        Http::fake([
            'https://gadgets.lemonwares.com/lemonwareslogo.webp' => Http::response('error', 500),
            'https://api.flutterwave.com/v3/payments' => function ($request) {
                $payload = $request->data();
                $this->assertArrayNotHasKey('logo', $payload['customizations'] ?? []);
                $this->assertSame('card,banktransfer,ussd,account', $payload['payment_options'] ?? null);

                return Http::response([
                    'status' => 'success',
                    'data' => ['link' => 'https://checkout-v2.dev-flutterwave.com/v3/hosted/pay/email-token'],
                ], 200);
            },
        ]);

        $user = \App\Models\User::factory()->create([
            'phone' => '+2348011111111',
        ]);

        $order = \App\Models\EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'auto',
            'domain' => 'example.test',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 6737.15,
            'status' => 'awaiting_payment',
        ]);

        $link = \App\Support\FlutterwavePayment::createEmailPaymentLink($order);

        $this->assertSame('https://checkout-v2.dev-flutterwave.com/v3/hosted/pay/email-token', $link);
    }
}
