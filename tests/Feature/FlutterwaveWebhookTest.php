<?php

namespace Tests\Feature;

use App\Models\EmailOrder;
use App\Models\HostingLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlutterwaveWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['services.flutterwave.secret_hash' => 'expected-hash']);

        $this->postJson(route('webhooks.flutterwave'), [
            'event' => 'charge.completed',
            'data' => ['tx_ref' => 'LW-HOST-1', 'id' => '123'],
        ], [
            'verif-hash' => 'wrong-hash',
        ])->assertUnauthorized();
    }

    public function test_webhook_confirms_payment_and_syncs_whmcs(): void
    {
        config([
            'services.flutterwave.secret_hash' => 'expected-hash',
            'services.flutterwave.secret_key' => 'flw_test_key',
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.payment_method' => 'banktransfer',
        ]);

        $lead = HostingLead::create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+2348012345678',
            'plan_slug' => 'cpanel',
            'plan_name' => 'Cloud Hosting',
            'spec_key' => 'starter',
            'spec_label' => 'Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 5,
            'amount_ngn' => 7500,
            'checkout_provider' => 'whmcs',
            'payment_provider' => 'flutterwave',
            'payment_reference' => 'LW-HOST-99',
            'status' => 'awaiting_payment',
            'whmcs_order_id' => 501,
            'whmcs_invoice_id' => 902,
            'whmcs_sync_status' => 'checkout_synced',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => '5555',
                    'status' => 'successful',
                    'amount' => 7500,
                    'currency' => 'NGN',
                ],
            ], 200),
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success'], 200)
                ->push(['result' => 'success'], 200),
        ]);

        $this->postJson(route('webhooks.flutterwave'), [
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'LW-HOST-99',
                'id' => '5555',
            ],
        ], [
            'verif-hash' => 'expected-hash',
        ])->assertOk();

        $fresh = $lead->fresh();
        $this->assertSame('successful', $fresh->payment_status);
        $this->assertSame('payment_synced', $fresh->whmcs_sync_status);
    }

    public function test_webhook_is_idempotent_when_payment_already_confirmed(): void
    {
        config([
            'services.flutterwave.secret_hash' => 'expected-hash',
            'services.flutterwave.secret_key' => 'flw_test_key',
        ]);

        HostingLead::create([
            'full_name' => 'Paid User',
            'email' => 'paid@example.com',
            'phone' => '+2348012345678',
            'plan_slug' => 'cpanel',
            'plan_name' => 'Cloud Hosting',
            'spec_key' => 'starter',
            'spec_label' => 'Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 5,
            'amount_ngn' => 7500,
            'checkout_provider' => 'whmcs',
            'payment_provider' => 'flutterwave',
            'payment_reference' => 'LW-HOST-PAID',
            'payment_status' => 'successful',
            'status' => 'paid',
            'whmcs_sync_status' => 'payment_synced',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/*' => function () {
                $this->fail('Flutterwave verify should not run for already paid leads.');
            },
        ]);

        $this->postJson(route('webhooks.flutterwave'), [
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'LW-HOST-PAID',
                'id' => '7777',
            ],
        ], [
            'verif-hash' => 'expected-hash',
        ])->assertOk();
    }

    public function test_webhook_confirms_email_order_payment(): void
    {
        config([
            'services.flutterwave.secret_hash' => 'expected-hash',
            'services.flutterwave.secret_key' => 'flw_test_key',
        ]);

        $user = User::factory()->create();

        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'team',
            'plan_name' => 'Team',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'domain' => 'acme.ng',
            'mailbox_count' => 5,
            'billing_cycle' => 'monthly',
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'status' => 'awaiting_payment',
            'payment_provider' => 'flutterwave',
            'payment_reference' => 'LW-MAIL-100',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 'tx-email-1',
                    'status' => 'successful',
                    'amount' => 15000,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $this->postJson(route('webhooks.flutterwave'), [
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'LW-MAIL-100',
                'id' => 'tx-email-1',
            ],
        ], [
            'verif-hash' => 'expected-hash',
        ])->assertOk();

        $fresh = $order->fresh();
        $this->assertSame('successful', $fresh->payment_status);
        $this->assertSame('paid', $fresh->status);
    }

    public function test_email_webhook_is_idempotent_when_order_already_paid(): void
    {
        config([
            'services.flutterwave.secret_hash' => 'expected-hash',
            'services.flutterwave.secret_key' => 'flw_test_key',
        ]);

        $user = User::factory()->create();
        EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'team',
            'plan_name' => 'Team',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'domain' => 'acme.ng',
            'mailbox_count' => 5,
            'billing_cycle' => 'monthly',
            'amount_usd' => 10,
            'amount_ngn' => 15000,
            'status' => 'paid',
            'payment_provider' => 'flutterwave',
            'payment_status' => 'successful',
            'payment_reference' => 'LW-MAIL-PAID-1',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/*' => function () {
                $this->fail('Verify call should not run for already-paid email orders.');
            },
        ]);

        $this->postJson(route('webhooks.flutterwave'), [
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => 'LW-MAIL-PAID-1',
                'id' => 'tx-email-paid',
            ],
        ], [
            'verif-hash' => 'expected-hash',
        ])->assertOk();
    }
}
