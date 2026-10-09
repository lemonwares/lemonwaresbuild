@extends('layouts.account')

@section('title', __('account.nav_invoices') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.invoices_lede'))

@section('breadcrumbs')
    <x-account.breadcrumbs :items="[__('account.nav_invoices') => null]" />
@endsection

@section('content')
    <x-account.table-card
        :title="__('account.nav_invoices')"
        :count="$totalCount"
        :paginator="$invoices"
        :filters-open="$search !== '' || $status !== ''"
    >
        <x-slot:filters>
            <form method="GET" action="{{ route('account.invoices.index') }}" class="acc-filter-form" role="search">
                <label class="sr-only" for="invoice-search">{{ __('account.search') }}</label>
                <input
                    id="invoice-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    class="acc-input"
                    placeholder="{{ __('account.search_invoices') }}"
                    autocomplete="off"
                >
                @if ($status !== '')
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <button type="submit" class="acc-btn">{{ __('account.search') }}</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('account.invoices.index') }}" class="acc-btn is-quiet">{{ __('account.clear_search') }}</a>
                @endif
            </form>
        </x-slot:filters>

        @if ($statusCounts->count() > 1)
            <div class="acc-tabs" role="tablist">
                <a href="{{ route('account.invoices.index', array_filter(['q' => $search])) }}" @class(['acc-tab', 'is-active' => $status === ''])>
                    {{ __('account.all') }} <span>{{ $totalCount }}</span>
                </a>
                @foreach ($statusCounts as $label => $count)
                    <a href="{{ route('account.invoices.index', array_filter(['q' => $search, 'status' => $label])) }}" @class(['acc-tab', 'is-active' => strcasecmp($status, $label) === 0])>
                        {{ $label }} <span>{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($invoices->isEmpty())
            <div class="acc-empty">
                @if ($totalCount === 0)
                    <p class="acc-empty-title">{{ __('account.invoices_empty') }}</p>
                    <p>{{ __('account.invoices_lede') }}</p>
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
                            <th>{{ __('account.reference') }}</th>
                            <th>{{ __('account.description') }}</th>
                            <th class="is-num">{{ __('account.amount') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.date') }}</th>
                            <th class="is-actions"><span class="sr-only">{{ __('account.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>
                                    @if ($invoice['url'])
                                        <a href="{{ $invoice['url'] }}" class="acc-link">{{ $invoice['reference'] }}</a>
                                    @else
                                        <span class="acc-link">{{ $invoice['reference'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="acc-cell-main">{{ $invoice['label'] }}</span>
                                    <span class="acc-cell-sub">{{ __('account.invoice_sources.' . $invoice['source']) }}</span>
                                </td>
                                <td class="is-num">{{ strtoupper($invoice['currency']) === 'NGN' ? '₦' : $invoice['currency'] . ' ' }}{{ number_format((float) $invoice['amount'], 2) }}</td>
                                <td><x-account.status :value="$invoice['status']" /></td>
                                <td class="acc-cell-muted">{{ $invoice['date']?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}</td>
                                <td class="is-actions">
                                    @if ($invoice['pay_url'])
                                        <a href="{{ $invoice['pay_url'] }}" class="acc-btn is-primary is-sm">{{ __('account.pay') }}</a>
                                    @elseif ($invoice['url'])
                                        <a href="{{ $invoice['url'] }}" class="acc-btn is-sm">{{ __('account.view') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-account.table-card>
@endsection
