@extends('layouts.admin')

@section('title', 'Edit Campaign — Admin')
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Edit Campaign"
        :lede="$campaign->subject"
        :back-href="route('admin.newsletter-campaigns.show', $campaign)"
        back-label="Go back"
        :breadcrumbs="[
            ['label' => 'Campaigns', 'href' => route('admin.newsletter-campaigns.index')],
            ['label' => $campaign->subject, 'href' => route('admin.newsletter-campaigns.show', $campaign)],
            ['label' => 'Edit'],
        ]"
        class="mb-5"
    />

    <div class="admin-page-stack">
        <section class="admin-panel">
            <form method="POST" action="{{ route('admin.newsletter-campaigns.update', $campaign) }}" enctype="multipart/form-data" class="space-y-6" data-submit-form>
                @csrf
                @method('PUT')
                @include('admin.newsletter-campaigns.form')

                <div class="admin-customers-toolbar">
                    <button type="submit" class="admin-btn-primary inline-flex items-center gap-2" data-submit-button>
                        <span class="admin-btn-spinner hidden" data-submit-spinner></span>
                        <span data-submit-label>Save Draft</span>
                        <span class="hidden" data-submit-loading>Saving…</span>
                    </button>
                    <a href="{{ route('admin.newsletter-campaigns.show', $campaign) }}" class="admin-btn-ghost">Cancel</a>
                </div>
            </form>
        </section>
    </div>
@endsection
