@extends('layouts.admin')

@section('title', 'Edit Hosting Lead #' . $lead->id . ' — CRM')
@section('hide_auto_breadcrumbs', true)

@php
    $field = fn (string $name) => old($name, $lead->{$name});
@endphp

@section('content')
    <x-admin.page-header
        :title="'Edit '.$lead->full_name"
        lede="Changes here update the LemonWares record only. Update WHMCS separately if the service already exists there."
        :back-href="route('admin.hosting-leads.show', $lead)"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Hosting Leads', 'href' => route('admin.hosting-leads.index')],
            ['label' => '#'.$lead->id, 'href' => route('admin.hosting-leads.show', $lead)],
            ['label' => 'Edit'],
        ]"
        class="mb-5"
    />

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.hosting-leads.update', $lead) }}" class="admin-page-stack" data-submit-form>
        @csrf
        @method('PUT')

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Status</h2></div>
            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>Order status</span>
                    <select name="status" class="admin-input">
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption }}" @selected($field('status') === $statusOption)>{{ __('account.status.'.$statusOption) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="admin-field">
                    <span>Payment status</span>
                    <input type="text" name="payment_status" value="{{ $field('payment_status') }}" class="admin-input" maxlength="40" placeholder="e.g. successful">
                </label>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Plan &amp; price</h2></div>
            <div class="admin-edit-grid">
                <label class="admin-field"><span>Plan name</span><input type="text" name="plan_name" value="{{ $field('plan_name') }}" class="admin-input" required maxlength="120"></label>
                <label class="admin-field"><span>Spec label</span><input type="text" name="spec_label" value="{{ $field('spec_label') }}" class="admin-input" maxlength="120"></label>
                <label class="admin-field admin-field-span"><span>Spec summary</span><input type="text" name="spec_summary" value="{{ $field('spec_summary') }}" class="admin-input" maxlength="500"></label>
                <label class="admin-field">
                    <span>Billing cycle</span>
                    <select name="billing_cycle" class="admin-input">
                        <option value="">—</option>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle }}" @selected($field('billing_cycle') === $cycle)>{{ \App\Support\HostingPricing::cycleLabel($cycle) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="admin-field"><span>Amount (USD)</span><input type="number" step="0.01" min="0" name="amount_usd" value="{{ $field('amount_usd') }}" class="admin-input"></label>
                <label class="admin-field"><span>Amount (₦)</span><input type="number" step="0.01" min="0" name="amount_ngn" value="{{ $field('amount_ngn') }}" class="admin-input"></label>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Server</h2></div>
            <div class="admin-edit-grid">
                <label class="admin-field"><span>Hostname / primary domain</span><input type="text" name="hostname" value="{{ $field('hostname') }}" class="admin-input" maxlength="190"></label>
                <label class="admin-field"><span>IPv4</span><input type="text" name="ipv4" value="{{ $field('ipv4') }}" class="admin-input admin-mono"></label>
                <label class="admin-field admin-field-span"><span>Control panel URL</span><input type="url" name="panel_url" value="{{ $field('panel_url') }}" class="admin-input" maxlength="500"></label>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">Customer &amp; billing</h2></div>
            <div class="admin-edit-grid">
                <label class="admin-field"><span>Full name</span><input type="text" name="full_name" value="{{ $field('full_name') }}" class="admin-input" required maxlength="160"></label>
                <label class="admin-field"><span>Email</span><input type="email" name="email" value="{{ $field('email') }}" class="admin-input" required maxlength="190"></label>
                <label class="admin-field"><span>Phone</span><input type="text" name="phone" value="{{ $field('phone') }}" class="admin-input" required maxlength="40"></label>
                <label class="admin-field"><span>Company</span><input type="text" name="company" value="{{ $field('company') }}" class="admin-input" maxlength="160"></label>
                <label class="admin-field"><span>Address line 1</span><input type="text" name="billing_address_line_1" value="{{ $field('billing_address_line_1') }}" class="admin-input" maxlength="190"></label>
                <label class="admin-field"><span>Address line 2</span><input type="text" name="billing_address_line_2" value="{{ $field('billing_address_line_2') }}" class="admin-input" maxlength="190"></label>
                <label class="admin-field"><span>City</span><input type="text" name="billing_city" value="{{ $field('billing_city') }}" class="admin-input" maxlength="120"></label>
                <label class="admin-field"><span>State</span><input type="text" name="billing_state" value="{{ $field('billing_state') }}" class="admin-input" maxlength="120"></label>
                <label class="admin-field"><span>Postcode</span><input type="text" name="billing_postcode" value="{{ $field('billing_postcode') }}" class="admin-input" maxlength="40"></label>
                <label class="admin-field">
                    <span>Country</span>
                    <select name="billing_country" class="admin-input">
                        <option value="">—</option>
                        @foreach ($countries as $code => $label)
                            <option value="{{ $code }}" @selected($field('billing_country') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('admin.hosting-leads.show', $lead) }}" class="admin-btn-ghost">Cancel</a>
            <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                <span data-submit-label>Save changes</span>
                <span class="hidden" data-submit-loading>Saving…</span>
            </button>
        </div>
    </form>
@endsection
