<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\CloudinarySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCloudinarySettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.cloudinary-settings.index', [
            'settings' => [
                'enabled' => CloudinarySettings::isEnabled(),
                'cloud_name' => CloudinarySettings::cloudName(),
                'api_key' => CloudinarySettings::apiKey(),
                'api_secret' => CloudinarySettings::apiSecret(),
            ],
            'is_configured' => CloudinarySettings::isConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'cloud_name' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_secret' => ['nullable', 'string', 'max:255'],
        ]);

        IntegrationSetting::putMany([
            'cloudinary.enabled' => $request->boolean('enabled') ? '1' : '0',
            'cloudinary.cloud_name' => trim((string) ($validated['cloud_name'] ?? '')),
            'cloudinary.api_key' => trim((string) ($validated['api_key'] ?? '')),
            'cloudinary.api_secret' => trim((string) ($validated['api_secret'] ?? '')),
        ]);

        return redirect()
            ->route('admin.cloudinary-settings.index')
            ->with('status', 'Cloudinary settings updated.');
    }

    public function testConnection(): RedirectResponse
    {
        $result = CloudinarySettings::verifyConnection();

        return redirect()
            ->route('admin.cloudinary-settings.index')
            ->with('connection_test_result', $result);
    }
}
