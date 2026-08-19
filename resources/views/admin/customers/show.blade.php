@extends('layouts.admin')

@section('title', $customer->name . ' — CRM')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Customer</p>
        <h1 class="heading">{{ $customer->name }}</h1>
        <p class="lede mt-3">{{ $customer->company ?: 'No company on file' }}</p>
    </div>

    <div class="mb-8 grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-border bg-white p-6 lg:col-span-1">
            <h2 class="text-lg font-bold text-black">Profile</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="text-on-blush/55">Email</dt>
                    <dd class="font-semibold"><a href="mailto:{{ $customer->email }}" class="text-rose hover:underline">{{ $customer->email }}</a></dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Job title</dt>
                    <dd class="font-semibold">{{ $customer->job_title ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Phone</dt>
                    <dd class="font-semibold">{{ $customer->phone ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Company</dt>
                    <dd class="font-semibold">{{ $customer->company ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Trading name</dt>
                    <dd class="font-semibold">{{ $customer->trading_name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Website</dt>
                    <dd class="font-semibold">
                        @if ($customer->website)
                            <a href="{{ $customer->website }}" class="text-rose hover:underline" target="_blank" rel="noopener noreferrer">{{ $customer->website }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Industry</dt>
                    <dd class="font-semibold">{{ $customer->industryLabel() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Tax / VAT</dt>
                    <dd class="font-semibold">{{ $customer->tax_id ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Registration no.</dt>
                    <dd class="font-semibold">{{ $customer->registration_number ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Billing address</dt>
                    <dd class="font-semibold">{{ $customer->formattedBillingAddress() ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-on-blush/55">Joined</dt>
                    <dd class="font-semibold">{{ $customer->created_at?->format('d M Y') }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-3xl border border-border bg-white p-6 lg:col-span-2">
            <h2 class="text-lg font-bold text-black">Email orders</h2>
            @forelse ($customer->emailOrders as $order)
                <a href="{{ route('admin.email-orders.show', $order) }}" class="mt-4 block rounded-2xl border border-border p-4 hover:border-rose/40">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-black">{{ $order->domain }}</p>
                            <p class="text-sm text-on-blush/65">{{ $order->plan_name }} · {{ $order->billing_cycle }}</p>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ str_replace('_', ' ', $order->status) }}</p>
                    </div>
                    <p class="mt-2 text-sm text-on-blush/70">{{ $order->mailboxes->pluck('address')->join(', ') }}</p>
                </a>
            @empty
                <p class="mt-4 body-text">No email orders for this customer yet.</p>
            @endforelse
        </section>
    </div>

    <section class="mb-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">Notification contacts</h2>
        <p class="mt-1 text-sm text-on-blush/65">People who receive account mail if the primary contact is away.</p>
        @forelse ($customer->contacts as $contact)
            <div class="mt-4 rounded-2xl border border-border p-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-black">{{ $contact->name }}</p>
                        <p class="text-sm"><a href="mailto:{{ $contact->email }}" class="text-rose hover:underline">{{ $contact->email }}</a></p>
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ $contact->roleLabel() }}</p>
                </div>
                <p class="mt-2 text-sm text-on-blush/70">
                    {{ $contact->notify ? 'Receives account emails' : 'Not on the mailing list' }}
                    @if ($contact->unavailable_backup)
                        · Backup if unavailable
                    @endif
                </p>
            </div>
        @empty
            <p class="mt-4 body-text">No extra contacts on this account yet.</p>
        @endforelse
    </section>

    <section class="mb-8 rounded-3xl border border-border bg-white p-6">
        <h2 class="text-lg font-bold text-black">VPS & hosting</h2>
        @forelse ($hostingLeads as $lead)
            <a href="{{ route('admin.hosting-leads.show', $lead) }}" class="mt-4 block rounded-2xl border border-border p-4 hover:border-rose/40">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-black">{{ $lead->displayName() }}</p>
                        <p class="text-sm text-on-blush/65">{{ $lead->plan_name }}{{ $lead->spec_label ? ' · ' . $lead->spec_label : '' }}</p>
                        @if ($lead->ipv4)
                            <p class="mt-1 font-mono text-sm text-on-blush/70">{{ $lead->ipv4 }}</p>
                        @endif
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-rose">{{ $lead->statusLabel() }}</p>
                </div>
            </a>
        @empty
            <p class="mt-4 body-text">No VPS or hosting records for this customer yet.</p>
        @endforelse
    </section>

    <a href="{{ route('admin.customers.index') }}" class="btn btn-ghost">Back to customers</a>
@endsection
