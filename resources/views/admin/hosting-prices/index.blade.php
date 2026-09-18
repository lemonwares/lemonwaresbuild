@extends('layouts.admin')

@section('title', 'Hosting Prices — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Hosting Prices"
        lede="Set public Naira prices for cPanel, Plesk, and VPS. Specs come from config; leave blank amounts to fall back to defaults after sync."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Hosting Prices']]"
        class="mb-5"
    />

    <form
        method="POST"
        action="{{ route('admin.hosting-prices.update') }}"
        class="admin-page-stack"
        data-admin-prices-form
        data-submit-form
    >
        @csrf
        @method('PUT')

        @forelse ($plans as $planSlug => $plan)
            @php
                $planPrices = $prices->get($planSlug, collect());
            @endphp

            <section class="admin-panel admin-panel-flush" aria-label="{{ $plan['title'] ?? $planSlug }}">
                <div class="admin-panel-toolbar">
                    <div>
                        <p class="admin-metric-meta">{{ $plan['name'] ?? $planSlug }}</p>
                        <h2 class="admin-dash-panel-title">{{ $plan['title'] ?? $planSlug }}</h2>
                        <p class="admin-dash-panel-lede">Prices are in Naira (₦) and shown on the public site as ₦ only.</p>
                    </div>
                </div>

                @forelse ($planPrices as $index => $price)
                    @php
                        $specMeta = collect($plan['specifications'] ?? [])->firstWhere('key', $price->spec_key);
                        $defaultNgn = (float) ($specMeta['default_price'] ?? 0);
                    @endphp

                    <div class="admin-panel-pad border-t border-border">
                        <input type="hidden" name="prices[{{ $price->id }}][id]" value="{{ $price->id }}">
                        <input type="hidden" name="prices[{{ $price->id }}][currency]" value="NGN">

                        <div class="admin-edit-grid">
                            <div class="admin-field">
                                <span>{{ $specMeta['label'] ?? $price->spec_key }}</span>
                                <p class="admin-muted text-xs">{{ $price->spec_key }}</p>
                                @if ($defaultNgn > 0)
                                    <p class="admin-muted text-xs">Default ₦{{ number_format($defaultNgn, 0) }}/mo</p>
                                @endif
                            </div>

                            <label class="admin-field">
                                <span>Amount (₦ / mo)</span>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    name="prices[{{ $price->id }}][price_amount]"
                                    value="{{ old("prices.{$price->id}.price_amount", $price->price_amount) }}"
                                    class="admin-input"
                                    required
                                >
                            </label>

                            <label class="admin-field">
                                <span>Billing</span>
                                <select
                                    name="prices[{{ $price->id }}][billing_cycle]"
                                    class="admin-input"
                                    required
                                >
                                    @foreach (['monthly' => 'Monthly', 'bimonthly' => '2 Months', 'quarterly' => 'Quarterly', 'annually' => 'Annually'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old("prices.{$price->id}.billing_cycle", $price->billing_cycle) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="admin-field">
                                <span>Suffix</span>
                                <input
                                    type="text"
                                    name="prices[{{ $price->id }}][display_suffix]"
                                    value="{{ old("prices.{$price->id}.display_suffix", $price->display_suffix) }}"
                                    placeholder="/mo"
                                    class="admin-input"
                                >
                            </label>
                        </div>

                        <label class="admin-check">
                            <input
                                type="checkbox"
                                name="prices[{{ $price->id }}][is_visible]"
                                value="1"
                                @checked(old("prices.{$price->id}.is_visible", $price->is_visible))
                            >
                            <span>Show on site · {{ $price->formattedPrice() }}</span>
                        </label>
                    </div>
                @empty
                    <p class="admin-empty admin-panel-pad">No specifications found for this plan.</p>
                @endforelse
            </section>
        @empty
            <section class="admin-panel">
                <p class="admin-empty">No hosting plans configured.</p>
            </section>
        @endforelse

        <div class="flex justify-end">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save Prices</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>
@endsection
