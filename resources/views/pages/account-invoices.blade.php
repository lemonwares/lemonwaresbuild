@extends('layouts.account')

@section('title', __('account.nav_invoices') . ' — ' . config('site.short_name'))
@section('meta_description', __('account.invoices_lede'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_invoices')"
        :title="__('account.invoices_title')"
        :lede="__('account.invoices_lede')"
    />

    @if ($invoices->isEmpty())
        <div class="account-empty">
            <p class="account-empty-title">{{ __('account.invoices_empty') }}</p>
            <p class="account-empty-lede">{{ __('account.invoices_lede') }}</p>
        </div>
    @else
        <div class="account-list lg:hidden">
            @foreach ($invoices as $invoice)
                <div class="account-list-item">
                    <div class="account-list-copy">
                        <p class="account-list-title">{{ $invoice['label'] }}</p>
                        <p class="account-list-meta">
                            <span class="account-mono">{{ $invoice['reference'] }}</span>
                            · {{ strtoupper($invoice['source']) }}
                        </p>
                        <p class="account-list-meta">
                            {{ $invoice['currency'] }} {{ number_format((float) $invoice['amount'], 2) }}
                            · {{ $invoice['status'] }}
                            · {{ $invoice['date']?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}
                        </p>
                    </div>
                    <div class="account-list-side">
                        @if ($invoice['url'])
                            <a href="{{ $invoice['url'] }}" class="account-btn-ghost">{{ __('account.view') }}</a>
                        @endif
                        @if ($invoice['pay_url'])
                            <a href="{{ $invoice['pay_url'] }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('account.pay') }}</a>
                        @endif
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
                            <th>{{ __('account.description') }}</th>
                            <th>{{ __('account.amount') }}</th>
                            <th>{{ __('account.status_label') }}</th>
                            <th>{{ __('account.date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="account-mono">{{ $invoice['reference'] }}</td>
                                <td>
                                    <strong>{{ $invoice['label'] }}</strong>
                                    <div class="account-list-meta">{{ strtoupper($invoice['source']) }}</div>
                                </td>
                                <td>{{ $invoice['currency'] }} {{ number_format((float) $invoice['amount'], 2) }}</td>
                                <td><span class="account-pill is-muted">{{ $invoice['status'] }}</span></td>
                                <td>{{ $invoice['date']?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}</td>
                                <td>
                                    <div class="account-table-actions">
                                        @if ($invoice['url'])
                                            <a href="{{ $invoice['url'] }}" class="account-btn-ghost">{{ __('account.view') }}</a>
                                        @endif
                                        @if ($invoice['pay_url'])
                                            <a href="{{ $invoice['pay_url'] }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('account.pay') }}</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
