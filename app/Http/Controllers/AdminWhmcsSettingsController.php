<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Models\WhmcsProductMapping;
use App\Support\WhmcsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWhmcsSettingsController extends Controller
{
    public function index(): View
    {
        $plans = config('site.hosting_plans', []);
        $mappings = WhmcsProductMapping::query()
            ->orderBy('plan_slug')
            ->orderBy('spec_key')
            ->get()
            ->keyBy(fn (WhmcsProductMapping $item) => strtolower($item->plan_slug . ':' . $item->spec_key));

        return view('admin.whmcs-settings.index', [
            'plans' => $plans,
            'mappings' => $mappings,
            'settings' => [
                'base_url' => WhmcsSettings::baseUrl(),
                'client_login_url' => WhmcsSettings::clientLoginUrl(),
                'order_route' => WhmcsSettings::orderRoute(),
                'api_identifier' => WhmcsSettings::apiIdentifier(),
                'api_secret' => WhmcsSettings::apiSecret(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'base_url' => ['required', 'url', 'max:255'],
            'client_login_url' => ['required', 'url', 'max:255'],
            'order_route' => ['required', 'string', 'max:120'],
            'api_identifier' => ['required', 'string', 'max:255'],
            'api_secret' => ['required', 'string', 'max:255'],
            'mappings' => ['nullable', 'array'],
            'mappings.*.plan_slug' => ['required_with:mappings', 'string', 'max:50'],
            'mappings.*.spec_key' => ['required_with:mappings', 'string', 'max:80'],
            'mappings.*.whmcs_pid' => ['nullable', 'integer', 'min:1'],
            'mappings.*.is_active' => ['nullable', 'boolean'],
        ]);

        IntegrationSetting::putMany([
            'whmcs.base_url' => rtrim((string) $validated['base_url'], '/'),
            'whmcs.client_login_url' => (string) $validated['client_login_url'],
            'whmcs.order_route' => '/' . ltrim((string) $validated['order_route'], '/'),
            'whmcs.api_identifier' => (string) $validated['api_identifier'],
            'whmcs.api_secret' => (string) $validated['api_secret'],
        ]);

        foreach (($validated['mappings'] ?? []) as $mapping) {
            $planSlug = strtolower((string) $mapping['plan_slug']);
            $specKey = strtolower((string) $mapping['spec_key']);
            $pid = (int) ($mapping['whmcs_pid'] ?? 0);

            if ($pid < 1) {
                WhmcsProductMapping::query()
                    ->where('plan_slug', $planSlug)
                    ->where('spec_key', $specKey)
                    ->delete();
                continue;
            }

            WhmcsProductMapping::query()->updateOrCreate(
                [
                    'plan_slug' => $planSlug,
                    'spec_key' => $specKey,
                ],
                [
                    'whmcs_pid' => $pid,
                    'is_active' => (bool) ($mapping['is_active'] ?? false),
                ]
            );
        }

        return redirect()
            ->route('admin.whmcs-settings.index')
            ->with('status', 'WHMCS settings updated.');
    }
}
