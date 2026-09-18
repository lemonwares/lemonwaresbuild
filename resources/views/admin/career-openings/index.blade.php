@extends('layouts.admin')

@section('title', 'Career Openings — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Career Openings"
        lede="Add, update, reorder, hide, or remove roles shown on the public Careers pages."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Careers']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.career-openings.create') }}" class="admin-btn-primary">Add Opening</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Career metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $openings->count() }}</span>
                <span class="admin-metric-meta">All openings</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Visible</span>
                <span class="admin-metric-value">{{ $openings->where('is_active', true)->count() }}</span>
                <span class="admin-metric-meta">On public Careers pages</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Hidden</span>
                <span class="admin-metric-value">{{ $openings->where('is_active', false)->count() }}</span>
                <span class="admin-metric-meta">Not shown publicly</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Career openings">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Openings</h2>
                    <p class="admin-dash-panel-lede">Roles listed on the public Careers pages.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($openings as $opening)
                            <tr>
                                <td>
                                    <strong>{{ $opening->title }}</strong>
                                    <div class="admin-muted">/careers/{{ $opening->slug }}</div>
                                </td>
                                <td>{{ $opening->type ?: '—' }}</td>
                                <td>{{ $opening->location ?: '—' }}</td>
                                <td>{{ $opening->sort_order }}</td>
                                <td>
                                    <span @class(['admin-pill', 'is-ok' => $opening->is_active])>
                                        {{ $opening->is_active ? 'Active' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        <a href="{{ route('careers.show', $opening) }}" target="_blank" rel="noopener noreferrer">View</a>
                                        <a href="{{ route('admin.career-openings.edit', $opening) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.career-openings.destroy', $opening) }}"
                                            data-submit-form
                                            onsubmit="return confirm('Remove this opening? This cannot be undone.')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn-danger inline-flex items-center gap-2" data-submit-button>
                                                <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                                                <span data-submit-label>Remove</span>
                                                <span class="hidden" data-submit-loading>Removing…</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-table-empty">No openings yet. Click Add Opening to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
