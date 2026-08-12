@extends('layouts.admin')

@section('title', 'Admin Dashboard — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Admin Controls</p>
        <h1 class="heading">Dashboard</h1>
        <p class="lede mt-3">Manage team members now, with room to expand into full content, inquiries, and site operations control.</p>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-3xl border border-border bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Team Members</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $teamMembersCount }}</p>
        </article>
        <article class="rounded-3xl border border-border bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Active Profiles</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $activeTeamMembersCount }}</p>
        </article>
        <article class="rounded-3xl border border-border bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Priced Specs</p>
            <p class="mt-2 text-3xl font-bold text-black">{{ $pricedSpecsCount ?? 0 }}</p>
        </article>
        <article class="rounded-3xl border border-border bg-white p-5">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Inquiry Inbox</p>
            <p class="mt-2 text-3xl font-bold text-black">Soon</p>
        </article>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <a href="{{ route('admin.team-members.index') }}" class="group rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40 hover:shadow-[0_12px_26px_rgba(72,79,86,0.10)]">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">People</p>
            <h2 class="mt-2 text-2xl font-bold text-black">Manage Team Members</h2>
            <p class="mt-3 body-text">Add photos, quotes, social links, profile order, and visibility for the public Team page.</p>
            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-rose">Open Manager <x-ui.icons.arrow-up-right class="size-4" /></span>
        </a>

        <a href="{{ route('admin.hosting-prices.index') }}" class="group rounded-3xl border border-border bg-white p-6 transition hover:border-rose/40 hover:shadow-[0_12px_26px_rgba(72,79,86,0.10)]">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Hosting</p>
            <h2 class="mt-2 text-2xl font-bold text-black">Manage Hosting Prices</h2>
            <p class="mt-3 body-text">Update public prices, currency, and billing cycle for each hosting specification without code changes.</p>
            <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-rose">Open Pricing <x-ui.icons.arrow-up-right class="size-4" /></span>
        </a>
    </div>
@endsection

