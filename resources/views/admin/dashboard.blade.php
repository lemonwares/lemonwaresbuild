@extends('layouts.admin')

@section('title', 'Staff CRM — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Lemonwares</p>
        <h1 class="heading">Staff CRM</h1>
        <p class="lede mt-3">Monitor customers, email orders, hosting requests, and subscribers from one place.</p>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('admin.customers.index') }}" class="rounded-3xl border border-border bg-white p-5 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Customers</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $customersCount }}</p>
        </a>
        <a href="{{ route('admin.email-orders.index') }}" class="rounded-3xl border border-border bg-white p-5 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Email Orders</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $emailOrdersCount }}</p>
            <p class="mt-1 text-sm text-on-blush/60">{{ $paidEmailOrdersCount }} paid · {{ $pendingEmailSetupCount }} pending setup</p>
        </a>
        <a href="{{ route('admin.hosting-leads.index') }}" class="rounded-3xl border border-border bg-white p-5 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Hosting Leads</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $hostingLeadsCount }}</p>
        </a>
        <a href="{{ route('admin.subscribers.index') }}" class="rounded-3xl border border-border bg-white p-5 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Subscribers</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $subscribersCount }}</p>
        </a>
    </div>

    <div class="mb-8 grid gap-5 lg:grid-cols-2">
        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-black">Recent customers</h2>
                <a href="{{ route('admin.customers.index') }}" class="text-sm font-semibold text-rose hover:underline">View all</a>
            </div>
            @forelse ($recentCustomers as $customer)
                <a href="{{ route('admin.customers.show', $customer) }}" class="flex items-center justify-between gap-3 border-b border-border py-3 last:border-0 hover:text-rose">
                    <span>
                        <span class="block font-semibold text-black">{{ $customer->name }}</span>
                        <span class="text-sm text-on-blush/65">{{ $customer->email }}</span>
                    </span>
                    <span class="text-xs text-on-blush/50">{{ $customer->created_at?->diffForHumans() }}</span>
                </a>
            @empty
                <p class="body-text">No customers yet.</p>
            @endforelse
        </section>

        <section class="rounded-3xl border border-border bg-white p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-black">Recent email orders</h2>
                <a href="{{ route('admin.email-orders.index') }}" class="text-sm font-semibold text-rose hover:underline">View all</a>
            </div>
            @forelse ($recentEmailOrders as $order)
                <a href="{{ route('admin.email-orders.show', $order) }}" class="flex items-center justify-between gap-3 border-b border-border py-3 last:border-0">
                    <span>
                        <span class="block font-semibold text-black">{{ $order->domain }}</span>
                        <span class="text-sm text-on-blush/65">{{ $order->user?->name }} · {{ $order->plan_name }}</span>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-widest text-rose">{{ str_replace('_', ' ', $order->status) }}</span>
                </a>
            @empty
                <p class="body-text">No email orders yet.</p>
            @endforelse
        </section>
    </div>

    <section class="mb-8 rounded-3xl border border-border bg-white p-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-black">Hosting leads</h2>
            <a href="{{ route('admin.hosting-leads.index') }}" class="text-sm font-semibold text-rose hover:underline">View all</a>
        </div>
        @forelse ($recentHostingLeads as $lead)
            <a href="{{ route('admin.hosting-leads.show', $lead) }}" class="flex flex-wrap items-center justify-between gap-3 border-b border-border py-3 last:border-0">
                <span>
                    <span class="block font-semibold text-black">{{ $lead->full_name }}</span>
                    <span class="text-sm text-on-blush/65">{{ $lead->plan_name }} · {{ $lead->email }}</span>
                </span>
                <span class="text-xs font-semibold uppercase tracking-widest text-rose">{{ str_replace('_', ' ', $lead->status ?: 'pending') }}</span>
            </a>
        @empty
            <p class="body-text">No hosting leads yet.</p>
        @endforelse
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('admin.team-members.index') }}" class="group rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Site</p>
            <h2 class="mt-2 text-2xl font-bold text-black">Team page</h2>
            <p class="mt-3 body-text">{{ $teamMembersCount }} profiles · manage the public Team page.</p>
        </a>
        <a href="{{ route('admin.hosting-prices.index') }}" class="group rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Hosting</p>
            <h2 class="mt-2 text-2xl font-bold text-black">Plan prices</h2>
            <p class="mt-3 body-text">{{ $pricedSpecsCount }} priced specs on the public hosting catalog.</p>
        </a>
        <a href="{{ route('admin.subscribers.index') }}" class="group rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Marketing</p>
            <h2 class="mt-2 text-2xl font-bold text-black">Newsletter</h2>
            <p class="mt-3 body-text">{{ $subscribersCount }} people on the footer list.</p>
        </a>
    </div>
@endsection
