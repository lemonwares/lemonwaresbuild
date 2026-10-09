@extends('layouts.account')

@section('title', __('account.nav_subscriptions') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.subscriptions_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs :items="[__('account.nav_subscriptions') => null]" />
@endsection

@section('content')
    <x-account.table-card
        :title="__('account.nav_subscriptions')"
        :count="$totalCount"
        :paginator="$subscriptions"
        :filters-open="$search !== '' || $status !== ''"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('account.subscriptions.index') }}" class="acc-filter-form" role="search">
                <label class="sr-only" for="subscription-search">{{ __('account.search') }}</label>
                <input
                    id="subscription-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    class="acc-input"
                    placeholder="{{ __('account.search_subscriptions') }}"
                    autocomplete="off"
                >
                @if ($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <button type="submit" class="acc-btn">{{ __('account.search') }}</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('account.subscriptions.index') }}" class="acc-btn is-quiet">{{ __('account.clear_search') }}</a>
                @endif
            </form>
        </x-slot:filters>

        @if ($statusCounts->count() > 1)
            <div class="acc-tabs" role="tablist">
                <a href="{{ route('account.subscriptions.index', array_filter(['q' => $search])) }}" @class(['acc-tab', 'is-active' => $status === ''])>
                    {{ __('account.all') }} <span>{{ $totalCount }}</span>
                </a>
                @foreach ($statusCounts as $label => $count)
                    <a href="{{ route('account.subscriptions.index', array_filter(['q' => $search, 'status' => $label])) }}" @class(['acc-tab', 'is-active' => strcasecmp($status, $label) === 0])>
                        {{ $label }} <span>{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($subscriptions->isEmpty())
            <div class="acc-empty">
                @if ($totalCount === 0)
                    <p class="acc-empty-title">{{ __('account.subscriptions_empty') }}</p>
                    <p>{{ __('account.subscriptions_lede') }}</p>
                @else
                    <p class="acc-empty-title">{{ __('account.search_no_results') }}</p>
                    <p>{{ __('account.search_no_results_lede', ['q' => $search ?: $status]) }}</p>
                @endif
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.subscription') }}</th>
                            <th>{{ __('account.subscription_type') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.billing_cycle') }}</th>
                            <th>{{ __('account.next_due') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $sub)
                            <tr>
                                <td>
                                    <a href="{{ $sub['url'] }}" class="acc-link">{{ $sub['label'] }}</a>
                                    @if ($sub['domain'])
                                        <span class="acc-cell-sub">{{ $sub['domain'] }}</span>
                                    @endif
                                </td>
                                <td class="acc-cell-muted">{{ __('account.subscription_sources.' . $sub['source']) }}</td>
                                <td><x-account.status :value="$sub['status']" /></td>
                                <td class="acc-cell-muted">{{ $sub['billing_cycle'] ?: '—' }}</td>
                                <td class="acc-cell-muted">
                                    {{ $sub['next_due'] ? \Illuminate\Support\Carbon::parse($sub['next_due'])->timezone(config('app.timezone'))->format('d M Y') : '—' }}
                                </td>
                                <td class="is-actions">
                                    <div class="acc-row-actions">
                                        @if (! empty($sub['site_url']))
                                            <a href="{{ $sub['site_url'] }}" target="_blank" rel="noopener noreferrer" class="acc-btn is-sm is-quiet">{{ __('account.visit_site') }}</a>
                                        @endif
                                        @if (! empty($sub['cpanel_url']))
                                            <a href="{{ $sub['cpanel_url'] }}" class="acc-btn is-sm">{{ __('account.open_cpanel') }}</a>
                                        @endif
                                        @if (! empty($sub['renew_url']))
                                            <form method="POST" action="{{ $sub['renew_url'] }}" data-submit-form>
                                                @csrf
                                                <x-ui.submit-button :label="__('account.renew')" :loading="__('account.starting_payment')" class="acc-btn is-primary is-sm" />
                                            </form>
                                        @endif
                                        <a href="{{ $sub['url'] }}" class="acc-btn is-sm">{{ __('account.manage') }}</a>
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
