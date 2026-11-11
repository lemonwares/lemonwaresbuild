@extends('layouts.account')

@section('title', __('account.nav_support') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.support_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs :items="[__('account.nav_support') => null]" />
@endsection

@section('content')
    <x-account.table-card
        :title="__('account.nav_support')"
        :count="$hasBillingAccount ? $tickets->total() : null"
        :paginator="$hasBillingAccount ? $tickets : null"
    >
        @if ($hasBillingAccount)
            <x-slot:tools>
                <a href="{{ route('account.support.create') }}" class="acc-btn is-primary">{{ __('account.support_new') }}</a>
            </x-slot:tools>
        @endif

        @if (! $hasBillingAccount)
            <div class="acc-empty">
                <p class="acc-empty-title">{{ __('account.support_no_account') }}</p>
                <p>{{ __('account.support_no_account_lede', ['email' => config('site.email')]) }}</p>
            </div>
        @elseif ($tickets->isEmpty())
            <div class="acc-empty">
                <p class="acc-empty-title">{{ __('account.support_empty') }}</p>
                <p>{{ __('account.support_lede') }}</p>
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.reference') }}</th>
                            <th>{{ __('account.support_subject') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.support_last_reply') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                            <tr>
                                <td><a href="{{ route('account.support.show', $ticket) }}" class="acc-link">#{{ $ticket->tid }}</a></td>
                                <td><span class="acc-cell-main">{{ $ticket->subject }}</span></td>
                                <td><x-account.status :value="$ticket->status" /></td>
                                <td class="acc-cell-muted">{{ $ticket->last_reply_at?->diffForHumans() ?? '—' }}</td>
                                <td class="is-actions">
                                    <a href="{{ route('account.support.show', $ticket) }}" class="acc-btn is-sm">{{ __('account.view') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-account.table-card>
@endsection
