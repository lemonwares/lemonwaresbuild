@extends('layouts.admin')

@section('title', 'Subscribers — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Newsletter subscribers"
        lede="People who joined from the site footer."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Subscribers']]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Subscriber metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ method_exists($subscribers, 'total') ? $subscribers->total() : $subscribers->count() }}</span>
                <span class="admin-metric-meta">All subscribers</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">On this page</span>
                <span class="admin-metric-value">{{ $subscribers->count() }}</span>
                <span class="admin-metric-meta">Current results</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Subscribers">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Subscribers</h2>
                    <p class="admin-dash-panel-lede">Latest newsletter signups.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscribers as $subscriber)
                            <tr>
                                <td><strong>{{ $subscriber->full_name }}</strong></td>
                                <td><a href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a></td>
                                <td>{{ $subscriber->created_at?->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="admin-table-empty">No subscribers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($subscribers, 'hasPages') && $subscribers->hasPages())
                <div class="admin-pagination">{{ $subscribers->links() }}</div>
            @endif
        </section>
    </div>
@endsection
