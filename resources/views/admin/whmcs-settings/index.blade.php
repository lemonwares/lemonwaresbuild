@extends('layouts.admin')

@section('title', 'WHMCS Settings — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Integrations</p>
        <h1 class="heading">WHMCS Settings</h1>
        <p class="lede mt-3">Manage WHMCS connection details and map each hosting spec to its WHMCS product ID.</p>
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('admin.whmcs-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6">
            <h2 class="text-lg font-bold text-black">Connection</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">WHMCS Base URL</label>
                    <input type="url" name="base_url" value="{{ old('base_url', $settings['base_url']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Client Login URL</label>
                    <input type="url" name="client_login_url" value="{{ old('client_login_url', $settings['client_login_url']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Order Route</label>
                    <input type="text" name="order_route" value="{{ old('order_route', $settings['order_route']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Default Payment Method</label>
                    <input type="text" name="payment_method" value="{{ old('payment_method', $settings['payment_method'] ?: 'banktransfer') }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required placeholder="banktransfer">
                    <p class="mt-2 text-xs text-on-blush/60">WHMCS gateway system name used when creating orders via API (e.g. <code class="rounded bg-blush-soft px-1">banktransfer</code>, <code class="rounded bg-blush-soft px-1">paypal</code>).</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Identifier</label>
                    <input type="text" name="api_identifier" value="{{ old('api_identifier', $settings['api_identifier']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Secret</label>
                    <input type="text" name="api_secret" value="{{ old('api_secret', $settings['api_secret']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Access Key (optional)</label>
                    <input type="text" name="api_access_key" value="{{ old('api_access_key', $settings['api_access_key']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" placeholder="Only if enabled in WHMCS General Settings > Security">
                    <p class="mt-2 text-xs text-on-blush/60">Required only when WHMCS has a global API Access Key configured. This also lets API calls work from any IP when combined with WHMCS <code class="rounded bg-blush-soft px-1">$api_access_key</code> in configuration.php.</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="inline-flex items-start gap-3 rounded-xl border border-border bg-blush-soft/40 px-4 py-3">
                        <input
                            type="checkbox"
                            name="defer_payment_redirect"
                            value="1"
                            class="mt-1 rounded border-border text-rose focus:ring-rose/20"
                            @checked(\App\Support\WhmcsSettings::deferPaymentRedirect())
                        />
                        <span>
                            <span class="block text-sm font-semibold text-black">Test mode: skip WHMCS payment redirect</span>
                            <span class="mt-1 block text-xs text-on-blush/65">Create the WHMCS client and pending order via API, then stay on Lemonwares instead of sending the customer to WHMCS checkout or invoice payment. Enabled automatically when <code class="rounded bg-white px-1">APP_ENV=local</code> unless overridden here.</span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        @foreach ($plans as $planSlug => $plan)
            @php
                $specs = $plan['specifications'] ?? [];
            @endphp
            <section class="rounded-3xl border border-border bg-white p-6">
                <h2 class="text-lg font-bold text-black">{{ $plan['title'] ?? $planSlug }}</h2>
                <p class="mt-1 text-sm text-on-blush/65">{{ strtoupper($planSlug) }} spec to WHMCS PID mapping.</p>

                <div class="mt-4 space-y-3">
                    @foreach ($specs as $spec)
                        @php
                            $specKey = strtolower((string) ($spec['key'] ?? ''));
                            $mapKey = strtolower($planSlug . ':' . $specKey);
                            $mapping = $mappings->get($mapKey);
                        @endphp
                        <div class="grid gap-3 rounded-2xl border border-border p-4 sm:grid-cols-12 sm:items-center">
                            <div class="sm:col-span-4">
                                <p class="font-semibold text-black">{{ $spec['label'] ?? $specKey }}</p>
                                <p class="text-xs text-on-blush/60">{{ $specKey }}</p>
                                <input type="hidden" name="mappings[{{ $planSlug }}_{{ $specKey }}][plan_slug]" value="{{ $planSlug }}">
                                <input type="hidden" name="mappings[{{ $planSlug }}_{{ $specKey }}][spec_key]" value="{{ $specKey }}">
                            </div>
                            <div class="sm:col-span-4">
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">WHMCS PID</label>
                                <input
                                    type="number"
                                    min="1"
                                    name="mappings[{{ $planSlug }}_{{ $specKey }}][whmcs_pid]"
                                    value="{{ old("mappings.{$planSlug}_{$specKey}.whmcs_pid", $mapping?->whmcs_pid) }}"
                                    class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                                    placeholder="e.g. 16"
                                >
                            </div>
                            <div class="sm:col-span-4">
                                <label class="inline-flex items-center gap-2 text-sm font-semibold text-black">
                                    <input
                                        type="checkbox"
                                        name="mappings[{{ $planSlug }}_{{ $specKey }}][is_active]"
                                        value="1"
                                        class="size-4 rounded border-border text-rose focus:ring-rose"
                                        @checked(old("mappings.{$planSlug}_{$specKey}.is_active", $mapping?->is_active))
                                    >
                                    Active mapping
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary">Save WHMCS Settings</button>
        </div>
    </form>

    <section class="mt-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Test Domain Lookup</h2>
        <p class="mt-2 text-sm text-on-blush/65">
            Step 1 checks API credentials with <code class="rounded bg-blush-soft px-1.5 py-0.5">GetClients</code>.
            Step 2 checks domain availability with <code class="rounded bg-blush-soft px-1.5 py-0.5">DomainWhois</code>.
            Your API role must allow domain lookups, and WHMCS WHOIS servers must be configured for the TLD.
        </p>

        <form method="POST" action="{{ route('admin.whmcs-settings.test-domain') }}" class="mt-5 grid gap-4 sm:grid-cols-3">
            @csrf
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Domain</label>
                <input type="text" name="domain" value="{{ old('domain', 'google.com') }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Option</label>
                <select name="domain_option" class="footer-input w-full rounded-xl border border-border px-3 py-2.5">
                    <option value="register">Register</option>
                    <option value="transfer">Transfer</option>
                    <option value="owndomain">Already own</option>
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="btn btn-ghost">Run Domain Test</button>
            </div>
        </form>

        @if (session('domain_test_result'))
            @php($test = session('domain_test_result'))
            <div class="mt-6 rounded-2xl border border-border bg-blush-soft p-4 text-sm">
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
