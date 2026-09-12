<?php

namespace Tests\Feature;

use App\Models\HostingLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_hosting_checkout_syncs_to_whmcs_when_configured(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.order_route' => '/cart.php',
            'site.whmcs.payment_method' => 'banktransfer',
            'site.whmcs.defer_payment_redirect' => false,
            'site.hosting_plans.cpanel.whmcs_pid' => '15',
            'services.flutterwave.secret_key' => 'flw_test_key',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success', 'status' => 'available', 'whois' => ''], 200)
                ->push(['result' => 'error', 'message' => 'Client not found'], 200)
                ->push(['result' => 'success', 'clientid' => '99'], 200)
                ->push(['result' => 'success', 'orderid' => '321', 'invoiceid' => '654'], 200),
            'https://api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => ['link' => 'https://checkout.flutterwave.com/v3/hosted/pay/test-link'],
            ], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->post(route('hosting.intake.submit'), [
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+2348012345678',
            'company' => 'Analytical Engines Ltd',
            'domain' => 'brightmedia.ng',
            'domain_option' => 'register',
            'billing_address_line_1' => '12 Marina',
            'billing_address_line_2' => '',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
            'billing_country' => 'NG',
            'plan' => 'cpanel',
            'spec' => 'starter',
            'billing_cycle' => 'monthly',
            'notes' => 'test order',
        ]);

        $response->assertRedirect('https://checkout.flutterwave.com/v3/hosted/pay/test-link');

        $lead = HostingLead::query()->latest()->firstOrFail();
        $this->assertSame('brightmedia.ng', $lead->hostname);
        $this->assertSame('checkout_synced', $lead->whmcs_sync_status);
        $this->assertSame(99, (int) $lead->whmcs_client_id);
        $this->assertSame(321, (int) $lead->whmcs_order_id);
        $this->assertSame(654, (int) $lead->whmcs_invoice_id);
        $this->assertSame('flutterwave', $lead->payment_provider);
        $this->assertSame('awaiting_payment', $lead->status);
    }

    public function test_hosting_checkout_falls_back_with_failed_sync_state_when_whmcs_errors(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.order_route' => '/cart.php',
            'site.whmcs.payment_method' => 'banktransfer',
            'site.whmcs.defer_payment_redirect' => false,
            'site.hosting_plans.cpanel.whmcs_pid' => '15',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success', 'status' => 'available', 'whois' => ''], 200)
                ->push(['result' => 'error', 'message' => 'WHMCS unavailable'], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->post(route('hosting.intake.submit'), [
            'full_name' => 'Grace Hopper',
            'email' => 'grace@example.com',
            'phone' => '+2348012345600',
            'company' => 'Compilers Inc',
            'domain' => 'compilerworks.com',
            'domain_option' => 'owndomain',
            'billing_address_line_1' => '42 Dockyard',
            'billing_address_line_2' => '',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
            'billing_country' => 'NG',
            'plan' => 'cpanel',
            'spec' => 'starter',
            'billing_cycle' => 'monthly',
            'notes' => 'fallback test',
        ]);

        $response->assertRedirect();

        $lead = HostingLead::query()->latest()->firstOrFail();
        $this->assertSame('failed', $lead->whmcs_sync_status);
        $this->assertNotNull($lead->checkout_url);
        $this->assertStringContainsString('/cart.php', (string) $lead->checkout_url);
        $this->assertStringContainsString('sld=compilerworks', (string) $lead->checkout_url);
        $this->assertStringContainsString('tld=.com', (string) $lead->checkout_url);
    }

    public function test_hosting_payment_callback_syncs_payment_to_whmcs_and_is_idempotent(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'services.flutterwave.secret_key' => 'flw_test_key',
        ]);

        $lead = HostingLead::create([
            'full_name' => 'Linus Torvalds',
            'email' => 'linus@example.com',
            'phone' => '+2348012345601',
            'company' => 'Kernel Works',
            'plan_slug' => 'cpanel',
            'plan_name' => 'Cloud Hosting Powered by cPanel',
            'spec_key' => 'starter',
            'spec_label' => 'Starter',
            'billing_cycle' => 'monthly',
            'amount_usd' => 5,
            'amount_ngn' => 7500,
            'checkout_provider' => 'whmcs',
            'payment_provider' => 'flutterwave',
            'payment_reference' => 'LW-TX-1',
            'status' => 'awaiting_payment',
            'whmcs_order_id' => 777,
            'whmcs_invoice_id' => 888,
            'whmcs_sync_status' => 'checkout_synced',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => '9876',
                    'status' => 'successful',
                    'amount' => 7500,
                    'currency' => 'NGN',
                ],
            ], 200),
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success'], 200)
                ->push(['result' => 'success'], 200),
        ]);

        $this->get(route('hosting.flutterwave.callback', [
            'status' => 'successful',
            'tx_ref' => 'LW-TX-1',
            'transaction_id' => 'tx-900',
        ]))->assertRedirect(route('hosting.order-received', $lead));

        $fresh = $lead->fresh();
        $this->assertSame('payment_synced', $fresh->whmcs_sync_status);
        $this->assertSame('successful', $fresh->payment_status);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => '9876',
                    'status' => 'successful',
                    'amount' => 7500,
                    'currency' => 'NGN',
                ],
            ], 200),
            'https://billing.example.test/includes/api.php' => function () {
                $this->fail('WHMCS AcceptOrder should not be retried after payment is already synced.');
            },
        ]);

        $this->get(route('hosting.flutterwave.callback', [
            'status' => 'successful',
            'tx_ref' => 'LW-TX-1',
            'transaction_id' => 'tx-900',
        ]))->assertRedirect(route('hosting.order-received', $lead));
    }

    public function test_hosting_payment_callback_accepts_flutterwave_completed_redirect_status(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'services.flutterwave.secret_key' => 'flw_test_key',
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
            'payment_reference' => 'LW-COMPLETED-1',
            'status' => 'awaiting_payment',
            'whmcs_order_id' => 777,
            'whmcs_invoice_id' => 888,
            'whmcs_sync_status' => 'checkout_synced',
        ]);

        Http::fake([
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => '9876',
                    'status' => 'successful',
                    'amount' => 7500,
                    'currency' => 'NGN',
                ],
            ], 200),
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success'], 200)
                ->push(['result' => 'success'], 200),
        ]);

        $this->get(route('hosting.flutterwave.callback', [
            'status' => 'completed',
            'tx_ref' => 'LW-COMPLETED-1',
            'transaction_id' => 'tx-completed',
        ]))->assertRedirect(route('hosting.order-received', $lead));

        $fresh = $lead->fresh();
        $this->assertTrue($fresh->isPaid());
        $this->assertSame('paid', $fresh->status);
        $this->assertSame('payment_synced', $fresh->whmcs_sync_status);
    }

    public function test_hosting_checkout_stays_on_site_when_payment_deferred(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.order_route' => '/cart.php',
            'site.whmcs.payment_method' => 'banktransfer',
            'site.whmcs.defer_payment_redirect' => true,
            'site.hosting_plans.cpanel.whmcs_pid' => '15',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'success', 'status' => 'available', 'whois' => ''], 200)
                ->push(['result' => 'error', 'message' => 'Client not found'], 200)
                ->push(['result' => 'success', 'clientid' => '99'], 200)
                ->push(['result' => 'success', 'orderid' => '321', 'invoiceid' => '654'], 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->post(route('hosting.intake.submit'), [
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+2348012345678',
            'company' => 'Analytical Engines Ltd',
            'domain' => 'brightmedia.ng',
            'domain_option' => 'register',
            'billing_address_line_1' => '12 Marina',
            'billing_address_line_2' => '',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
            'billing_country' => 'NG',
            'plan' => 'cpanel',
            'spec' => 'starter',
            'billing_cycle' => 'monthly',
            'notes' => 'deferred payment test',
        ]);

        $lead = HostingLead::query()->latest()->firstOrFail();
        $response->assertRedirect(route('hosting.order-received', $lead));
        $this->assertSame('awaiting_payment', $lead->status);
        $this->assertSame('checkout_synced', $lead->whmcs_sync_status);
        $this->assertSame(321, (int) $lead->whmcs_order_id);
    }
}
