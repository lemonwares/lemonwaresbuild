@extends('layouts.admin')

@section('title', 'Saved Replies — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Saved replies"
        lede="Answers your team sends often. Insert them from any support ticket."
        :back-href="route('admin.support-tickets.index')"
        back-label="Go back"
        :breadcrumbs="[['label' => 'Support Tickets', 'href' => route('admin.support-tickets.index')], ['label' => 'Saved replies']]"
        class="mb-5"
    />

    @if ($errors->any())
        <p class="mb-5 rounded-xl border border-rose/20 bg-rose/5 px-4 py-3 text-sm text-rose">{{ $errors->first() }}</p>
    @endif

    <div class="admin-page-stack">
        @foreach ($replies as $reply)
            <section class="admin-panel">
                <form method="POST" action="{{ route('admin.saved-replies.update', $reply) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <input type="text" name="title" value="{{ $reply->title }}" class="admin-input w-full font-semibold" maxlength="120" required>
                    <textarea name="body" rows="5" class="admin-input w-full" required>{{ $reply->body }}</textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="admin-btn-ghost">Save</button>
                        <button type="submit" form="delete-reply-{{ $reply->id }}" class="admin-btn-danger" onclick="return confirm('Delete this saved reply?');">Delete</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.saved-replies.destroy', $reply) }}" id="delete-reply-{{ $reply->id }}">
                    @csrf
                    @method('DELETE')
                </form>
            </section>
        @endforeach

        <section class="admin-panel">
            <div class="admin-panel-toolbar compact"><h2 class="admin-dash-panel-title">New saved reply</h2></div>
            <form method="POST" action="{{ route('admin.saved-replies.store') }}" class="space-y-3">
                @csrf
                <input type="text" name="title" value="{{ old('title') }}" class="admin-input w-full" placeholder="Title, e.g. DNS records not propagated yet" maxlength="120" required>
                <textarea name="body" rows="5" class="admin-input w-full" placeholder="Reply text" required>{{ old('body') }}</textarea>
                <button type="submit" class="admin-btn-primary">Add</button>
            </form>
        </section>
    </div>
@endsection
