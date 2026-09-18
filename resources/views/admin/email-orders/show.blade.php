@extends('layouts.admin')

@section('title', 'Email Order #' . $order->id . ' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $editorRows = old('records', $dnsRecords !== [] ? $dnsRecords : [['type' => '', 'name' => '@', 'value' => '', 'priority' => 10]]);
    while (count($editorRows) < 4) {
        $editorRows[] = ['type' => '', 'name' => '@', 'value' => '', 'priority' => 10];
    }
@endphp

@section('content')
    <x-admin.page-header
        :title="$order->domain"
        :lede="trim(($order->user?->name ? $order->user->name.' · ' : '').($order->user?->email ?: 'No customer linked'))"
        :back-href="route('admin.email-orders.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Email Orders', 'href' => route('admin.email-orders.index')],
            ['label' => '#'.$order->id],
        ]"
        class="mb-5"
    >
        <x-slot:actions>
            <div class="admin-customers-toolbar">
                @if ($order->user && $order->user->isCustomer())
                    <a href="{{ route('admin.customers.show', $order->user) }}" class="admin-btn-ghost">Customer profile</a>
                @endif
                @if ($order->isPaid() && ! $order->isManualFulfilment() && ! $order->isDeactivated())
                    <form method="POST" action="{{ route('admin.email-orders.provision', $order) }}" data-submit-form>
                        @csrf
                        <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Retry provision</span>
                            <span class="hidden" data-submit-loading>Provisioning…</span>
                        </button>
                    </form>
                @endif
            </div>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Order snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ str_replace('_', ' ', $order->status) }}</span>
                <span class="admin-metric-meta">{{ $order->payment_status ?: 'No payment status' }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Provider</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ __('email.providers.' . ($order->provider ?: 'lemonmail')) }}</span>
                <span class="admin-metric-meta">{{ $order->fulfilment_mode ?: 'auto' }} fulfilment</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Amount</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ \App\Support\HostingPricing::dualPriceDisplay((float) $order->amount_usd) }}</span>
                <span class="admin-metric-meta">{{ $order->plan_name }} · {{ $order->billing_cycle }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Mailboxes</span>
                <span class="admin-metric-value">{{ $order->mailboxes->count() }}</span>
                <span class="admin-metric-meta">
                    @if ($order->period_starts_at && $order->period_ends_at)
                        {{ $order->period_starts_at->format('d M Y') }} → {{ $order->period_ends_at->format('d M Y') }}
                    @else
                        No service period
                    @endif
                </span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Order details</h2>
                    <div class="admin-pill-row">
                        @if ($order->isManualFulfilment())
                            <span class="admin-pill is-info">{{ $order->fulfilmentStatusLabel() }}</span>
                        @else
                            <span class="admin-pill is-ok">Auto</span>
                        @endif
                        @if ($order->isDeactivated())
                            <span class="admin-pill" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;">Deactivated</span>
                        @endif
                    </div>
                </div>
                <dl class="admin-dl">
                    <div><dt>Order ID</dt><dd>#{{ $order->id }}</dd></div>
                    <div><dt>Domain</dt><dd>{{ $order->domain }}</dd></div>
                    <div><dt>Plan</dt><dd>{{ $order->plan_name }} · {{ $order->billing_cycle }}</dd></div>
                    <div><dt>Mail provider domain</dt><dd>{{ $order->trekmail_domain_id ?: '—' }}</dd></div>
                    @if ($order->isManualFulfilment())
                        <div><dt>Fulfilment</dt><dd>{{ $order->fulfilmentStatusLabel() }}</dd></div>
                        <div><dt>SLA</dt><dd>{{ __('email.fulfilment_sla') }}</dd></div>
                    @endif
                    @if ($order->isDeactivated())
                        <div class="admin-dl-span">
                            <dt>Deactivated</dt>
                            <dd>
                                {{ $order->deactivated_at?->format('d M Y H:i') ?: '—' }}
                                @if ($order->deactivated_reason)
                                    · {{ $order->deactivated_reason }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
                @if ($order->provision_error)
                    <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $order->provision_error }}</p>
                @endif
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Mailboxes</h2>
                </div>
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->mailboxes as $mailbox)
                                <tr>
                                    <td><strong>{{ $mailbox->address }}</strong></td>
                                    <td><span class="admin-mini-status">{{ $mailbox->status }}</span></td>
                                    <td>{{ $mailbox->error_message ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="admin-table-empty">No mailboxes on this order.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">DNS checklist</h2>
                    <p class="admin-dash-panel-lede">
                        Save records for the customer checklist (Lemon Mail uses TrekMail hosts). If the domain is on Cloudflare, apply them with one click.
                        @if ($order->dns_applied_at)
                            Last Cloudflare apply: {{ $order->dns_applied_at->format('d M Y H:i') }}
                            @if ($order->dns_provider) ({{ $order->dns_provider }}) @endif
                        @endif
                    </p>
                </div>
                <div class="admin-customers-toolbar">
                    <form method="POST" action="{{ route('admin.email-orders.dns.template', $order) }}" data-submit-form>
                        @csrf
                        <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Load Lemon Mail template</span>
                            <span class="hidden" data-submit-loading>Loading…</span>
                        </button>
                    </form>
                    <a href="{{ route('admin.cloudflare-settings.index') }}" class="admin-btn-ghost">Cloudflare settings</a>
                </div>
            </div>

            @if ($errors->has('dns'))
                <p class="mb-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first('dns') }}</p>
            @endif

            @if (session('dns_verify_result'))
                @php($verify = session('dns_verify_result'))
                <div class="mb-4 rounded-xl border border-border bg-blush-soft/40 px-4 py-3 text-sm">
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

            <form method="POST" action="{{ route('admin.email-orders.dns', $order) }}" class="space-y-3" id="dns-checklist-form" data-submit-form>
                @csrf
                @method('PUT')
                <div class="admin-table-wrap is-full">
                    <table class="admin-table is-full">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Host</th>
                                <th>Value</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($editorRows as $index => $row)
                                <tr>
                                    <td>
                                        <select name="records[{{ $index }}][type]" class="admin-input admin-input-sm w-full">
                                            <option value="">—</option>
                                            @foreach (['MX', 'TXT', 'A', 'AAAA', 'CNAME'] as $type)
                                                <option value="{{ $type }}" @selected(($row['type'] ?? '') === $type)>{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="records[{{ $index }}][name]" value="{{ $row['name'] ?? '@' }}" class="admin-input admin-input-sm w-full" placeholder="@">
                                    </td>
                                    <td>
                                        <input type="text" name="records[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" class="admin-input admin-input-sm w-full font-mono text-xs" placeholder="mx.trekmail.net">
                                    </td>
                                    <td>
                                        <input type="number" name="records[{{ $index }}][priority]" value="{{ $row['priority'] ?? 10 }}" min="0" max="65535" class="admin-input admin-input-sm w-full">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                    <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                    <span data-submit-label>Save DNS checklist</span>
                    <span class="hidden" data-submit-loading>Saving…</span>
                </button>
            </form>

            <div class="admin-customer-grid mt-6 border-t border-border pt-5">
                <form method="POST" action="{{ route('admin.email-orders.dns.cloudflare', $order) }}" class="space-y-3" data-submit-form>
                    @csrf
                    <p class="admin-dash-panel-title">Apply to Cloudflare</p>
                    <p class="admin-dash-panel-lede">
                        Saves must be done first (or the Lemon Mail / TrekMail template is used). Replaces foreign MX.
                        @unless ($cloudflareConfigured)
                            <span class="text-rose">Global Cloudflare token not configured — paste a zone token below or</span>
                            <a href="{{ route('admin.cloudflare-settings.index') }}" class="font-semibold text-rose hover:underline">configure settings</a>.
                        @endunless
                    </p>
                    <input
                        type="password"
                        name="cloudflare_token"
                        class="admin-input w-full"
                        placeholder="Optional one-off zone token (not stored)"
                        autocomplete="off"
                    >
                    <button
                        type="submit"
                        class="admin-btn-primary inline-flex items-center gap-2"
                        data-submit-button
                        onclick="return confirm('Apply DNS to Cloudflare for {{ $order->domain }}? Conflicting MX records will be removed.');"
                    >
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Apply DNS to Cloudflare</span>
                        <span class="hidden" data-submit-loading>Applying…</span>
                    </button>
                </form>

                <div class="space-y-3">
                    <p class="admin-dash-panel-title">Verify DNS</p>
                    <p class="admin-dash-panel-lede">Check Cloudflare zone records, or public DNS after propagation.</p>
                    <div class="admin-customers-toolbar">
                        <form method="POST" action="{{ route('admin.email-orders.dns.verify', $order) }}" data-submit-form>
                            @csrf
                            <input type="hidden" name="source" value="cloudflare">
                            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                                <span data-submit-label>Verify in Cloudflare</span>
                                <span class="hidden" data-submit-loading>Verifying…</span>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.email-orders.dns.verify', $order) }}" data-submit-form>
                            @csrf
                            <input type="hidden" name="source" value="public">
                            <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                                <span data-submit-label>Verify public DNS</span>
                                <span class="hidden" data-submit-loading>Verifying…</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        @if ($order->isManualFulfilment())
            <div class="admin-customer-grid">
                <section class="admin-panel">
                    <div class="admin-panel-toolbar compact">
                        <h2 class="admin-dash-panel-title">Provider credentials</h2>
                        <a href="{{ route('admin.email-provider-settings.index') }}" class="admin-dash-link">Edit</a>
                    </div>
                    @if ($providerSettings && collect($providerSettings)->filter()->isNotEmpty())
                        <dl class="admin-dl">
                            @if ($providerSettings['portal_url'])
                                <div><dt>Portal</dt><dd><a href="{{ $providerSettings['portal_url'] }}" target="_blank" rel="noopener noreferrer">{{ $providerSettings['portal_url'] }}</a></dd></div>
                            @endif
                            @if ($providerSettings['account_ref'])
                                <div><dt>Account ref</dt><dd>{{ $providerSettings['account_ref'] }}</dd></div>
                            @endif
                            @if ($providerSettings['api_key'])
                                <div><dt>API key</dt><dd class="break-all">{{ $providerSettings['api_key'] }}</dd></div>
                            @endif
                            @if ($providerSettings['notes'])
                                <div class="admin-dl-span"><dt>Notes</dt><dd class="whitespace-pre-wrap">{{ $providerSettings['notes'] }}</dd></div>
                            @endif
                        </dl>
                    @else
                        <p class="admin-empty">No partner credentials saved yet.</p>
                    @endif
                </section>

                <section class="admin-panel">
                    <div class="admin-panel-toolbar compact">
                        <h2 class="admin-dash-panel-title">Update fulfilment queue</h2>
                    </div>
                    <form method="POST" action="{{ route('admin.email-orders.fulfilment', $order) }}" class="space-y-4" data-submit-form>
                        @csrf
                        @method('PUT')
                        <label class="admin-field">
                            <span>Status</span>
                            <select name="fulfilment_status" class="admin-input" required>
                                @foreach ($fulfilmentStatuses as $status)
                                    <option value="{{ $status }}" @selected(old('fulfilment_status', $order->fulfilment_status ?: 'queued') === $status)>
                                        {{ __('email.fulfilment_statuses.' . $status) }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Internal notes</span>
                            <textarea name="fulfilment_notes" rows="3" class="admin-input" placeholder="Contacted customer, awaiting DNS, etc.">{{ old('fulfilment_notes', $order->fulfilment_notes) }}</textarea>
                        </label>
                        <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Save fulfilment</span>
                            <span class="hidden" data-submit-loading>Saving…</span>
                        </button>
                    </form>
                </section>
            </div>

            @if ($order->provider === 'lemonmail' && ($order->isPaid() || $order->status === 'awaiting_manual_fulfilment') && ! $order->isDeactivated())
                <section class="admin-panel">
                    <div class="admin-panel-toolbar compact">
                        <div>
                            <h2 class="admin-dash-panel-title">Send mailbox credentials</h2>
                            <p class="admin-dash-panel-lede">Create mailboxes in TrekMail first, then enter temporary passwords. We email the customer via ZeptoMail and do not store passwords.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.email-orders.credentials', $order) }}" class="space-y-4" data-submit-form>
                        @csrf
                        <label class="admin-field">
                            <span>Webmail URL</span>
                            <input type="url" name="webmail_url" value="{{ old('webmail_url', $defaultWebmailUrl) }}" required class="admin-input" placeholder="https://mail.lemonwares.com">
                        </label>
                        <label class="admin-field">
                            <span>Optional note to customer</span>
                            <textarea name="note" rows="2" class="admin-input" placeholder="DNS is live — you can sign in now.">{{ old('note') }}</textarea>
                        </label>
                        <div class="admin-edit-grid">
                            @foreach ($order->mailboxes as $mailbox)
                                <label class="admin-field">
                                    <span>Password for {{ $mailbox->address }}</span>
                                    <input type="text" name="passwords[{{ $mailbox->id }}]" value="{{ old('passwords.'.$mailbox->id) }}" required minlength="6" autocomplete="off" class="admin-input font-mono text-sm">
                                </label>
                            @endforeach
                        </div>
                        <button
                            type="submit"
                            class="admin-btn-primary inline-flex items-center gap-2"
                            data-submit-button
                            onclick="return confirm('Email these credentials to {{ $order->user?->email }}?');"
                        >
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Email credentials to customer</span>
                            <span class="hidden" data-submit-loading>Sending…</span>
                        </button>
                    </form>
                </section>
            @endif
        @endif

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <h2 class="admin-dash-panel-title">Lifecycle actions</h2>
            </div>
            <div class="admin-customers-toolbar">
                @if ($order->canBeDeactivated())
                    <form method="POST" action="{{ route('admin.email-orders.deactivate', $order) }}" data-submit-form onsubmit="return confirm('Deactivate this email service? Mailboxes will be paused where possible.');">
                        @csrf
                        <button type="submit" class="admin-btn-danger inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Deactivate service</span>
                            <span class="hidden" data-submit-loading>Deactivating…</span>
                        </button>
                    </form>
                @endif
                @if ($order->canBeReactivated())
                    <form method="POST" action="{{ route('admin.email-orders.reactivate', $order) }}" data-submit-form>
                        @csrf
                        <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Reactivate service</span>
                            <span class="hidden" data-submit-loading>Reactivating…</span>
                        </button>
                    </form>
                @endif
                @if ($order->canBeRenewed())
                    <form method="POST" action="{{ route('admin.email-orders.extend', $order) }}" data-submit-form onsubmit="return confirm('Extend this service by one billing cycle without charging?');">
                        @csrf
                        <button type="submit" class="admin-btn-ghost inline-flex items-center gap-2" data-submit-button>
                            <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                            <span data-submit-label>Extend period</span>
                            <span class="hidden" data-submit-loading>Extending…</span>
                        </button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
