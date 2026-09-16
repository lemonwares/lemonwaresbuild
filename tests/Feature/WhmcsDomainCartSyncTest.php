<?php

namespace Tests\Feature;

use App\Models\DomainCheckout;
use App\Models\DomainOrder;
use App\Models\User;
use App\Models\WhmcsCustomer;
use App\Support\WhmcsDomainOrderSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsDomainCartSyncTest extends TestCase
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
        ]);

        Cache::forget('whmcs.tld_pricing');
    }

    public function test_cart_checkout_creates_multi_domain_whmcs_order_and_links_customer(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push(['result' => 'error', 'message' => 'Client Not Found'], 200) // GetClientsDetails
                ->push(['result' => 'success', 'clientid' => '88'], 200) // AddClient
                ->push([ // GetTLDPricing (currency id)
                    'result' => 'success',
                    'currency' => ['id' => 2, 'code' => 'NGN'],
                    'pricing' => [],
                ], 200)
                ->push(['result' => 'success', 'orderid' => '501', 'invoiceid' => '902'], 200), // AddOrder
        ]);

        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@domains.test',
            'company' => 'Analytical Engines',
            'phone' => '+2348012345678',
            'billing_country' => 'NG',
            'billing_city' => 'Lagos',
            'billing_address_line_1' => '12 Marina',
        ]);

        $checkout = DomainCheckout::create([
            'user_id' => $user->id,
            'item_count' => 2,
            'amount_usd' => 20,
            'amount_ngn' => 32000,
            'status' => 'awaiting_payment',
        ]);

        DomainOrder::create([
            'user_id' => $user->id,
            'domain_checkout_id' => $checkout->id,
            'domain' => 'brightmedia.ng',
            'option' => 'register',
            'reg_period' => 2,
            'amount_usd' => 12,
            'amount_ngn' => 19200,
            'status' => 'awaiting_payment',
        ]);

        DomainOrder::create([
            'user_id' => $user->id,
            'domain_checkout_id' => $checkout->id,
            'domain' => 'brightmedia.com',
            'option' => 'transfer',
            'epp_code' => 'AUTHCODE123',
            'reg_period' => 1,
            'amount_usd' => 8,
            'amount_ngn' => 12800,
            'status' => 'awaiting_payment',
        ]);

        $synced = WhmcsDomainOrderSync::syncCheckoutBundle($checkout->fresh(['orders', 'user']));

        $this->assertSame('checkout_synced', $synced->whmcs_sync_status);
        $this->assertSame(88, (int) $synced->whmcs_client_id);
        $this->assertSame(501, (int) $synced->whmcs_order_id);
        $this->assertSame(902, (int) $synced->whmcs_invoice_id);

        $this->assertDatabaseHas('whmcs_customers', [
            'user_id' => $user->id,
            'whmcs_client_id' => 88,
            'email' => 'ada@domains.test',
        ]);

        Http::assertSent(function ($request) {
            if (! str_contains((string) $request->url(), 'includes/api.php')) {
                return false;
            }

            $data = $request->data();
            if (($data['action'] ?? null) !== 'AddOrder') {
                return false;
            }

            $this->assertSame('88', (string) ($data['clientid'] ?? ''));
            $this->assertSame(['brightmedia.ng', 'brightmedia.com'], $data['domain'] ?? null);
            $this->assertSame(['register', 'transfer'], $data['domaintype'] ?? null);
            $this->assertSame(['2', '1'], array_map('strval', $data['regperiod'] ?? []));
            $this->assertSame(['', 'AUTHCODE123'], $data['eppcode'] ?? null);
            $this->assertSame('2', (string) ($data['currencyid'] ?? ''));

            return true;
        });
    }

    public function test_payment_sync_settles_invoice_balance_and_accepts_order(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::sequence()
                ->push([
                    'result' => 'success',
                    'invoiceid' => '902',
                    'balance' => '18.50',
                    'total' => '18.50',
                    'status' => 'Unpaid',
                ], 200) // GetInvoice
                ->push(['result' => 'success'], 200) // AddInvoicePayment
                ->push(['result' => 'success'], 200), // AcceptOrder
        ]);

        $user = User::factory()->create();
        $checkout = DomainCheckout::create([
            'user_id' => $user->id,
            'item_count' => 1,
            'amount_usd' => 12,
            'amount_ngn' => 19200,
            'status' => 'paid',
            'payment_status' => 'successful',
            'payment_reference' => 'LW-DCART-1-TEST',
            'flutterwave_transaction_id' => 'flw-tx-99',
            'whmcs_client_id' => 88,
            'whmcs_order_id' => 501,
            'whmcs_invoice_id' => 902,
            'whmcs_sync_status' => 'checkout_synced',
        ]);

        DomainOrder::create([
            'user_id' => $user->id,
            'domain_checkout_id' => $checkout->id,
            'domain' => 'brightmedia.ng',
            'option' => 'register',
            'reg_period' => 1,
            'amount_usd' => 12,
            'amount_ngn' => 19200,
            'status' => 'paid',
            'whmcs_order_id' => 501,
            'whmcs_invoice_id' => 902,
            'whmcs_sync_status' => 'checkout_synced',
        ]);

        $synced = WhmcsDomainOrderSync::syncPaymentBundle($checkout->fresh(['orders', 'user']));

        $this->assertSame('payment_synced', $synced->whmcs_sync_status);
        $this->assertSame('submitted', $synced->status);

        Http::assertSent(function ($request) use ($checkout) {
            $data = $request->data();
            if (($data['action'] ?? null) !== 'AddInvoicePayment') {
                return false;
            }

            $this->assertSame('902', (string) ($data['invoiceid'] ?? ''));
            $this->assertEqualsWithDelta(18.5, (float) ($data['amount'] ?? 0), 0.001);
            $this->assertSame('flw-tx-99-C'.$checkout->id, (string) ($data['transid'] ?? ''));

            return true;
        });

        Http::assertSent(function ($request) {
            $data = $request->data();
            if (($data['action'] ?? null) !== 'AcceptOrder') {
                return false;
            }

            $this->assertSame('501', (string) ($data['orderid'] ?? ''));
            $this->assertTrue(filter_var($data['autosetup'] ?? false, FILTER_VALIDATE_BOOLEAN));

            return true;
        });
    }
}
