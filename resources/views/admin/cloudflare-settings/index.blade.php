@extends('layouts.admin')

@section('title', 'Cloudflare Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Cloudflare Settings"
        lede="One-click Lemon Mail DNS apply for domains on Cloudflare (TrekMail records). Token here is the default; you can paste a one-off zone token on an email order when the domain is on a customer’s account."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Cloudflare Settings']]"
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

    <form method="POST" action="{{ route('admin.cloudflare-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">API credentials</h2>
                    <p class="admin-dash-panel-lede">
                        Create an API token with Zone → DNS → Edit (and Zone → Zone → Read).
                        Domains must use Cloudflare nameservers for apply to work.
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
                <span>Enable Cloudflare DNS apply — when off, admin email orders only show the copy checklist.</span>
            </label>

            <div class="admin-edit-grid mt-5">
                <label class="admin-field admin-field-span">
                    <span>API token</span>
                    <textarea
                        name="api_token"
                        rows="3"
                        class="admin-input admin-mono text-sm"
                        placeholder="Cloudflare API token"
                        autocomplete="off"
                    >{{ old('api_token', $settings['api_token']) }}</textarea>
                </label>

                <label class="admin-field admin-field-span">
                    <span>Account ID (optional)</span>
                    <input
                        type="text"
                        name="account_id"
                        value="{{ old('account_id', $settings['account_id']) }}"
                        class="admin-input admin-mono text-sm"
                        placeholder="Only needed if the token can see multiple accounts"
                        autocomplete="off"
                    >
                </label>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save Cloudflare settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test API connection</h2>
                <p class="admin-dash-panel-lede">Verifies the saved token with Cloudflare (no DNS changes).</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.cloudflare-settings.test-connection') }}" data-submit-form>
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
