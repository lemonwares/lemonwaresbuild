@extends('layouts.app')

@section('title', __('cart.title') . ' — ' . config('site.short_name'))
@section('meta_description', __('cart.lede'))

@section('content')
    <section class="domain-cart-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-14 sm:py-18">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('domain') }}" class="page-back">
                <x-ui.icons.arrow-left class="page-back-icon" />
                {{ __('site.common.go_back') }}
            </a>
            <h1 class="mt-8 max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('cart.title') }}
            </h1>
            <p class="domain-cart-lede mt-4 max-w-2xl text-base sm:text-lg">
                {{ __('cart.lede') }}
            </p>
        </div>
    </section>

    <section class="domain-cart-body">
        <div class="container-page py-10 sm:py-14">
            @php
                $feedback = session('cart_feedback') ?? session('domain_feedback');
            @endphp
            @if ($feedback)
                <p @class([
                    'mb-6 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => ($feedback['type'] ?? '') === 'success',
                    'border border-sky-200 bg-sky-50 text-sky-800' => ($feedback['type'] ?? '') === 'info',
                    'border border-rose/20 bg-rose/5 text-rose' => ($feedback['type'] ?? '') === 'error',
                ])>{{ $feedback['message'] ?? '' }}</p>
            @endif

            @if ($refreshError)
                <p class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $refreshError }}</p>
            @endif

            @if (count($items) < 1)
                <div class="domain-cart-empty">
                    <p class="text-lg font-semibold text-black">{{ __('cart.empty') }}</p>
                    <p class="mt-2 text-sm text-on-blush/70">{{ __('cart.empty_hint') }}</p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('domain') }}" class="btn btn-primary inline-flex">{{ __('cart.empty_cta') }}</a>
                        <a href="{{ route('email.plans') }}" class="btn btn-ghost inline-flex">{{ __('site.nav.email') }}</a>
                    </div>
                </div>
            @else
                <div
                    class="domain-cart-layout"
                    data-site-cart
                    data-update-url-template="{{ url('/cart/__ID__') }}"
                    data-csrf="{{ csrf_token() }}"
                >
                    <div class="domain-cart-lines">
                        @foreach ($items as $item)
                            @php $type = $item['type'] ?? 'domain'; @endphp
                            <article
                                class="domain-cart-line"
                                data-cart-item
                                data-item-id="{{ $item['id'] }}"
                                data-item-type="{{ $type }}"
                            >
                                <div class="domain-cart-line-main">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-widest text-rose/80">
                                            {{ __('cart.type_'.$type) }}
                                        </p>
                                        <p class="domain-cart-domain mt-1">{{ $item['label'] ?? $item['domain'] ?? 'Item' }}</p>
                                        <p class="domain-cart-meta">
                                            @if ($type === 'domain')
                                                {{ ($item['option'] ?? '') === 'transfer' ? __('domain.option_transfer') : __('domain.option_register') }}
                                                · <span data-item-period-label>{{ $item['period_label'] }}</span>
                                            @elseif ($type === 'email')
                                                {{ $item['billing_cycle_label'] ?? '' }}
                                                · {{ trans_choice('email.mailboxes', $item['mailbox_count'] ?? 1, ['count' => $item['mailbox_count'] ?? 1]) }}
                                            @else
                                                {{ $item['spec_label'] ?? '' }}
                                                · {{ $item['billing_cycle_label'] ?? '' }}
                                            @endif
                                        </p>
                                    </div>
                                    <p class="domain-cart-price" data-item-price>{{ $item['display'] }}</p>
                                </div>

                                <div class="domain-cart-line-actions">
                                    @if ($type === 'domain' && ($item['option'] ?? '') === 'register' && count($item['available_periods'] ?? []) > 1)
                                        <div>
                                            <label class="domain-cart-label" for="period-{{ $item['id'] }}">
                                                {{ __('cart.period') }}
                                            </label>
                                            <select
                                                id="period-{{ $item['id'] }}"
                                                class="domain-cart-select"
                                                data-cart-period
                                            >
                                                @foreach ($item['available_periods'] as $years)
                                                    <option value="{{ $years }}" @selected((int) $item['reg_period'] === (int) $years)>
                                                        {{ trans_choice('domain.period_years', $years, ['count' => $years]) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <div class="ml-auto">
                                        <x-ui.confirm-modal
                                            :action="route('cart.destroy', $item['id'])"
                                            :title="__('cart.remove_confirm_title')"
                                            :body="__('cart.remove_confirm_body', ['item' => $item['label'] ?? $item['domain'] ?? __('cart.title')])"
                                            :confirm-label="__('cart.remove')"
                                            :cancel-label="__('site.common.cancel')"
                                            :open-label="__('cart.remove')"
                                            open-class="domain-cart-remove"
                                        >
                                            @method('DELETE')
                                        </x-ui.confirm-modal>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="domain-cart-summary">
                        <p class="domain-cart-summary-label">{{ __('cart.subtotal') }}</p>
                        <p class="domain-cart-summary-total" data-cart-total>{{ $totals['display'] }}</p>
                        <p class="domain-cart-summary-count">
                            {{ trans_choice('cart.item_count', count($items), ['count' => count($items)]) }}
                        </p>
                        <a
                            href="{{ route('checkout') }}"
                            class="domain-cart-checkout-btn"
                            data-action-loading
                            data-loading-label="{{ __('cart.continue_loading') }}"
                        >
                            <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-action-spinner aria-hidden="true"></span>
                            <span data-action-label>{{ __('cart.continue') }}</span>
                            <span class="hidden" data-action-loading-label>{{ __('cart.continue_loading') }}</span>
                        </a>
                        <a href="{{ route('domain') }}" class="domain-cart-keep-btn">
                            {{ __('cart.keep_shopping') }}
                        </a>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection
