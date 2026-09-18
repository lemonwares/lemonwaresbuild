@extends('layouts.account')

@section('title', __('account.service_vps') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.vps_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_vps')"
        :title="__('account.service_vps')"
        :lede="__('account.vps_lede')"
    >
        <x-slot:actions>
            <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="account-btn-ghost">{{ __('account.buy_vps') }}</a>
        </x-slot:actions>
    </x-account.page-header>

    <div class="account-page-stack">
        @if ($pendingVpsPayment)
            <section class="account-hero-soft">
                <p class="account-page-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-panel-title" style="font-size:1.25rem">{{ __('account.next_pay_vps_title') }}</h2>
                <p class="account-panel-lede">{{ __('account.next_pay_vps_body', ['plan' => $pendingVpsPayment->displayName()]) }}</p>
                <form method="POST" action="{{ route('hosting.flutterwave.pay', $pendingVpsPayment) }}" class="mt-4" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="account-btn-primary" />
                </form>
            </section>
        @endif

        <section class="account-panel">
            @if ($vpsServers->isEmpty())
                <div class="account-empty">
                    <p class="account-empty-title">{{ __('account.no_vps') }}</p>
                    <div class="account-hero-actions">
                        <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="account-btn-primary">{{ __('account.buy_vps') }}</a>
                    </div>
                </div>
            @else
                <div class="account-list">
                    @foreach ($vpsServers as $server)
                        <a href="{{ route('account.vps.show', $server) }}" class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $server->displayName() }}</p>
                                <p class="account-list-meta">{{ $server->plan_name }}{{ $server->spec_label ? ' · ' . $server->spec_label : '' }}</p>
                                @if ($server->ipv4)
                                    <p class="account-list-meta account-mono">{{ $server->ipv4 }}</p>
                                @endif
                            </div>
                            <span class="account-metric-cta">{{ $server->statusLabel() }} →</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
