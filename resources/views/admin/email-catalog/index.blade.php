@extends('layouts.admin')

@section('title', 'Email & Suite Pricing — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $mailemonPlans = $plans->filter(fn ($plan) => in_array($plan->provider, ['lemonmail', 'titan'], true))->values();
    $googlePlans = $plans->where('provider', 'google_workspace')->values();
    $microsoftPlans = $plans->where('provider', 'ms365')->values();
@endphp

@section('content')
    <x-admin.page-header
        title="Email & Suite Pricing"
        lede="Edit Mailemon mailbox plans plus Google Workspace and Microsoft 365 per-user prices shown on the public pages. Amounts are ₦ only."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Email & Suite Pricing']]"
        class="mb-5"
    />

    <form
        method="POST"
        action="{{ route('admin.email-catalog.update') }}"
        class="admin-page-stack"
        data-admin-prices-form
        data-submit-form
    >
        @csrf
        @method('PUT')

        @foreach ([
            [
                'title' => 'Mailemon plans',
                'meta' => 'Lemon Mail · TrekMail',
                'lede' => 'Mailbox counts, fulfilment mode, and featured plan for /email.',
                'rows' => $mailemonPlans,
            ],
            [
                'title' => 'Google Workspace',
                'meta' => 'Public /google-workspace',
                'lede' => 'Per-user monthly ₦ prices shown on the Google Workspace page.',
                'rows' => $googlePlans,
            ],
            [
                'title' => 'Microsoft 365',
                'meta' => 'Public /microsoft-365',
                'lede' => 'Per-user monthly ₦ prices shown on the Microsoft 365 page.',
                'rows' => $microsoftPlans,
            ],
        ] as $group)
            <section class="admin-panel admin-panel-flush" aria-label="{{ $group['title'] }}">
                <div class="admin-panel-toolbar">
                    <div>
                        <p class="admin-metric-meta">{{ $group['meta'] }}</p>
                        <h2 class="admin-dash-panel-title">{{ $group['title'] }}</h2>
                        <p class="admin-dash-panel-lede">{{ $group['lede'] }}</p>
                    </div>
                </div>

                @forelse ($group['rows'] as $plan)
                    <div class="admin-panel-pad border-t border-border">
                        <input type="hidden" name="plans[{{ $plan->id }}][id]" value="{{ $plan->id }}">

                        <div class="admin-edit-grid">
                            <div class="admin-field">
                                <span>{{ __('email.plans.' . $plan->plan_key . '.name') }}</span>
                                <p class="admin-muted text-xs">{{ $plan->plan_key }}</p>
                            </div>

                            <label class="admin-field">
                                <span>Provider</span>
                                <select name="plans[{{ $plan->id }}][provider]" class="admin-input">
                                    <option value="lemonmail" @selected(old("plans.{$plan->id}.provider", $plan->provider) === 'lemonmail')>Mailemon</option>
                                    <option value="titan" @selected(old("plans.{$plan->id}.provider", $plan->provider) === 'titan')>Titan</option>
                                    <option value="google_workspace" @selected(old("plans.{$plan->id}.provider", $plan->provider) === 'google_workspace')>Google Workspace</option>
                                    <option value="ms365" @selected(old("plans.{$plan->id}.provider", $plan->provider) === 'ms365')>Microsoft 365</option>
                                </select>
                            </label>

                            <label class="admin-field">
                                <span>Fulfilment</span>
                                <select name="plans[{{ $plan->id }}][fulfilment_mode]" class="admin-input">
                                    <option value="auto" @selected(old("plans.{$plan->id}.fulfilment_mode", $plan->fulfilment_mode) === 'auto')>Automatic</option>
                                    <option value="manual" @selected(old("plans.{$plan->id}.fulfilment_mode", $plan->fulfilment_mode) === 'manual')>Manual queue</option>
                                </select>
                            </label>

                            <label class="admin-field">
                                <span>{{ in_array($plan->provider, ['google_workspace', 'ms365'], true) ? 'Seats (pricing unit)' : 'Mailboxes' }}</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="500"
                                    name="plans[{{ $plan->id }}][mailbox_count]"
                                    value="{{ old("plans.{$plan->id}.mailbox_count", $plan->mailbox_count) }}"
                                    class="admin-input"
                                    required
                                >
                            </label>

                            <label class="admin-field">
                                <span>{{ in_array($plan->provider, ['google_workspace', 'ms365'], true) ? 'Monthly ₦ / user' : 'Monthly ₦' }}</span>
                                <input
                                    type="number"
                                    step="1"
                                    min="0"
                                    name="plans[{{ $plan->id }}][monthly_ngn]"
                                    value="{{ old("plans.{$plan->id}.monthly_ngn", (int) round((float) $plan->monthly_usd * max(1, \App\Support\HostingPricing::usdToNgnRate()))) }}"
                                    class="admin-input"
                                    required
                                >
                                <p class="admin-muted text-xs">Public site shows Naira only. Stored against the live USD→NGN rate.</p>
                            </label>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-6">
                            @if (in_array($plan->provider, ['lemonmail', 'titan'], true))
                                <label class="admin-check" style="margin-top:0">
                                    <input
                                        type="radio"
                                        name="featured_plan_id"
                                        value="{{ $plan->id }}"
                                        @checked(old('featured_plan_id', $plans->firstWhere('featured', true)?->id) == $plan->id)
                                    >
                                    <span>Featured on /email</span>
                                </label>
                            @else
                                <label class="admin-check" style="margin-top:0">
                                    <input
                                        type="checkbox"
                                        name="plans[{{ $plan->id }}][featured_suite]"
                                        value="1"
                                        @checked(old("plans.{$plan->id}.featured_suite", $plan->featured))
                                    >
                                    <span>Featured on suite page</span>
                                </label>
                            @endif

                            <label class="admin-check" style="margin-top:0">
                                <input
                                    type="checkbox"
                                    name="plans[{{ $plan->id }}][is_visible]"
                                    value="1"
                                    @checked(old("plans.{$plan->id}.is_visible", $plan->is_visible))
                                >
                                <span>Show on site</span>
                            </label>
                        </div>
                    </div>
                @empty
                    <p class="admin-empty admin-panel-pad">No plans in this group yet. Run catalog sync to create defaults.</p>
                @endforelse
            </section>
        @endforeach

        <section class="admin-panel admin-panel-flush" aria-label="Billing cycle discounts">
            <div class="admin-panel-toolbar">
                <div>
                    <p class="admin-metric-meta">Billing</p>
                    <h2 class="admin-dash-panel-title">Cycle discounts</h2>
                    <p class="admin-dash-panel-lede">Bi-annual is 6 months. Discount applies to the full period total.</p>
                </div>
            </div>

            @foreach ($cycles as $cycle)
                <div class="admin-panel-pad border-t border-border">
                    <input type="hidden" name="cycles[{{ $cycle->id }}][id]" value="{{ $cycle->id }}">

                    <div class="admin-edit-grid">
                        <div class="admin-field">
                            <span>{{ __('hosting.cycles.' . $cycle->cycle_key) }}</span>
                            <p class="admin-muted text-xs">{{ $cycle->cycle_key }} · {{ $cycle->months }} {{ str('month')->plural($cycle->months) }}</p>
                        </div>

                        <label class="admin-field">
                            <span>Discount %</span>
                            <input
                                type="number"
                                min="0"
                                max="90"
                                name="cycles[{{ $cycle->id }}][discount_percent]"
                                value="{{ old("cycles.{$cycle->id}.discount_percent", $cycle->discount_percent) }}"
                                class="admin-input"
                                required
                            >
                        </label>
                    </div>

                    <label class="admin-check">
                        <input
                            type="checkbox"
                            name="cycles[{{ $cycle->id }}][is_visible]"
                            value="1"
                            @checked(old("cycles.{$cycle->id}.is_visible", $cycle->is_visible))
                        >
                        <span>Show on site</span>
                    </label>
                </div>
            @endforeach
        </section>

        <div class="flex justify-end">
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save pricing</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>
@endsection
