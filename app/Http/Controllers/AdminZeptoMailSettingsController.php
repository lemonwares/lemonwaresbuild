<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\ZeptoMailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminZeptoMailSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.zeptomail-settings.index', [
            'settings' => [
                'enabled' => ZeptoMailSettings::isEnabled(),
                'token' => ZeptoMailSettings::token(),
                'endpoint' => ZeptoMailSettings::endpoint(),
                'from_address' => ZeptoMailSettings::fromAddress(),
                'from_name' => ZeptoMailSettings::fromName(),
                'logo_url' => IntegrationSetting::getValue('zeptomail.logo_url', (string) config('services.zeptomail.logo_url', '')),
            ],
            'logo_preview_url' => ZeptoMailSettings::logoUrl(),
            'is_configured' => ZeptoMailSettings::isConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'token' => ['nullable', 'string', 'max:2000'],
            'endpoint' => ['nullable', 'url', 'max:255'],
            'from_address' => ['nullable', 'email', 'max:190'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'logo_url' => ['nullable', 'url', 'max:500'],
        ]);

        IntegrationSetting::putMany([
            'zeptomail.enabled' => $request->boolean('enabled') ? '1' : '0',
            'zeptomail.token' => trim((string) ($validated['token'] ?? '')),
            'zeptomail.endpoint' => rtrim(trim((string) ($validated['endpoint'] ?? '')), '/')
                ?: 'https://api.zeptomail.com/v1.1/email',
            'zeptomail.from_address' => trim((string) ($validated['from_address'] ?? '')),
            'zeptomail.from_name' => trim((string) ($validated['from_name'] ?? '')),
            'zeptomail.logo_url' => trim((string) ($validated['logo_url'] ?? '')),
        ]);

        ZeptoMailSettings::applyRuntimeConfig();

        return redirect()
            ->route('admin.zeptomail-settings.index')
            ->with('status', 'ZeptoMail settings updated.');
    }

    public function testConnection(): RedirectResponse
    {
        $result = ZeptoMailSettings::verifyConnection();

        return redirect()
            ->route('admin.zeptomail-settings.index')
            ->with('connection_test_result', $result);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:190'],
        ]);

        $result = ZeptoMailSettings::sendTestEmail($validated['test_email']);

        return redirect()
            ->route('admin.zeptomail-settings.index')
            ->with('send_test_result', $result);
    }
}
