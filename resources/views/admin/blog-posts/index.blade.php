@extends('layouts.admin')

@section('title', 'Blog — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Blog"
        lede="Write and publish posts for the public blog."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Blog']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.blog-posts.create') }}" class="admin-btn-primary">New Post</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Blog metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $posts->count() }}</span>
                <span class="admin-metric-meta">All posts</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Published</span>
                <span class="admin-metric-value">{{ $posts->where('is_published', true)->count() }}</span>
                <span class="admin-metric-meta">Live on /blog</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Drafts</span>
                <span class="admin-metric-value">{{ $posts->where('is_published', false)->count() }}</span>
                <span class="admin-metric-meta">Not public</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Blog posts">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Posts</h2>
                    <p class="admin-dash-panel-lede">Articles listed on the public blog.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Published</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr>
                                <td>
                                    <strong>{{ $post->title }}</strong>
                                    <div class="admin-muted">/blog/{{ $post->slug }}</div>
                                </td>
                                <td>{{ $post->author?->name ?: '—' }}</td>
                                <td>{{ $post->published_at?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}</td>
                                <td>
                                    <span @class(['admin-pill', 'is-ok' => $post->is_published])>
                                        {{ $post->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        @if ($post->is_published)
                                            <a href="{{ route('blog.show', $post) }}" target="_blank" rel="noopener noreferrer">View</a>
                                        @endif
                                        <a href="{{ route('admin.blog-posts.edit', $post) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.blog-posts.destroy', $post) }}"
                                            data-submit-form
                                            onsubmit="return confirm('Remove this post?')"
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
                                <td colspan="5" class="admin-table-empty">No posts yet. Click New Post to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
