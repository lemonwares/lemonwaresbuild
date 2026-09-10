@props(['key' => 'email_feedback', 'showStatus' => false])

@php $feedback = session($key); @endphp

@if ($feedback)
    <div @class([
        'mb-5 flex items-start gap-2.5 rounded-xl border px-4 py-3 text-sm',
        'border-emerald-200 bg-emerald-50 text-emerald-800' => ($feedback['type'] ?? '') === 'success',
        'border-sky-200 bg-sky-50 text-sky-800'             => ($feedback['type'] ?? '') === 'info',
        'border-red/20 bg-red/4 text-red'                   => ($feedback['type'] ?? '') === 'error',
    ]) role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
             stroke-linejoin="round" aria-hidden="true">
            @if (($feedback['type'] ?? '') === 'error')
                <circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/>
                <line x1="12" x2="12.01" y1="16" y2="16"/>
            @else
                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            @endif
        </svg>
        <span>{{ $feedback['message'] ?? '' }}</span>
    </div>
@elseif ($showStatus && session('status'))
    <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50
                px-4 py-3 text-sm text-emerald-800" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
             stroke-linejoin="round" aria-hidden="true">
            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        <span>{{ session('status') }}</span>
    </div>
@endif
