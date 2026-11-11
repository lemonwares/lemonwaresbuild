<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'site.whmcs.base_url' => 'https://billing.example.test',
            'site.whmcs.api_identifier' => 'identifier',
            'site.whmcs.api_secret' => 'secret',
            'site.whmcs.webhook_secret' => 'hook-secret',
        ]);

        Http::fake(['https://billing.example.test/includes/api.php' => Http::response(['result' => 'success'])]);
    }

    private function send(array $payload, string $secret = 'hook-secret')
    {
        $body = json_encode($payload);

        return $this->call('POST', route('webhooks.whmcs'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_LEMONWARES_SIGNATURE' => hash_hmac('sha256', $body, $secret),
        ], $body);
    }

    public function test_signed_notification_refreshes_that_client(): void
    {
        $this->send(['event' => 'InvoicePaid', 'client_id' => 42, 'ts' => time()])->assertOk();

        Http::assertSent(fn ($r) => $r['action'] === 'GetClientsDetails' && (int) $r['clientid'] === 42);
    }

    public function test_bad_signature_or_stale_timestamp_is_rejected(): void
    {
        $this->send(['client_id' => 42, 'ts' => time()], 'wrong')->assertUnauthorized();
        $this->send(['client_id' => 42, 'ts' => time() - 3600])->assertUnauthorized();

        config(['site.whmcs.webhook_secret' => '']);
        $this->send(['client_id' => 42, 'ts' => time()], '')->assertUnauthorized();

        Http::assertNothingSent();
    }
}
