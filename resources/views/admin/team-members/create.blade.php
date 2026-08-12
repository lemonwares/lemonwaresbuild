@extends('layouts.admin')

@section('title', 'Add Team Member — Admin')

@section('content')
    <div class="mb-8">
        <p class="section-label mb-3">Team Manager</p>
        <h1 class="heading">Add Team Member</h1>
    </div>

    <form method="POST" action="{{ route('admin.team-members.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-3xl border border-border bg-white p-6 sm:p-8">
        @csrf
        @include('admin.team-members.form')

        <div class="flex flex-wrap gap-3">
            <button type="submit" data-submit-button class="inline-flex items-center gap-2 rounded-full bg-rose px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#c93737]">
                <span class="hidden size-4 animate-spin rounded-full border-2 border-white/35 border-t-white" data-submit-spinner></span>
                <span data-submit-label>Save Member</span>
                <span class="hidden" data-submit-loading>Saving...</span>
            </button>
            <a href="{{ route('admin.team-members.index') }}" class="inline-flex items-center rounded-full border border-border px-6 py-3 text-sm font-semibold text-black transition hover:border-rose hover:text-rose">
                Cancel
            </a>
        </div>
    </form>
@endsection

