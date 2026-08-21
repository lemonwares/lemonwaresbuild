@extends('layouts.admin')

@section('title', 'Email Provider Settings — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Integrations</p>
        <h1 class="heading">Email Provider Settings</h1>
        <p class="mt-3 lede">
            Configure Lemon Mail (TrekMail) API credentials for auto-provisioning, and store partner portal details for Titan, Google Workspace, and Microsoft 365 manual fulfilment.
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

    <form method="POST" action="{{ route('admin.email-provider-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-black">Lemon Mail · TrekMail API</h2>
                    <p class="mt-1 text-sm text-on-blush/65">Used for automatic domain and mailbox provisioning after payment.</p>
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
                <p class="font-semibold text-black">Required ops-token scopes</p>
                <p class="mt-1">
                    Create a <code class="rounded bg-white px-1">tm_live_</code> token in
                    <a href="https://trekmail.net/docs/ai-agents-api/creating-api-tokens" target="_blank" rel="noopener noreferrer" class="font-semibold text-rose hover:underline">TrekMail → AI Agents &amp; API</a>
                    on a <strong>Pro or Agency</strong> plan, then enable at least:
                </p>
                <ul class="mt-2 list-disc space-y-1 pl-5 font-mono text-xs text-black">
                    <li>domains:read · domains:create · domains:dns:read · domains:dns:recheck</li>
                    <li>mailboxes:create · mailboxes:invites:create</li>
                </ul>
                <p class="mt-2 text-xs">
                    Starter tokens are read-only and cannot provision Lemon Mail. After updating scopes, paste the new token here, save, run the connection test, then retry provision on the order.
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
                        placeholder="tm_live_..."
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
                        placeholder="https://trekmail.net/api/v1"
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
                        placeholder="https://trekmail.net/webmail"
                        autocomplete="off"
                    >
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
