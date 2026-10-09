@extends('layouts.account')

@section('title', __('account.nav_domains') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.domains_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs
        :items="[__('account.nav_products') => route('account.products.index'), __('account.nav_domains') => null]"
        :back="route('account.products.index')"
    />
@endsection

@section('content')
    <x-account.table-card
        :title="__('account.nav_domains')"
        :count="$totalCount"
        :paginator="$domains"
        :filters-open="$search !== ''"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('account.domains.index') }}" class="acc-filter-form" role="search">
                <label class="sr-only" for="domain-search">{{ __('account.search') }}</label>
                <input
                    id="domain-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    class="acc-input"
                    placeholder="{{ __('account.search_domains') }}"
                    autocomplete="off"
                >
                <button type="submit" class="acc-btn">{{ __('account.search') }}</button>
                @if ($search !== '')
                    <a href="{{ route('account.domains.index') }}" class="acc-btn is-quiet">{{ __('account.clear_search') }}</a>
                @endif
            </form>
        </x-slot:filters>

        @if ($domains->isEmpty())
            <div class="acc-empty">
                @if ($totalCount === 0)
                    <p class="acc-empty-title">{{ __('account.domains_empty') }}</p>
                    <p>{{ __('account.domains_lede') }}</p>
                @else
                    <p class="acc-empty-title">{{ __('account.search_no_results') }}</p>
                    <p>{{ __('account.search_no_results_lede', ['q' => $search]) }}</p>
                @endif
            </div>
        @else
            <div class="acc-table-wrap">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>{{ __('account.stat_domain') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.domain_term') }}</th>
                            <th>{{ __('account.next_due') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($domains as $domain)
                            <tr>
                                <td>
                                    @if (! empty($domain['url']))
                                        <a href="{{ $domain['url'] }}" class="acc-link">{{ $domain['domain'] }}</a>
                                    @else
                                        <span class="acc-cell-main">{{ $domain['domain'] }}</span>
                                    @endif
                                    <span class="acc-cell-sub">{{ $domain['label'] }}</span>
                                </td>
                                <td><x-account.status :value="$domain['status']" /></td>
                                <td class="acc-cell-muted">{{ ! empty($domain['reg_period']) ? $domain['reg_period'] . ' ' . __('account.years') : '—' }}</td>
                                <td class="acc-cell-muted">{{ ! empty($domain['expiry']) ? $domain['expiry']->timezone(config('app.timezone'))->format('d M Y') : '—' }}</td>
                                <td class="is-actions">
                                    <div class="acc-row-actions">
                                        @if (! empty($domain['site_url']))
                                            <a href="{{ $domain['site_url'] }}" target="_blank" rel="noopener noreferrer" class="acc-btn is-sm is-quiet">{{ __('account.visit_site') }}</a>
                                        @endif
                                        @if (! empty($domain['url']))
                                            <a href="{{ $domain['url'] }}" class="acc-btn is-sm">{{ __('account.manage') }}</a>
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
