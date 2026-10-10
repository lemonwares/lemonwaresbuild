@extends('layouts.admin')

@section('title', 'Ticket #'.($ticket->tid ?: $ticket->whmcs_ticket_id).' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    @php
        $customerHref = $customer ? ($customer->user_id ? route('admin.customers.show', $customer->user_id) : route('admin.customers.legacy.show', $customer)) : null;
    @endphp

    <x-admin.page-header
        :title="$ticket->subject ?: 'Ticket #'.($ticket->tid ?: $ticket->whmcs_ticket_id)"
        :lede="'Ticket #'.($ticket->tid ?: $ticket->whmcs_ticket_id).' · '.($ticket->department ?: 'Support').' · '.$status"
        :breadcrumbs="[['label' => 'Needs action', 'href' => route('admin.queue.index')], ['label' => 'Ticket #'.($ticket->tid ?: $ticket->whmcs_ticket_id)]]"
        class="mb-5"
    />

    @error('whmcs')
        <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</p>
    @enderror
    @if ($error)
        <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Could not load the conversation from WHMCS: {{ $error }}</p>
    @endif

    <div class="flex flex-col gap-5">
        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <div>
                    <h2 class="admin-dash-panel-title">Conversation</h2>
                    <p class="admin-dash-panel-lede">
                        @if ($customer)
                            <a href="{{ $customerHref }}">{{ $customer->displayName() }}</a> ({{ $customer->email }})
                        @else
                            Client #{{ $ticket->whmcs_client_id ?: '—' }}
                        @endif
                    </p>
                </div>
                <x-admin.status :value="$status" />
            </div>

            <div class="flex flex-col gap-3">
                @forelse ($replies as $reply)
                    @php $fromStaff = ! empty($reply['admin']); @endphp
                    <article @class(['rounded-xl border px-4 py-3', 'border-amber-200 bg-amber-50' => $fromStaff, 'border-black/10 bg-white' => ! $fromStaff])>
                        <header class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                            <strong>{{ $fromStaff ? $reply['admin'].' (staff)' : ($reply['name'] ?? $reply['requestor_name'] ?? 'Client') }}</strong>
                            <span class="text-black/50">{{ $reply['date'] ?? '' }}</span>
                        </header>
                        <div class="text-sm leading-relaxed" style="white-space: pre-wrap">{{ trim(strip_tags(html_entity_decode((string) ($reply['message'] ?? ''), ENT_QUOTES))) }}</div>
                        @if (! empty($reply['attachment']))
                            <p class="mt-2 text-xs text-black/50">Attachment: {{ $reply['attachment'] }} (open in WHMCS)</p>
                        @endif
                    </article>
                @empty
                    <p class="text-sm text-black/55">No messages to show.</p>
                @endforelse
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact">
                <h2 class="admin-dash-panel-title">Reply</h2>
            </div>
            <form method="POST" action="{{ route('admin.queue.tickets.reply', $ticket) }}" class="flex flex-col gap-3">
                @csrf
                <textarea name="message" rows="7" class="admin-input" required placeholder="Write your reply to the client…">{{ old('message') }}</textarea>
                @error('message')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
                <div class="flex flex-wrap items-center gap-2">
                    <select name="status" class="admin-input" aria-label="Status after reply" style="max-width: 16rem">
                        @foreach (['Answered', 'In Progress', 'On Hold', 'Closed'] as $option)
                            <option value="{{ $option }}">Then mark: {{ $option }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="admin-btn-primary">Send reply</button>
                </div>
            </form>
            @if ($status !== 'Closed')
                <form method="POST" action="{{ route('admin.queue.tickets.status', $ticket) }}" class="mt-3" onsubmit="return confirm('Close this ticket without replying?')">
                    @csrf
                    <input type="hidden" name="status" value="Closed">
                    <button type="submit" class="admin-btn-ghost">Close without replying</button>
                </form>
            @endif
        </section>
    </div>
@endsection
