@extends('layouts.admin')

@section('title', 'Staff — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Staff"
        lede="Manage admin accounts, super admins, and per-area permissions."
        :back-href="route('admin.dashboard')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Staff']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.staff.create') }}" class="admin-btn-primary">Add Staff</a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</p>
    @endif

    <div class="admin-page-stack">
        <section class="admin-dash-metrics admin-customers-metrics" aria-label="Staff metrics">
            <div class="admin-metric is-static">
                <span class="admin-metric-label">Staff</span>
                <span class="admin-metric-value">{{ $staff->count() }}</span>
                <span class="admin-metric-meta">Admin accounts</span>
            </div>
        </section>

        <section class="admin-panel admin-panel-flush" aria-label="Staff list">
            <div class="admin-panel-toolbar">
                <div>
                    <h2 class="admin-dash-panel-title">Team access</h2>
                    <p class="admin-dash-panel-lede">People who can open the admin panel.</p>
                </div>
            </div>

            <div class="admin-table-wrap is-full">
                <table class="admin-table is-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Access</th>
                            <th>Added</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staff as $member)
                            <tr>
                                <td><strong>{{ $member->name }}</strong></td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    @if ($member->is_super_admin)
                                        <span class="admin-pill is-ok">Super admin</span>
                                    @else
                                        <span class="admin-pill is-info">{{ count($member->admin_permissions ?? []) }} permissions</span>
                                    @endif
                                </td>
                                <td>{{ $member->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</td>
                                <td class="admin-table-actions">
                                    <div class="admin-customers-toolbar">
                                        <a href="{{ route('admin.staff.edit', $member) }}">Edit</a>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.staff.destroy', $member) }}"
                                            data-submit-form
                                            onsubmit="return confirm('Remove this staff account?')"
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
                                <td colspan="5" class="admin-table-empty">No staff accounts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
