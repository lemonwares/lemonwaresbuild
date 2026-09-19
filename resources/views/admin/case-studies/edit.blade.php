@extends('layouts.admin')

@section('title', 'Edit Case Study — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Edit Case Study"
        :lede="$caseStudy->title"
        :back-href="route('admin.case-studies.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Case Studies', 'href' => route('admin.case-studies.index')],
            ['label' => $caseStudy->title],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.case-studies.update', $caseStudy) }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @method('PUT')
                @include('admin.case-studies.form', ['caseStudy' => $caseStudy])

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Changes</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.case-studies.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
