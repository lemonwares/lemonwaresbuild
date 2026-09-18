@extends('layouts.admin')

@section('title', 'Edit Staff — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Edit Staff"
        :lede="$member->name"
        :back-href="route('admin.staff.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Staff', 'href' => route('admin.staff.index')],
            ['label' => $member->name],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.staff.update', $member) }}" class="space-y-6" data-submit-form>
                @csrf
                @method('PUT')
                @include('admin.staff.form', ['member' => $member])

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Changes</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.staff.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
