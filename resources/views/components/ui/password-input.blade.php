@props([
    'id' => 'password',
    'name' => 'password',
    'autocomplete' => 'current-password',
    'placeholder' => '••••••••',
    'required' => true,
    'value' => null,
])

@php
    $showLabel = __('account.password_show');
    $hideLabel = __('account.password_hide');
@endphp

<div
    class="auth-password-field"
    data-password-toggle
    data-show-label="{{ $showLabel }}"
    data-hide-label="{{ $hideLabel }}"
>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="password"
        @if ($required) required @endif
        autocomplete="{{ $autocomplete }}"
        @if ($value !== null) value="{{ $value }}" @endif
        {{ $attributes->class(['auth-input auth-input-password']) }}
        placeholder="{{ $placeholder }}"
        data-password-input
    >
    <button
        type="button"
        class="auth-password-toggle"
        data-password-toggle-btn
        aria-label="{{ $showLabel }}"
        aria-controls="{{ $id }}"
        aria-pressed="false"
    >
        <x-ui.icons.eye class="size-4" data-password-icon-show />
        <x-ui.icons.eye-off class="size-4 hidden" data-password-icon-hide />
    </button>
</div>
