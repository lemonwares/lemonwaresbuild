<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhmcsSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_whmcs_connection_and_pid_mappings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->put(route('admin.whmcs-settings.update'), [
            'base_url' => 'https://billing.example.com',
            'client_login_url' => 'https://billing.example.com/clientarea.php',
            'order_route' => '/cart.php',
            'api_identifier' => 'abc123',
            'api_secret' => 'secret456',
            'payment_method' => 'banktransfer',
            'defer_payment_redirect' => '1',
            'mappings' => [
                'cpanel_aspire' => [
                    'plan_slug' => 'cpanel',
                    'spec_key' => 'aspire',
                    'whmcs_pid' => 16,
                    'is_active' => 1,
                ],
            ],
        ])->assertRedirect(route('admin.whmcs-settings.index'));

        $this->assertDatabaseHas('integration_settings', [
            'key' => 'whmcs.base_url',
            'value' => 'https://billing.example.com',
        ]);

        $this->assertDatabaseHas('whmcs_product_mappings', [
            'plan_slug' => 'cpanel',
            'spec_key' => 'aspire',
            'whmcs_pid' => 16,
            'is_active' => 1,
        ]);
    }

    public function test_hosting_checkout_uses_db_mapping_pid_when_available(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->put(route('admin.whmcs-settings.update'), [
            'base_url' => 'https://billing.example.com',
            'client_login_url' => 'https://billing.example.com/clientarea.php',
            'order_route' => '/cart.php',
            'api_identifier' => 'abc123',
            'api_secret' => 'secret456',
            'payment_method' => 'banktransfer',
            'defer_payment_redirect' => '1',
            'mappings' => [
                'cpanel_starter' => [
                    'plan_slug' => 'cpanel',
                    'spec_key' => 'starter',
                    'whmcs_pid' => 16,
                    'is_active' => 1,
                ],
            ],
        ])->assertRedirect(route('admin.whmcs-settings.index'));

        Http::fake([
            'https://billing.example.com/includes/api.php' => fn ($request) => Http::response(match ($request['action'] ?? '') {
                'DomainWhois' => ['result' => 'success', 'status' => 'available'],
                'GetTLDPricing' => [
                    'result' => 'success',
                    'currency' => ['id' => 1, 'code' => 'NGN'],
                    'pricing' => ['com' => ['register' => ['1' => '15000.00']]],
                ],
                'AddClient' => ['result' => 'success', 'clientid' => '99'],
                'AddOrder' => ['result' => 'success', 'orderid' => '321', 'invoiceid' => '654'],
                default => ['result' => 'error', 'message' => 'Client not found'],
            }, 200),
            'open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1600]], 200),
        ]);

        $response = $this->post(route('hosting.intake.submit'), [
            'full_name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+2348012345678',
            'company' => 'Analytical Engines',
            'domain' => 'example.com',
            'domain_option' => 'register',
            'billing_address_line_1' => '14 Admiralty Way',
            'billing_address_line_2' => '',
            'billing_city' => 'Lagos',
            'billing_state' => 'Lagos',
            'billing_postcode' => '100001',
            'billing_country' => 'NG',
            'plan' => 'cpanel',
            'spec' => 'starter',
            'billing_cycle' => 'monthly',
            'notes' => '',
        ]);

        $response->assertRedirect();
        $lead = \App\Models\HostingLead::query()->latest('id')->firstOrFail();
        $this->assertStringContainsString('/cart.php', (string) $lead->checkout_url);
        $this->assertStringContainsString('pid=16', (string) $lead->checkout_url);
        $this->assertStringContainsString('sld=example', (string) $lead->checkout_url);
        $this->assertStringContainsString('domainoption=register', (string) $lead->checkout_url);
        Http::assertSent(fn ($request) => ($request['action'] ?? '') === 'AddOrder'
            && in_array('16', array_map('strval', (array) ($request['pid'] ?? [])), true));
    }
}
