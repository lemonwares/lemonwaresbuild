@extends('layouts.admin')

@section('title', 'WHMCS Ticket #'.$ticketId.' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Ticket #{{ $ticketId }}"
        lede="{{ $ticket['subject'] ?? '' }} · {{ $ticket['status'] ?? '' }}"
        :back-href="route('admin.whmcs-console.tickets')"
        back-label="All tickets"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Tickets'], ['label' => '#'.$ticketId]]"
        class="mb-5"
    />

    @include('admin.whmcs-console._tabs')

    @if (session('status'))
        <p class="mb-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 text-sm font-semibold text-rose">{{ session('error') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-panel space-y-4">
            <p class="text-sm admin-muted">Client #{{ $ticket['userid'] ?? '—' }} · Dept {{ $ticket['deptname'] ?? ($ticket['deptid'] ?? '—') }}</p>
            @if (! empty($ticket['message']))
                <div class="rounded-xl border border-border bg-blush-soft/40 p-4 text-sm whitespace-pre-wrap">{{ $ticket['message'] }}</div>
            @endif

            @foreach ($replies as $reply)
                <div class="rounded-xl border border-border p-4 text-sm">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide admin-muted">
                        {{ $reply['name'] ?? ($reply['admin'] ?? 'Reply') }} · {{ $reply['date'] ?? '' }}
                    </p>
                    <p class="whitespace-pre-wrap">{{ $reply['message'] ?? '' }}</p>
                </div>
            @endforeach
        </section>

        <section class="admin-panel">
            <h2 class="admin-dash-panel-title mb-4">Reply</h2>
            <form method="POST" action="{{ route('admin.whmcs-console.tickets.reply', $ticketId) }}" data-submit-form>
                @csrf
                <label class="admin-field admin-field-span">
                    <span>Message</span>
                    <textarea name="message" rows="5" class="admin-input" required>{{ old('message') }}</textarea>
                </label>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="submit" class="admin-btn-primary">Send reply</button>
                </div>
            </form>

            @if (($ticket['status'] ?? '') !== 'Closed')
                <form method="POST" action="{{ route('admin.whmcs-console.tickets.close', $ticketId) }}" class="mt-4" data-confirm data-confirm-title="Close ticket?" data-confirm-body="Marks this ticket closed in WHMCS.">
                    @csrf
                    <button type="submit" class="admin-btn-ghost">Close ticket</button>
                </form>
            @endif
        </section>
    </div>
@endsection
