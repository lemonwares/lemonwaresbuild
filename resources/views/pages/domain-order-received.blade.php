@extends('layouts.app')

@section('title', __('domain.order_received_title') . ' — ' . config('site.short_name'))
@section('meta_description', ($checkout ?? $order)?->isPaid() ? __('domain.order_received_paid_lede') : __('domain.order_received_awaiting_lede'))
@section('focus_flow', '1')

@section('content')
    @php
        $paid = $checkout?->isPaid() || $order?->isPaid();
        $lines = $checkout?->orders ?? collect($order ? [$order] : []);
        $hasTransfer = $lines->contains(fn ($line) => $line->isTransfer());
        $totalNgn = $checkout?->amount_ngn ?? $order?->amount_ngn ?? 0;
        $status = $checkout?->status ?? $order?->status;
        $payRoute = $checkout
            ? route('domain.checkout.pay', $checkout)
            : ($order ? route('domain.pay', $order) : null);
    @endphp

    <x-layout.page-hero
        :eyebrow="__('pages.domain.eyebrow')"
        :title="__('domain.order_received_title')"
        :lede="$paid ? __('domain.order_received_paid_lede') : __('domain.order_received_awaiting_lede')"
        cta-href="{{ route('domain') }}"
        :cta-label="__('domain.back_to_domains')"
    />

    <x-layout.page-content>
        <div class="mx-auto max-w-2xl rounded-3xl border border-border bg-white p-6 sm:p-8">
            @if (session('domain_feedback'))
                <p @class([
                    'mb-5 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => session('domain_feedback.type') === 'success',
                    'border border-sky-200 bg-sky-50 text-sky-800' => session('domain_feedback.type') === 'info',
                    'border border-rose/20 bg-rose/5 text-rose' => session('domain_feedback.type') === 'error',
                ])>{{ session('domain_feedback.message') }}</p>
            @endif

            <p class="section-label mb-3">{{ __('domain.order_summary') }}</p>

            <ul class="space-y-3">
                @foreach ($lines as $line)
                    <li class="border-b border-border pb-3">
                        <p class="text-lg font-bold text-black">{{ $line->domain }}</p>
                        <p class="mt-1 text-sm text-on-blush/70">
                            {{ $line->optionLabel() }} · {{ $line->periodLabel() }}
                            · {{ \App\Support\HostingPricing::ngnPriceDisplay((float) $line->amount_ngn) }}
                        </p>
                    </li>
                @endforeach
            </ul>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">{{ __('domain.amount_label') }}</dt>
                    <dd class="font-semibold text-black">{{ \App\Support\HostingPricing::ngnPriceDisplay((float) $totalNgn) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-b border-border pb-3">
                    <dt class="text-on-blush/60">{{ __('domain.status_label') }}</dt>
                    <dd class="font-semibold text-black">{{ $status }}</dd>
                </div>
                @if ($checkout?->whmcs_order_id || $order?->whmcs_order_id)
                    <div class="flex justify-between gap-4 border-b border-border pb-3">
                        <dt class="text-on-blush/60">WHMCS order</dt>
                        <dd class="font-semibold text-black">{{ $checkout?->whmcs_order_id ?: $order?->whmcs_order_id }}</dd>
                    </div>
                @endif
            </dl>

            @if ($hasTransfer)
                <p class="mt-5 text-sm text-on-blush/75">{{ __('domain.order_received_transfer_note') }}</p>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                @if (! $paid && $payRoute)
                    <form method="POST" action="{{ $payRoute }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ __('domain.pay_again') }}</button>
                    </form>
                @endif
                <a href="{{ route('domain') }}" class="btn btn-secondary">{{ __('domain.back_to_domains') }}</a>
                <a href="{{ route('contact') }}" class="btn btn-ghost">{{ __('domain.contact_support') }}</a>
            </div>
        </div>
    </x-layout.page-content>
@endsection
