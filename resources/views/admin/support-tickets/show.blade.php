@extends('layouts.admin')

@section('title', $ticket->reference . ' — Support — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        :title="$ticket->reference"
        :lede="$ticket->subject"
        :back-href="route('admin.support-tickets.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Support Tickets', 'href' => route('admin.support-tickets.index')],
            ['label' => $ticket->reference],
        ]"
        class="mb-5"
    />

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customer-stats" aria-label="Ticket snapshot">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Status</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ str_replace('_', ' ', $ticket->status) }}</span>
                <span class="admin-metric-meta">Current workflow</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Priority</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $ticket->priority }}</span>
                <span class="admin-metric-meta">{{ $ticket->category }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Opened</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $ticket->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</span>
                <span class="admin-metric-meta">{{ $ticket->created_at?->timezone(config('app.timezone'))->format('g:i A') }}</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">From</span>
                <span class="admin-metric-value admin-metric-value-sm">{{ $ticket->full_name }}</span>
                <span class="admin-metric-meta">{{ $ticket->email }}</span>
            </div>
        </section>

        <div class="admin-customer-grid">
            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Conversation</h2>
                    <div class="admin-pill-row">
                        <span class="admin-pill">{{ $ticket->messages->count() }} messages</span>
                    </div>
                </div>

                <div class="admin-ticket-thread">
                    @forelse ($ticket->messages as $message)
                        <article @class([
                            'admin-ticket-bubble',
                            'is-admin' => $message->author_type === 'admin',
                            'is-customer' => $message->author_type !== 'admin',
                            'is-internal' => $message->is_internal,
                        ])>
                            <header class="admin-ticket-bubble-meta">
                                <strong>{{ $message->author_name ?: ($message->author_type === 'admin' ? 'Support' : $ticket->full_name) }}</strong>
                                <span>
                                    @if ($message->is_internal)
                                        Internal ·
                                    @endif
                                    {{ $message->created_at?->timezone(config('app.timezone'))->format('d M Y g:i A') }}
                                </span>
                            </header>
                            <p class="admin-ticket-bubble-body whitespace-pre-wrap">{{ $message->body }}</p>
                        </article>
                    @empty
                        <p class="admin-muted">No messages yet.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.support-tickets.reply', $ticket) }}" class="mt-5 space-y-4" data-submit-form>
                    @csrf
                    <label class="admin-field">
                        <span>Reply</span>
                        <textarea id="body" name="body" rows="5" required class="admin-input" placeholder="Write a reply to the customer…">{{ old('body') }}</textarea>
                        @error('body') <em>{{ $message }}</em> @enderror
                    </label>
                    <div class="flex flex-wrap gap-4">
                        <label class="admin-check">
                            <input type="checkbox" name="is_internal" value="1" @checked(old('is_internal'))>
                            <span>Internal note only</span>
                        </label>
                        <label class="admin-check">
                            <input type="hidden" name="notify_customer" value="0">
                            <input type="checkbox" name="notify_customer" value="1" @checked(old('notify_customer', true))>
                            <span>Email customer</span>
                        </label>
                    </div>
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Send reply</span>
                        <span class="hidden" data-submit-loading>Sending…</span>
                    </button>
                </form>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-toolbar compact">
                    <h2 class="admin-dash-panel-title">Update status</h2>
                </div>
                <dl class="admin-dl mb-5">
                    <div><dt>From</dt><dd>{{ $ticket->full_name }}</dd></div>
                    <div><dt>Email</dt><dd><a href="mailto:{{ $ticket->email }}">{{ $ticket->email }}</a></dd></div>
                    @if ($ticket->phone)
                        <div><dt>Phone</dt><dd>{{ $ticket->phone }}</dd></div>
                    @endif
                    <div><dt>Category</dt><dd>{{ $ticket->category }}</dd></div>
                    <div><dt>Priority</dt><dd>{{ $ticket->priority }}</dd></div>
                </dl>
                <form method="POST" action="{{ route('admin.support-tickets.update', $ticket) }}" class="space-y-4" data-submit-form>
                    @csrf
                    @method('PUT')
                    <label class="admin-field">
                        <span>Status</span>
                        <select id="status" name="status" class="admin-input">
                            @foreach (\App\Models\SupportTicket::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $ticket->status) === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="admin-field">
                        <span>Internal notes</span>
                        <textarea id="admin_notes" name="admin_notes" rows="6" class="admin-input" placeholder="Internal follow-up notes…">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
                    </label>
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
