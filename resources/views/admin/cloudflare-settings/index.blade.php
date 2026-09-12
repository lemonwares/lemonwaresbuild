@extends('layouts.admin')

@section('title', 'Cloudflare Settings — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Integrations</p>
        <h1 class="heading">Cloudflare Settings</h1>
        <p class="mt-3 lede">
            One-click Lemon Mail DNS apply for domains on Cloudflare. Token here is the default;
            you can paste a one-off zone token on an email order when the domain is on a customer’s account.
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

    <form method="POST" action="{{ route('admin.cloudflare-settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-black">API credentials</h2>
                    <p class="mt-1 text-sm text-on-blush/65">
                        Create an API token with <strong>Zone → DNS → Edit</strong> (and Zone → Zone → Read).
                        Domains must use Cloudflare nameservers for apply to work.
                    </p>
                </div>
                @if ($is_configured)
                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-800">
                        Ready
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
                        <span class="block text-sm font-semibold text-black">Enable Cloudflare DNS apply</span>
                        <span class="mt-1 block text-xs text-on-blush/65">When off, admin email orders only show the copy checklist.</span>
                    </span>
                </label>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">API token</label>
                    <textarea
                        name="api_token"
                        rows="3"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5 font-mono text-sm"
                        placeholder="Cloudflare API token"
                        autocomplete="off"
                    >{{ old('api_token', $settings['api_token']) }}</textarea>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Account ID (optional)</label>
                    <input
                        type="text"
                        name="account_id"
                        value="{{ old('account_id', $settings['account_id']) }}"
                        class="footer-input w-full rounded-xl border border-border px-3 py-2.5 font-mono text-sm"
                        placeholder="Only needed if the token can see multiple accounts"
                        autocomplete="off"
                    >
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="submit" class="btn btn-primary">Save Cloudflare settings</button>
        </div>
    </form>

    <section class="mt-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Test API connection</h2>
        <p class="mt-2 text-sm text-on-blush/65">Verifies the saved token with Cloudflare (no DNS changes).</p>

        <form method="POST" action="{{ route('admin.cloudflare-settings.test-connection') }}" class="mt-5">
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
@endsection
