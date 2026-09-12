@extends('layouts.account')

@section('title', __('account.service_vps') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.vps_lede'))

@section('content')
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="section-label mb-2">{{ __('account.nav_vps') }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.service_vps') }}</h1>
            <p class="lede mt-2">{{ __('account.vps_lede') }}</p>
        </div>
        <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="btn btn-ghost">{{ __('account.buy_vps') }}</a>
    </div>

    @if ($pendingVpsPayment)
        <section class="mb-6 rounded-3xl border border-rose/20 bg-white p-6">
            <h2 class="text-xl font-bold text-black">{{ __('account.next_pay_vps_title') }}</h2>
            <p class="mt-2 body-text">{{ __('account.next_pay_vps_body', ['plan' => $pendingVpsPayment->displayName()]) }}</p>
            <form method="POST" action="{{ route('hosting.flutterwave.pay', $pendingVpsPayment) }}" class="mt-4" data-submit-form>
                @csrf
                <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="btn btn-primary" />
            </form>
        </section>
    @endif

    <section class="rounded-3xl border border-border bg-white p-6">
        @forelse ($vpsServers as $server)
            <a href="{{ route('account.vps.show', $server) }}" class="mb-3 block rounded-2xl border border-border px-4 py-4 last:mb-0 transition hover:border-rose/40">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-black">{{ $server->displayName() }}</p>
                        <p class="mt-1 text-sm text-on-blush/65">{{ $server->plan_name }}{{ $server->spec_label ? ' · ' . $server->spec_label : '' }}</p>
                        @if ($server->ipv4)
                            <p class="mt-2 font-mono text-sm text-black">{{ $server->ipv4 }}</p>
                        @endif
                    </div>
                    <p class="text-sm font-semibold text-rose">{{ $server->statusLabel() }}</p>
                </div>
            </a>
        @empty
            <p class="body-text">{{ __('account.no_vps') }}</p>
        @endforelse
    </section>
@endsection
