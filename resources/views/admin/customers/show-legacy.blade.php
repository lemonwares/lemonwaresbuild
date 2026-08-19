@extends('layouts.admin')

@section('title', ($legacyCustomer->full_name ?: 'Legacy Customer') . ' — CRM')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Legacy WHMCS customer</p>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="heading">{{ $legacyCustomer->full_name ?: 'Unknown customer' }}</h1>
            <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-sky-700">
                Legacy WHMCS
            </span>
            @if ($legacyCustomer->user)
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-700">
                    Linked Native
                </span>
            @endif
        </div>
        <p class="lede mt-3">{{ $legacyCustomer->company ?: 'No company on file' }}</p>
    </div>

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-border bg-white p-6 lg:col-span-1">
            <h2 class="text-lg font-bold text-black">Profile</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-on-blush/55">Name</dt>
                    <dd class="font-semibold">{{ $legacyCustomer->full_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Email</dt>
                    <dd class="font-semibold">{{ $legacyCustomer->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Phone</dt>
                    <dd class="font-semibold">{{ $legacyCustomer->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Status</dt>
                    <dd class="font-semibold">{{ $legacyCustomer->status ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">WHMCS Client ID</dt>
                    <dd class="font-semibold">{{ $legacyCustomer->whmcs_client_id }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Linked Lemonwares account</dt>
                    <dd class="font-semibold">
                        @if ($legacyCustomer->user)
                            <a href="{{ route('admin.customers.show', $legacyCustomer->user) }}" class="text-rose hover:underline">
                                {{ $legacyCustomer->user->name }} ({{ $legacyCustomer->user->email }})
                            </a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-3xl border border-border bg-white p-6 lg:col-span-2">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-lg font-bold text-black">Services</h2>
                <form method="GET" action="{{ route('admin.customers.legacy.show', $legacyCustomer) }}" class="flex items-center gap-2">
                    <label for="service_status" class="text-xs uppercase tracking-widest text-on-blush/55">Status</label>
                    <select id="service_status" name="service_status" class="rounded-xl border border-border px-3 py-2 text-sm">
                        @foreach (['all', 'active', 'pending', 'suspended', 'terminated', 'cancelled'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($serviceStatus === $statusOption)>
                                {{ ucfirst($statusOption) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-ghost">Filter</button>
                </form>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach (['active', 'pending', 'suspended', 'cancelled', 'terminated', 'unknown'] as $statusKey)
                    <span class="inline-flex items-center rounded-full border border-border bg-blush-soft px-3 py-1 text-xs font-semibold text-on-blush/75">
                        {{ ucfirst($statusKey) }}: {{ (int) ($whmcsServiceSummary[$statusKey] ?? 0) }}
                    </span>
                @endforeach
            </div>
            @forelse ($services as $service)
                <div class="mt-4 rounded-2xl border border-border p-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-black">{{ $service->product_name ?: 'Service #' . $service->whmcs_service_id }}</p>
                            <p class="text-sm text-on-blush/65">
                                {{ $service->domain ?: ($service->username ?: '—') }}
                                · {{ $service->billing_cycle ?: '—' }}
                            </p>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ $service->status ?: 'unknown' }}</p>
                    </div>
                    <p class="mt-2 text-sm text-on-blush/70">
                        Next due: {{ $service->next_due_date?->format('d M Y') ?: '—' }}
                        · WHMCS Service ID: {{ $service->whmcs_service_id }}
                    </p>
                </div>
            @empty
                <p class="mt-4 body-text">No WHMCS services found for this customer yet.</p>
            @endforelse
        </section>
    </div>

    <a href="{{ route('admin.customers.index', ['source' => 'legacy']) }}" class="btn btn-ghost">Back to customers</a>
@endsection
