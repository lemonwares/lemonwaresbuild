<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ExchangeRate;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['https://open.er-api.com/*' => Http::response(['rates' => ['NGN' => 1500]])]);
    }

    private function signInAdmin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_super_admin' => true]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $admin->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'site__email' => 'support@lemonwares.test',
            'site__phone' => '+234 800 000 0000',
            'currency__mode' => 'auto',
            'maintenance__enabled' => '0',
        ], $overrides);
    }

    public function test_settings_page_loads_and_saves_contact_details_used_by_the_site(): void
    {
        $this->signInAdmin();

        $this->get(route('admin.site-settings.index'))->assertOk()->assertSee('Company &amp; contact', false);

        $this->put(route('admin.site-settings.update'), $this->form([
            'social__x' => 'https://x.com/lemonwares',
            'social__facebook' => '-',
        ]))->assertRedirect(route('admin.site-settings.index'));

        SiteSettings::applyRuntimeConfig();
        $this->assertSame('support@lemonwares.test', config('site.email'));
        $icons = collect(config('site.social'))->pluck('icon')->all();
        $this->assertContains('x', $icons);
        $this->assertNotContains('facebook', $icons);

        $this->get(route('contact'))->assertOk()->assertSee('support@lemonwares.test');
    }

    public function test_manual_exchange_rate_overrides_the_live_rate(): void
    {
        $this->signInAdmin();

        $this->put(route('admin.site-settings.update'), $this->form(['currency__mode' => 'manual']))
            ->assertSessionHasErrors('currency__manual_rate');

        $this->put(route('admin.site-settings.update'), $this->form(['currency__mode' => 'manual', 'currency__manual_rate' => '1650.5']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1650.5, ExchangeRate::usdToNgn());

        $this->put(route('admin.site-settings.update'), $this->form(['currency__mode' => 'auto']));
        $this->assertSame(1500.0, ExchangeRate::refresh());
    }

    public function test_bad_social_link_is_rejected(): void
    {
        $this->signInAdmin();

        $this->put(route('admin.site-settings.update'), $this->form(['social__linkedin' => 'not a link']))
            ->assertSessionHasErrors('social__linkedin');
    }

    public function test_integration_secrets_are_encrypted_at_rest_and_kept_when_blank(): void
    {
        \App\Models\IntegrationSetting::putMany(['flutterwave.secret_key' => 'FLWSECK-abc', 'flutterwave.public_key' => 'FLWPUBK-xyz']);

        $raw = \App\Models\IntegrationSetting::query()->where('key', 'flutterwave.secret_key')->value('value');
        $this->assertStringStartsWith('enc:', $raw);
        $this->assertStringNotContainsString('FLWSECK-abc', $raw);
        $this->assertSame('FLWSECK-abc', \App\Models\IntegrationSetting::getValue('flutterwave.secret_key'));
        $this->assertSame('FLWPUBK-xyz', \App\Models\IntegrationSetting::query()->where('key', 'flutterwave.public_key')->value('value'));

        \App\Models\IntegrationSetting::putMany(['flutterwave.secret_key' => '']);
        $this->assertSame('FLWSECK-abc', \App\Models\IntegrationSetting::getValue('flutterwave.secret_key'));
    }

    public function test_secret_field_is_kept_when_left_empty(): void
    {
        $this->signInAdmin();

        $this->put(route('admin.site-settings.update'), $this->form(['services__google__places_api_key' => 'g-key-1']));
        $this->put(route('admin.site-settings.update'), $this->form(['services__google__places_api_key' => '']));

        $this->assertSame('g-key-1', SiteSettings::get('services.google.places_api_key'));
    }

    public function test_maintenance_mode_blocks_visitors_but_not_admins_or_webhooks(): void
    {
        SiteSettings::save(['maintenance.enabled' => '1', 'maintenance.message' => 'Upgrading servers until 6pm.']);

        $this->get(route('home'))->assertStatus(503)->assertSee('Upgrading servers until 6pm.');
        $this->get(route('admin.login'))->assertOk();
        $this->postJson(route('webhooks.flutterwave'), [])->assertStatus(401);

        $this->signInAdmin();
        $this->get(route('home'))->assertOk();
    }

    public function test_settings_need_permission(): void
    {
        $staff = User::factory()->create(['role' => 'admin', 'admin_permissions' => ['blog']]);
        $this->withSession(['admin_authenticated' => true, 'admin_user_id' => $staff->id]);

        $this->get(route('admin.site-settings.index'))->assertForbidden();
    }
}
