@extends('layouts.account')

@section('title', __('account.service_email') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.email_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_email')"
        :title="__('account.service_email')"
        :lede="__('account.email_lede')"
    >
        <x-slot:actions>
            @if ($latestOrder && ($latestOrder->status === 'provisioned' || $latestOrder->trekmail_domain_id))
                <a href="{{ $webmailUrl }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('email.open_webmail') }}</a>
            @endif
            <a href="{{ route('email.plans') }}" class="account-btn-ghost">{{ __('account.buy_email') }}</a>
        </x-slot:actions>
    </x-account.page-header>

    <div class="account-page-stack">
        @if ($pendingEmailPayment)
            <section class="account-hero-soft">
                <p class="account-page-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-panel-title" style="font-size:1.25rem">{{ __('account.next_pay_title') }}</h2>
                <p class="account-panel-lede">{{ __('account.next_pay_body', ['domain' => $pendingEmailPayment->domain]) }}</p>
                <form method="POST" action="{{ route('email.pay', $pendingEmailPayment) }}" class="mt-4" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                </form>
            </section>
        @endif

        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.your_mailboxes') }}</h2>
            @if ($mailboxes->isEmpty())
                <div class="mt-4 account-empty">
                    <p class="account-empty-title">{{ __('account.no_orders') }}</p>
                </div>
            @else
                <div class="account-list mt-4">
                    @foreach ($mailboxes as $mailbox)
                        <div class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $mailbox->address }}</p>
                            </div>
                            <span class="account-pill is-muted">{{ $mailbox->statusLabel() }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-on-blush/70">{{ __('email.invite_note') }}</p>
            @endif
        </section>

        @if ($orders->isNotEmpty())
            <section class="account-panel">
                <h2 class="account-panel-title">{{ __('account.email_orders') }}</h2>
                <div class="account-list mt-4">
                    @foreach ($orders as $order)
                        <a href="{{ route('account.email.show', $order) }}" class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $order->domain }}</p>
                                <p class="account-list-meta">{{ $order->plan_name }} · {{ __('hosting.cycles.' . $order->billing_cycle) }}</p>
                            </div>
                            <span class="account-metric-cta">{{ $order->statusLabel() }} →</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($latestOrder && ! empty($latestOrder->dns_records))
            <section id="dns-records" class="account-panel account-panel-flush scroll-mt-8">
                <div class="account-panel-toolbar">
                    <div>
                        <h2 class="account-panel-title">{{ __('email.dns_title') }}</h2>
                        <p class="account-panel-lede">{{ __('email.dns_lede') }}</p>
                    </div>
                </div>
                <div class="account-table-wrap">
                    <table class="account-table" style="min-width:28rem">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Host</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($latestOrder->dns_records as $record)
                                @php $row = is_array($record) ? $record : ['value' => $record]; @endphp
                                <tr>
                                    <td><strong>{{ $row['type'] ?? $row['record_type'] ?? 'DNS' }}</strong></td>
                                    <td>{{ $row['name'] ?? $row['host'] ?? '@' }}</td>
                                    <td class="account-mono break-all">{{ $row['value'] ?? $row['content'] ?? $row['data'] ?? json_encode($row) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
