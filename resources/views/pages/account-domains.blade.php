@extends('layouts.account')

@section('title', __('account.nav_domains') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.domains_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_domains')"
        :title="__('account.domains_title')"
        :lede="__('account.domains_lede')"
        :back-href="route('account.products.index')"
        :back-label="__('account.nav_products')"
    />

    <div class="account-page-stack">
        @if ($totalCount === 0)
            <div class="account-empty">
                <p class="account-empty-title">{{ __('account.domains_empty') }}</p>
                <p class="account-empty-lede">{{ __('account.domains_lede') }}</p>
            </div>
        @else
            <form method="GET" action="{{ route('account.domains.index') }}" class="account-filter-bar" role="search">
                <label class="sr-only" for="domain-search">{{ __('account.search') }}</label>
                <input
                    id="domain-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    class="account-input"
                    placeholder="{{ __('account.search_domains') }}"
                    autocomplete="off"
                >
                <button type="submit" class="account-btn-ghost">{{ __('account.search') }}</button>
                @if ($search !== '')
                    <a href="{{ route('account.domains.index') }}" class="account-btn-ghost">{{ __('account.clear_search') }}</a>
                @endif
            </form>

            @if ($domains->isEmpty())
                <div class="account-empty">
                    <p class="account-empty-title">{{ __('account.search_no_results') }}</p>
                    <p class="account-empty-lede">{{ __('account.search_no_results_lede', ['q' => $search]) }}</p>
                </div>
            @else
                <div class="account-list">
                    @foreach ($domains as $domain)
                        <div class="account-list-item">
                            <div class="account-list-copy">
                                <p class="account-list-title">{{ $domain['domain'] }}</p>
                                <p class="account-list-meta">
                                    {{ $domain['label'] }}
                                    @if (! empty($domain['reg_period']))
                                        · {{ $domain['reg_period'] }} {{ __('account.years') }}
                                    @endif
                                    @if (! empty($domain['expiry']))
                                        · {{ __('account.next_due') }} {{ $domain['expiry']->timezone(config('app.timezone'))->format('d M Y') }}
                                    @endif
                                </p>
                            </div>
                            <div class="account-list-side account-table-actions">
                                <span class="account-pill is-muted">{{ $domain['status'] ?: '—' }}</span>
                                @if (! empty($domain['site_url']))
                                    <a href="{{ $domain['site_url'] }}" target="_blank" rel="noopener noreferrer" class="account-btn-ghost">{{ __('account.visit_site') }}</a>
                                @endif
                                @if (! empty($domain['url']))
                                    <a href="{{ $domain['url'] }}" class="account-btn-ghost">{{ __('account.manage') }}</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($domains->hasPages())
                    <div class="account-pagination">{{ $domains->links() }}</div>
                @endif
            @endif
        @endif
    </div>
@endsection
