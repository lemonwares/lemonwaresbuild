@extends('layouts.admin')

@section('title', 'Roles — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Roles"
        lede="Saved access sets you can give staff. Change a role once and everyone with it is updated."
        :back-href="route('admin.staff.index')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Staff', 'href' => route('admin.staff.index')], ['label' => 'Roles']]"
        class="mb-5"
    >
        <x-slot:actions>
            <a href="{{ route('admin.roles.create') }}" class="admin-btn-primary">New role</a>
        </x-slot:actions>
    </x-admin.page-header>

    <section class="admin-panel admin-panel-flush">
        <div class="admin-table-wrap is-full">
            <table class="admin-table is-full">
                <thead>
                    <tr><th>Role</th><th>Access</th><th>Staff</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        @php($levels = \App\Support\AdminPermissions::levelsFromEntries($role->permissions))
                        <tr>
                            <td><strong>{{ $role->name }}</strong>@if ($role->description)<br><span class="admin-muted">{{ $role->description }}</span>@endif</td>
                            <td>
                                @forelse ($levels as $key => $level)
                                    <span class="admin-mini-status">{{ $permissionOptions[$key] ?? $key }} · {{ $level }}</span>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td>{{ $role->members_count }}</td>
                            <td class="admin-table-actions">
                                <div class="admin-customers-toolbar">
                                    <a href="{{ route('admin.roles.edit', $role) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete the {{ $role->name }} role?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="admin-table-empty">No roles yet. Create one such as Support, Sales, Billing or Content.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
