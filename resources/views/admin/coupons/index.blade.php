@extends('layouts.admin')

@section('title', 'Discount Codes — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Discount Codes"
        lede="Codes customers type at the cart checkout. Percentage or fixed naira amounts, with limits and dates."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Discount Codes']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.coupons.create') }}" class="admin-btn-primary">New code</a>
        </x-slot:actions>
    </x-admin.page-header>

    <section class="admin-panel admin-panel-flush">
        <div class="admin-table-wrap is-full">
            <table class="admin-table is-full">
                <thead>
                    <tr><th>Code</th><th>Discount</th><th>For</th><th>Used</th><th>Given away</th><th>Dates</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td><strong class="font-mono">{{ $coupon->code }}</strong>@if ($coupon->description)<br><span class="admin-muted">{{ $coupon->description }}</span>@endif</td>
                            <td>{{ $coupon->label() }}</td>
                            <td>{{ $coupon->applies_to ? collect($coupon->applies_to)->map(fn ($p) => \App\Models\Coupon::PRODUCTS[$p] ?? $p)->implode(', ') : 'Everything' }}</td>
                            <td>{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                            <td>{{ \App\Support\HostingPricing::formatMoney((float) ($coupon->discount_total ?? 0)) }}</td>
                            <td>{{ $coupon->starts_at?->format('d M Y') ?: 'now' }} → {{ $coupon->expires_at?->format('d M Y') ?: 'no end' }}</td>
                            <td><x-admin.status :value="match ($coupon->statusLabel()) { 'Active' => 'active', 'Scheduled' => 'pending', default => 'expired' }" :label="$coupon->statusLabel()" /></td>
                            <td class="admin-table-actions">
                                <div class="admin-customers-toolbar">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete {{ $coupon->code }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="admin-table-empty">No discount codes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($coupons->hasPages())
            <div class="admin-pagination">{{ $coupons->links() }}</div>
        @endif
    </section>
@endsection
