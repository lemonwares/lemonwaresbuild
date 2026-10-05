@extends('layouts.admin')

@section('title', ($role->exists ? 'Edit role' : 'New role') . ' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@php
    $selectedLevels = old('permission_levels', \App\Support\AdminPermissions::levelsFromEntries($role->permissions));
@endphp

@section('content')
    <x-admin.page-header
        :title="$role->exists ? 'Edit '.$role->name : 'New role'"
        lede="View only = can open pages. View + edit = can change things. Full = can also delete."
        :back-href="route('admin.roles.index')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Staff', 'href' => route('admin.staff.index')], ['label' => 'Roles', 'href' => route('admin.roles.index')], ['label' => $role->exists ? $role->name : 'New']]"
        class="mb-5"
    />

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="admin-page-stack" data-submit-form>
        @csrf
        @if ($role->exists)
            @method('PUT')
        @endif

        <section class="admin-panel">
            <div class="admin-edit-grid">
                <label class="admin-field">
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="admin-input" required maxlength="80" placeholder="e.g. Support">
                    @error('name') <em>{{ $message }}</em> @enderror
                </label>
                <label class="admin-field">
                    <span>Description</span>
                    <input type="text" name="description" value="{{ old('description', $role->description) }}" class="admin-input" maxlength="255">
                </label>
            </div>

            <div class="mt-5">
                @include('admin.staff.permission-levels', ['permissionOptions' => $permissionOptions, 'selectedLevels' => $selectedLevels])
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="admin-btn-ghost">Cancel</a>
            <button type="submit" class="admin-btn-primary">Save role</button>
        </div>
    </form>
@endsection
