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
                $domain = (string) data_get($request->data(), 'domain', '');

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
    }
}
