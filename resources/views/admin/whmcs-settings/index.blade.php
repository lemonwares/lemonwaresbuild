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
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Identifier</label>
                    <input type="text" name="api_identifier" value="{{ old('api_identifier', $settings['api_identifier']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Secret</label>
                    <input type="text" name="api_secret" value="{{ old('api_secret', $settings['api_secret']) }}" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" required>
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
@endsection
