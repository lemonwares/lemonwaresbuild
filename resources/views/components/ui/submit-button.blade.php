@props([
    'label',
    'loading' => null,
])

@php
    $loadingLabel = $loading ?? __('account.processing');
@endphp

<button type="submit" data-submit-button {{ $attributes->class('inline-flex items-center justify-center gap-2') }}>
    <span class="hidden size-4 animate-spin rounded-full border-2 border-current/30 border-t-current" data-submit-spinner aria-hidden="true"></span>
    <span data-submit-label>{{ $label }}</span>
    <span class="hidden" data-submit-loading>{{ $loadingLabel }}</span>
</button>
