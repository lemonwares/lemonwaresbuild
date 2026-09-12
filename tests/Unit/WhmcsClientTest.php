<?php

namespace Tests\Unit;

use App\Support\WhmcsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsClientTest extends TestCase
{
    public function test_whmcs_client_reports_configured_only_when_required_values_exist(): void
    {
        config([
            'site.whmcs.base_url' => '',
            'site.whmcs.api_identifier' => '',
            'site.whmcs.api_secret' => '',
        ]);

        $this->assertFalse(WhmcsClient::isConfigured());

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        $this->assertTrue(WhmcsClient::isConfigured());
    }

    public function test_create_order_returns_null_on_non_success_response(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'error',
                'message' => 'Bad request',
            ], 200),
        ]);

        $result = WhmcsClient::createOrder([
            'clientid' => 1,
            'pid' => [10],
            'billingcycle' => ['monthly'],
        ]);

        $this->assertNull($result);
    }
}
