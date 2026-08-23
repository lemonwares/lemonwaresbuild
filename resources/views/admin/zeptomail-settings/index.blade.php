@extends('layouts.admin')

@section('title', 'ZeptoMail Settings — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Integrations</p>
        <h1 class="heading">ZeptoMail Settings</h1>
        <p class="mt-3 lede">
            Transactional mail for password resets and account notices. From address, from name,
            logo, and API values here override <code class="rounded bg-blush-soft px-1">.env</code> fallbacks.
        </p>
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </p>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.zeptomail-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-black">API credentials</h2>
                    <p class="mt-1 text-sm text-on-blush/65">
                        Copy the Send Mail Token from ZeptoMail → Agent → SMTP/API.
                        The From address must be on a domain verified in that Agent.
                    </p>
                </div>
                @if ($is_configured)
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-800">
                        Ready to send
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-amber-800">
                        Not configured
                    </span>
                @endif
            </div>

            <div class="mt-5 space-y-4">
                <label class="inline-flex items-start gap-3 rounded-xl border border-border bg-blush-soft/40 px-4 py-3">
                    <input type="hidden" name="enabled" value="0">
                    <input
                        type="checkbox"
                        name="enabled"
                        value="1"
                        class="mt-1 rounded border-border text-rose focus:ring-rose/20"
                        @checked(old('enabled', $settings['enabled']))
                    />
                    <span>
                        <span class="block text-sm font-semibold text-black">Send mail through ZeptoMail</span>
                        <span class="mt-1 block text-xs text-on-blush/65">When off, the app keeps using the default mailer (usually log/smtp from .env).</span>
                    </span>
                </label>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Send Mail Token</label>
                    <textarea
                        name="token"
                        rows="3"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5 font-mono text-sm"
                        placeholder="Paste Send Mail Token only (or full Zoho-enczapikey … value)"
                        autocomplete="off"
                    >{{ old('token', $settings['token']) }}</textarea>
                    <p class="mt-2 text-xs text-on-blush/60">
                        Paste the token from ZeptoMail → Agent → SMTP/API. If you copy the full
                        <code class="rounded bg-blush-soft px-1">Zoho-enczapikey …</code> line, we strip the prefix automatically.
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API endpoint</label>
                    <input
                        type="url"
                        name="endpoint"
                        value="{{ old('endpoint', $settings['endpoint']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="https://api.zeptomail.com/v1.1/email"
                        autocomplete="off"
                    >
                    <p class="mt-2 text-xs text-on-blush/60">EU accounts usually use <code class="rounded bg-blush-soft px-1">https://api.zeptomail.eu/v1.1/email</code>.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">From address</label>
                        <input
                            type="email"
                            name="from_address"
                            value="{{ old('from_address', $settings['from_address']) }}"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            placeholder="noreply@lemonwares.com"
                            autocomplete="off"
                        >
                        <p class="mt-2 text-xs text-on-blush/60">Must be verified on your ZeptoMail agent (e.g. noreply@ or mails@).</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">From name</label>
                        <input
                            type="text"
                            name="from_name"
                            value="{{ old('from_name', $settings['from_name']) }}"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            placeholder="Lemonwares"
                            autocomplete="off"
                        >
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Email logo URL</label>
                    <input
                        type="url"
                        name="logo_url"
                        value="{{ old('logo_url', $settings['logo_url']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="https://gadgets.lemonwares.com/lemonwareslogo.png"
                        autocomplete="off"
                    >
                    <p class="mt-2 text-xs text-on-blush/60">
                        Public HTTPS image shown in password-reset and account emails.
                        Leave blank to use the default Lemonwares logo on this site.
                    </p>
                    @if ($logo_preview_url)
                        <div class="mt-4 flex items-center gap-4 rounded-xl border border-border bg-blush-soft/40 px-4 py-3">
                            <img
                                src="{{ $logo_preview_url }}"
                                alt="Email logo preview"
                                class="h-10 w-auto max-w-[180px] object-contain"
                            >
                            <span class="text-xs text-on-blush/65 break-all">{{ $logo_preview_url }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="btn btn-primary">Save ZeptoMail settings</button>
        </div>
    </form>

    <section class="mt-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Test API connection</h2>
        <p class="mt-2 text-sm text-on-blush/65">Checks that ZeptoMail accepts your saved send-mail token (no email is delivered).</p>

        <form method="POST" action="{{ route('admin.zeptomail-settings.test-connection') }}" class="mt-5">
            @csrf
            <button type="submit" class="btn btn-ghost">Run connection test</button>
        </form>

        @if (session('connection_test_result'))
            @php($test = session('connection_test_result'))
            <div class="mt-6 rounded-2xl border border-border bg-blush-soft p-4 text-sm">
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

    <section class="mt-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Send a test email</h2>
        <p class="mt-2 text-sm text-on-blush/65">Delivers a real message so you can confirm inbox arrival before customers use forgot password.</p>

        <form method="POST" action="{{ route('admin.zeptomail-settings.send-test') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Recipient</label>
                <input
                    type="email"
                    name="test_email"
                    value="{{ old('test_email', config('site.admin_email', env('ADMIN_EMAIL', ''))) }}"
                    required
                    class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                    placeholder="you@lemonwares.com"
                >
            </div>
            <button type="submit" class="btn btn-primary">Send test email</button>
        </form>

        @if (session('send_test_result'))
            @php($send = session('send_test_result'))
            <div class="mt-6 rounded-2xl border border-border bg-blush-soft p-4 text-sm">
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
