@props([
    'key' => 'email_feedback',
    'showStatus' => false,
])

@php
    $feedback = session($key);
@endphp

@if ($feedback)
    <p @class([
        'mb-5 rounded-xl px-4 py-3 text-sm',
        'border border-emerald-200 bg-emerald-50 text-emerald-800' => (($feedback['type'] ?? '') === 'success'),
        'border border-sky-200 bg-sky-50 text-sky-800' => (($feedback['type'] ?? '') === 'info'),
        'border border-rose/20 bg-rose/5 text-rose' => (($feedback['type'] ?? '') === 'error'),
    ])>{{ $feedback['message'] ?? '' }}</p>
@elseif ($showStatus && session('status'))
    <p class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
@endif
