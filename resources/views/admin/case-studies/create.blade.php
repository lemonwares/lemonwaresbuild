@extends('layouts.admin')

@section('title', 'Add Case Study — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Add Case Study"
        lede="Add a shipped product or platform to the public portfolio."
        :back-href="route('admin.case-studies.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Case Studies', 'href' => route('admin.case-studies.index')],
            ['label' => 'Add'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.case-studies.store') }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @include('admin.case-studies.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Case Study</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.case-studies.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
