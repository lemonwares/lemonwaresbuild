@props(['value'])

@php
    $key = strtolower(trim((string) $value));
    $tone = match (true) {
        in_array($key, ['paid', 'active', 'completed', 'approved', 'successful', 'answered'], true) => 'is-success',
        in_array($key, ['unpaid', 'pending', 'open', 'awaiting_payment', 'customer-reply', 'in progress', 'grace'], true) => 'is-warning',
        in_array($key, ['overdue', 'suspended', 'failed', 'payment_failed', 'terminated', 'fraud', 'redemption', 'expired'], true) => 'is-danger',
        default => 'is-neutral',
    };
@endphp

<span {{ $attributes->class(['acc-status', $tone]) }}>{{ $value !== '' && $value !== null ? $value : '—' }}</span>
