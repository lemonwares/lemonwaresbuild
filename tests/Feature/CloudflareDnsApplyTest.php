<?php

namespace Tests\Feature;

use App\Models\EmailOrder;
use App\Models\IntegrationSetting;
use App\Models\User;
use App\Support\CloudflareDnsClient;
use App\Support\CloudflareDnsException;
use App\Support\EmailDnsTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareDnsApplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_cloudflare_settings_and_verify_token(): void
    {
        $this->withSession(['admin_authenticated' => true]);

        $this->put(route('admin.cloudflare-settings.update'), [
            'enabled' => '1',
            'api_token' => 'cf_test_token',
            'account_id' => 'acct_1',
        ])->assertRedirect(route('admin.cloudflare-settings.index'));

        $this->assertDatabaseHas('integration_settings', [
            'key' => 'cloudflare.api_token',
            'value' => 'cf_test_token',
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/user/tokens/verify' => Http::response([
                'success' => true,
                'result' => ['status' => 'active'],
            ], 200),
        ]);

        $this->post(route('admin.cloudflare-settings.test-connection'))
            ->assertRedirect(route('admin.cloudflare-settings.index'))
            ->assertSessionHas('connection_test_result.ok', true);
    }

    public function test_admin_can_load_template_and_save_dns_checklist(): void
    {
        $order = $this->makeOrder();

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.email-orders.dns.template', $order))
            ->assertRedirect(route('admin.email-orders.show', $order));

        $this->assertNotEmpty($order->fresh()->dns_records);

        $this->withSession(['admin_authenticated' => true])
            ->put(route('admin.email-orders.dns', $order), [
                'records' => [
                    ['type' => 'MX', 'name' => '@', 'value' => 'mail.trekmail.net', 'priority' => 10],
                    ['type' => 'TXT', 'name' => '@', 'value' => 'v=spf1 include:_spf.trekmail.net ~all', 'priority' => null],
                ],
            ])
            ->assertRedirect(route('admin.email-orders.show', $order));

        $records = EmailDnsTemplate::normalizeRecords($order->fresh()->dns_records);
        $this->assertCount(2, $records);
        $this->assertSame('MX', $records[0]['type']);
    }

    public function test_apply_cloudflare_upserts_records_and_removes_conflicting_mx(): void
    {
        IntegrationSetting::putMany([
            'cloudflare.enabled' => '1',
            'cloudflare.api_token' => 'cf_live',
        ]);

        $order = $this->makeOrder([
            'dns_records' => EmailDnsTemplate::lemonMail(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/zones?') && ! str_contains($url, 'dns_records')) {
                return Http::response([
                    'success' => true,
                    'result' => [['id' => 'zone1', 'name' => 'acme.ng', 'status' => 'active']],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, 'dns_records') && str_contains($url, 'type=MX')) {
                return Http::response([
                    'success' => true,
                    'result' => [[
                        'id' => 'mx-old',
                        'type' => 'MX',
                        'name' => 'acme.ng',
                        'content' => 'eforward3.registrar-servers.com',
                        'priority' => 10,
                    ]],
                ], 200);
            }

            if ($method === 'DELETE' && str_contains($url, 'dns_records/mx-old')) {
                return Http::response(['success' => true, 'result' => []], 200);
            }

            if ($method === 'GET' && str_contains($url, 'dns_records')) {
                return Http::response(['success' => true, 'result' => []], 200);
            }

            if ($method === 'POST' && str_contains($url, 'dns_records')) {
                return Http::response([
                    'success' => true,
                    'result' => ['id' => 'new-'.md5(json_encode($request->data()))],
                ], 200);
            }

            if ($method === 'PUT' && str_contains($url, 'dns_records')) {
                return Http::response([
                    'success' => true,
                    'result' => ['id' => 'updated'],
                ], 200);
            }

            return Http::response(['success' => false, 'errors' => [['message' => 'Unfaked '.$url]]], 500);
        });

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.email-orders.dns.cloudflare', $order))
            ->assertRedirect(route('admin.email-orders.show', $order))
            ->assertSessionHas('status');

        $fresh = $order->fresh();
        $this->assertSame('cloudflare', $fresh->dns_provider);
        $this->assertNotNull($fresh->dns_applied_at);

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), 'mx-old'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), 'dns_records'));
    }

    public function test_apply_cloudflare_zone_not_found_shows_clear_error(): void
    {
        IntegrationSetting::putMany([
            'cloudflare.enabled' => '1',
            'cloudflare.api_token' => 'cf_live',
        ]);

        $order = $this->makeOrder([
            'dns_records' => EmailDnsTemplate::lemonMail(),
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'success' => true,
                'result' => [],
            ], 200),
        ]);

        $this->withSession(['admin_authenticated' => true])
            ->post(route('admin.email-orders.dns.cloudflare', $order))
            ->assertRedirect(route('admin.email-orders.show', $order))
            ->assertSessionHasErrors('dns');

        $this->assertNull($order->fresh()->dns_applied_at);
    }

    public function test_customer_order_page_shows_dns_checklist(): void
    {
        $user = User::factory()->create();
        $order = EmailOrder::create([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'fulfilment_status' => 'queued',
            'domain' => 'acme.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7485,
            'status' => 'awaiting_manual_fulfilment',
            'payment_status' => 'successful',
            'dns_records' => EmailDnsTemplate::lemonMail(),
        ]);

        $this->actingAs($user)
            ->get(route('account.email.show', $order))
            ->assertOk()
            ->assertSee(__('email.dns_title'), false)
            ->assertSee('mail.trekmail.net', false)
            ->assertSee(__('email.dns_hint_namecheap'), false)
            ->assertSee(__('email.dns_copy_all'), false);
    }

    public function test_dns_client_rejects_missing_token(): void
    {
        $this->expectException(CloudflareDnsException::class);
        CloudflareDnsClient::fromSettings('');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeOrder(array $overrides = []): EmailOrder
    {
        $user = User::factory()->create();

        return EmailOrder::create(array_merge([
            'user_id' => $user->id,
            'plan_key' => 'solo',
            'plan_name' => 'Solo',
            'provider' => 'lemonmail',
            'fulfilment_mode' => 'manual',
            'fulfilment_status' => 'queued',
            'domain' => 'acme.ng',
            'mailbox_count' => 1,
            'billing_cycle' => 'monthly',
            'amount_usd' => 4.99,
            'amount_ngn' => 7485,
            'status' => 'awaiting_manual_fulfilment',
            'payment_status' => 'successful',
        ], $overrides));
    }
}
