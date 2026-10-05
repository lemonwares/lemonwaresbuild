@extends('layouts.account')

@section('title', __('account.invoice') . ' #' . $invoiceId . ' — ' . config('site.short_name'))

@section('content')
    <x-account.page-header
        :kicker="__('account.nav_invoices')"
        :title="__('account.invoice') . ' #' . $invoiceId"
        :lede="($invoice['status'] ?? '') . ' · ' . ($invoice['date'] ?? '')"
        :back-href="route('account.invoices.index')"
        :back-label="__('account.nav_invoices')"
    >
        @if ($payUrl)
            <x-slot:actions>
                <a href="{{ $payUrl }}" target="_blank" rel="noopener noreferrer" class="account-btn-primary">{{ __('account.pay') }}</a>
            </x-slot:actions>
        @endif
    </x-account.page-header>

    <div class="account-page-stack">
        <section class="account-panel">
            <h2 class="account-panel-title">{{ __('account.invoice_summary') }}</h2>
            <dl class="account-dl mt-5">
                <div>
                    <dt>{{ __('account.status_label') }}</dt>
                    <dd><span class="account-pill is-muted">{{ $invoice['status'] ?? '—' }}</span></dd>
                </div>
                <div>
                    <dt>{{ __('account.date') }}</dt>
                    <dd>{{ $invoice['date'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_due') }}</dt>
                    <dd>{{ $invoice['duedate'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_paid_on') }}</dt>
                    <dd>{{ ! empty($invoice['datepaid']) && $invoice['datepaid'] !== '0000-00-00 00:00:00' ? $invoice['datepaid'] : '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_payment_method') }}</dt>
                    <dd>{{ $invoice['paymentmethod'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_subtotal') }}</dt>
                    <dd>{{ $invoice['subtotal'] ?? '0.00' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_tax') }}</dt>
                    <dd>{{ $invoice['tax'] ?? '0.00' }}@if (! empty($invoice['tax2']) && (float) $invoice['tax2'] > 0) + {{ $invoice['tax2'] }}@endif</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_credit') }}</dt>
                    <dd>{{ $invoice['credit'] ?? '0.00' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.amount') }}</dt>
                    <dd style="color:var(--color-rose);font-weight:700">{{ $invoice['total'] ?? '0.00' }}</dd>
                </div>
                <div>
                    <dt>{{ __('account.invoice_balance') }}</dt>
                    <dd>{{ $invoice['balance'] ?? '0.00' }}</dd>
                </div>
            </dl>
        </section>

        <section class="account-panel account-panel-flush">
            <div class="account-panel-toolbar">
                <div>
                    <h2 class="account-panel-title">{{ __('account.invoice_items') }}</h2>
                    <p class="account-panel-lede">{{ __('account.invoice_items_lede') }}</p>
                </div>
            </div>
            @if (count($items) === 0)
                <p class="account-table-empty">{{ __('account.invoice_items_empty') }}</p>
            @else
                <div class="account-table-wrap">
                    <table class="account-table" style="min-width:28rem">
                        <thead>
                            <tr>
                                <th>{{ __('account.description') }}</th>
                                <th>{{ __('account.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item['description'] ?? '—' }}</td>
                                    <td class="account-mono">{{ $item['amount'] ?? '0.00' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if (count($transactions) > 0)
            <section class="account-panel account-panel-flush">
                <div class="account-panel-toolbar">
                    <div>
                        <h2 class="account-panel-title">{{ __('account.invoice_transactions') }}</h2>
                    </div>
                </div>
                <div class="account-table-wrap">
                    <table class="account-table" style="min-width:32rem">
                        <thead>
                            <tr>
                                <th>{{ __('account.date') }}</th>
                                <th>{{ __('account.invoice_gateway') }}</th>
                                <th>{{ __('account.reference') }}</th>
                                <th>{{ __('account.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $txn)
                                <tr>
                                    <td>{{ $txn['date'] ?? '—' }}</td>
                                    <td>{{ $txn['gateway'] ?? '—' }}</td>
                                    <td class="account-mono">{{ $txn['transid'] ?? '—' }}</td>
                                    <td class="account-mono">{{ $txn['amountin'] ?? $txn['amount'] ?? '0.00' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endsection
