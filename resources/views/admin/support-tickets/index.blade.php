@extends('layouts.admin')

@section('title', 'Support Tickets — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Support Tickets"
        lede="Requests submitted from the public support page."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Support Tickets']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Support ticket metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $totalTickets }}</span>
                <span class="admin-metric-meta">All tickets</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Open</span>
                <span class="admin-metric-value">{{ $openTickets }}</span>
                <span class="admin-metric-meta">Needs attention</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Resolved</span>
                <span class="admin-metric-value">{{ $resolvedTickets }}</span>
                <span class="admin-metric-meta">Closed / resolved</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">High priority</span>
                <span class="admin-metric-value">{{ $highPriority }}</span>
                <span class="admin-metric-meta">Urgent queue</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New / 7d</span>
                <span class="admin-metric-value">{{ $newWeek }}</span>
                <span class="admin-metric-meta">This week</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Showing</span>
                <span class="admin-metric-value">{{ $tickets->total() }}</span>
                <span class="admin-metric-meta">Current page set</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Support tickets">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Tickets</h2>
                    <p class="admin-dash-panel-lede">Latest support requests from the public form.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.support-tickets.index') }}" class="admin-filter-bar">
                <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Reference, subject, name or email">
                <select name="status" class="admin-input">
                    <option value="">All statuses</option>
                    <option value="active" @selected($status === 'active')>Open or in progress</option>
                    @foreach (\App\Models\SupportTicket::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ str_replace('_', ' ', $statusOption) }}</option>
                    @endforeach
                </select>
                <select name="priority" class="admin-input">
                    <option value="">Any priority</option>
                    @foreach (\App\Models\SupportTicket::PRIORITIES as $priorityOption)
                        <option value="{{ $priorityOption }}" @selected($priority === $priorityOption)>{{ $priorityOption }}</option>
                    @endforeach
                </select>
                <select name="assigned" class="admin-input">
                    <option value="">Anyone</option>
                    <option value="me" @selected($assigned === 'me')>Assigned to me</option>
                    <option value="none" @selected($assigned === 'none')>Unassigned</option>
                </select>
                <button class="admin-btn-primary" type="submit">Apply</button>
                @if ($status || $priority || $assigned || $search)
                    <a href="{{ route('admin.support-tickets.index') }}" class="admin-btn-ghost">Clear</a>
                @endif
                <a href="{{ route('admin.saved-replies.index') }}" class="admin-btn-ghost">Saved replies</a>
            </form>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td><strong>{{ $ticket->reference }}</strong></td>
                                <td>
                                    <strong>{{ $ticket->full_name }}</strong>
                                    <div class="admin-muted">{{ $ticket->email }}</div>
                                </td>
                                <td>{{ $ticket->category }}</td>
                                <td><x-admin.status :value="$ticket->priority === 'high' ? 'failed' : ($ticket->priority === 'low' ? 'refunded' : 'pending')" :label="$ticket->priority" /></td>
                                <td><x-admin.status :value="$ticket->status" /></td>
                                <td>{{ $ticket->assignedAdmin?->name ?: '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.support-tickets.show', $ticket) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="admin-table-empty">No support tickets match.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="admin-pagination">{{ $tickets->links() }}</div>
            @endif
        </section>
    </div>
@endsection
