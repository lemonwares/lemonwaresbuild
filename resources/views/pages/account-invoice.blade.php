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

    <section class="account-panel">
        <dl class="account-dl">
            <div>
                <dt>{{ __('account.amount') }}</dt>
                <dd>{{ $invoice['total'] ?? '0.00' }}</dd>
            </div>
            <div>
                <dt>{{ __('account.status_label') }}</dt>
                <dd>{{ $invoice['status'] ?? '—' }}</dd>
            </div>
        </dl>
    </section>
@endsection
