@extends('layouts.app')

@section('title', __('cart.received_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('cart.received_paid_lede'))

@section('content')
    <section class="domain-cart-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-14 sm:py-18">
            <a href="{{ route('cart') }}" class="page-back">
                <x-ui.icons.arrow-left class="page-back-icon" />
                {{ __('site.common.go_back') }}
            </a>
            <h1 class="mt-8 max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('cart.received_title') }}
            </h1>
            <p class="domain-cart-lede mt-4 max-w-2xl text-base sm:text-lg">
                {{ $checkout->isPaid() ? __('cart.received_paid_lede') : __('cart.received_awaiting_lede') }}
            </p>
        </div>
    </section>

    <section class="domain-cart-body">
        <div class="container-page py-10 sm:py-14">
            @if (session('cart_feedback'))
                <p @class([
                    'mb-6 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => session('cart_feedback.type') === 'success',
                    'border border-sky-200 bg-sky-50 text-sky-800' => session('cart_feedback.type') === 'info',
                    'border border-rose/20 bg-rose/5 text-rose' => session('cart_feedback.type') === 'error',
                ])>{{ session('cart_feedback.message') }}</p>
            @endif

            <div class="mx-auto max-w-2xl rounded-md border border-border bg-white p-6 shadow-[0_8px_28px_rgba(0,0,0,0.04)] sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-widest text-on-blush/55">{{ __('cart.order_summary') }}</p>
                <ul class="mt-4 space-y-2">
                    @foreach ($checkout->items as $item)
                        <li class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <span class="font-semibold text-black">
                                {{ __('cart.type_'.$item->type) }} · {{ $item->label }}
                            </span>
                            <span class="text-on-blush/75">{{ \App\Support\HostingPricing::ngnPriceDisplay((float) $item->amount_ngn) }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-5 text-lg font-bold text-black">
                    {{ __('domain.amount_label') }}:
                    {{ \App\Support\HostingPricing::ngnPriceDisplay((float) $checkout->amount_ngn) }}
                </p>
                <p class="mt-2 text-sm text-on-blush/70">
                    {{ __('domain.status_label') }}: {{ $checkout->status }}
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @if (! $checkout->isPaid())
                        <form method="POST" action="{{ route('checkout.pay', $checkout) }}" data-submit-form>
                            @csrf
                            <x-ui.submit-button
                                :label="__('cart.pay_again')"
                                :loading="__('account.starting_payment')"
                                class="btn btn-primary"
                            />
                        </form>
                    @endif
                    <a href="{{ route('cart') }}" class="btn btn-ghost">{{ __('cart.nav') }}</a>
                    <a href="{{ route('contact') }}" class="btn btn-ghost">{{ __('domain.contact_support') }}</a>
                </div>
            </div>
        </div>
    </section>
@endsection
