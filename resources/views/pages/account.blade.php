@extends('layouts.account')

@section('title', __('account.account_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.dashboard_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_overview')"
        :title="__('account.welcome', ['name' => $user->name])"
        :lede="__('account.dashboard_lede')"
    />

    <div class="account-page-stack">
        @if ($nextStep === 'pay_email' && $pendingEmailPayment)
            <section class="account-hero-soft">
                <p class="account-page-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-panel-title" style="font-size:1.35rem">{{ __('account.next_pay_title') }}</h2>
                <p class="account-panel-lede">{{ __('account.next_pay_body', ['domain' => $pendingEmailPayment->domain]) }}</p>
                <div class="account-hero-actions">
                    <form method="POST" action="{{ route('email.pay', $pendingEmailPayment) }}" data-submit-form>
                        @csrf
                        <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                    </form>
                    <a href="{{ route('account.email.index') }}" class="account-btn-ghost">{{ __('account.manage') }}</a>
                </div>
            </section>
        @elseif ($nextStep === 'pay_vps' && $pendingVpsPayment)
            <section class="account-hero-soft">
                <p class="account-page-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-panel-title" style="font-size:1.35rem">{{ __('account.next_pay_vps_title') }}</h2>
                <p class="account-panel-lede">{{ __('account.next_pay_vps_body', ['plan' => $pendingVpsPayment->displayName()]) }}</p>
                <div class="account-hero-actions">
                    <form method="POST" action="{{ route('hosting.flutterwave.pay', $pendingVpsPayment) }}" data-submit-form>
                        @csrf
                        <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                    </form>
                    <a href="{{ route('account.vps.index') }}" class="account-btn-ghost">{{ __('account.manage') }}</a>
                </div>
            </section>
        @elseif ($nextStep === 'dns')
            <section class="account-hero">
                <p class="account-hero-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-hero-title">{{ __('account.next_dns_title') }}</h2>
                <p class="account-hero-lede">{{ __('account.next_dns_body') }}</p>
                <div class="account-hero-actions">
                    <a href="{{ route('account.email.index') }}#dns-records" class="acc-btn is-primary">{{ __('account.manage') }}</a>
                </div>
            </section>
        @elseif ($nextStep === 'webmail')
            <section class="account-hero">
                <p class="account-hero-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-hero-title">{{ __('account.next_webmail_title') }}</h2>
                <p class="account-hero-lede">{{ __('account.next_webmail_body') }}</p>
                <div class="account-hero-actions">
                    <a href="{{ $webmailUrl }}" target="_blank" rel="noopener noreferrer" class="acc-btn is-primary">{{ __('email.open_webmail') }}</a>
                    <a href="{{ route('account.email.index') }}" class="acc-btn">{{ __('account.manage') }}</a>
                </div>
            </section>
        @elseif ($nextStep === 'all_set')
            <section class="account-hero">
                <p class="account-hero-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-hero-title">{{ __('account.next_all_set_title') }}</h2>
                <p class="account-hero-lede">{{ __('account.next_all_set_body') }}</p>
            </section>
        @else
            <section class="account-hero">
                <p class="account-hero-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-hero-title">{{ __('account.next_browse_title') }}</h2>
                <p class="account-hero-lede">{{ __('account.next_browse_body') }}</p>
                <div class="account-hero-actions">
                    <a href="{{ route('email.plans') }}" class="acc-btn is-primary">{{ __('account.buy_email') }}</a>
                    <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="acc-btn">{{ __('account.buy_vps') }}</a>
                    <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="acc-btn">{{ __('account.buy_hosting') }}</a>
                </div>
            </section>
        @endif

        <section class="account-metrics" aria-label="{{ __('account.nav_overview') }}">
            <a href="{{ route('account.email.index') }}" class="account-metric">
                <span class="account-metric-label">{{ __('account.service_email') }}</span>
                <span class="account-metric-value">{{ $mailboxes->count() }}</span>
                <span class="account-metric-meta">{{ $mailboxes->take(2)->pluck('address')->join(', ') ?: __('account.no_orders') }}</span>
                <span class="account-metric-cta">{{ __('account.manage') }} →</span>
            </a>
            <a href="{{ route('account.vps.index') }}" class="account-metric">
                <span class="account-metric-label">{{ __('account.service_vps') }}</span>
                <span class="account-metric-value">{{ $vpsServers->count() }}</span>
                <span class="account-metric-meta">
                    {{ $vpsServers->first()?->displayName() ?: __('account.no_vps_short') }}
                    @if ($vpsServers->first()?->ipv4)
                        · {{ $vpsServers->first()->ipv4 }}
                    @endif
                </span>
                <span class="account-metric-cta">{{ __('account.manage') }} →</span>
            </a>
            <a href="{{ route('account.hosting.index') }}" class="account-metric">
                <span class="account-metric-label">{{ __('account.service_hosting') }}</span>
                <span class="account-metric-value">{{ $sharedHosting->count() }}</span>
                <span class="account-metric-meta">{{ $sharedHosting->first()?->plan_name ?: __('account.no_hosting_short') }}</span>
                <span class="account-metric-cta">{{ __('account.manage') }} →</span>
            </a>
            @if ($whmcsLinked ?? false)
                <a href="{{ route('account.subscriptions.index') }}" class="account-metric">
                    <span class="account-metric-label">{{ __('account.service_whmcs') }}</span>
                    <span class="account-metric-value">{{ $whmcsActiveCount }}</span>
                    <span class="account-metric-meta">
                        {{ __('account.whmcs_services_meta', ['total' => $whmcsServiceCount]) }}
                    </span>
                    <span class="account-metric-cta">{{ __('account.manage') }} →</span>
                </a>
                <a href="{{ route('account.domains.index') }}" class="account-metric">
                    <span class="account-metric-label">{{ __('account.nav_domains') }}</span>
                    <span class="account-metric-value">{{ $whmcsDomainCount }}</span>
                    <span class="account-metric-meta">{{ __('account.whmcs_domains_meta') }}</span>
                    <span class="account-metric-cta">{{ __('account.manage') }} →</span>
                </a>
                <a href="{{ route('account.invoices.index') }}" class="account-metric">
                    <span class="account-metric-label">{{ __('account.nav_invoices') }}</span>
                    <span class="account-metric-value">{{ $whmcsUnpaidInvoices }}</span>
                    <span class="account-metric-meta">{{ __('account.whmcs_invoices_meta') }}</span>
                    <span class="account-metric-cta">{{ __('account.manage') }} →</span>
                </a>
            @endif
        </section>

        @if ($recentInvoices->isNotEmpty())
            <x-account.table-card :title="__('account.recent_invoices')">
                <x-slot:tools>
                    <a href="{{ route('account.invoices.index') }}" class="acc-btn is-sm">{{ __('account.view_all') }}</a>
                </x-slot:tools>
                <div class="acc-table-wrap">
                    <table class="acc-table">
                        <thead>
                            <tr>
                                <th>{{ __('account.reference') }}</th>
                                <th>{{ __('account.description') }}</th>
                                <th class="is-num">{{ __('account.amount') }}</th>
                                <th>{{ __('account.status_label') }}</th>
                                <th>{{ __('account.date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentInvoices as $invoice)
                                <tr>
                                    <td><a href="{{ $invoice['url'] }}" class="acc-link">{{ $invoice['reference'] }}</a></td>
                                    <td><span class="acc-cell-main">{{ $invoice['label'] }}</span></td>
                                    <td class="is-num">{{ strtoupper($invoice['currency']) === 'NGN' ? '₦' : $invoice['currency'] . ' ' }}{{ number_format($invoice['amount'], 2) }}</td>
                                    <td><x-account.status :value="$invoice['status']" /></td>
                                    <td class="acc-cell-muted">{{ $invoice['date']?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-account.table-card>
        @endif
    </div>
@endsection
