@props(['value' => null, 'label' => null])
@php
    $key = strtolower((string) ($value ?: 'pending'));
    $tone = match (true) {
        in_array($key, ['paid', 'provisioned', 'fulfilled', 'submitted', 'successful', 'completed', 'active', 'resolved', 'closed', 'synced', 'payment_synced', 'checkout_synced', 'running', 'ok'], true) => 'ok',
        in_array($key, ['payment_failed', 'sync_failed', 'failed', 'cancelled', 'rejected', 'suspended', 'deactivated', 'expired', 'terminated', 'amount_mismatch', 'invalid'], true) => 'bad',
        in_array($key, ['refunded', 'partially_refunded'], true) => 'muted',
        default => 'wait',
    };
@endphp
<span {{ $attributes->class(['admin-status', 'is-'.$tone]) }}>{{ $label ?? str_replace('_', ' ', $key) }}</span>
