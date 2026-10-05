@extends('layouts.admin')

@section('title', 'Email & Suite Pricing — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $mailemonPlans = $plans->where('provider', 'lemonmail')->values();
    $titanPlans = $plans->where('provider', 'titan')->values();
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
                'title' => 'Titan',
                'meta' => 'Public /email · manual setup',
                'lede' => 'Paid at checkout, then set up by your team from the email orders queue.',
                'rows' => $titanPlans,
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
                            <label class="admin-field">
                                <span>Name (English)</span>
                                <input type="text" maxlength="120" name="plans[{{ $plan->id }}][content][en][name]" value="{{ old("plans.{$plan->id}.content.en.name", $plan->displayName('en')) }}" class="admin-input">
                                <p class="admin-muted text-xs">{{ $plan->plan_key }}</p>
                            </label>

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

                        <label class="admin-field mt-3">
                            <span>Summary (English)</span>
                            <textarea rows="2" maxlength="500" name="plans[{{ $plan->id }}][content][en][summary]" class="admin-input">{{ old("plans.{$plan->id}.content.en.summary", $plan->displaySummary('en')) }}</textarea>
                        </label>

                        <details class="mt-3">
                            <summary class="admin-muted cursor-pointer text-xs font-semibold">French &amp; German text (empty uses English)</summary>
                            <div class="admin-edit-grid mt-3">
                                @foreach (['fr' => 'French', 'de' => 'German'] as $locale => $language)
                                    <label class="admin-field">
                                        <span>Name ({{ $language }})</span>
                                        <input type="text" maxlength="120" name="plans[{{ $plan->id }}][content][{{ $locale }}][name]" value="{{ old("plans.{$plan->id}.content.{$locale}.name", $plan->content[$locale]['name'] ?? '') }}" class="admin-input">
                                    </label>
                                    <label class="admin-field">
                                        <span>Summary ({{ $language }})</span>
                                        <textarea rows="2" maxlength="500" name="plans[{{ $plan->id }}][content][{{ $locale }}][summary]" class="admin-input">{{ old("plans.{$plan->id}.content.{$locale}.summary", $plan->content[$locale]['summary'] ?? '') }}</textarea>
                                    </label>
                                @endforeach
                            </div>
                        </details>

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

    <form method="POST" action="{{ route('admin.email-catalog.store') }}" class="admin-panel admin-panel-pad mt-5" data-submit-form>
        @csrf
        <h2 class="admin-dash-panel-title">+ Add plan</h2>
        <p class="admin-dash-panel-lede">Creates a new card. It stays hidden until you tick "Show on site" above and save.</p>
        <div class="admin-edit-grid mt-3">
            <label class="admin-field">
                <span>Provider</span>
                <select name="provider" class="admin-input" required>
                    <option value="lemonmail">Mailemon</option>
                    <option value="titan">Titan</option>
                    <option value="google_workspace">Google Workspace</option>
                    <option value="ms365">Microsoft 365</option>
                </select>
            </label>
            <label class="admin-field">
                <span>Name</span>
                <input type="text" name="name" maxlength="120" class="admin-input" required value="{{ old('name') }}">
            </label>
            <label class="admin-field">
                <span>Mailboxes / seats</span>
                <input type="number" name="mailbox_count" min="1" max="500" class="admin-input" required value="{{ old('mailbox_count', 1) }}">
            </label>
            <label class="admin-field">
                <span>Monthly ₦</span>
                <input type="number" name="monthly_ngn" min="0" step="1" class="admin-input" required value="{{ old('monthly_ngn') }}">
            </label>
        </div>
        <label class="admin-field mt-3">
            <span>Summary</span>
            <textarea name="summary" rows="2" maxlength="500" class="admin-input">{{ old('summary') }}</textarea>
        </label>
        <div class="mt-3 flex justify-end">
            <button type="submit" class="admin-btn-primary" data-submit-button><span data-submit-label>Add plan</span></button>
        </div>
    </form>
@endsection
