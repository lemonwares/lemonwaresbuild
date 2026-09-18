@extends('layouts.admin')

@section('title', 'Add Staff — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Add Staff"
        lede="Create an admin account for the panel."
        :back-href="route('admin.staff.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Staff', 'href' => route('admin.staff.index')],
            ['label' => 'Add'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-6" data-submit-form>
                @csrf
                @include('admin.staff.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Create Staff</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.staff.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
