@extends('layouts.account')

@section('title', __('account.notifications_title') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.notifications_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs :items="[__('account.nav_notifications') => null]" />
@endsection

@section('content')
    <x-account.table-card
        :title="__('account.nav_notifications')"
        :count="$notifications->total()"
        :paginator="$notifications"
    >
        @if ($unreadCount > 0)
            <x-slot:tools>
                <span class="acc-count">{{ __('account.notifications_unread', ['count' => $unreadCount]) }}</span>
                <form method="POST" action="{{ route('account.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="acc-btn">{{ __('account.notifications_mark_all') }}</button>
                </form>
            </x-slot:tools>
        @endif

        @if ($notifications->isEmpty())
            <div class="acc-empty">
                <p class="acc-empty-title">{{ __('account.notifications_empty') }}</p>
                <p>{{ __('account.notifications_lede') }}</p>
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.notifications_title') }}</th>
                            <th>{{ __('account.date') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifications as $notification)
                            @php
                                $data = is_array($notification->data) ? $notification->data : [];
                                $unread = $notification->read_at === null;
                            @endphp
                            <tr @class(['is-unread' => $unread])>
                                <td>
                                    <span class="acc-cell-main">
                                        @if ($unread)
                                            <span class="acc-dot" aria-hidden="true"></span>
                                        @endif
                                        {{ $data['title'] ?? __('account.notifications_title') }}
                                    </span>
                                    @if (! empty($data['body']))
                                        <span class="acc-cell-sub">{{ $data['body'] }}</span>
                                    @endif
                                </td>
                                <td class="acc-cell-muted">{{ $notification->created_at?->diffForHumans() }}</td>
                                <td class="is-actions">
                                    <div class="acc-row-actions">
                                        @if ($unread)
                                            <form method="POST" action="{{ route('account.notifications.read', $notification->id) }}">
                                                @csrf
                                                <button type="submit" class="acc-btn is-sm is-quiet">{{ __('account.notifications_mark_read') }}</button>
                                            </form>
                                        @endif
                                        @if (! empty($data['url']))
                                            <a href="{{ $data['url'] }}" class="acc-btn is-sm">{{ __('account.notifications_open') }}</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-account.table-card>
@endsection
