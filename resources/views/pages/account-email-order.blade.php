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
    @elseif ($order->isManualFulfilment() && $order->fulfilment_status !== 'completed' && $order->status === 'awaiting_manual_fulfilment')
        <section class="mb-6 rounded-3xl border border-sky-200 bg-sky-50 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-sky-700">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('email.request_setup') }}</h2>
            <p class="mt-2 body-text">{{ __('email.manual_fulfilment_queued', ['hours' => 4]) }}</p>
            <p class="mt-3 text-sm font-semibold text-sky-800">{{ __('email.fulfilment_progress') }}: {{ $order->fulfilmentStatusLabel() }}</p>
            <p class="mt-1 text-sm text-sky-700/80">{{ __('email.fulfilment_sla') }}</p>
        </section>
    @elseif ($order->isManualFulfilment() && $order->isPaid() && $order->fulfilment_status !== 'completed')
        <section class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('email.payment_confirmed') }}</h2>
            <p class="mt-2 body-text">{{ __('email.manual_fulfilment_paid', ['hours' => 4]) }}</p>
            <p class="mt-3 text-sm font-semibold text-emerald-800">{{ __('email.fulfilment_progress') }}: {{ $order->fulfilmentStatusLabel() }}</p>
            <p class="mt-1 text-sm text-emerald-700/80">{{ __('email.fulfilment_sla') }}</p>
        </section>
    @elseif ($order->status === 'provisioned' || $order->trekmail_domain_id || ($order->isManualFulfilment() && $order->fulfilment_status === 'completed'))
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_webmail_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">{{ __('email.invite_note') }}</p>
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
            @if ($order->isPaid() && $order->status !== 'provisioned')
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
        <section class="mt-6 rounded-3xl border border-border bg-white p-6 sm:p-8">
            <h2 class="text-xl font-bold text-black">{{ __('email.dns_title') }}</h2>
            <p class="mt-2 body-text">{{ __('email.dns_lede') }}</p>
            <div class="mt-4 overflow-x-auto rounded-2xl border border-border">
                <table class="min-w-full text-left text-sm">
                    <tbody>
                        @foreach ($order->dns_records as $record)
                            @php $row = is_array($record) ? $record : ['value' => $record]; @endphp
                            <tr class="border-b border-border last:border-0">
                                <td class="px-4 py-3 font-semibold text-black">{{ $row['type'] ?? $row['record_type'] ?? 'DNS' }}</td>
                                <td class="px-4 py-3 text-on-blush/80">{{ $row['name'] ?? $row['host'] ?? '@' }}</td>
                                <td class="px-4 py-3 break-all font-mono text-xs text-black">{{ $row['value'] ?? $row['content'] ?? $row['data'] ?? json_encode($row) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
