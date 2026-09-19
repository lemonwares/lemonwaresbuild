@extends('layouts.admin')

@section('title', 'Cloudinary Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Cloudinary Settings"
        lede="Store media covers and photos on Cloudinary so uploads survive cPanel redeploys. Values here override .env fallbacks."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Cloudinary Settings']]"
        class="mb-5"
    />

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.cloudinary-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">API credentials</h2>
                    <p class="admin-dash-panel-lede">
                        From the Cloudinary dashboard → Account details. Used for case studies, blog, team, and campaign images.
                    </p>
                </div>
                @if ($is_configured)
                    <span class="admin-pill is-ok">Ready</span>
                @else
                    <span class="admin-pill is-info">Not configured</span>
                @endif
            </div>

            <input type="hidden" name="enabled" value="0">
            <label class="admin-check" style="margin-top:0">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    @checked(old('enabled', $settings['enabled']))
                />
                <span>Enable Cloudinary uploads — when off, images are stored on the local public disk.</span>
            </label>

            <div class="admin-edit-grid mt-5">
                <label class="admin-field">
                    <span>Cloud name</span>
                    <input
                        type="text"
                        name="cloud_name"
                        value="{{ old('cloud_name', $settings['cloud_name']) }}"
                        class="admin-input admin-mono text-sm"
                        placeholder="your_cloud_name"
                        autocomplete="off"
                    >
                </label>

                <label class="admin-field">
                    <span>API key</span>
                    <input
                        type="text"
                        name="api_key"
                        value="{{ old('api_key', $settings['api_key']) }}"
                        class="admin-input admin-mono text-sm"
                        placeholder="123456789012345"
                        autocomplete="off"
                    >
                </label>

                <label class="admin-field admin-field-span">
                    <span>API secret</span>
                    <input
                        type="text"
                        name="api_secret"
                        value="{{ old('api_secret', $settings['api_secret']) }}"
                        class="admin-input admin-mono text-sm"
                        placeholder="your_api_secret"
                        autocomplete="off"
                    >
                </label>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save Cloudinary settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test API connection</h2>
                <p class="admin-dash-panel-lede">Pings Cloudinary with the saved credentials (no upload).</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.cloudinary-settings.test-connection') }}" data-submit-form>
            @csrf
            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Run connection test</span>
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
                </p>
            </div>
        @endif
    </section>
@endsection
