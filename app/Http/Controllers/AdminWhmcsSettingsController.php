<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Models\WhmcsProductMapping;
use App\Support\WhmcsClient;
use App\Support\WhmcsDomainCheck;
use App\Support\WhmcsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminWhmcsSettingsController extends Controller
{
    public function index(): View
    {
        $mappings = WhmcsProductMapping::query()
            ->orderBy('plan_slug')
            ->orderBy('spec_key')
            ->get();

        $suggestedPlans = collect(config('site.hosting_plans', []))
            ->map(fn (array $plan, string $slug) => [
                'slug' => $slug,
                'title' => (string) ($plan['title'] ?? $slug),
                'specs' => collect($plan['specifications'] ?? [])
                    ->map(fn (array $spec) => [
                        'key' => strtolower((string) ($spec['key'] ?? '')),
                        'label' => (string) ($spec['label'] ?? ($spec['key'] ?? '')),
                    ])
                    ->filter(fn (array $spec) => $spec['key'] !== '')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return view('admin.whmcs-settings.index', [
            'mappings' => $mappings,
            'suggestedPlans' => $suggestedPlans,
            'settings' => [
                'base_url' => WhmcsSettings::baseUrl(),
                'client_login_url' => WhmcsSettings::clientLoginUrl(),
                'order_route' => WhmcsSettings::orderRoute(),
                'api_identifier' => WhmcsSettings::apiIdentifier(),
                'api_secret' => WhmcsSettings::apiSecret(),
                'api_access_key' => WhmcsSettings::apiAccessKey(),
                'payment_method' => WhmcsSettings::paymentMethod(),
                'defer_payment_redirect' => WhmcsSettings::deferPaymentRedirect(),
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
            'api_access_key' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'max:80'],
            'defer_payment_redirect' => ['nullable', 'boolean'],
            'mappings' => ['nullable', 'array'],
            'mappings.*.plan_slug' => ['nullable', 'string', 'max:50'],
            'mappings.*.spec_key' => ['nullable', 'string', 'max:80'],
            'mappings.*.whmcs_pid' => ['nullable', 'integer', 'min:1'],
            'mappings.*.is_active' => ['nullable', 'boolean'],
        ]);

        IntegrationSetting::putMany([
            'whmcs.base_url' => rtrim((string) $validated['base_url'], '/'),
            'whmcs.client_login_url' => (string) $validated['client_login_url'],
            'whmcs.order_route' => '/' . ltrim((string) $validated['order_route'], '/'),
            'whmcs.api_identifier' => (string) $validated['api_identifier'],
            'whmcs.api_secret' => (string) $validated['api_secret'],
            'whmcs.api_access_key' => (string) ($validated['api_access_key'] ?? ''),
            'whmcs.payment_method' => strtolower(trim((string) $validated['payment_method'])),
            'whmcs.defer_payment_redirect' => ! empty($validated['defer_payment_redirect']) ? '1' : '0',
        ]);

        $rows = collect($validated['mappings'] ?? [])
            ->map(function (array $mapping): ?array {
                $planSlug = strtolower(trim((string) ($mapping['plan_slug'] ?? '')));
                $specKey = strtolower(trim((string) ($mapping['spec_key'] ?? '')));
                $pid = (int) ($mapping['whmcs_pid'] ?? 0);

                if ($planSlug === '' || $specKey === '' || $pid < 1) {
                    return null;
                }

                return [
                    'plan_slug' => $planSlug,
                    'spec_key' => $specKey,
                    'whmcs_pid' => $pid,
                    'is_active' => (bool) ($mapping['is_active'] ?? false),
                ];
            })
            ->filter()
            ->unique(fn (array $row) => $row['plan_slug'] . ':' . $row['spec_key'])
            ->values();

        DB::transaction(function () use ($rows): void {
            WhmcsProductMapping::query()->delete();

            foreach ($rows as $row) {
                WhmcsProductMapping::query()->create($row);
            }
        });

        return redirect()
            ->route('admin.whmcs-settings.index')
            ->with('status', 'WHMCS settings updated.');
    }

    public function testDomain(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
            'domain_option' => ['required', 'string', 'in:register,transfer,owndomain'],
        ]);

        $connection = WhmcsClient::verifyConnection();
        $result = WhmcsDomainCheck::validate(
            (string) $validated['domain'],
            (string) $validated['domain_option'],
            true
        );

        $whois = WhmcsClient::domainWhois((string) $validated['domain']);

        return redirect()
            ->route('admin.whmcs-settings.index')
            ->with('domain_test_result', [
                'input' => $validated,
                'connection' => $connection,
                'validation' => $result,
                'configured' => WhmcsClient::isConfigured(),
                'whmcs_error' => WhmcsClient::lastError(),
                'whois' => $whois,
            ]);
    }
}
