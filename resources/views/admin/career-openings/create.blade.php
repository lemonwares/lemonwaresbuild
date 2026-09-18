@extends('layouts.admin')

@section('title', 'Add Career Opening — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Add Career Opening"
        lede="Create a new role for the public Careers pages."
        :back-href="route('admin.career-openings.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Careers', 'href' => route('admin.career-openings.index')],
            ['label' => 'New opening'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.career-openings.store') }}" class="space-y-6" data-submit-form>
                @csrf
                @include('admin.career-openings.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Opening</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.career-openings.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
