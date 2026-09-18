@extends('layouts.admin')

@section('title', 'WHMCS Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="WHMCS Settings"
        lede="Manage WHMCS connection details and map any plan/spec to a WHMCS product ID. Add or remove mappings as needed."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'WHMCS Settings']]"
        class="mb-5"
    />

    <form method="POST" action="{{ route('admin.whmcs-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">Connection</h2>
                    <p class="admin-dash-panel-lede">API credentials and checkout behaviour for WHMCS orders.</p>
                </div>
            </div>

            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>WHMCS Base URL</span>
                    <input type="url" name="base_url" value="{{ old('base_url', $settings['base_url']) }}" class="admin-input" required>
                </label>
                <label class="admin-field">
                    <span>Client Login URL</span>
                    <input type="url" name="client_login_url" value="{{ old('client_login_url', $settings['client_login_url']) }}" class="admin-input" required>
                </label>
                <label class="admin-field">
                    <span>Order Route</span>
                    <input type="text" name="order_route" value="{{ old('order_route', $settings['order_route']) }}" class="admin-input" required>
                </label>
                <label class="admin-field">
                    <span>Default Payment Method</span>
                    <input type="text" name="payment_method" value="{{ old('payment_method', $settings['payment_method'] ?: 'banktransfer') }}" class="admin-input" required placeholder="banktransfer">
                    <p class="admin-muted text-xs">WHMCS gateway system name used when creating orders via API (e.g. <code class="rounded bg-blush-soft px-1">banktransfer</code>, <code class="rounded bg-blush-soft px-1">paypal</code>).</p>
                </label>
                <label class="admin-field">
                    <span>API Identifier</span>
                    <input type="text" name="api_identifier" value="{{ old('api_identifier', $settings['api_identifier']) }}" class="admin-input" required>
                </label>
                <label class="admin-field admin-field-span">
                    <span>API Secret</span>
                    <input type="text" name="api_secret" value="{{ old('api_secret', $settings['api_secret']) }}" class="admin-input" required>
                </label>
                <label class="admin-field admin-field-span">
                    <span>API Access Key (optional)</span>
                    <input type="text" name="api_access_key" value="{{ old('api_access_key', $settings['api_access_key']) }}" class="admin-input" placeholder="Only if enabled in WHMCS General Settings > Security">
                    <p class="admin-muted text-xs">Required only when WHMCS has a global API Access Key configured.</p>
                </label>
            </div>

            <label class="admin-check">
                <input
                    type="checkbox"
                    name="defer_payment_redirect"
                    value="1"
                    @checked(\App\Support\WhmcsSettings::deferPaymentRedirect())
                />
                <span>
                    Test mode: skip WHMCS payment redirect — create the WHMCS client and pending order via API, then stay on Lemonwares instead of sending the customer to WHMCS checkout. Enabled automatically when APP_ENV=local unless overridden here.
                </span>
            </label>
        </section>

        <section class="admin-panel admin-panel-flush" data-whmcs-mappings>
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Product mappings</h2>
                    <p class="admin-dash-panel-lede">
                        Map any plan slug + spec key to a WHMCS product ID. Add rows for new products, remove ones you no longer need.
                    </p>
                </div>
                <button type="button" class="admin-btn-ghost" data-whmcs-mapping-add>Add mapping</button>
            </div>

            @if (! empty($suggestedPlans))
                <div class="admin-panel-pad">
                    <p class="admin-muted text-xs mb-2">Quick-fill from site hosting catalog:</p>
                    <div class="admin-customers-toolbar">
                        @foreach ($suggestedPlans as $plan)
                            <button
                                type="button"
                                class="admin-btn-ghost"
                                data-whmcs-mapping-seed
                                data-plan-slug="{{ $plan['slug'] }}"
                                data-specs='@json($plan['specs'])'
                            >
                                Fill {{ $plan['title'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Plan slug</th>
                            <th>Spec key</th>
                            <th>WHMCS PID</th>
                            <th>Active</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody data-whmcs-mapping-rows>
                        @php
                            $oldMappings = old('mappings');
                            $rows = is_array($oldMappings)
                                ? collect($oldMappings)
                                : $mappings->map(fn ($m) => [
                                    'plan_slug' => $m->plan_slug,
                                    'spec_key' => $m->spec_key,
                                    'whmcs_pid' => $m->whmcs_pid,
                                    'is_active' => $m->is_active,
                                ]);
                            if ($rows->isEmpty()) {
                                $rows = collect([['plan_slug' => '', 'spec_key' => '', 'whmcs_pid' => '', 'is_active' => true]]);
                            }
                        @endphp
                        @foreach ($rows as $index => $row)
                            <tr data-whmcs-mapping-row>
                                <td>
                                    <input
                                        type="text"
                                        name="mappings[{{ $index }}][plan_slug]"
                                        value="{{ $row['plan_slug'] ?? '' }}"
                                        class="admin-input admin-input-sm w-full"
                                        placeholder="e.g. cpanel"
                                        data-whmcs-plan
                                    >
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        name="mappings[{{ $index }}][spec_key]"
                                        value="{{ $row['spec_key'] ?? '' }}"
                                        class="admin-input admin-input-sm w-full"
                                        placeholder="e.g. business"
                                        data-whmcs-spec
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="1"
                                        name="mappings[{{ $index }}][whmcs_pid]"
                                        value="{{ $row['whmcs_pid'] ?? '' }}"
                                        class="admin-input admin-input-sm w-full"
                                        placeholder="e.g. 16"
                                        data-whmcs-pid
                                    >
                                </td>
                                <td>
                                    <label class="admin-check" style="margin-top:0">
                                        <input
                                            type="checkbox"
                                            name="mappings[{{ $index }}][is_active]"
                                            value="1"
                                            @checked(! empty($row['is_active']))
                                            data-whmcs-active
                                        >
                                        <span>Active</span>
                                    </label>
                                </td>
                                <td class="admin-table-actions">
                                    <button type="button" class="admin-btn-danger" data-whmcs-mapping-remove>Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <template data-whmcs-mapping-template>
                <tr data-whmcs-mapping-row>
                    <td>
                        <input type="text" name="mappings[__INDEX__][plan_slug]" value="" class="admin-input admin-input-sm w-full" placeholder="e.g. cpanel" data-whmcs-plan>
                    </td>
                    <td>
                        <input type="text" name="mappings[__INDEX__][spec_key]" value="" class="admin-input admin-input-sm w-full" placeholder="e.g. business" data-whmcs-spec>
                    </td>
                    <td>
                        <input type="number" min="1" name="mappings[__INDEX__][whmcs_pid]" value="" class="admin-input admin-input-sm w-full" placeholder="e.g. 16" data-whmcs-pid>
                    </td>
                    <td>
                        <label class="admin-check" style="margin-top:0">
                            <input type="checkbox" name="mappings[__INDEX__][is_active]" value="1" checked data-whmcs-active>
                            <span>Active</span>
                        </label>
                    </td>
                    <td class="admin-table-actions">
                        <button type="button" class="admin-btn-danger" data-whmcs-mapping-remove>Remove</button>
                    </td>
                </tr>
            </template>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save WHMCS Settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test Domain Lookup</h2>
                <p class="admin-dash-panel-lede">
                    Step 1 checks API credentials with <code class="rounded bg-blush-soft px-1">GetClients</code>.
                    Step 2 checks domain availability with <code class="rounded bg-blush-soft px-1">DomainWhois</code>.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.whmcs-settings.test-domain') }}" class="admin-edit-grid" data-submit-form>
            @csrf
            <label class="admin-field admin-field-span">
                <span>Domain</span>
                <input type="text" name="domain" value="{{ old('domain', 'google.com') }}" class="admin-input" required>
            </label>
            <label class="admin-field">
                <span>Option</span>
                <select name="domain_option" class="admin-input">
                    <option value="register">Register</option>
                    <option value="transfer">Transfer</option>
                    <option value="owndomain">Already own</option>
                </select>
            </label>
            <div class="admin-field flex items-end">
                <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                    <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                    <span data-submit-label>Run Domain Test</span>
                    <span class="hidden" data-submit-loading>Testing…</span>
                </button>
            </div>
        </form>

        @if (session('domain_test_result'))
            @php($test = session('domain_test_result'))
            <div class="mt-6 rounded-xl border border-border bg-blush-soft/40 px-4 py-3 text-sm">
                <p class="font-semibold text-black">API configured: {{ ($test['configured'] ?? false) ? 'Yes' : 'No' }}</p>
                @if (! empty($test['connection']['message']))
                    <p class="mt-2 {{ ($test['connection']['ok'] ?? false) ? 'text-emerald-700' : 'text-rose' }}">
                        <span class="font-semibold">API connection:</span> {{ $test['connection']['message'] }}
                    </p>
                @endif
                @if (! empty($test['whmcs_error']))
                    <p class="mt-2 text-rose"><span class="font-semibold">WHMCS error:</span> {{ $test['whmcs_error'] }}</p>
                @endif
                @if (! empty($test['validation']['message']))
                    <p class="mt-2"><span class="font-semibold">Validation:</span> {{ $test['validation']['message'] }} ({{ $test['validation']['status'] ?? '—' }})</p>
                @endif
                @if (! empty($test['whois']))
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-white p-4 text-xs text-black">{{ json_encode($test['whois'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </div>
        @endif
    </section>
@endsection
