@extends('layouts.admin')

@section('title', 'Flutterwave Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Flutterwave Settings"
        lede="Manage Flutterwave keys for hosting, VPS, and Lemon Mail checkout. Values here override .env fallbacks."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Flutterwave Settings']]"
        class="mb-5"
    />

    <form method="POST" action="{{ route('admin.flutterwave-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">API Credentials</h2>
                    <p class="admin-dash-panel-lede">Use Flutterwave test keys locally, then swap to live keys in production.</p>
                </div>
                @if ($is_configured)
                    <span @class([
                        'admin-pill',
                        'is-info' => $is_test_mode,
                        'is-ok' => ! $is_test_mode,
                    ])>
                        {{ $is_test_mode ? 'Test mode keys' : 'Live keys detected' }}
                    </span>
                @endif
            </div>

            <label class="admin-check" style="margin-top:0">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    @checked(old('enabled', $settings['enabled']))
                />
                <span>Enable Flutterwave checkout — when disabled, orders are saved but customers are not redirected to Flutterwave.</span>
            </label>

            <div class="admin-edit-grid mt-5">
                <label class="admin-field admin-field-span">
                    <span>Public Key</span>
                    <input
                        type="text"
                        name="public_key"
                        value="{{ old('public_key', $settings['public_key']) }}"
                        class="admin-input"
                        placeholder="FLWPUBK_TEST-..."
                        autocomplete="off"
                    >
                </label>

                <label class="admin-field admin-field-span">
                    <span>Secret Key</span>
                    <input
                        type="text"
                        name="secret_key"
                        value="{{ old('secret_key', $settings['secret_key']) }}"
                        class="admin-input"
                        required
                        placeholder="FLWSECK_TEST-..."
                        autocomplete="off"
                    >
                </label>

                <label class="admin-field admin-field-span">
                    <span>Webhook Secret Hash</span>
                    <input
                        type="text"
                        name="secret_hash"
                        value="{{ old('secret_hash', $settings['secret_hash']) }}"
                        class="admin-input"
                        placeholder="Same hash configured in Flutterwave dashboard"
                        autocomplete="off"
                    >
                    <p class="admin-muted text-xs">Required for server-to-server webhook verification. Set the same value in Flutterwave → Settings → Webhooks.</p>
                </label>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">Webhook URL</h2>
                    <p class="admin-dash-panel-lede">Register this URL in your Flutterwave dashboard so paid orders sync to WHMCS automatically.</p>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-blush-soft/40 px-4 py-3">
                <code class="admin-mono break-all text-sm text-black">{{ $webhook_url }}</code>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save Flutterwave Settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test API Connection</h2>
                <p class="admin-dash-panel-lede">Calls Flutterwave’s banks endpoint with your saved secret key.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.flutterwave-settings.test-connection') }}" data-submit-form>
            @csrf
            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Run Connection Test</span>
                <span class="hidden" data-submit-loading>Testing…</span>
            </button>
        </form>

        @if (session('connection_test_result'))
            @php($test = session('connection_test_result'))
            <div class="mt-6 rounded-xl border border-border bg-blush-soft/40 px-4 py-3 text-sm">
                <p @class([
                    'font-semibold',
                    'text-emerald-700' => $test['ok'] ?? false,
                    'text-rose' => ! ($test['ok'] ?? false),
                ])>
                    {{ $test['message'] ?? 'Connection test completed.' }}
                    @if (! empty($test['mode']))
                        <span class="ml-2 text-xs uppercase tracking-widest">({{ $test['mode'] }})</span>
                    @endif
                </p>
            </div>
        @endif
    </section>
@endsection
