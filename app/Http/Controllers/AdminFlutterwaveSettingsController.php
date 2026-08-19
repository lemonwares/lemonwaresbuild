<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\FlutterwaveSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFlutterwaveSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.flutterwave-settings.index', [
            'settings' => [
                'enabled' => FlutterwaveSettings::isEnabled(),
                'public_key' => FlutterwaveSettings::publicKey(),
                'secret_key' => FlutterwaveSettings::secretKey(),
                'secret_hash' => FlutterwaveSettings::secretHash(),
            ],
            'webhook_url' => FlutterwaveSettings::webhookUrl(),
            'is_configured' => FlutterwaveSettings::isConfigured(),
            'is_test_mode' => FlutterwaveSettings::isTestMode(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'public_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['required', 'string', 'max:255'],
            'secret_hash' => ['nullable', 'string', 'max:255'],
        ]);

        IntegrationSetting::putMany([
            'flutterwave.enabled' => ! empty($validated['enabled']) ? '1' : '0',
            'flutterwave.public_key' => trim((string) ($validated['public_key'] ?? '')),
            'flutterwave.secret_key' => trim((string) $validated['secret_key']),
            'flutterwave.secret_hash' => trim((string) ($validated['secret_hash'] ?? '')),
        ]);

        return redirect()
            ->route('admin.flutterwave-settings.index')
            ->with('status', 'Flutterwave settings updated.');
    }

    public function testConnection(): RedirectResponse
    {
        $result = FlutterwaveSettings::verifyConnection();

        return redirect()
            ->route('admin.flutterwave-settings.index')
            ->with('connection_test_result', $result);
    }
}
