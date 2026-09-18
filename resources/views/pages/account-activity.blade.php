@extends('layouts.account')

@section('title', __('account.nav_activity') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.activity_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_activity')"
        :title="__('account.activity_title')"
        :lede="__('account.activity_lede')"
    />

    @if ($activities->isEmpty())
        <div class="account-empty">
            <p class="account-empty-title">{{ __('account.activity_empty') }}</p>
            <p class="account-empty-lede">{{ __('account.activity_lede') }}</p>
        </div>
    @else
        <section class="account-panel">
            <div class="account-list" style="gap:1.15rem">
                @foreach ($activities as $activity)
                    <article class="account-activity-item">
                        <span class="account-activity-dot" aria-hidden="true"></span>
                        <div class="account-list-side" style="margin-bottom:0.35rem;justify-content:flex-start">
                            <span class="account-pill">{{ $activity->actor_type }}</span>
                            <span class="account-list-meta">{{ $activity->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</span>
                        </div>
                        <h2 class="account-list-title">{{ $activity->title }}</h2>
                        @if ($activity->body)
                            <p class="account-list-meta">{{ $activity->body }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    @endif
@endsection
