@extends('layouts.app')

@section('title', __('domain.cart_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('domain.cart_lede'))
@section('focus_flow', '1')

@section('content')
    <section class="domain-cart-hero">
        <div class="domain-landing-hero-glow" aria-hidden="true"></div>
        <div class="container-page relative z-10 py-14 sm:py-18">
            <p class="domain-cart-eyebrow">{{ __('pages.domain.eyebrow') }}</p>
            <h1 class="mt-3 max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('domain.cart_title') }}
            </h1>
            <p class="domain-cart-lede mt-4 max-w-2xl text-base sm:text-lg">
                {{ __('domain.cart_lede') }}
            </p>
        </div>
    </section>

    <section class="domain-cart-body">
        <div class="container-page py-10 sm:py-14">
            @if (session('domain_feedback'))
                <p @class([
                    'mb-6 rounded-xl px-4 py-3 text-sm',
                    'border border-emerald-200 bg-emerald-50 text-emerald-800' => session('domain_feedback.type') === 'success',
                    'border border-sky-200 bg-sky-50 text-sky-800' => session('domain_feedback.type') === 'info',
                    'border border-rose/20 bg-rose/5 text-rose' => session('domain_feedback.type') === 'error',
                ])>{{ session('domain_feedback.message') }}</p>
            @endif

            @if ($refreshError)
                <p class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $refreshError }}</p>
            @endif

            @if (count($items) < 1)
                <div class="domain-cart-empty">
                    <p class="text-lg font-semibold text-black">{{ __('domain.cart_empty') }}</p>
                    <p class="mt-2 text-sm text-on-blush/70">{{ __('domain.cart_lede') }}</p>
                    <a href="{{ route('domain') }}" class="btn btn-primary mt-6 inline-flex">
                        {{ __('domain.cart_empty_cta') }}
                    </a>
                </div>
            @else
                <div
                    class="domain-cart-layout"
                    data-domain-cart
                    data-update-url-template="{{ url('/domain/cart/__ID__') }}"
                    data-csrf="{{ csrf_token() }}"
                >
                    <div class="domain-cart-lines">
                        @foreach ($items as $item)
                            <article
                                class="domain-cart-line"
                                data-cart-item
                                data-item-id="{{ $item['id'] }}"
                            >
                                <div class="domain-cart-line-main">
                                    <div>
                                        <p class="domain-cart-domain">{{ $item['domain'] }}</p>
                                        <p class="domain-cart-meta">
                                            {{ ($item['option'] ?? '') === 'transfer' ? __('domain.option_transfer') : __('domain.option_register') }}
                                            · <span data-item-period-label>{{ $item['period_label'] }}</span>
                                        </p>
                                    </div>
                                    <p class="domain-cart-price" data-item-price>{{ $item['display'] }}</p>
                                </div>

                                <div class="domain-cart-line-actions">
                                    @if (($item['option'] ?? '') === 'register' && count($item['available_periods'] ?? []) > 1)
                                        <div>
                                            <label class="domain-cart-label" for="period-{{ $item['id'] }}">
                                                {{ __('domain.cart_period') }}
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

                                    <form method="POST" action="{{ route('domain.cart.destroy', $item['id']) }}" class="ml-auto">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="domain-cart-remove">
                                            {{ __('domain.cart_remove') }}
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside class="domain-cart-summary">
                        <p class="domain-cart-summary-label">{{ __('domain.cart_subtotal') }}</p>
                        <p class="domain-cart-summary-total" data-cart-total>{{ $totals['display'] }}</p>
                        <p class="domain-cart-summary-count">
                            {{ trans_choice('domain.cart_item_count', count($items), ['count' => count($items)]) }}
                        </p>
                        <a href="{{ route('domain.checkout') }}" class="domain-cart-checkout-btn">
                            {{ __('domain.cart_continue') }}
                        </a>
                        <a href="{{ route('domain') }}" class="domain-cart-keep-btn">
                            {{ __('domain.cart_keep_shopping') }}
                        </a>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection
