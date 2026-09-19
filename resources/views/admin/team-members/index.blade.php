@extends('layouts.admin')

@section('title', 'Team Members — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Team Members"
        lede="Add, update, reorder, hide, or remove members shown on the public Team page."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Team']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.team-members.create') }}" class="admin-btn-primary">Add Team Member</a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Team metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Total</span>
                <span class="admin-metric-value">{{ $members->count() }}</span>
                <span class="admin-metric-meta">All members</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Visible</span>
                <span class="admin-metric-value">{{ $members->where('is_active', true)->count() }}</span>
                <span class="admin-metric-meta">On public Team page</span>
            </div>
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Hidden</span>
                <span class="admin-metric-value">{{ $members->where('is_active', false)->count() }}</span>
                <span class="admin-metric-meta">Not shown publicly</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Team members">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Members</h2>
                    <p class="admin-dash-panel-lede">People listed on the public Team page.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td>
                                    @if ($member->photo_path)
                                        <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="size-11 rounded-full object-cover" />
                                    @else
                                        <span class="inline-flex size-11 items-center justify-center rounded-full bg-blush text-sm font-bold text-rose">
                                            {{ \Illuminate\Support\Str::of($member->name)->explode(' ')->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->take(2)->join('') }}
                                        </span>
                                    @endif
                                </td>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>{{ $member->role }}</td>
                                <td>{{ \App\Models\TeamMember::departments()[$member->department] ?? ucfirst((string) $member->department) }}</td>
                                <td>{{ $member->sort_order }}</td>
                                <td>
                                    <span @class(['admin-pill', 'is-ok' => $member->is_active])>
                                        {{ $member->is_active ? 'Active' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        <a href="{{ route('admin.team-members.edit', $member) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.team-members.destroy', $member) }}"
                                            data-submit-form
                                            onsubmit="return confirm('Remove this team member? This action cannot be undone.')"
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
                                <td colspan="7" class="admin-table-empty">No team members yet. Click Add Team Member to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
