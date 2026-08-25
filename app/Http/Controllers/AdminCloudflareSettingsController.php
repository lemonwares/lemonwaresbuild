<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\CloudflareSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCloudflareSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.cloudflare-settings.index', [
            'settings' => [
                'enabled' => CloudflareSettings::isEnabled(),
                'api_token' => CloudflareSettings::apiToken(),
                'account_id' => CloudflareSettings::accountId(),
            ],
            'is_configured' => CloudflareSettings::isConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'api_token' => ['nullable', 'string', 'max:2000'],
            'account_id' => ['nullable', 'string', 'max:64'],
        ]);

        IntegrationSetting::putMany([
            'cloudflare.enabled' => $request->boolean('enabled') ? '1' : '0',
            'cloudflare.api_token' => trim((string) ($validated['api_token'] ?? '')),
            'cloudflare.account_id' => trim((string) ($validated['account_id'] ?? '')),
        ]);

        return redirect()
            ->route('admin.cloudflare-settings.index')
            ->with('status', 'Cloudflare settings updated.');
    }

    public function testConnection(): RedirectResponse
    {
        $result = CloudflareSettings::verifyConnection();

        return redirect()
            ->route('admin.cloudflare-settings.index')
            ->with('connection_test_result', $result);
    }
}
