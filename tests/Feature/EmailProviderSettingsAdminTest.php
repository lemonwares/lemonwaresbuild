<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Support\EmailProviderSettings;
use App\Support\TrekMailClient;
use App\Support\TrekMailSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailProviderSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_email_provider_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->put(route('admin.email-provider-settings.update'), [
            'trekmail_token' => 'tm_live_from_admin',
            'trekmail_base_url' => 'https://trekmail.example.test/api/v1',
            'trekmail_webmail_url' => 'https://webmail.example.test',
            'providers' => [
                'titan' => [
                    'portal_url' => 'https://partners.titan.email',
                    'account_ref' => 'reseller-42',
                    'api_key' => 'titan-key',
                    'api_secret' => 'titan-secret',
                    'notes' => 'Provision within 4 hours.',
                ],
                'google_workspace' => [
                    'portal_url' => 'https://admin.google.com',
                    'account_ref' => 'gsuite@lemonwares.com',
                    'api_key' => '',
                    'api_secret' => '',
                    'notes' => '',
                ],
                'ms365' => [
                    'portal_url' => '',
                    'account_ref' => '',
                    'api_key' => '',
                    'api_secret' => '',
                    'notes' => '',
                ],
            ],
        ])->assertRedirect(route('admin.email-provider-settings.index'));

        $this->assertDatabaseHas('integration_settings', [
            'key' => 'trekmail.token',
            'value' => 'tm_live_from_admin',
        ]);

        $this->assertSame('tm_live_from_admin', TrekMailSettings::token());
        $this->assertTrue(TrekMailSettings::isConfigured());
        $this->assertTrue(TrekMailClient::isConfigured());
        $this->assertSame('https://webmail.example.test', TrekMailClient::webmailUrl());

        $titan = EmailProviderSettings::for('titan');
        $this->assertSame('https://partners.titan.email', $titan['portal_url']);
        $this->assertSame('reseller-42', $titan['account_ref']);
        $this->assertSame('titan-key', $titan['api_key']);
    }

    public function test_trekmail_connection_test_uses_admin_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        IntegrationSetting::putMany([
            'trekmail.token' => 'tm_live_verify',
            'trekmail.base_url' => 'https://trekmail.example.test/api/v1',
        ]);

        Http::fake([
            'https://trekmail.example.test/api/v1/domains*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.email-provider-settings.test-connection'))
            ->assertRedirect(route('admin.email-provider-settings.index'))
            ->assertSessionHas('connection_test_result.ok', true);
    }
}
