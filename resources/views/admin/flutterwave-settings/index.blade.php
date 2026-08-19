@extends('layouts.admin')

@section('title', 'Flutterwave Settings — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Integrations</p>
        <h1 class="heading">Flutterwave Settings</h1>
        <p class="mt-3 lede">Manage Flutterwave keys for hosting, VPS, and Lemon Mail checkout. Values here override <code class="rounded bg-blush-soft px-1">.env</code> fallbacks.</p>
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </p>
    @endif

    <form method="POST" action="{{ route('admin.flutterwave-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-black">API Credentials</h2>
                    <p class="mt-1 text-sm text-on-blush/65">Use Flutterwave test keys locally, then swap to live keys in production.</p>
                </div>
                @if ($is_configured)
                    <span @class([
                        'inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-widest',
                        'bg-amber-100 text-amber-800' => $is_test_mode,
                        'bg-emerald-100 text-emerald-800' => ! $is_test_mode,
                    ])>
                        {{ $is_test_mode ? 'Test mode keys' : 'Live keys detected' }}
                    </span>
                @endif
            </div>

            <div class="mt-5 space-y-4">
                <label class="inline-flex items-start gap-3 rounded-xl border border-border bg-blush-soft/40 px-4 py-3">
                    <input
                        type="checkbox"
                        name="enabled"
                        value="1"
                        class="mt-1 rounded border-border text-rose focus:ring-rose/20"
                        @checked(old('enabled', $settings['enabled']))
                    />
                    <span>
                        <span class="block text-sm font-semibold text-black">Enable Flutterwave checkout</span>
                        <span class="mt-1 block text-xs text-on-blush/65">When disabled, orders are saved but customers are not redirected to Flutterwave.</span>
                    </span>
                </label>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Public Key</label>
                    <input
                        type="text"
                        name="public_key"
                        value="{{ old('public_key', $settings['public_key']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="FLWPUBK_TEST-..."
                        autocomplete="off"
                    >
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Secret Key</label>
                    <input
                        type="text"
                        name="secret_key"
                        value="{{ old('secret_key', $settings['secret_key']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        required
                        placeholder="FLWSECK_TEST-..."
                        autocomplete="off"
                    >
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Webhook Secret Hash</label>
                    <input
                        type="text"
                        name="secret_hash"
                        value="{{ old('secret_hash', $settings['secret_hash']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                        placeholder="Same hash configured in Flutterwave dashboard"
                        autocomplete="off"
                    >
                    <p class="mt-2 text-xs text-on-blush/60">Required for server-to-server webhook verification. Set the same value in Flutterwave → Settings → Webhooks.</p>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-border bg-white p-6">
            <h2 class="text-lg font-bold text-black">Webhook URL</h2>
            <p class="mt-2 text-sm text-on-blush/65">Register this URL in your Flutterwave dashboard so paid orders sync to WHMCS automatically.</p>
            <div class="mt-4 rounded-2xl border border-border bg-blush-soft px-4 py-3">
                <code class="break-all text-sm text-black">{{ $webhook_url }}</code>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="btn btn-primary">Save Flutterwave Settings</button>
        </div>
    </form>

    <section class="mt-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Test API Connection</h2>
        <p class="mt-2 text-sm text-on-blush/65">Calls Flutterwave’s banks endpoint with your saved secret key.</p>

        <form method="POST" action="{{ route('admin.flutterwave-settings.test-connection') }}" class="mt-5">
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
                    @if (! empty($test['mode']))
                        <span class="ml-2 text-xs uppercase tracking-widest">({{ $test['mode'] }})</span>
                    @endif
                </p>
            </div>
        @endif
    </section>
@endsection
