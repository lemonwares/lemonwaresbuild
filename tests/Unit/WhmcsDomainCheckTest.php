<?php

namespace Tests\Unit;

use App\Support\WhmcsDomainCheck;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsDomainCheckTest extends TestCase
{
    public function test_register_option_requires_available_domain(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'status' => 'available',
            ], 200),
        ]);

        $result = WhmcsDomainCheck::validate('fresh-domain.com', 'register');

        $this->assertTrue($result['ok']);
        $this->assertSame('available', $result['status']);
    }

    public function test_register_option_rejects_unavailable_domain(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'status' => 'unavailable',
            ], 200),
        ]);

        $result = WhmcsDomainCheck::validate('taken.com', 'register');

        $this->assertFalse($result['ok']);
        $this->assertSame('unavailable', $result['status']);
    }

    public function test_transfer_option_requires_registered_domain(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => Http::response([
                'result' => 'success',
                'status' => 'available',
            ], 200),
        ]);

        $result = WhmcsDomainCheck::validate('fresh-domain.com', 'transfer');

        $this->assertFalse($result['ok']);
        $this->assertSame('available', $result['status']);
    }

    public function test_own_domain_option_skips_lookup(): void
    {
        Http::fake();

        $result = WhmcsDomainCheck::validate('mine.com', 'owndomain');

        $this->assertTrue($result['ok']);
        $this->assertSame('skipped', $result['status']);
        Http::assertNothingSent();
    }
}
