@extends('layouts.account')

@section('title', __('account.account_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.dashboard_lede'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-2">{{ __('account.nav_overview') }}</p>
        <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.welcome', ['name' => $user->name]) }}</h1>
        <p class="lede mt-2">{{ __('account.dashboard_lede') }}</p>
    </div>

    @if ($nextStep === 'pay_email' && $pendingEmailPayment)
        <section class="mb-6 rounded-3xl border border-rose/20 bg-white p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('account.next_pay_title') }}</h2>
            <p class="mt-2 body-text">{{ __('account.next_pay_body', ['domain' => $pendingEmailPayment->domain]) }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('email.pay', $pendingEmailPayment) }}" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="btn btn-primary" />
                </form>
                <a href="{{ route('account.email.index') }}" class="btn btn-ghost">{{ __('account.manage') }}</a>
            </div>
        </section>
    @elseif ($nextStep === 'pay_vps' && $pendingVpsPayment)
        <section class="mb-6 rounded-3xl border border-rose/20 bg-white p-6 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-black">{{ __('account.next_pay_vps_title') }}</h2>
            <p class="mt-2 body-text">{{ __('account.next_pay_vps_body', ['plan' => $pendingVpsPayment->displayName()]) }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('hosting.flutterwave.pay', $pendingVpsPayment) }}" data-submit-form>
                    @csrf
                    <x-ui.submit-button :label="__('email.pay_with_flutterwave')" :loading="__('account.starting_payment')" class="btn btn-primary" />
                </form>
                <a href="{{ route('account.vps.index') }}" class="btn btn-ghost">{{ __('account.manage') }}</a>
            </div>
        </section>
    @elseif ($nextStep === 'dns')
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_dns_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">{{ __('account.next_dns_body') }}</p>
            <a href="{{ route('account.email.index') }}#dns-records" class="btn mt-5 bg-white text-rose hover:bg-blush">{{ __('account.manage') }}</a>
        </section>
    @elseif ($nextStep === 'webmail')
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_webmail_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">{{ __('account.next_webmail_body') }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ $webmailUrl }}" target="_blank" rel="noopener noreferrer" class="btn bg-white text-rose hover:bg-blush">{{ __('email.open_webmail') }}</a>
                <a href="{{ route('account.email.index') }}" class="btn border border-white/40 bg-transparent text-white hover:bg-white/10">{{ __('account.manage') }}</a>
            </div>
        </section>
    @elseif ($nextStep === 'all_set')
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_all_set_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">{{ __('account.next_all_set_body') }}</p>
        </section>
    @else
        <section class="mb-6 rounded-3xl bg-rose p-6 text-white sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-white/75">{{ __('account.next_step') }}</p>
            <h2 class="mt-2 text-2xl font-bold">{{ __('account.next_browse_title') }}</h2>
            <p class="mt-2 text-base font-light text-white/90">{{ __('account.next_browse_body') }}</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('email.plans') }}" class="btn bg-white text-rose hover:bg-blush">{{ __('account.buy_email') }}</a>
                <a href="{{ route('hosting.specifications', ['plan' => 'vps']) }}" class="btn border border-white/40 bg-transparent text-white hover:bg-white/10">{{ __('account.buy_vps') }}</a>
                <a href="{{ route('hosting.specifications', ['plan' => 'cpanel']) }}" class="btn border border-white/40 bg-transparent text-white hover:bg-white/10">{{ __('account.buy_hosting') }}</a>
            </div>
        </section>
    @endif

    <div class="grid gap-4 sm:grid-cols-3">
        <a href="{{ route('account.email.index') }}" class="rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/50">{{ __('account.service_email') }}</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $mailboxes->count() }}</p>
            <p class="mt-2 text-sm text-on-blush/70">
                {{ $mailboxes->take(2)->pluck('address')->join(', ') ?: __('account.no_orders') }}
            </p>
            <p class="mt-4 text-sm font-semibold text-rose">{{ __('account.manage') }} →</p>
        </a>

        <a href="{{ route('account.vps.index') }}" class="rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/50">{{ __('account.service_vps') }}</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $vpsServers->count() }}</p>
            <p class="mt-2 text-sm text-on-blush/70">{{ $vpsServers->first()?->displayName() ?: __('account.no_vps_short') }}</p>
            @if ($vpsServers->first()?->ipv4)
                <p class="mt-1 font-mono text-sm text-black">{{ $vpsServers->first()->ipv4 }}</p>
            @endif
            <p class="mt-4 text-sm font-semibold text-rose">{{ __('account.manage') }} →</p>
        </a>

        <a href="{{ route('account.hosting.index') }}" class="rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/50">{{ __('account.service_hosting') }}</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $sharedHosting->count() }}</p>
            <p class="mt-2 text-sm text-on-blush/70">
                {{ $sharedHosting->first()?->plan_name ?: __('account.no_hosting_short') }}
            </p>
            <p class="mt-4 text-sm font-semibold text-rose">{{ __('account.manage') }} →</p>
        </a>
    </div>
@endsection
