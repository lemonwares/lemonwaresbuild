@extends('layouts.account')

@section('title', __('account.service_vps') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.vps_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs
        :items="[__('account.nav_products') => route('account.products.index'), __('account.nav_vps') => null]"
        :back="route('account.products.index')"
    />
@endsection

@section('content')
    <div class="account-page-stack">
        @if ($pendingVpsPayment)
            <section class="account-hero-soft">
                <p class="account-hero-kicker">{{ __('account.next_step') }}</p>
                <h2 class="account-panel-title">{{ __('account.next_pay_vps_title') }}</h2>
                <p class="account-panel-lede">{{ __('account.next_pay_vps_body', ['plan' => $pendingVpsPayment->displayName()]) }}</p>
                <form method="POST" action="{{ route('hosting.flutterwave.pay', $pendingVpsPayment) }}" class="mt-4" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="acc-btn is-primary" />
                </form>
            </section>
        @endif

        <x-account.table-card :title="__('account.service_vps')" :count="$vpsServers->count()">
            <x-slot:tools>
                <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="acc-btn is-primary">{{ __('account.buy_vps') }}</a>
            </x-slot:tools>

            @if ($vpsServers->isEmpty())
                <div class="acc-empty">
                    <p class="acc-empty-title">{{ __('account.no_vps') }}</p>
                    <p>{{ __('account.vps_lede') }}</p>
                </div>
            @else
                <div class="acc-table-wrap">
                    <table class="acc-table">
                        <thead>
                            <tr>
                                <th>{{ __('account.service_vps') }}</th>
                                <th>{{ __('account.vps_ip') }}</th>
                                <th>{{ __('account.status_label') }}</th>
                                <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vpsServers as $server)
                                <tr>
                                    <td>
                                        <a href="{{ route('account.vps.show', $server) }}" class="acc-link">{{ $server->displayName() }}</a>
                                        <span class="acc-cell-sub">{{ $server->plan_name }}{{ $server->spec_label ? ' · ' . $server->spec_label : '' }}</span>
                                    </td>
                                    <td class="acc-cell-muted account-mono">{{ $server->ipv4 ?: '—' }}</td>
                                    <td><x-account.status :value="$server->statusLabel()" /></td>
                                    <td class="is-actions"><a href="{{ route('account.vps.show', $server) }}" class="acc-btn is-sm">{{ __('account.manage') }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-account.table-card>
    </div>
@endsection
