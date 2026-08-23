<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Support\ZeptoMailSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZeptoMailSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_zeptomail_settings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->put(route('admin.zeptomail-settings.update'), [
            'enabled' => '1',
            'token' => 'zm_admin_token',
            'endpoint' => 'https://api.zeptomail.test/v1.1/email',
            'from_address' => 'noreply@lemonwares.com',
            'from_name' => 'Lemonwares',
            'logo_url' => 'https://cdn.example.com/lemonwareslogo.png',
        ])->assertRedirect(route('admin.zeptomail-settings.index'));

        $this->assertDatabaseHas('integration_settings', [
            'key' => 'zeptomail.token',
            'value' => 'zm_admin_token',
        ]);
        $this->assertDatabaseHas('integration_settings', [
            'key' => 'zeptomail.logo_url',
            'value' => 'https://cdn.example.com/lemonwareslogo.png',
        ]);

        $this->assertTrue(ZeptoMailSettings::isEnabled());
        $this->assertTrue(ZeptoMailSettings::isConfigured());
        $this->assertSame('zm_admin_token', ZeptoMailSettings::token());
        $this->assertSame('noreply@lemonwares.com', ZeptoMailSettings::fromAddress());
        $this->assertSame('https://cdn.example.com/lemonwareslogo.png', ZeptoMailSettings::logoUrl());
        $this->assertSame('zeptomail', config('mail.default'));
    }

    public function test_connection_test_uses_admin_token(): void
    {
        $this->seed(DatabaseSeeder::class);

        IntegrationSetting::putMany([
            'zeptomail.enabled' => '1',
            'zeptomail.token' => 'zm_verify',
            'zeptomail.endpoint' => 'https://api.zeptomail.test/v1.1/email',
            'zeptomail.from_address' => 'mails@lemonwares.com',
        ]);

        Http::fake([
            'https://api.zeptomail.test/v1.1/email' => Http::response([
                'error' => ['message' => 'Required field missing'],
            ], 400),
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.zeptomail-settings.test-connection'))
            ->assertRedirect(route('admin.zeptomail-settings.index'))
            ->assertSessionHas('connection_test_result.ok', true);
    }

    public function test_send_test_email_uses_zeptomail_mailer(): void
    {
        $this->seed(DatabaseSeeder::class);

        IntegrationSetting::putMany([
            'zeptomail.enabled' => '1',
            'zeptomail.token' => 'zm_send',
            'zeptomail.endpoint' => 'https://api.zeptomail.test/v1.1/email',
            'zeptomail.from_address' => 'mails@lemonwares.com',
            'zeptomail.from_name' => 'Lemonwares',
        ]);

        Http::fake([
            'https://api.zeptomail.test/v1.1/email' => Http::response([
                'message' => 'OK',
                'request_id' => 'req-test',
            ], 200),
        ]);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.zeptomail-settings.send-test'), [
            'test_email' => 'ops@lemonwares.com',
        ])->assertRedirect(route('admin.zeptomail-settings.index'))
            ->assertSessionHas('send_test_result.ok', true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.zeptomail.test/v1.1/email'
                && data_get($request->data(), 'to.0.email_address.address') === 'ops@lemonwares.com';
        });
    }

    public function test_disabled_zeptomail_does_not_override_default_mailer(): void
    {
        config(['mail.default' => 'log']);

        IntegrationSetting::putMany([
            'zeptomail.enabled' => '0',
            'zeptomail.token' => 'zm_unused',
            'zeptomail.from_address' => 'mails@lemonwares.com',
        ]);

        ZeptoMailSettings::applyRuntimeConfig();

        $this->assertSame('log', config('mail.default'));
        $this->assertFalse(ZeptoMailSettings::isEnabled());
    }
}
