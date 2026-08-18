@extends('layouts.admin')

@section('title', 'Email Order #' . $order->id . ' — ' . config('site.short_name'))

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Email Order</p>
        <h1 class="heading">{{ $order->domain }}</h1>
        <p class="lede mt-3">{{ $order->user?->name }} · {{ $order->user?->email }}</p>
        @if ($order->user && $order->user->isCustomer())
            <a href="{{ route('admin.customers.show', $order->user) }}" class="mt-3 inline-flex text-sm font-semibold text-rose hover:underline">Open customer profile</a>
        @endif
    </div>

    <div class="rounded-3xl border border-border bg-white p-6">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Status</dt>
                <dd class="font-semibold">{{ $order->status }} / {{ $order->payment_status ?: '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Plan</dt>
                <dd class="font-semibold">{{ $order->plan_name }} · {{ $order->billing_cycle }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-border pb-3">
                <dt class="text-on-blush/60">Amount</dt>
                <dd class="font-semibold">{{ \App\Support\HostingPricing::dualPriceDisplay((float) $order->amount_usd) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-on-blush/60">TrekMail domain</dt>
                <dd class="font-semibold">{{ $order->trekmail_domain_id ?: '—' }}</dd>
            </div>
        </dl>

        @if ($order->provision_error)
            <p class="mt-4 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $order->provision_error }}</p>
        @endif

        <ul class="mt-6 space-y-2 text-sm">
            @foreach ($order->mailboxes as $mailbox)
                <li>{{ $mailbox->address }} — {{ $mailbox->status }}{{ $mailbox->error_message ? ' (' . $mailbox->error_message . ')' : '' }}</li>
            @endforeach
        </ul>

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($order->isPaid())
                <form method="POST" action="{{ route('admin.email-orders.provision', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Retry TrekMail provision</button>
                </form>
            @endif
            <a href="{{ route('admin.email-orders.index') }}" class="btn btn-ghost">Back to list</a>
        </div>
    </div>
@endsection
