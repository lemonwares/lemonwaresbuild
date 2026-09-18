@extends('layouts.admin')

@section('title', 'Edit Career Opening — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Edit Career Opening"
        :lede="$careerOpening->title"
        :back-href="route('admin.career-openings.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Careers', 'href' => route('admin.career-openings.index')],
            ['label' => 'Edit opening'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.career-openings.update', $careerOpening) }}" class="space-y-6" data-submit-form>
                @csrf
                @method('PUT')
                @include('admin.career-openings.form', ['opening' => $careerOpening])

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Update Opening</span>
                        <span class="hidden" data-submit-loading>Updating…</span>
                    </button>
                    <a href="{{ route('admin.career-openings.index') }}" class="admin-btn-ghost">Back</a>
                </div>
            </form>
        </section>
    </div>
@endsection
