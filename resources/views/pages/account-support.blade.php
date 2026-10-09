@extends('layouts.account')

@section('title', __('account.nav_support') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.support_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_support')"
        :title="__('account.support_title')"
        :lede="__('account.support_lede')"
    >
        @if ($hasBillingAccount)
            <x-slot:actions>
                <a href="{{ route('account.support.create') }}" class="account-btn-primary">{{ __('account.support_new') }}</a>
            </x-slot:actions>
        @endif
    </x-account.page-header>

    @if (! $hasBillingAccount)
        <div class="account-empty">
            <p class="account-empty-title">{{ __('account.support_no_account') }}</p>
            <p class="account-empty-lede">{{ __('account.support_no_account_lede', ['email' => config('site.email')]) }}</p>
        </div>
    @elseif ($tickets->isEmpty())
        <div class="account-empty">
            <p class="account-empty-title">{{ __('account.support_empty') }}</p>
            <p class="account-empty-lede">{{ __('account.support_lede') }}</p>
        </div>
    @else
        <div class="account-list lg:hidden">
            @foreach ($tickets as $ticket)
                <div class="account-list-item">
                    <div class="account-list-copy">
                        <p class="account-list-title">{{ $ticket->subject }}</p>
                        <p class="account-list-meta">
                            <span class="account-mono">#{{ $ticket->tid }}</span>
                            · {{ $ticket->status }}
                            · {{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}
                        </p>
                    </div>
                    <div class="account-list-side">
                        <a href="{{ route('account.support.show', $ticket) }}" class="account-btn-ghost">{{ __('account.view') }}</a>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="account-panel account-panel-flush hidden lg:block">
            <div class="account-table-wrap">
                <table class="account-table" style="min-width:42rem">
                    <thead>
                        <tr>
                            <th>{{ __('account.reference') }}</th>
                            <th>{{ __('account.support_subject') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.support_last_reply') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td class="account-mono">#{{ $ticket->tid }}</td>
                                <td><strong>{{ $ticket->subject }}</strong></td>
                                <td><span class="account-pill is-muted">{{ $ticket->status }}</span></td>
                                <td>{{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}</td>
                                <td>
                                    <div class="account-table-actions">
                                        <a href="{{ route('account.support.show', $ticket) }}" class="account-btn-ghost">{{ __('account.view') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if ($tickets->hasPages())
            <div class="account-pagination">{{ $tickets->links() }}</div>
        @endif
    @endif
@endsection
