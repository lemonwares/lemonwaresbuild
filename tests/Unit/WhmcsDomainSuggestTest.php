<?php

namespace Tests\Unit;

use App\Support\WhmcsDomainSuggest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsDomainSuggestTest extends TestCase
{
    public function test_suggest_returns_available_domains_for_sld(): void
    {
        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.domain_suggestion_tlds' => ['com', 'org'],
        ]);

        Http::fake([
            'https://billing.example.test/includes/api.php' => function ($request) {
                $action = (string) data_get($request->data(), 'action', '');
                $domain = (string) data_get($request->data(), 'domain', '');

                if ($action === 'GetTLDPricing') {
                    return Http::response([
                        'result' => 'success',
                        'currency' => ['code' => 'NGN'],
                        'pricing' => [
                            'com' => ['register' => ['1' => '15000']],
                            'org' => ['register' => ['1' => '12000']],
                        ],
                    ], 200);
                }

                return Http::response([
                    'result' => 'success',
                    'status' => str_ends_with($domain, '.org') ? 'available' : 'unavailable',
                ], 200);
            },
        ]);

        $suggestions = WhmcsDomainSuggest::suggest('fran');

        $this->assertCount(2, $suggestions);
        $this->assertTrue(collect($suggestions)->firstWhere('domain', 'fran.org')['available'] ?? false);
        $this->assertFalse(collect($suggestions)->firstWhere('domain', 'fran.com')['available'] ?? true);
        $this->assertSame('₦12,000', collect($suggestions)->firstWhere('domain', 'fran.org')['price_display'] ?? null);
        $this->assertNull(collect($suggestions)->firstWhere('domain', 'fran.com')['price_display']);
    }
}
