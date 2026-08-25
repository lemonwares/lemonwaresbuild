@extends('layouts.admin')

@section('title', 'Email Order #' . $order->id . ' — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Email Order</p>
        <h1 class="heading">{{ $order->domain }}</h1>
        <p class="lede mt-3">{{ $order->user?->name }} · {{ $order->user?->email }}</p>
        @if ($order->user && $order->user->isCustomer())
            <a href="{{ route('admin.customers.show', $order->user) }}" class="mt-3 inline-flex text-sm font-semibold text-rose hover:underline">Open customer profile</a>
        @endif
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="rounded-3xl border border-border bg-white p-6">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Status</dt>
                <dd class="font-semibold">{{ $order->status }} / {{ $order->payment_status ?: '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Provider</dt>
                <dd class="font-semibold">{{ __('email.providers.' . ($order->provider ?: 'lemonmail')) }} · {{ $order->fulfilment_mode ?: 'auto' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Plan</dt>
                <dd class="font-semibold">{{ $order->plan_name }} · {{ $order->billing_cycle }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Amount</dt>
                <dd class="font-semibold">{{ \App\Support\HostingPricing::dualPriceDisplay((float) $order->amount_usd) }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Service period</dt>
                <dd class="font-semibold text-right">
                    @if ($order->period_starts_at && $order->period_ends_at)
                        {{ $order->period_starts_at->format('d M Y') }} → {{ $order->period_ends_at->format('d M Y') }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            @if ($order->isDeactivated())
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">Deactivated</dt>
                    <dd class="font-semibold text-right text-rose">
                        {{ $order->deactivated_at?->format('d M Y H:i') ?: '—' }}
                        @if ($order->deactivated_reason)
                            · {{ $order->deactivated_reason }}
                        @endif
                    </dd>
                </div>
            @endif
            @if ($order->isManualFulfilment())
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">Fulfilment</dt>
                    <dd class="font-semibold">{{ $order->fulfilmentStatusLabel() }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">SLA</dt>
                    <dd class="font-semibold text-on-blush/80">{{ __('email.fulfilment_sla') }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <dt class="text-on-blush/60">TrekMail domain</dt>
                <dd class="font-semibold">{{ $order->trekmail_domain_id ?: '—' }}</dd>
            </div>
        </dl>

        @if ($order->provision_error)
            <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $order->provision_error }}</p>
        @endif

        <ul class="mt-6 space-y-2 text-sm">
            @foreach ($order->mailboxes as $mailbox)
                <li>{{ $mailbox->address }} — {{ $mailbox->status }}{{ $mailbox->error_message ? ' (' . $mailbox->error_message . ')' : '' }}</li>
            @endforeach
        </ul>

        @php
            $editorRows = old('records', $dnsRecords !== [] ? $dnsRecords : [['type' => '', 'name' => '@', 'value' => '', 'priority' => 10]]);
            while (count($editorRows) < 4) {
                $editorRows[] = ['type' => '', 'name' => '@', 'value' => '', 'priority' => 10];
            }
        @endphp

        <section class="mt-8 rounded-2xl border border-border bg-blush-soft/30 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-black">DNS checklist</p>
                    <p class="mt-1 text-xs text-on-blush/65">
                        Save records for the customer copy checklist. If the domain is on Cloudflare, apply them with one click.
                        @if ($order->dns_applied_at)
                            Last Cloudflare apply: {{ $order->dns_applied_at->format('d M Y H:i') }}
                            @if ($order->dns_provider) ({{ $order->dns_provider }}) @endif
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.email-orders.dns.template', $order) }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost text-xs">Load Lemon Mail template</button>
                    </form>
                    <a href="{{ route('admin.cloudflare-settings.index') }}" class="btn btn-ghost text-xs">Cloudflare settings</a>
                </div>
            </div>

            @if ($errors->has('dns'))
                <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first('dns') }}</p>
            @endif

            @if (session('dns_verify_result'))
                @php($verify = session('dns_verify_result'))
                <div class="mt-4 rounded-xl border border-border bg-white px-4 py-3 text-sm">
                    <p @class(['font-semibold', 'text-emerald-700' => $verify['ok'] ?? false, 'text-rose' => ! ($verify['ok'] ?? false)])>
                        {{ $verify['message'] ?? 'DNS verify finished.' }}
                    </p>
                    @if (! empty($verify['missing']))
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-on-blush/70">
                            @foreach ($verify['missing'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('admin.email-orders.dns', $order) }}" class="mt-4 space-y-3" id="dns-checklist-form">
                @csrf
                @method('PUT')
                <div class="space-y-2">
                    @foreach ($editorRows as $index => $row)
                        <div class="grid gap-2 sm:grid-cols-12">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[0.65rem] font-semibold uppercase tracking-widest text-on-blush/55">Type</label>
                                <select name="records[{{ $index }}][type]" class="footer-input w-full rounded-xl border border-border bg-white px-2 py-2 text-sm">
                                    <option value="">—</option>
                                    @foreach (['MX', 'TXT', 'A', 'AAAA', 'CNAME'] as $type)
                                        <option value="{{ $type }}" @selected(($row['type'] ?? '') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-[0.65rem] font-semibold uppercase tracking-widest text-on-blush/55">Host</label>
                                <input type="text" name="records[{{ $index }}][name]" value="{{ $row['name'] ?? '@' }}" class="footer-input w-full rounded-xl border border-border bg-white px-2 py-2 text-sm" placeholder="@">
                            </div>
                            <div class="sm:col-span-5">
                                <label class="mb-1 block text-[0.65rem] font-semibold uppercase tracking-widest text-on-blush/55">Value</label>
                                <input type="text" name="records[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" class="footer-input w-full rounded-xl border border-border bg-white px-2 py-2 font-mono text-xs" placeholder="mail.trekmail.net">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="mb-1 block text-[0.65rem] font-semibold uppercase tracking-widest text-on-blush/55">Priority (MX)</label>
                                <input type="number" name="records[{{ $index }}][priority]" value="{{ $row['priority'] ?? 10 }}" min="0" max="65535" class="footer-input w-full rounded-xl border border-border bg-white px-2 py-2 text-sm">
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary">Save DNS checklist</button>
            </form>

            <div class="mt-6 grid gap-4 border-t border-border pt-4 lg:grid-cols-2">
                <form method="POST" action="{{ route('admin.email-orders.dns.cloudflare', $order) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm font-semibold text-black">Apply to Cloudflare</p>
                    <p class="text-xs text-on-blush/65">
                        Saves must be done first (or the Lemon Mail template is used). Replaces foreign MX (e.g. Namecheap eforward).
                        @unless ($cloudflareConfigured)
                            <span class="text-rose">Global Cloudflare token not configured — paste a zone token below or</span>
                            <a href="{{ route('admin.cloudflare-settings.index') }}" class="font-semibold text-rose hover:underline">configure settings</a>.
                        @endunless
                    </p>
                    <input
                        type="password"
                        name="cloudflare_token"
                        class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2 text-sm"
                        placeholder="Optional one-off zone token (not stored)"
                        autocomplete="off"
                    >
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Apply DNS to Cloudflare for {{ $order->domain }}? Conflicting MX records will be removed.');">
                        Apply DNS to Cloudflare
                    </button>
                </form>

                <div class="space-y-3">
                    <p class="text-sm font-semibold text-black">Verify DNS</p>
                    <p class="text-xs text-on-blush/65">Check Cloudflare zone records, or public DNS after propagation.</p>
                    <form method="POST" action="{{ route('admin.email-orders.dns.verify', $order) }}" class="flex flex-wrap gap-2">
                        @csrf
                        <input type="hidden" name="source" value="cloudflare">
                        <button type="submit" class="btn btn-ghost">Verify in Cloudflare</button>
                    </form>
                    <form method="POST" action="{{ route('admin.email-orders.dns.verify', $order) }}">
                        @csrf
                        <input type="hidden" name="source" value="public">
                        <button type="submit" class="btn btn-ghost">Verify public DNS</button>
                    </form>
                </div>
            </div>
        </section>

        @if ($order->isManualFulfilment())
            @if ($providerSettings && collect($providerSettings)->filter()->isNotEmpty())
                <div class="mt-8 rounded-2xl border border-border bg-blush-soft/40 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-black">Provider credentials</p>
                        <a href="{{ route('admin.email-provider-settings.index') }}" class="text-xs font-semibold text-rose hover:underline">Edit in Email Providers</a>
                    </div>
                    <dl class="mt-4 space-y-2 text-sm">
                        @if ($providerSettings['portal_url'])
                            <div class="flex justify-between gap-4">
                                <dt class="text-on-blush/60">Portal</dt>
                                <dd class="text-right font-semibold">
                                    <a href="{{ $providerSettings['portal_url'] }}" target="_blank" rel="noopener noreferrer" class="text-rose hover:underline">{{ $providerSettings['portal_url'] }}</a>
                                </dd>
                            </div>
                        @endif
                        @if ($providerSettings['account_ref'])
                            <div class="flex justify-between gap-4">
                                <dt class="text-on-blush/60">Account ref</dt>
                                <dd class="font-semibold">{{ $providerSettings['account_ref'] }}</dd>
                            </div>
                        @endif
                        @if ($providerSettings['api_key'])
                            <div class="flex justify-between gap-4">
                                <dt class="text-on-blush/60">API key</dt>
                                <dd class="break-all font-semibold">{{ $providerSettings['api_key'] }}</dd>
                            </div>
                        @endif
                        @if ($providerSettings['notes'])
                            <div>
                                <dt class="text-on-blush/60">Notes</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-on-blush/80">{{ $providerSettings['notes'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @else
                <p class="mt-8 text-sm text-on-blush/70">
                    No partner credentials saved yet.
                    <a href="{{ route('admin.email-provider-settings.index') }}" class="font-semibold text-rose hover:underline">Add them under Email Providers</a>.
                </p>
            @endif

            <form method="POST" action="{{ route('admin.email-orders.fulfilment', $order) }}" class="mt-8 space-y-4 rounded-2xl border border-border bg-blush-soft/40 p-4">
                @csrf
                @method('PUT')
                <p class="text-sm font-semibold text-black">Update fulfilment queue</p>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Status</label>
                    <select name="fulfilment_status" class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5" required>
                        @foreach ($fulfilmentStatuses as $status)
                            <option value="{{ $status }}" @selected(old('fulfilment_status', $order->fulfilment_status ?: 'queued') === $status)>
                                {{ __('email.fulfilment_statuses.' . $status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Internal notes</label>
                    <textarea name="fulfilment_notes" rows="3" class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5" placeholder="Contacted customer, awaiting DNS, etc.">{{ old('fulfilment_notes', $order->fulfilment_notes) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save fulfilment</button>
            </form>

            @if ($order->provider === 'lemonmail' && ($order->isPaid() || $order->status === 'awaiting_manual_fulfilment') && ! $order->isDeactivated())
                <form method="POST" action="{{ route('admin.email-orders.credentials', $order) }}" class="mt-8 space-y-4 rounded-2xl border border-rose/20 bg-white p-4">
                    @csrf
                    <div>
                        <p class="text-sm font-semibold text-black">Send mailbox credentials</p>
                        <p class="mt-1 text-xs text-on-blush/65">
                            Create the mailboxes in TrekMail first, then enter temporary passwords here.
                            We email the customer via ZeptoMail and do not store the passwords.
                        </p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Webmail URL</label>
                        <input
                            type="url"
                            name="webmail_url"
                            value="{{ old('webmail_url', $defaultWebmailUrl) }}"
                            required
                            class="footer-input w-full rounded-xl border border-border px-3 py-2.5"
                            placeholder="https://mail.lemonwares.com"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">Optional note to customer</label>
                        <textarea name="note" rows="2" class="footer-input w-full rounded-xl border border-border px-3 py-2.5" placeholder="DNS is live — you can sign in now.">{{ old('note') }}</textarea>
                    </div>
                    <div class="space-y-3">
                        @foreach ($order->mailboxes as $mailbox)
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-on-blush/60">
                                    Password for {{ $mailbox->address }}
                                </label>
                                <input
                                    type="text"
                                    name="passwords[{{ $mailbox->id }}]"
                                    value="{{ old('passwords.'.$mailbox->id) }}"
                                    required
                                    minlength="6"
                                    autocomplete="off"
                                    class="footer-input w-full rounded-xl border border-border px-3 py-2.5 font-mono text-sm"
                                >
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Email these credentials to {{ $order->user?->email }}?');">
                        Email credentials to customer
                    </button>
                </form>
            @endif
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($order->isPaid() && ! $order->isManualFulfilment() && ! $order->isDeactivated())
                <form method="POST" action="{{ route('admin.email-orders.provision', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Retry TrekMail provision</button>
                </form>
            @endif
            @if ($order->canBeDeactivated())
                <form method="POST" action="{{ route('admin.email-orders.deactivate', $order) }}" onsubmit="return confirm('Deactivate this email service? Mailboxes will be paused where possible.');">
                    @csrf
                    <button type="submit" class="btn btn-ghost text-rose">Deactivate service</button>
                </form>
            @endif
            @if ($order->canBeReactivated())
                <form method="POST" action="{{ route('admin.email-orders.reactivate', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Reactivate service</button>
                </form>
            @endif
            @if ($order->canBeRenewed())
                <form method="POST" action="{{ route('admin.email-orders.extend', $order) }}" onsubmit="return confirm('Extend this service by one billing cycle without charging?');">
                    @csrf
                    <button type="submit" class="btn btn-ghost">Extend period</button>
                </form>
            @endif
            <a href="{{ route('admin.email-orders.index') }}" class="btn btn-ghost">Back to list</a>
        </div>
    </div>
@endsection
