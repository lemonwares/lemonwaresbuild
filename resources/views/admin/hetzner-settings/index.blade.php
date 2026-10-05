@extends('layouts.admin')

@section('title', 'Hetzner Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Hetzner Settings"
        lede="Connects the admin panel to your Hetzner Cloud project so VPS orders can be powered on, rebooted, resized and snapshotted from the hosting request page."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Hetzner Settings']]"
        class="mb-5"
    />

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.hetzner-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">API token</h2>
                    <p class="admin-dash-panel-lede">
                        In Hetzner Cloud Console open the project → Security → API tokens → Generate (Read &amp; Write).
                        Leave empty to fall back to HETZNER_API_TOKEN in the server's .env file.
                    </p>
                </div>
                @if ($isConfigured)
                    <span class="admin-pill is-ok">Ready</span>
                @else
                    <span class="admin-pill is-info">Not configured</span>
                @endif
            </div>

            <label class="admin-field admin-field-span">
                <span>API token</span>
                <input type="password" name="api_token" value="" placeholder="{{ filled($apiToken) ? '•••••••• saved, leave blank to keep' : '' }}" class="admin-input admin-mono text-sm" autocomplete="off">
            </label>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save Hetzner settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test API connection</h2>
                <p class="admin-dash-panel-lede">Lists servers with the saved token. Nothing is changed.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.hetzner-settings.test-connection') }}" data-submit-form>
            @csrf
            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Run connection test</span>
                <span class="hidden" data-submit-loading>Testing…</span>
            </button>
        </form>

        @if (session('connection_test_result'))
            @php($test = session('connection_test_result'))
            <p @class(['mt-4 text-sm font-semibold', 'text-emerald-700' => $test['ok'] ?? false, 'text-rose' => ! ($test['ok'] ?? false)])>
                {{ $test['message'] ?? 'Connection test completed.' }}
            </p>
        @endif
    </section>
@endsection
