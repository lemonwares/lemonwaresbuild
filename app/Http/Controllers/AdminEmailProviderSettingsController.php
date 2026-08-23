<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\EmailProviderSettings;
use App\Support\TrekMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEmailProviderSettingsController extends Controller
{
    public function index(): View
    {
        $manualProviders = [];
        foreach (EmailProviderSettings::manualProviders() as $provider) {
            $manualProviders[$provider] = [
                'label' => EmailProviderSettings::label($provider),
                'settings' => EmailProviderSettings::for($provider),
            ];
        }

        $branding = TrekMailSettings::branding();

        return view('admin.email-provider-settings.index', [
            'trekmail' => [
                'token' => TrekMailSettings::token(),
                'base_url' => TrekMailSettings::baseUrl(),
                'webmail_url' => TrekMailSettings::webmailUrl(),
            ],
            'branding' => $branding,
            'is_configured' => TrekMailSettings::isConfigured(),
            'manualProviders' => $manualProviders,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'trekmail_token' => ['nullable', 'string', 'max:255'],
            'trekmail_base_url' => ['nullable', 'url', 'max:255'],
            'trekmail_webmail_url' => ['nullable', 'url', 'max:255'],
            'trekmail_branding_enabled' => ['nullable', 'boolean'],
            'trekmail_brand_name' => ['nullable', 'string', 'max:120'],
            'trekmail_brand_primary_color' => ['nullable', 'string', 'max:7', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'trekmail_brand_accent_color' => ['nullable', 'string', 'max:7', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'trekmail_brand_support_email' => ['nullable', 'email', 'max:190'],
            'trekmail_brand_support_url' => ['nullable', 'url', 'max:255'],
            'trekmail_brand_sender_email' => ['nullable', 'email', 'max:190'],
            'trekmail_brand_logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024'],
            'providers' => ['nullable', 'array'],
            'providers.*.portal_url' => ['nullable', 'string', 'max:255'],
            'providers.*.account_ref' => ['nullable', 'string', 'max:190'],
            'providers.*.api_key' => ['nullable', 'string', 'max:255'],
            'providers.*.api_secret' => ['nullable', 'string', 'max:255'],
            'providers.*.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $primary = (string) ($validated['trekmail_brand_primary_color'] ?? '#e04545');
        $accent = (string) ($validated['trekmail_brand_accent_color'] ?? '#ffeded');
        if (! str_starts_with($primary, '#')) {
            $primary = '#' . $primary;
        }
        if (! str_starts_with($accent, '#')) {
            $accent = '#' . $accent;
        }

        $settings = [
            'trekmail.token' => trim((string) ($validated['trekmail_token'] ?? '')),
            'trekmail.base_url' => rtrim(trim((string) ($validated['trekmail_base_url'] ?? '')), '/'),
            'trekmail.webmail_url' => rtrim(trim((string) ($validated['trekmail_webmail_url'] ?? '')), '/'),
            'trekmail.branding_enabled' => $request->boolean('trekmail_branding_enabled') ? '1' : '0',
            'trekmail.brand_name' => trim((string) ($validated['trekmail_brand_name'] ?? '')),
            'trekmail.brand_primary_color' => strtolower($primary),
            'trekmail.brand_accent_color' => strtolower($accent),
            'trekmail.brand_support_email' => trim((string) ($validated['trekmail_brand_support_email'] ?? '')),
            'trekmail.brand_support_url' => rtrim(trim((string) ($validated['trekmail_brand_support_url'] ?? '')), '/'),
            'trekmail.brand_sender_email' => trim((string) ($validated['trekmail_brand_sender_email'] ?? '')),
        ];

        if ($request->hasFile('trekmail_brand_logo')) {
            $stored = $request->file('trekmail_brand_logo')->store('trekmail-branding', 'local');
            $settings['trekmail.brand_logo_path'] = $stored;
        }

        IntegrationSetting::putMany($settings);

        foreach (EmailProviderSettings::manualProviders() as $provider) {
            EmailProviderSettings::put($provider, $validated['providers'][$provider] ?? []);
        }

        return redirect()
            ->route('admin.email-provider-settings.index')
            ->with('status', 'Email provider settings updated.');
    }

    public function testConnection(): RedirectResponse
    {
        $result = TrekMailSettings::verifyConnection();

        return redirect()
            ->route('admin.email-provider-settings.index')
            ->with('connection_test_result', $result);
    }
}
