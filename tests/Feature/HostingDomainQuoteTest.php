<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostingDomainQuoteTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_domain_quote_endpoint_returns_register_price(): void
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

        $response = $this->postJson(route('hosting.domain.quote'), [
            'domain' => 'francisuzoigwe.com',
            'domain_option' => 'register',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('domain', 'francisuzoigwe.com');
    }
}
