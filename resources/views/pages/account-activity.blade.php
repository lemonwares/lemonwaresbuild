@extends('layouts.account')

@section('title', __('account.nav_activity') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.activity_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs :items="[__('account.nav_activity') => null]" />
@endsection

@section('content')
    <x-account.table-card
        :title="__('account.nav_activity')"
        :count="$activities->total()"
        :paginator="$activities"
    >
        @if ($activities->isEmpty())
            <div class="acc-empty">
                <p class="acc-empty-title">{{ __('account.activity_empty') }}</p>
                <p>{{ __('account.activity_lede') }}</p>
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.activity_event') }}</th>
                            <th>{{ __('account.activity_by') }}</th>
                            <th>{{ __('account.date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activities as $activity)
                            <tr>
                                <td>
                                    <span class="acc-cell-main">{{ $activity->title }}</span>
                                    @if ($activity->body)
                                        <span class="acc-cell-sub">{{ $activity->body }}</span>
                                    @endif
                                </td>
                                <td><span class="acc-status is-neutral">{{ ucfirst((string) $activity->actor_type) }}</span></td>
                                <td class="acc-cell-muted">{{ $activity->created_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-account.table-card>
@endsection
