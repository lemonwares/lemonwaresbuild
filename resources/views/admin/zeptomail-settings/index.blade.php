@extends('layouts.admin')

@section('title', 'ZeptoMail Settings — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="ZeptoMail Settings"
        lede="Transactional mail for password resets, account notices, and the public contact form. From address, contact inbox, logo, and API values here override .env fallbacks."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'ZeptoMail Settings']]"
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

    <form method="POST" action="{{ route('admin.zeptomail-settings.update') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">API credentials</h2>
                    <p class="admin-dash-panel-lede">
                        Copy the Send Mail Token from ZeptoMail → Agent → SMTP/API.
                        The From address must be on a domain verified in that Agent.
                    </p>
                </div>
                @if ($is_configured)
                    <span class="admin-pill is-ok">Ready to send</span>
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
                <span>Send mail through ZeptoMail — when off, the app keeps using the default mailer (usually log/smtp from .env).</span>
            </label>

            <div class="admin-edit-grid mt-5">
                <label class="admin-field admin-field-span">
                    <span>Send Mail Token</span>
                    <textarea
                        name="token"
                        rows="3"
                        class="admin-input admin-mono text-sm"
                        placeholder="Paste Send Mail Token only (or full Zoho-enczapikey … value)"
                        autocomplete="off"
                    >{{ old('token', $settings['token']) }}</textarea>
                    <p class="admin-muted text-xs">
                        Paste the token from ZeptoMail → Agent → SMTP/API. If you copy the full
                        <code class="rounded bg-blush-soft px-1">Zoho-enczapikey …</code> line, we strip the prefix automatically.
                    </p>
                </label>

                <label class="admin-field admin-field-span">
                    <span>API endpoint</span>
                    <input
                        type="url"
                        name="endpoint"
                        value="{{ old('endpoint', $settings['endpoint']) }}"
                        class="admin-input"
                        placeholder="https://api.zeptomail.com/v1.1/email"
                        autocomplete="off"
                    >
                    <p class="admin-muted text-xs">EU accounts usually use <code class="rounded bg-blush-soft px-1">https://api.zeptomail.eu/v1.1/email</code>.</p>
                </label>

                <label class="admin-field">
                    <span>From address</span>
                    <input
                        type="email"
                        name="from_address"
                        value="{{ old('from_address', $settings['from_address']) }}"
                        class="admin-input"
                        placeholder="noreply@lemonwares.com"
                        autocomplete="off"
                    >
                    <p class="admin-muted text-xs">Must be verified on your ZeptoMail agent (e.g. noreply@ or mails@).</p>
                </label>
                <label class="admin-field">
                    <span>From name</span>
                    <input
                        type="text"
                        name="from_name"
                        value="{{ old('from_name', $settings['from_name']) }}"
                        class="admin-input"
                        placeholder="LemonWares"
                        autocomplete="off"
                    >
                </label>

                <label class="admin-field admin-field-span">
                    <span>Email logo URL</span>
                    <input
                        type="url"
                        name="logo_url"
                        value="{{ old('logo_url', $settings['logo_url']) }}"
                        class="admin-input"
                        placeholder="https://gadgets.lemonwares.com/lemonwareslogo.png"
                        autocomplete="off"
                    >
                    <p class="admin-muted text-xs">
                        Public HTTPS image shown in password-reset and account emails.
                        Leave blank to use the default LemonWares logo on this site.
                    </p>
                    @if ($logo_preview_url)
                        <div class="mt-3 flex items-center gap-4 rounded-xl border border-border bg-blush-soft/40 px-4 py-3">
                            <img
                                src="{{ $logo_preview_url }}"
                                alt="Email logo preview"
                                class="h-10 w-auto max-w-[180px] object-contain"
                            >
                            <span class="admin-muted break-all text-xs">{{ $logo_preview_url }}</span>
                        </div>
                    @endif
                </label>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">Contact form inbox</h2>
                    <p class="admin-dash-panel-lede">
                        Submissions from <a href="{{ route('contact') }}" class="text-rose hover:underline" target="_blank" rel="noopener noreferrer">/contact</a>
                        are delivered here. Visitors still receive an automatic acknowledgement from the From address above.
                    </p>
                </div>
            </div>

            <label class="admin-field">
                <span>Inbox address</span>
                <input
                    type="email"
                    name="contact_form_inbox"
                    value="{{ old('contact_form_inbox', $settings['contact_form_inbox']) }}"
                    class="admin-input"
                    placeholder="{{ config('site.email') }}"
                    autocomplete="off"
                >
                <p class="admin-muted text-xs">
                    Leave blank to use the <code class="rounded bg-blush-soft px-1">CONTACT_FORM_TO</code> env value or
                    <code class="rounded bg-blush-soft px-1">{{ config('site.email') }}</code> fallback.
                </p>
            </label>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save ZeptoMail settings</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Test API connection</h2>
                <p class="admin-dash-panel-lede">Checks that ZeptoMail accepts your saved send-mail token (no email is delivered).</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.zeptomail-settings.test-connection') }}" data-submit-form>
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

    <section class="admin-panel mt-6">
        <div class="admin-panel-toolbar compact">
            <div>
                <h2 class="admin-dash-panel-title">Send a test email</h2>
                <p class="admin-dash-panel-lede">Delivers a real message so you can confirm inbox arrival before customers use forgot password.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.zeptomail-settings.send-test') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end" data-submit-form>
            @csrf
            <label class="admin-field flex-1">
                <span>Recipient</span>
                <input
                    type="email"
                    name="test_email"
                    value="{{ old('test_email', config('site.admin_email', env('ADMIN_EMAIL', ''))) }}"
                    required
                    class="admin-input"
                    placeholder="you@lemonwares.com"
                >
            </label>
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Send test email</span>
                <span class="hidden" data-submit-loading>Sending…</span>
            </button>
        </form>

        @if (session('send_test_result'))
            @php($send = session('send_test_result'))
            <div class="mt-6 rounded-xl border border-border bg-blush-soft/40 px-4 py-3 text-sm">
                <p @class([
                    'font-semibold',
                    'text-emerald-700' => $send['ok'] ?? false,
                    'text-rose' => ! ($send['ok'] ?? false),
                ])>
                    {{ $send['message'] ?? 'Send test completed.' }}
                </p>
            </div>
        @endif
    </section>
@endsection
