@extends('layouts.admin')

@section('title', 'Email Provider Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Email Provider Settings"
        lede="Configure Lemon Mail (powered by TrekMail) API credentials for auto-provisioning, plus partner portal details for Titan, Google Workspace, and Microsoft 365 manual fulfilment. Admin values override .env fallbacks."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Email Provider Settings']]"
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

    <form
        method="POST"
        action="{{ route('admin.email-provider-settings.update') }}"
        class="admin-page-stack"
        enctype="multipart/form-data"
        data-submit-form
    >
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">Lemon Mail · TrekMail</h2>
                    <p class="admin-dash-panel-lede">Webmail and optional API credentials used after payment. Mailboxes are provisioned on TrekMail.</p>
                </div>
                @if ($is_configured)
                    <span class="admin-pill is-ok">Token configured</span>
                @else
                    <span class="admin-pill is-info">Not configured</span>
                @endif
            </div>

            <div class="mb-5 rounded-xl border border-border bg-blush-soft/40 px-4 py-3 text-sm text-on-blush/80">
                <p class="font-semibold text-black">TrekMail setup</p>
                <p class="mt-1">
                    Set the API token and base URL from your TrekMail account
                    (<code class="rounded bg-white px-1">https://trekmail.net/api/v1</code>),
                    and the customer webmail URL (default
                    <a href="https://mail.trekmail.net" target="_blank" rel="noopener noreferrer" class="font-semibold text-rose hover:underline">mail.trekmail.net</a>).
                    DNS defaults use <code class="rounded bg-white px-1">mx.trekmail.net</code> /
                    <code class="rounded bg-white px-1">_spf.trekmail.net</code>.
                </p>
                <p class="mt-2 text-xs">
                    Lemon Mail plans provision automatically when TrekMail is configured. Partner providers stay manual.
                </p>
            </div>

            <div class="admin-edit-grid">
                <label class="admin-field admin-field-span">
                    <span>API Token</span>
                    <input
                        type="text"
                        name="trekmail_token"
                        value="{{ old('trekmail_token', $trekmail['token']) }}"
                        class="admin-input"
                        placeholder="TrekMail API token"
                        autocomplete="off"
                    >
                </label>
                <label class="admin-field">
                    <span>API Base URL</span>
                    <input
                        type="url"
                        name="trekmail_base_url"
                        value="{{ old('trekmail_base_url', $trekmail['base_url']) }}"
                        class="admin-input"
                        placeholder="https://trekmail.net/api/v1"
                        autocomplete="off"
                    >
                </label>
                <label class="admin-field">
                    <span>Webmail URL</span>
                    <input
                        type="url"
                        name="trekmail_webmail_url"
                        value="{{ old('trekmail_webmail_url', $trekmail['webmail_url']) }}"
                        class="admin-input"
                        placeholder="https://mail.trekmail.net"
                        autocomplete="off"
                    >
                </label>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">Lemon Mail · Invite branding</h2>
                    <p class="admin-dash-panel-lede">
                        Optional branding applied when TrekMail creates mailboxes / sends invites.
                    </p>
                </div>
            </div>

            <input type="hidden" name="trekmail_branding_enabled" value="0">
            <label class="admin-check" style="margin-top:0">
                <input type="checkbox" name="trekmail_branding_enabled" value="1" @checked(old('trekmail_branding_enabled', $branding['enabled'] ?? true))>
                <span>Apply branding when provisioning — runs automatically after a domain is created, before mailbox invites are sent.</span>
            </label>

            <div class="admin-edit-grid mt-5">
                <label class="admin-field">
                    <span>Brand name</span>
                    <input
                        type="text"
                        name="trekmail_brand_name"
                        value="{{ old('trekmail_brand_name', $branding['name']) }}"
                        class="admin-input"
                        placeholder="LemonWares"
                    >
                </label>
                <label class="admin-field">
                    <span>Support email</span>
                    <input
                        type="email"
                        name="trekmail_brand_support_email"
                        value="{{ old('trekmail_brand_support_email', $branding['support_email']) }}"
                        class="admin-input"
                        placeholder="support@lemonwares.com"
                    >
                </label>
                <label class="admin-field">
                    <span>Primary color</span>
                    <input
                        type="text"
                        name="trekmail_brand_primary_color"
                        value="{{ old('trekmail_brand_primary_color', $branding['primary_color']) }}"
                        class="admin-input"
                        placeholder="#e04545"
                    >
                </label>
                <label class="admin-field">
                    <span>Accent color</span>
                    <input
                        type="text"
                        name="trekmail_brand_accent_color"
                        value="{{ old('trekmail_brand_accent_color', $branding['accent_color']) }}"
                        class="admin-input"
                        placeholder="#ffeded"
                    >
                </label>
                <label class="admin-field">
                    <span>Support / help URL</span>
                    <input
                        type="url"
                        name="trekmail_brand_support_url"
                        value="{{ old('trekmail_brand_support_url', $branding['support_url']) }}"
                        class="admin-input"
                        placeholder="https://lemonwares.com"
                    >
                </label>
                <label class="admin-field">
                    <span>Transactional sender (optional)</span>
                    <input
                        type="email"
                        name="trekmail_brand_sender_email"
                        value="{{ old('trekmail_brand_sender_email', $branding['sender_email']) }}"
                        class="admin-input"
                        placeholder="noreply@your-verified-domain.com"
                    >
                    <p class="admin-muted text-xs">Must be on a TrekMail domain with verified DKIM, or TrekMail will reject it.</p>
                </label>
                <label class="admin-field admin-field-span">
                    <span>Logo (PNG or JPG, max 1MB)</span>
                    <input
                        type="file"
                        name="trekmail_brand_logo"
                        accept="image/png,image/jpeg"
                        class="admin-input"
                    >
                    <p class="admin-muted text-xs">
                        @if ($branding['has_logo'] ?? false)
                            Custom logo uploaded. Leave empty to keep it. Otherwise we fall back to <code class="rounded bg-blush-soft px-1">public/lemonwareslogo.webp</code> (converted to PNG).
                        @else
                            Optional. If empty, we use <code class="rounded bg-blush-soft px-1">public/lemonwareslogo.webp</code> when GD can convert it.
                        @endif
                    </p>
                </label>
            </div>
        </section>

        @foreach ($manualProviders as $provider => $pack)
            @php($settings = $pack['settings'])
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <div>
                        <h2 class="admin-dash-panel-title">{{ $pack['label'] }}</h2>
                        <p class="admin-dash-panel-lede">
                            No auto-provisioning yet. Store partner portal login details and API keys here for the fulfilment team. Orders still move through the Email Orders queue.
                        </p>
                    </div>
                    <span class="admin-pill is-info">Manual fulfilment</span>
                </div>

                <div class="admin-edit-grid">
                    <label class="admin-field">
                        <span>Partner portal URL</span>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][portal_url]"
                            value="{{ old("providers.{$provider}.portal_url", $settings['portal_url']) }}"
                            class="admin-input"
                            placeholder="https://..."
                            autocomplete="off"
                        >
                    </label>
                    <label class="admin-field">
                        <span>Account / reseller ref</span>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][account_ref]"
                            value="{{ old("providers.{$provider}.account_ref", $settings['account_ref']) }}"
                            class="admin-input"
                            placeholder="Reseller ID or login email"
                            autocomplete="off"
                        >
                    </label>
                    <label class="admin-field">
                        <span>API key (optional)</span>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][api_key]"
                            value="{{ old("providers.{$provider}.api_key", $settings['api_key']) }}"
                            class="admin-input"
                            autocomplete="off"
                        >
                    </label>
                    <label class="admin-field">
                        <span>API secret (optional)</span>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][api_secret]"
                            value="{{ old("providers.{$provider}.api_secret", $settings['api_secret']) }}"
                            class="admin-input"
                            autocomplete="off"
                        >
                    </label>
                    <label class="admin-field admin-field-span">
                        <span>Internal notes</span>
                        <textarea
                            name="providers[{{ $provider }}][notes]"
                            rows="3"
                            class="admin-input"
                            placeholder="How we provision this provider, contacts, SKUs…"
                        >{{ old("providers.{$provider}.notes", $settings['notes']) }}</textarea>
                    </label>
                </div>
            </section>
        @endforeach

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save Email Provider Settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test TrekMail Connection</h2>
                <p class="admin-dash-panel-lede">Calls the TrekMail domains endpoint with your saved API token.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.email-provider-settings.test-connection') }}" data-submit-form>
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
                </p>
            </div>
        @endif
    </section>
@endsection
