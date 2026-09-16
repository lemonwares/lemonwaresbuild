@extends('layouts.admin')

@section('title', 'Email Provider Settings — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Integrations</p>
        <h1 class="heading">Email Provider Settings</h1>
        <p class="mt-3 lede">
            Configure Lemon Mail (MXRoute) DNS defaults and optional legacy API credentials for auto-provisioning, plus partner portal details for Titan, Google Workspace, and Microsoft 365 manual fulfilment.
            Admin values override <code class="rounded bg-blush-soft px-1">.env</code> fallbacks.
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

    <form method="POST" action="{{ route('admin.email-provider-settings.update') }}" class="space-y-6" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-black">Lemon Mail · MXRoute</h2>
                    <p class="mt-1 text-sm text-on-blush/65">Webmail and optional API credentials used after payment. Mailboxes are provisioned on MXRoute.</p>
                </div>
                @if ($is_configured)
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-800">
                        Token configured
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-amber-800">
                        Not configured
                    </span>
                @endif
            </div>

            <div class="mt-5 rounded-2xl border border-border bg-blush-soft/50 px-4 py-3 text-sm text-on-blush/80">
                <p class="font-semibold text-black">MXRoute setup</p>
                <p class="mt-1">
                    Set the customer webmail URL to
                    <a href="https://webmail.mxroute.com" target="_blank" rel="noopener noreferrer" class="font-semibold text-rose hover:underline">webmail.mxroute.com</a>
                    and point DNS to your assigned <code class="rounded bg-white px-1">*.mxrouting.net</code> MX hosts via
                    <code class="rounded bg-white px-1">MXROUTE_MX_HOST</code> /
                    <code class="rounded bg-white px-1">MXROUTE_MX_RELAY</code> in <code class="rounded bg-white px-1">.env</code>.
                </p>
                <p class="mt-2 text-xs">
                    API token / base URL fields below are optional legacy hooks. Prefer manual MXRoute fulfilment unless you still run an automated provisioner.
                </p>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Token</label>
                    <input
                        type="text"
                        name="trekmail_token"
                        value="{{ old('trekmail_token', $trekmail['token']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="Optional API token"
                        autocomplete="off"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API Base URL</label>
                    <input
                        type="url"
                        name="trekmail_base_url"
                        value="{{ old('trekmail_base_url', $trekmail['base_url']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="Optional — leave blank for manual MXRoute"
                        autocomplete="off"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Webmail URL</label>
                    <input
                        type="url"
                        name="trekmail_webmail_url"
                        value="{{ old('trekmail_webmail_url', $trekmail['webmail_url']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="https://webmail.mxroute.com"
                        autocomplete="off"
                    >
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-border bg-white p-6">
            <h2 class="text-lg font-bold text-black">Lemon Mail · Invite branding</h2>
            <p class="mt-1 text-sm text-on-blush/65">
                Optional branding applied when an automated provisioner creates mailboxes. For MXRoute DirectAdmin setups, brand the panel or welcome email separately.
            </p>

            <label class="mt-5 flex items-start gap-3 text-sm text-black">
                <input type="hidden" name="trekmail_branding_enabled" value="0">
                <input type="checkbox" name="trekmail_branding_enabled" value="1" class="mt-0.5 size-4 rounded border-border text-rose focus:ring-rose" @checked(old('trekmail_branding_enabled', $branding['enabled'] ?? true))>
                <span>
                    <span class="font-semibold">Apply branding when provisioning</span>
                    <span class="mt-1 block text-on-blush/65">Runs automatically after a domain is created, before mailbox invites are sent.</span>
                </span>
            </label>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Brand name</label>
                    <input
                        type="text"
                        name="trekmail_brand_name"
                        value="{{ old('trekmail_brand_name', $branding['name']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="Lemonwares"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Support email</label>
                    <input
                        type="email"
                        name="trekmail_brand_support_email"
                        value="{{ old('trekmail_brand_support_email', $branding['support_email']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="support@lemonwares.com"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Primary color</label>
                    <input
                        type="text"
                        name="trekmail_brand_primary_color"
                        value="{{ old('trekmail_brand_primary_color', $branding['primary_color']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="#e04545"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Accent color</label>
                    <input
                        type="text"
                        name="trekmail_brand_accent_color"
                        value="{{ old('trekmail_brand_accent_color', $branding['accent_color']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="#ffeded"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Support / help URL</label>
                    <input
                        type="url"
                        name="trekmail_brand_support_url"
                        value="{{ old('trekmail_brand_support_url', $branding['support_url']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="https://lemonwares.com"
                    >
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Transactional sender (optional)</label>
                    <input
                        type="email"
                        name="trekmail_brand_sender_email"
                        value="{{ old('trekmail_brand_sender_email', $branding['sender_email']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="noreply@your-verified-domain.com"
                    >
                    <p class="mt-1 text-xs text-on-blush/55">Must be on a TrekMail domain with verified DKIM, or TrekMail will reject it.</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Logo (PNG or JPG, max 1MB)</label>
                    <input
                        type="file"
                        name="trekmail_brand_logo"
                        accept="image/png,image/jpeg"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                    >
                    <p class="mt-1 text-xs text-on-blush/55">
                        @if ($branding['has_logo'] ?? false)
                            Custom logo uploaded. Leave empty to keep it. Otherwise we fall back to <code class="rounded bg-blush-soft px-1">public/lemonwareslogo.webp</code> (converted to PNG).
                        @else
                            Optional. If empty, we use <code class="rounded bg-blush-soft px-1">public/lemonwareslogo.webp</code> when GD can convert it.
                        @endif
                    </p>
                </div>
            </div>
        </section>

        @foreach ($manualProviders as $provider => $pack)
            @php($settings = $pack['settings'])
            <section class="rounded-3xl border border-border bg-white p-6">
                <div class="mb-1 flex flex-wrap items-center gap-3">
                    <h2 class="text-lg font-bold text-black">{{ $pack['label'] }}</h2>
                    <span class="inline-flex rounded-full bg-blush-soft px-3 py-1 text-xs font-semibold uppercase tracking-widest text-on-blush/70">
                        Manual fulfilment
                    </span>
                </div>
                <p class="text-sm text-on-blush/65">
                    No auto-provisioning yet. Store partner portal login details and API keys here for the fulfilment team. Orders still move through the Email Orders queue.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Partner portal URL</label>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][portal_url]"
                            value="{{ old("providers.{$provider}.portal_url", $settings['portal_url']) }}"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            placeholder="https://..."
                            autocomplete="off"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Account / reseller ref</label>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][account_ref]"
                            value="{{ old("providers.{$provider}.account_ref", $settings['account_ref']) }}"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            placeholder="Reseller ID or login email"
                            autocomplete="off"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API key (optional)</label>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][api_key]"
                            value="{{ old("providers.{$provider}.api_key", $settings['api_key']) }}"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            autocomplete="off"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API secret (optional)</label>
                        <input
                            type="text"
                            name="providers[{{ $provider }}][api_secret]"
                            value="{{ old("providers.{$provider}.api_secret", $settings['api_secret']) }}"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            autocomplete="off"
                        >
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Internal notes</label>
                        <textarea
                            name="providers[{{ $provider }}][notes]"
                            rows="3"
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            placeholder="How we provision this provider, contacts, SKUs…"
                        >{{ old("providers.{$provider}.notes", $settings['notes']) }}</textarea>
                    </div>
                </div>
            </section>
        @endforeach

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="btn btn-primary">Save Email Provider Settings</button>
        </div>
    </form>

    <section class="mt-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Test TrekMail Connection</h2>
        <p class="mt-2 text-sm text-on-blush/65">Calls the TrekMail domains endpoint with your saved API token.</p>

        <form method="POST" action="{{ route('admin.email-provider-settings.test-connection') }}" class="mt-5">
            @csrf
            <button type="submit" class="btn btn-ghost">Run Connection Test</button>
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
@endsection
