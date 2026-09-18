@extends('layouts.admin')

@section('title', 'Newsletter Campaigns — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Newsletter Campaigns"
        lede="Compose and send campaigns to newsletter subscribers."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Campaigns']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.newsletter-campaigns.create') }}" class="admin-btn-primary">New Campaign</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Campaign metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Campaigns</span>
                <span class="admin-metric-value">{{ $campaigns->total() }}</span>
                <span class="admin-metric-meta">All campaigns</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Subscribers</span>
                <span class="admin-metric-value">{{ $subscriberCount }}</span>
                <span class="admin-metric-meta">Ready to receive mail</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Campaigns">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Campaigns</h2>
                    <p class="admin-dash-panel-lede">Drafts and sent newsletters.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Recipients</th>
                            <th>Sent</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $campaign)
                            <tr>
                                <td>
                                    <strong>{{ $campaign->subject }}</strong>
                                    <div class="admin-muted">{{ $campaign->creator?->name ?: '—' }}</div>
                                </td>
                                <td>
                                    <span @class([
                                        'admin-pill',
                                        'is-ok' => $campaign->status === 'sent',
                                        'is-info' => $campaign->status === 'draft',
                                    ])>
                                        {{ $campaign->status }}
                                    </span>
                                </td>
                                <td>{{ $campaign->recipients_count ?: '—' }}</td>
                                <td>{{ $campaign->sent_at?->timezone(config('app.timezone'))->format('d M Y g:i A') ?: '—' }}</td>
                                <td class="admin-table-actions">
                                    <a href="{{ route('admin.newsletter-campaigns.show', $campaign) }}">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-table-empty">No campaigns yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($campaigns->hasPages())
                <div class="admin-panel-footer">{{ $campaigns->links() }}</div>
            @endif
        </section>
    </div>
@endsection
