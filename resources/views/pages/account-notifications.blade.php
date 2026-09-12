@extends('layouts.account')

@section('title', __('account.notifications_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.notifications_lede'))

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-label mb-2">{{ __('account.nav_notifications') }}</p>
            <h1 class="text-3xl font-bold tracking-tight text-black sm:text-4xl">{{ __('account.notifications_title') }}</h1>
            <p class="lede mt-2">{{ __('account.notifications_lede') }}</p>
            @if ($unreadCount > 0)
                <p class="mt-2 text-sm font-semibold text-rose">{{ __('account.notifications_unread', ['count' => $unreadCount]) }}</p>
            @endif
        </div>
        @if ($unreadCount > 0)
            <form method="POST" action="{{ route('account.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-ghost">{{ __('account.notifications_mark_all') }}</button>
            </form>
        @endif
    </div>

    <section class="rounded-3xl border border-border bg-white p-6 sm:p-8">
        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $data = is_array($notification->data) ? $notification->data : [];
                    $unread = $notification->read_at === null;
                @endphp
                <article @class([
                    'rounded-2xl border px-4 py-4',
                    'border-rose/25 bg-rose/5' => $unread,
                    'border-border bg-white' => ! $unread,
                ])>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-semibold text-black">{{ $data['title'] ?? __('account.notifications_title') }}</p>
                            <p class="mt-1 text-sm text-on-blush/70">{{ $data['body'] ?? '' }}</p>
                            <p class="mt-2 text-xs text-on-blush/50">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if (! empty($data['url']))
                                <a href="{{ $data['url'] }}" class="btn btn-primary">{{ __('account.notifications_open') }}</a>
                            @endif
                            @if ($unread)
                                <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost">{{ __('account.notifications_mark_read') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-border px-4 py-8 text-center text-sm text-on-blush/65">
                    {{ __('account.notifications_empty') }}
                </p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
@endsection
