<?php

namespace Tests\Unit;

use App\Support\WhmcsDomainPricing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsDomainPricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Cache::forget('whmcs.tld_pricing');
    }

    public function test_quote_returns_included_for_existing_domain(): void
    {
        $quote = WhmcsDomainPricing::quote('example.com', 'owndomain');

        $this->assertTrue($quote['ok']);
        $this->assertSame(0.0, $quote['amount_usd']);
        $this->assertSame(__('hosting.order_summary_included'), $quote['display']);
    }

    public function test_quote_returns_register_price_from_whmcs_catalog(): void
    {
        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'currency' => ['code' => 'NGN'],
                'pricing' => [
                    'com' => [
                        'register' => ['1' => '24236'],
                        'transfer' => ['1' => '24236'],
                    ],
                ],
            ], 200),
        ]);

        $quote = WhmcsDomainPricing::quote('francisuzoigwe.com', 'register');

        $this->assertTrue($quote['ok']);
        $this->assertSame('francisuzoigwe.com', $quote['domain']);
        $this->assertGreaterThan(0, $quote['amount_ngn']);
        $this->assertGreaterThan(0, $quote['amount_usd']);
    }
}
