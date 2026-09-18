@extends('layouts.admin')

@section('title', 'Lemon Mail Pricing — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Lemon Mail Pricing"
        lede="Set mailbox counts, monthly Naira prices, billing discounts, and which plan is featured on the public page. Lemon Mail is powered by TrekMail — pricing is ₦ only."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Lemon Mail Pricing']]"
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

        <section class="admin-panel admin-panel-flush" aria-label="Lemon Mail plans">
            <div class="admin-panel-toolbar">
                <div>
                    <p class="admin-metric-meta">Lemon Mail · TrekMail</p>
                    <h2 class="admin-dash-panel-title">Plans</h2>
                    <p class="admin-dash-panel-lede">Mailbox counts, providers, fulfilment mode, and featured plan.</p>
                </div>
            </div>

            @foreach ($plans as $plan)
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
                                <option value="lemonmail" @selected(old("plans.{$plan->id}.provider", $plan->provider) === 'lemonmail')>Lemon Mail</option>
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
                            <span>Mailboxes</span>
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
                            <span>Monthly ₦</span>
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
                        <label class="admin-check" style="margin-top:0">
                            <input
                                type="radio"
                                name="featured_plan_id"
                                value="{{ $plan->id }}"
                                @checked(old('featured_plan_id', $plans->firstWhere('featured', true)?->id) == $plan->id)
                            >
                            <span>Featured</span>
                        </label>

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
            @endforeach
        </section>

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
                <span data-submit-label>Save email catalog</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>
@endsection
