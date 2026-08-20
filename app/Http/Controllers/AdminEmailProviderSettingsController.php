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

        return view('admin.email-provider-settings.index', [
            'trekmail' => [
                'token' => TrekMailSettings::token(),
                'base_url' => TrekMailSettings::baseUrl(),
                'webmail_url' => TrekMailSettings::webmailUrl(),
            ],
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
            'providers' => ['nullable', 'array'],
            'providers.*.portal_url' => ['nullable', 'string', 'max:255'],
            'providers.*.account_ref' => ['nullable', 'string', 'max:190'],
            'providers.*.api_key' => ['nullable', 'string', 'max:255'],
            'providers.*.api_secret' => ['nullable', 'string', 'max:255'],
            'providers.*.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        IntegrationSetting::putMany([
            'trekmail.token' => trim((string) ($validated['trekmail_token'] ?? '')),
            'trekmail.base_url' => rtrim(trim((string) ($validated['trekmail_base_url'] ?? '')), '/'),
            'trekmail.webmail_url' => rtrim(trim((string) ($validated['trekmail_webmail_url'] ?? '')), '/'),
        ]);

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
