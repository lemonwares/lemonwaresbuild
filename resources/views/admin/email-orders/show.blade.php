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
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($order->isPaid() && ! $order->isManualFulfilment())
                <form method="POST" action="{{ route('admin.email-orders.provision', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Retry TrekMail provision</button>
                </form>
            @endif
            <a href="{{ route('admin.email-orders.index') }}" class="btn btn-ghost">Back to list</a>
        </div>
    </div>
@endsection
