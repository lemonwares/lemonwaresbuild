@extends('layouts.account')

@section('title', __('account.notifications_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.notifications_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_notifications')"
        :title="__('account.notifications_title')"
        :lede="__('account.notifications_lede')"
    >
        @if ($unreadCount > 0)
            <x-slot:actions>
                <form method="POST" action="{{ route('account.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="account-btn-ghost">{{ __('account.notifications_mark_all') }}</button>
                </form>
            </x-slot:actions>
        @endif
    </x-account.page-header>

    @if ($unreadCount > 0)
        <p class="mb-4 text-sm font-semibold text-rose">{{ __('account.notifications_unread', ['count' => $unreadCount]) }}</p>
    @endif

    <section class="account-panel">
        @if ($notifications->isEmpty())
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.notifications_empty') }}</p>
            </div>
        @else
            <div class="account-list">
                @foreach ($notifications as $notification)
                    @php
                        $data = is_array($notification->data) ? $notification->data : [];
                        $unread = $notification->read_at === null;
                    @endphp
                    <article @class([
                        'account-list-item',
                        'border-rose/25 bg-rose/5' => $unread,
                    ])>
                        <div class="account-list-copy">
                            <p class="account-list-title">
                                @if ($unread)
                                    <span class="account-pill">New</span>
                                @endif
                                <span>{{ $data['title'] ?? __('account.notifications_title') }}</span>
                            </p>
                            <p class="account-list-meta">{{ $data['body'] ?? '' }}</p>
                            <p class="account-list-meta">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                        <div class="account-list-side">
                            @if (! empty($data['url']))
                                <a href="{{ $data['url'] }}" class="account-btn-primary">{{ __('account.notifications_open') }}</a>
                            @endif
                            @if ($unread)
                                <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="account-btn-ghost">{{ __('account.notifications_mark_read') }}</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if ($notifications->hasPages())
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
@endsection
