@extends('layouts.admin')

@section('title', 'Email Orders — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Email</p>
        <h1 class="heading">Email Orders</h1>
        <p class="lede mt-3">Customer business-email orders billed on this site and provisioned through TrekMail or the manual provider queue.</p>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-border bg-white p-4 sm:grid-cols-4">
        <select name="provider" class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5">
            <option value="">All providers</option>
            <option value="lemonmail" @selected(($provider ?? '') === 'lemonmail')>Lemon Mail</option>
            <option value="titan" @selected(($provider ?? '') === 'titan')>Titan</option>
            <option value="google_workspace" @selected(($provider ?? '') === 'google_workspace')>Google Workspace</option>
            <option value="ms365" @selected(($provider ?? '') === 'ms365')>Microsoft 365</option>
        </select>
        <select name="mode" class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5">
            <option value="">All fulfilment modes</option>
            <option value="auto" @selected(($mode ?? '') === 'auto')>Automatic</option>
            <option value="manual" @selected(($mode ?? '') === 'manual')>Manual queue</option>
        </select>
        <select name="fulfilment_status" class="footer-input w-full rounded-xl border border-border bg-white px-3 py-2.5">
            <option value="">All queue statuses</option>
            @foreach (\App\Models\EmailOrder::FULFILMENT_STATUSES as $status)
                <option value="{{ $status }}" @selected(($fulfilmentStatus ?? '') === $status)>{{ __('email.fulfilment_statuses.' . $status) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit">Apply filters</button>
    </form>

    <div class="overflow-x-auto rounded-3xl border border-border bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-border text-xs uppercase tracking-widest text-on-blush/50">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Domain</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Provider</th>
                    <th class="px-4 py-3">Mode</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Queue</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="border-b border-border last:border-0">
                        <td class="px-4 py-3 font-semibold">{{ $order->id }}</td>
                        <td class="px-4 py-3">{{ $order->user?->email }}</td>
                        <td class="px-4 py-3">{{ $order->domain }}</td>
                        <td class="px-4 py-3">{{ $order->plan_name }}</td>
                        <td class="px-4 py-3">{{ __('email.providers.' . ($order->provider ?: 'lemonmail')) }}</td>
                        <td class="px-4 py-3">{{ $order->fulfilment_mode ?: 'auto' }}</td>
                        <td class="px-4 py-3">{{ $order->status }}</td>
                        <td class="px-4 py-3">{{ $order->isManualFulfilment() ? $order->fulfilmentStatusLabel() : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.email-orders.show', $order) }}" class="font-semibold text-rose hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-on-blush/60">No email orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
