@extends('layouts.admin')

@section('title', 'New Blog Post — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="New Blog Post"
        lede="Draft or publish an article for the public blog."
        :back-href="route('admin.blog-posts.index')"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Blog', 'href' => route('admin.blog-posts.index')],
            ['label' => 'New post'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.blog-posts.store') }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @include('admin.blog-posts.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Post</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.blog-posts.index') }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
