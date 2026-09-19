<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use App\Support\CloudinarySettings;
use App\Support\MediaStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudinarySettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->create();

        return $this->withSession([
            'admin_authenticated' => true,
            'admin_user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_save_cloudinary_settings_and_verify_connection(): void
    {
        $this->actingAsAdmin();

        $this->put(route('admin.cloudinary-settings.update'), [
            'enabled' => '1',
            'cloud_name' => 'lemonwares',
            'api_key' => 'key_123',
            'api_secret' => 'secret_456',
        ])->assertRedirect(route('admin.cloudinary-settings.index'));

        $this->assertDatabaseHas('integration_settings', [
            'key' => 'cloudinary.cloud_name',
            'value' => 'lemonwares',
        ]);
        $this->assertDatabaseHas('integration_settings', [
            'key' => 'cloudinary.api_key',
            'value' => 'key_123',
        ]);
        $this->assertDatabaseHas('integration_settings', [
            'key' => 'cloudinary.api_secret',
            'value' => 'secret_456',
        ]);

        $this->assertTrue(CloudinarySettings::isConfigured());
        $this->assertTrue(MediaStorage::enabled());

        Http::fake([
            'api.cloudinary.com/v1_1/lemonwares/ping' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->post(route('admin.cloudinary-settings.test-connection'))
            ->assertRedirect(route('admin.cloudinary-settings.index'))
            ->assertSessionHas('connection_test_result.ok', true);
    }

    public function test_media_storage_uses_admin_saved_cloudinary_credentials(): void
    {
        IntegrationSetting::putMany([
            'cloudinary.enabled' => '1',
            'cloudinary.cloud_name' => 'from-admin',
            'cloudinary.api_key' => 'admin-key',
            'cloudinary.api_secret' => 'admin-secret',
        ]);

        config([
            'services.cloudinary.cloud_name' => 'from-env',
            'services.cloudinary.api_key' => 'env-key',
            'services.cloudinary.api_secret' => 'env-secret',
        ]);

        $this->assertSame('from-admin', CloudinarySettings::cloudName());
        $this->assertSame('admin-key', CloudinarySettings::apiKey());
        $this->assertSame('admin-secret', CloudinarySettings::apiSecret());
        $this->assertTrue(MediaStorage::enabled());

        $url = MediaStorage::url('cloudinary:lemonwares/covers/demo');
        $this->assertIsString($url);
        $this->assertStringContainsString('res.cloudinary.com/from-admin/', $url);
        $this->assertStringContainsString('lemonwares/covers/demo', $url);
    }
}
