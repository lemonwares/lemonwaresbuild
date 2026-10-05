<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Support\HetznerClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminHetznerSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.hetzner-settings.index', [
            'apiToken' => HetznerClient::apiToken(),
            'isConfigured' => HetznerClient::isConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'api_token' => ['nullable', 'string', 'max:200'],
        ]);

        IntegrationSetting::putMany([
            'hetzner.api_token' => trim((string) ($data['api_token'] ?? '')),
        ]);

        return redirect()->route('admin.hetzner-settings.index')->with('status', 'Hetzner settings updated.');
    }

    public function testConnection(): RedirectResponse
    {
        return redirect()
            ->route('admin.hetzner-settings.index')
            ->with('connection_test_result', HetznerClient::verifyConnection());
    }
}
