@extends('layouts.account')

@section('title', $lead->displayName() . ' — ' . config('site.short_name'))
@section('meta_description', __('account.dashboard_lede'))

@section('content')
    <p class="mb-6">
        <a href="{{ $indexRoute }}" class="text-sm font-semibold text-rose hover:underline">← {{ $indexLabel }}</a>
    </p>

    <div class="mb-8">
        <p class="section-label mb-2">{{ $lead->isVps() ? __('account.service_vps') : __('account.service_hosting') }}</p>
        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ $lead->displayName() }}</h1>
        <p class="lede mt-2">{{ $lead->plan_name }}{{ $lead->spec_label ? ' · ' . $lead->spec_label : '' }}</p>
    </div>

    @if ($lead->isAwaitingPayment())
        <section class="mb-6 rounded-3xl border border-rose/20 bg-white p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('account.next_pay_vps_title') }}</h2>
            <p class="mt-2 body-text">{{ __('account.next_pay_vps_body', ['plan' => $lead->displayName()]) }}</p>
            <form method="POST" action="{{ route('hosting.flutterwave.pay', $lead) }}" class="mt-5" data-submit-form>
                @csrf
                <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="btn btn-primary" />
            </form>
        </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-border bg-white p-6 lg:col-span-2">
            <h2 class="text-xl font-bold text-black">{{ __('account.connection_details') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/55">{{ __('email.status_label') }}</dt>
                    <dd class="font-semibold text-black">{{ $lead->statusLabel() }}</dd>
                </div>
                @if ($lead->hostname)
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/55">{{ __('account.hostname') }}</dt>
                        <dd class="font-semibold text-black">{{ $lead->hostname }}</dd>
                    </div>
                @endif
                @if ($lead->ipv4)
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/55">{{ __('account.server_ip') }}</dt>
                        <dd class="font-mono font-semibold text-black">{{ $lead->ipv4 }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/55">{{ __('hosting.billing_period') }}</dt>
                    <dd class="font-semibold text-black">{{ __('hosting.cycles.' . ($lead->billing_cycle ?: 'monthly')) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-on-blush/55">{{ __('hosting.period_total') }}</dt>
                    <dd class="font-semibold text-rose">{{ \App\Support\HostingPricing::dualPriceDisplay((float) ($lead->amount_usd ?? 0)) }}</dd>
                </div>
            </dl>

            @if ($lead->isVps() && $lead->ipv4)
                <p class="mt-6 text-sm text-on-blush/70">{{ __('account.ssh_hint') }}</p>
            @elseif ($lead->isShared())
                <p class="mt-6 text-sm text-on-blush/70">{{ __('account.hosting_portal_help') }}</p>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($lead->panelUrl())
                    <a href="{{ $lead->panelUrl() }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">{{ __('account.open_panel') }}</a>
                @endif
                <a href="{{ config('site.whatsapp') }}" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">{{ __('account.need_help') }}</a>
            </div>
        </section>

        <aside class="rounded-3xl border border-border bg-white p-6">
            <h2 class="text-lg font-bold text-black">{{ $lead->spec_label ?: $lead->plan_name }}</h2>
            @if ($lead->spec_summary)
                <p class="mt-3 text-sm text-on-blush/70">{{ $lead->spec_summary }}</p>
            @endif
        </aside>
    </div>
@endsection
