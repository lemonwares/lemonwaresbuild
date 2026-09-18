@extends('layouts.admin')

@section('title', 'Add Team Member — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Add Team Member"
        lede="Create a new person for the public Team page."
        :back-href="route('admin.team-members.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Team', 'href' => route('admin.team-members.index')],
            ['label' => 'Add member'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.team-members.store') }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @include('admin.team-members.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Member</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.team-members.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
