@extends('layouts.admin')

@section('title', ($coupon->exists ? 'Edit '.$coupon->code : 'New discount code') . ' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $products = old('applies_to', $coupon->applies_to ?? []);
@endphp

@section('content')
    <x-admin.page-header
        :title="$coupon->exists ? 'Edit '.$coupon->code : 'New discount code'"
        lede="Applies to the cart checkout. Leave limits empty for no limit."
        :back-href="route('admin.coupons.index')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Discount Codes', 'href' => route('admin.coupons.index')], ['label' => $coupon->exists ? $coupon->code : 'New']]"
        class="mb-5"
    />

    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="admin-page-stack">
        @csrf
        @if ($coupon->exists)
            @method('PUT')
        @endif

        <section class="admin-panel">
            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>Code</span>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="admin-input font-mono uppercase" required maxlength="40" placeholder="WELCOME10">
                    @error('code') <em>{{ $message }}</em> @enderror
                </label>
                <label class="admin-field">
                    <span>Description (internal)</span>
                    <input type="text" name="description" value="{{ old('description', $coupon->description) }}" class="admin-input" maxlength="255">
                </label>
                <label class="admin-field">
                    <span>Type</span>
                    <select name="type" class="admin-input">
                        <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percentage off</option>
                        <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed amount off (₦)</option>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Value (% or ₦)</span>
                    <input type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon->value) }}" class="admin-input" required>
                    @error('value') <em>{{ $message }}</em> @enderror
                </label>
                <div class="admin-field admin-field-span">
                    <span>Applies to (none ticked = everything)</span>
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Models\Coupon::PRODUCTS as $key => $label)
                            <label class="admin-check mt-0">
                                <input type="checkbox" name="applies_to[]" value="{{ $key }}" @checked(in_array($key, (array) $products, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <label class="admin-field">
                    <span>Minimum order (₦)</span>
                    <input type="number" step="0.01" min="0" name="min_order_ngn" value="{{ old('min_order_ngn', $coupon->min_order_ngn) }}" class="admin-input">
                </label>
                <label class="admin-field">
                    <span>Total uses allowed</span>
                    <input type="number" min="1" name="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}" class="admin-input">
                </label>
                <label class="admin-field">
                    <span>Uses per customer</span>
                    <input type="number" min="1" name="max_uses_per_customer" value="{{ old('max_uses_per_customer', $coupon->max_uses_per_customer) }}" class="admin-input">
                </label>
                <label class="admin-field">
                    <span>Starts</span>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="admin-input">
                </label>
                <label class="admin-field">
                    <span>Expires</span>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" class="admin-input">
                    @error('expires_at') <em>{{ $message }}</em> @enderror
                </label>
                <label class="admin-check admin-field-span">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active))>
                    <span>Active</span>
                </label>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.coupons.index') }}" class="admin-btn-ghost">Cancel</a>
            <button type="submit" class="admin-btn-primary">Save</button>
        </div>
    </form>
@endsection
