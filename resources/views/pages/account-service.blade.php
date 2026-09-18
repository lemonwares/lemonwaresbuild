@extends('layouts.account')

@section('title', $lead->displayName() . ' — ' . config('site.short_name'))
@section('meta_description', __('account.dashboard_lede'))

@section('content')
    <x-account.page-header
        :kicker="$lead->isVps() ? __('account.service_vps') : __('account.service_hosting')"
        :title="$lead->displayName()"
        :lede="trim(($lead->plan_name ?? '') . ($lead->spec_label ? ' · ' . $lead->spec_label : ''))"
        :back-href="$indexRoute"
        :back-label="$indexLabel"
    >
        @if ($lead->panelUrl())
            <x-slot:actions>
                <a href="{{ $lead->panelUrl() }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('account.open_panel') }}</a>
            </x-slot:actions>
        @endif
    </x-account.page-header>

    <div class="account-page-stack">
        @if ($lead->isAwaitingPayment())
            <section class="account-hero-soft">
                <p class="account-page-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-panel-title" style="font-size:1.25rem">{{ __('account.next_pay_vps_title') }}</h2>
                <p class="account-panel-lede">{{ __('account.next_pay_vps_body', ['plan' => $lead->displayName()]) }}</p>
                <form method="POST" action="{{ route('hosting.flutterwave.pay', $lead) }}" class="mt-4" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                </form>
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="account-panel lg:col-span-2">
                <h2 class="account-panel-title">{{ __('account.connection_details') }}</h2>
                <dl class="account-dl mt-5">
                    <div>
                        <dt>{{ __('email.status_label') }}</dt>
                        <dd>{{ $lead->statusLabel() }}</dd>
                    </div>
                    @if ($lead->hostname)
                        <div>
                            <dt>{{ __('account.hostname') }}</dt>
                            <dd>{{ $lead->hostname }}</dd>
                        </div>
                    @endif
                    @if ($lead->ipv4)
                        <div>
                            <dt>{{ __('account.server_ip') }}</dt>
                            <dd class="account-mono">{{ $lead->ipv4 }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>{{ __('hosting.billing_period') }}</dt>
                        <dd>{{ __('hosting.cycles.' . ($lead->billing_cycle ?: 'monthly')) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('hosting.period_total') }}</dt>
                        <dd style="color:var(--color-rose)">{{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->amount_usd ?? 0)) }}</dd>
                    </div>
                </dl>

                @if ($lead->isVps() && $lead->ipv4)
                    <p class="mt-5 text-sm text-on-blush/70">{{ __('account.ssh_hint') }}</p>
                @elseif ($lead->isShared())
                    <p class="mt-5 text-sm text-on-blush/70">{{ __('account.hosting_portal_help') }}</p>
                @endif

                <div class="account-hero-actions">
                    @if ($lead->panelUrl())
                        <a href="{{ $lead->panelUrl() }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('account.open_panel') }}</a>
                    @endif
                    <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="account-btn-ghost">{{ __('account.need_help') }}</a>
                </div>
            </section>

            <aside class="account-panel">
                <h2 class="account-panel-title">{{ $lead->spec_label ?: $lead->plan_name }}</h2>
                @if ($lead->spec_summary)
                    <p class="account-panel-lede">{{ $lead->spec_summary }}</p>
                @endif
            </aside>
        </div>
    </div>
@endsection
