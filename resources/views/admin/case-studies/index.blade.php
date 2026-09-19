@extends('layouts.admin')

@section('title', 'Case Studies — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Case Studies"
        lede="Shipped products and platforms shown on the public Case Studies page. Covers upload to Cloudinary when configured."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Case Studies']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.case-studies.create') }}" class="admin-btn-primary">Add Case Study</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Case study metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $caseStudies->count() }}</span>
                <span class="admin-metric-meta">All case studies</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Published</span>
                <span class="admin-metric-value">{{ $caseStudies->where('is_published', true)->count() }}</span>
                <span class="admin-metric-meta">On public site</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Hidden</span>
                <span class="admin-metric-value">{{ $caseStudies->where('is_published', false)->count() }}</span>
                <span class="admin-metric-meta">Draft / hidden</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Case studies">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Shipped products</h2>
                    <p class="admin-dash-panel-lede">Portfolio stories on /case-studies.</p>
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
                        @forelse ($caseStudies as $caseStudy)
                            <tr>
                                <td>
                                    <strong>{{ $caseStudy->title }}</strong>
                                    <div class="admin-muted">/case-studies/{{ $caseStudy->slug }}</div>
                                </td>
                                <td>{{ $caseStudy->client_name ?: '—' }}</td>
                                <td>{{ $caseStudy->sort_order }}</td>
                                <td>
                                    <span @class(['admin-pill', 'is-ok' => $caseStudy->is_published])>
                                        {{ $caseStudy->is_published ? 'Published' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        @if ($caseStudy->is_published)
                                            <a href="{{ route('case-studies.show', $caseStudy) }}" target="_blank" rel="noopener noreferrer">View</a>
                                        @endif
                                        <a href="{{ route('admin.case-studies.edit', $caseStudy) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.case-studies.destroy', $caseStudy) }}"
                                            data-submit-form
                                            onsubmit="return confirm('Remove this case study?')"
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
                                <td colspan="5" class="admin-table-empty">No case studies yet. Click Add Case Study to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
