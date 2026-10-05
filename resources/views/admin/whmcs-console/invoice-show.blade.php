@extends('layouts.admin')

@section('title', 'WHMCS Invoice #'.$invoiceId.' — ' . config('site.short_name'))
@section('hide_auto_breadcrumbs', true)

@section('content')
    <x-admin.page-header
        title="Invoice #{{ $invoiceId }}"
        lede="Status: {{ $invoice['status'] ?? '—' }} · Client #{{ $invoice['userid'] ?? '—' }}"
        :back-href="route('admin.whmcs-console.invoices')"
        back-label="All invoices"
        :breadcrumbs="[['label' => 'WHMCS Console'], ['label' => 'Invoices'], ['label' => '#'.$invoiceId]]"
        class="mb-5"
    />

    @include('admin.whmcs-console._tabs')

    @if (session('status'))
        <p class="mb-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 text-sm font-semibold text-rose">{{ session('error') }}</p>
    @endif

    <section class="admin-panel">
        <dl class="admin-edit-grid">
            <div class="admin-field">
                <span class="admin-muted">Date</span>
                <p>{{ $invoice['date'] ?? '—' }}</p>
            </div>
            <div class="admin-field">
                <span class="admin-muted">Due</span>
                <p>{{ $invoice['duedate'] ?? '—' }}</p>
            </div>
            <div class="admin-field">
                <span class="admin-muted">Subtotal</span>
                <p>{{ $invoice['subtotal'] ?? '—' }}</p>
            </div>
            <div class="admin-field">
                <span class="admin-muted">Total</span>
                <p class="font-semibold">{{ $invoice['total'] ?? '—' }}</p>
            </div>
            <div class="admin-field">
                <span class="admin-muted">Balance</span>
                <p>{{ $invoice['balance'] ?? '—' }}</p>
            </div>
            <div class="admin-field">
                <span class="admin-muted">Payment method</span>
                <p>{{ $invoice['paymentmethod'] ?? '—' }}</p>
            </div>
        </dl>

        @if (($invoice['status'] ?? '') !== 'Paid')
            <form method="POST" action="{{ route('admin.whmcs-console.invoices.mark-paid', $invoiceId) }}" class="mt-6" data-confirm data-confirm-title="Record payment?" data-confirm-body="Adds a payment for the invoice total via the WHMCS API.">
                @csrf
                <button type="submit" class="admin-btn-primary">Record payment / mark paid</button>
            </form>
        @endif
    </section>
@endsection
