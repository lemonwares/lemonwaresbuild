@extends('layouts.account')

@section('title', $order->domain . ' — ' . config('site.short_name'))
@section('meta_description', __('email.checkout_lede'))

@section('content')
    <p class="mb-6">
        <a href="{{ route('account.email.index') }}" class="text-sm font-semibold text-rose hover:underline">← {{ __('account.service_email') }}</a>
    </p>

    <div class="mb-8">
        <p class="section-label mb-2">{{ $order->plan_name }}</p>
        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ $order->domain }}</h1>
        <p class="lede mt-2">{{ $order->statusLabel() }} · {{ __('hosting.cycles.' . $order->billing_cycle) }}</p>
        @if ($order->period_starts_at && $order->period_ends_at && ! $order->isDeactivated())
            <p class="mt-2 text-sm text-on-blush/65">
                {{ __('email.service_period', [
                    'start' => $order->period_starts_at->format('d M Y'),
                    'end' => $order->period_ends_at->format('d M Y'),
                ]) }}
            </p>
        @endif
    </div>

    @if ($order->isAwaitingPayment())
        <section class="mb-6 rounded-3xl border border-rose/20 bg-white p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('account.next_pay_title') }}</h2>
            <p class="mt-2 body-text">{{ __('email.awaiting_payment') }}</p>
            <form method="POST" action="{{ route('email.pay', $order) }}" class="mt-5" data-submit-form>
                @csrf
                <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="btn btn-primary" />
            </form>
        </section>
    @elseif ($order->isDeactivated())
        <section class="mb-6 rounded-3xl border border-rose/20 bg-rose/5 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('email.service_inactive_title') }}</h2>
            <p class="mt-2 body-text">{{ __('email.service_inactive_lede') }}</p>
            @if ($order->period_ends_at)
                <p class="mt-3 text-sm font-semibold text-on-blush/70">
                    {{ __('email.service_period_ended', ['date' => $order->period_ends_at->format('d M Y')]) }}
                </p>
            @endif
            @if ($order->canBeRenewed())
                <form method="POST" action="{{ route('email.renew', $order) }}" class="mt-5" data-submit-form>
                    @csrf
                    <p class="mb-4 text-sm text-on-blush/70">
                        {{ __('email.renew_lede_inactive', ['cycle' => __('hosting.cycles.' . $order->billing_cycle)]) }}
                        · {{ \App\Support\HostingPricing::dualPriceDisplay((float) $order->amount_usd) }}
                    </p>
                    <x-ui.submit-button :label="__('email.renew_cta')" :loading="__('account.starting_payment')" class="btn btn-primary" />
                </form>
            @endif
        </section>
    @elseif ($order->isManualFulfilment() && $order->fulfilment_status !== 'completed' && ! $order->isAwaitingPayment())
        <section class="mb-6 rounded-3xl border border-sky-200 bg-sky-50 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-sky-700">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('email.request_setup') }}</h2>
            <p class="mt-2 body-text">
                @if ($order->isPaid())
                    {{ __('email.manual_fulfilment_paid', ['hours' => 4]) }}
                @else
                    {{ __('email.manual_fulfilment_queued', ['hours' => 4]) }}
                @endif
            </p>
            <p class="mt-3 text-sm font-semibold text-sky-800">{{ __('email.fulfilment_progress') }}: {{ $order->fulfilmentStatusLabel() }}</p>
            <p class="mt-1 text-sm text-sky-700/80">{{ __('email.fulfilment_sla') }}</p>
        </section>
    @elseif ($order->status === 'provisioned' || $order->trekmail_domain_id || ($order->isManualFulfilment() && $order->fulfilment_status === 'completed'))
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_webmail_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">
                @if ($order->provider === 'lemonmail')
                    {{ __('email.credentials_sent_note') }}
                @else
                    {{ __('email.invite_note') }}
                @endif
            </p>
            <a href="{{ $webmailUrl }}" target="_blank" rel="noopener noreferrer" class="btn mt-5 bg-white text-rose hover:bg-blush">
                {{ __('email.open_webmail') }}
            </a>
        </section>
        @if ($order->canBeRenewed())
            <section class="mb-6 rounded-3xl border border-border bg-white p-6 sm:p-8">
                <h2 class="text-xl font-bold text-black">{{ __('email.renew_title') }}</h2>
                <p class="mt-2 body-text">
                    {{ __('email.renew_lede', [
                        'cycle' => __('hosting.cycles.' . $order->billing_cycle),
                        'date' => $order->period_ends_at
                            ? $order->period_ends_at->copy()->addMonthsNoOverflow($order->billingCycleMonths())->format('d M Y')
                            : now()->addMonthsNoOverflow($order->billingCycleMonths())->format('d M Y'),
                    ]) }}
                </p>
                <p class="mt-2 text-sm font-semibold text-rose">{{ \App\Support\HostingPricing::dualPriceDisplay((float) $order->amount_usd) }}</p>
                <form method="POST" action="{{ route('email.renew', $order) }}" class="mt-5" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.renew_cta')" :loading="__('account.starting_payment')" class="btn btn-primary" />
                </form>
            </section>
        @endif
    @else
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_dns_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">{{ __('email.pending_setup') }}</p>
            @if ($order->isPaid() && $order->status !== 'provisioned' && ! $order->isManualFulfilment())
                <form method="POST" action="{{ route('email.provision', $order) }}" class="mt-5" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.retry_setup')" :loading="__('account.retrying_setup')" class="btn bg-white text-rose hover:bg-blush" />
                </form>
            @endif
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-border bg-white p-6 lg:col-span-2">
            <h2 class="text-xl font-bold text-black">{{ __('account.your_mailboxes') }}</h2>
            <ul class="mt-4 space-y-2">
                @foreach ($order->mailboxes as $mailbox)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border px-4 py-3">
                        <span class="font-semibold text-black">{{ $mailbox->address }}</span>
                        <span class="text-sm text-on-blush/60">{{ $mailbox->statusLabel() }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($order->provision_error)
                <p class="mt-4 text-sm text-on-blush/65">{{ $order->provision_error }}</p>
            @endif
        </section>

        <aside class="rounded-3xl border border-border bg-white p-6">
            <h2 class="text-lg font-bold text-black">{{ __('email.order_title') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-on-blush/55">{{ __('email.status_label') }}</dt>
                    <dd class="font-semibold text-black">{{ $order->statusLabel() }}</dd>
                </div>
                @if ($order->isManualFulfilment())
                    <div>
                        <dt class="text-on-blush/55">{{ __('email.fulfilment_progress') }}</dt>
                        <dd class="font-semibold text-black">{{ $order->fulfilmentStatusLabel() }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-on-blush/55">{{ __('hosting.billing_period') }}</dt>
                    <dd class="font-semibold text-black">{{ __('hosting.cycles.' . $order->billing_cycle) }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">{{ __('hosting.period_total') }}</dt>
                    <dd class="font-semibold text-rose">{{ \App\Support\HostingPricing::dualPriceDisplay((float) $order->amount_usd) }}</dd>
                </div>
            </dl>
        </aside>
    </div>

    @if (! empty($order->dns_records))
        <section class="mt-6 rounded-3xl border border-border bg-white p-6 sm:p-8" data-dns-checklist>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-black">{{ __('email.dns_title') }}</h2>
                    <p class="mt-2 body-text">{{ __('email.dns_lede') }}</p>
                </div>
                <button type="button" class="btn btn-ghost text-sm" data-copy-all-dns>
                    {{ __('email.dns_copy_all') }}
                </button>
            </div>

            <div class="mt-4 space-y-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <p class="font-semibold">{{ __('email.dns_hints_title') }}</p>
                <ul class="list-disc space-y-1 pl-5 text-sky-900/85">
                    <li>{{ __('email.dns_hint_namecheap') }}</li>
                    <li>{{ __('email.dns_hint_cloudflare') }}</li>
                    <li>{{ __('email.dns_hint_manual') }}</li>
                </ul>
            </div>

            <div class="mt-4 overflow-x-auto rounded-2xl border border-border">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border bg-blush-soft/40 text-xs uppercase tracking-widest text-on-blush/55">
                            <th class="px-4 py-3 font-semibold">{{ __('email.dns_col_type') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('email.dns_col_host') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('email.dns_col_value') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('email.dns_col_priority') }}</th>
                            <th class="px-4 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->dns_records as $record)
                            @php
                                $row = is_array($record) ? $record : ['value' => $record];
                                $type = $row['type'] ?? $row['record_type'] ?? 'DNS';
                                $host = $row['name'] ?? $row['host'] ?? '@';
                                $value = $row['value'] ?? $row['content'] ?? $row['data'] ?? '';
                                $priority = $row['priority'] ?? null;
                                $copyText = $type === 'MX'
                                    ? trim($type.' '.$host.' '.$value.' '.(string) ($priority ?: 10))
                                    : trim($type.' '.$host.' '.$value);
                            @endphp
                            <tr class="border-b border-border last:border-0" data-dns-row data-copy-text="{{ e($copyText) }}">
                                <td class="px-4 py-3 font-semibold text-black">{{ $type }}</td>
                                <td class="px-4 py-3 text-on-blush/80">{{ $host }}</td>
                                <td class="px-4 py-3 break-all font-mono text-xs text-black">{{ is_string($value) ? $value : json_encode($value) }}</td>
                                <td class="px-4 py-3 text-on-blush/70">{{ $type === 'MX' ? ($priority ?: 10) : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="text-xs font-semibold text-rose hover:underline" data-copy-dns>
                                        {{ __('email.dns_copy') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-on-blush/55" data-copy-feedback hidden>{{ __('email.dns_copied') }}</p>
            <script>
                (() => {
                    const root = document.querySelector('[data-dns-checklist]');
                    if (!root) return;
                    const feedback = root.querySelector('[data-copy-feedback]');
                    const copy = async (text) => {
                        try {
                            await navigator.clipboard.writeText(text);
                            if (feedback) {
                                feedback.hidden = false;
                                setTimeout(() => { feedback.hidden = true; }, 2000);
                            }
                        } catch (e) {}
                    };
                    root.querySelectorAll('[data-copy-dns]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const row = btn.closest('[data-dns-row]');
                            if (row?.dataset.copyText) copy(row.dataset.copyText);
                        });
                    });
                    root.querySelector('[data-copy-all-dns]')?.addEventListener('click', () => {
                        const lines = [...root.querySelectorAll('[data-dns-row]')].map((row) => row.dataset.copyText).filter(Boolean);
                        if (lines.length) copy(lines.join('\n'));
                    });
                })();
            </script>
        </section>
    @endif
@endsection
