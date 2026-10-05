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
                <div class="admin-customers-toolbar">
                    <a href="{{ route('admin.subscribers.export') }}" class="admin-btn-ghost">Export CSV</a>
                </div>
            </div>

            @if ($errors->any())
                <p class="mx-5 mb-3 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
            @endif

            <div class="admin-filter-bar flex-wrap">
                <form method="GET" action="{{ route('admin.subscribers.index') }}" class="flex gap-2">
                    <input type="search" name="q" value="{{ $search }}" class="admin-input" placeholder="Search name or email">
                    <button class="admin-btn-ghost" type="submit">Search</button>
                </form>
                <form method="POST" action="{{ route('admin.subscribers.store') }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="full_name" class="admin-input" placeholder="Name (optional)" maxlength="160">
                    <input type="email" name="email" class="admin-input" placeholder="email@example.com" required>
                    <button class="admin-btn-primary" type="submit">Add</button>
                </form>
                <form method="POST" action="{{ route('admin.subscribers.import') }}" enctype="multipart/form-data" class="flex gap-2">
                    @csrf
                    <input type="file" name="file" accept=".csv,text/csv" class="admin-input" required>
                    <button class="admin-btn-ghost" type="submit">Import CSV</button>
                </form>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subscribers as $subscriber)
                            <tr>
                                <td><strong>{{ $subscriber->full_name }}</strong></td>
                                <td><a href="mailto:{{ $subscriber->email }}">{{ $subscriber->email }}</a></td>
                                <td>{{ $subscriber->created_at?->format('d M Y') }}</td>
                                <td class="admin-table-actions">
                                    <form method="POST" action="{{ route('admin.subscribers.destroy', $subscriber) }}" onsubmit="return confirm('Remove {{ $subscriber->email }} from the list?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="admin-table-empty">No subscribers match.</td>
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
