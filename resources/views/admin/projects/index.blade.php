@extends('layouts.admin')

@section('title', 'Projects — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Projects"
        lede="Showcase client work on the public Projects page."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Projects']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.projects.create') }}" class="admin-btn-primary">Add Project</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Project metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $projects->count() }}</span>
                <span class="admin-metric-meta">All projects</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Published</span>
                <span class="admin-metric-value">{{ $projects->where('is_published', true)->count() }}</span>
                <span class="admin-metric-meta">On public site</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Hidden</span>
                <span class="admin-metric-value">{{ $projects->where('is_published', false)->count() }}</span>
                <span class="admin-metric-meta">Draft / hidden</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Projects">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Portfolio</h2>
                    <p class="admin-dash-panel-lede">Case studies and client work.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td>
                                    <strong>{{ $project->title }}</strong>
                                    <div class="admin-muted">/projects/{{ $project->slug }}</div>
                                </td>
                                <td>{{ $project->client_name ?: '—' }}</td>
                                <td>{{ $project->sort_order }}</td>
                                <td>
                                    <span @class(['admin-pill', 'is-ok' => $project->is_published])>
                                        {{ $project->is_published ? 'Published' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        @if ($project->is_published)
                                            <a href="{{ route('projects.show', $project) }}" target="_blank" rel="noopener noreferrer">View</a>
                                        @endif
                                        <a href="{{ route('admin.projects.edit', $project) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.projects.destroy', $project) }}"
                                            data-submit-form
                                            onsubmit="return confirm('Remove this project?')"
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
                                <td colspan="5" class="admin-table-empty">No projects yet. Click Add Project to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
