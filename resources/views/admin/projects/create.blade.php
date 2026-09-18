@extends('layouts.admin')

@section('title', 'Add Project — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Add Project"
        lede="Create a portfolio entry for the public site."
        :back-href="route('admin.projects.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Projects', 'href' => route('admin.projects.index')],
            ['label' => 'Add'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.projects.store') }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @include('admin.projects.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Project</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
