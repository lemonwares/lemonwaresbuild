@extends('layouts.admin')

@section('title', 'Hosting Leads — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Hosting Leads"
        lede="Checkout requests from the hosting flow, including VPS orders waiting on payment."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Hosting Leads']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Hosting lead metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $totalLeads }}</span>
                <span class="admin-metric-meta">All leads</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Pending</span>
                <span class="admin-metric-value">{{ $pendingLeads }}</span>
                <span class="admin-metric-meta">Awaiting payment / setup</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Paid</span>
                <span class="admin-metric-value">{{ $paidLeads }}</span>
                <span class="admin-metric-meta">Paid leads</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">New / 7d</span>
                <span class="admin-metric-value">{{ $newWeek }}</span>
                <span class="admin-metric-meta">This week</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Showing</span>
                <span class="admin-metric-value">{{ $leads->total() }}</span>
                <span class="admin-metric-meta">Current page set</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Hosting leads">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Leads</h2>
                    <p class="admin-dash-panel-lede">Latest hosting checkout requests.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td><strong>{{ $lead->full_name }}</strong></td>
                                <td>{{ $lead->email }}</td>
                                <td>{{ $lead->plan_name }}{{ $lead->spec_label ? ' · '.$lead->spec_label : '' }}</td>
                                <td><span class="admin-mini-status">{{ str_replace('_', ' ', $lead->status ?: 'pending') }}</span></td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.hosting-leads.show', $lead) }}">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-table-empty">No hosting leads yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="admin-pagination">{{ $leads->links() }}</div>
            @endif
        </section>
    </div>
@endsection
