@extends('layouts.account')

@section('title', __('account.service_email') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.email_lede'))

@section('content')
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="section-label mb-2">{{ __('account.nav_email') }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.service_email') }}</h1>
            <p class="lede mt-2">{{ __('account.email_lede') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($latestOrder && ($latestOrder->status === 'provisioned' || $latestOrder->trekmail_domain_id))
                <a href="{{ $webmailUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">{{ __('email.open_webmail') }}</a>
            @endif
            <a href="{{ route('email.plans') }}" class="btn btn-ghost">{{ __('account.buy_email') }}</a>
        </div>
    </div>

    @if ($pendingEmailPayment)
        <section class="mb-6 rounded-3xl border border-rose/20 bg-white p-6">
            <h2 class="text-xl font-bold text-black">{{ __('account.next_pay_title') }}</h2>
            <p class="mt-2 body-text">{{ __('account.next_pay_body', ['domain' => $pendingEmailPayment->domain]) }}</p>
            <form method="POST" action="{{ route('email.pay', $pendingEmailPayment) }}" class="mt-4" data-submit-form>
                @csrf
                <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="btn btn-primary" />
            </form>
        </section>
    @endif

    <section class="rounded-3xl border border-border bg-white p-6">
        <h2 class="text-xl font-bold text-black">{{ __('account.your_mailboxes') }}</h2>
        @if ($mailboxes->isEmpty())
            <p class="mt-4 body-text">{{ __('account.no_orders') }}</p>
        @else
            <ul class="mt-4 space-y-2">
                @foreach ($mailboxes as $mailbox)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border px-4 py-3">
                        <span class="font-semibold text-black">{{ $mailbox->address }}</span>
                        <span class="text-sm text-on-blush/60">{{ $mailbox->statusLabel() }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-sm text-on-blush/70">{{ __('email.invite_note') }}</p>
        @endif
    </section>

    @if ($orders->isNotEmpty())
        <section class="mt-6 rounded-3xl border border-border bg-white p-6">
            <h2 class="text-xl font-bold text-black">{{ __('account.email_orders') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach ($orders as $order)
                    <a href="{{ route('account.email.show', $order) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border px-4 py-4 transition hover:border-rose/40">
                        <span>
                            <span class="block font-semibold text-black">{{ $order->domain }}</span>
                            <span class="text-sm text-on-blush/65">{{ $order->plan_name }} · {{ __('hosting.cycles.' . $order->billing_cycle) }}</span>
                        </span>
                        <span class="text-sm font-semibold text-rose">{{ $order->statusLabel() }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($latestOrder && ! empty($latestOrder->dns_records))
        <section id="dns-records" class="mt-6 scroll-mt-8 rounded-3xl border border-border bg-white p-6 sm:p-8">
            <h2 class="text-xl font-bold text-black">{{ __('email.dns_title') }}</h2>
            <p class="mt-2 body-text">{{ __('email.dns_lede') }}</p>
            <div class="mt-4 overflow-x-auto rounded-2xl border border-border">
                <table class="min-w-full text-left text-sm">
                    <tbody>
                        @foreach ($latestOrder->dns_records as $record)
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
