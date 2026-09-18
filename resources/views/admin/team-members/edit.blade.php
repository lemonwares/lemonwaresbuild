@extends('layouts.admin')

@section('title', 'Edit Team Member — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Edit Team Member"
        :lede="$teamMember->name"
        :back-href="route('admin.team-members.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Team', 'href' => route('admin.team-members.index')],
            ['label' => 'Edit member'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.team-members.update', $teamMember) }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @method('PUT')
                @include('admin.team-members.form', ['member' => $teamMember])

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Update Member</span>
                        <span class="hidden" data-submit-loading>Updating…</span>
                    </button>
                    <a href="{{ route('admin.team-members.index') }}" class="admin-btn-ghost">Back</a>
                </div>
            </form>
        </section>
    </div>
@endsection
