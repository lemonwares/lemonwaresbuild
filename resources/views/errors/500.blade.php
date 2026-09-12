@extends('layouts.app')

@section('title', '500 — Server Error')

@section('content')
<section class="flex items-center justify-center min-h-[70vh] px-6">
    <div class="text-center max-w-lg">
        <p class="text-7xl font-bold text-rose">500</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">Something went wrong</h1>
        <p class="mt-3 text-gray-600 leading-relaxed">
            An unexpected error occurred. Our team has been notified. Please try again in a moment.
        </p>

        @if(app()->hasDebugModeEnabled() && isset($exception))
        <div class="mt-6 relative rounded-lg border border-red-200 bg-red-50 p-4 text-left">
            <p class="text-xs font-medium text-red-800 mb-1">Error details</p>
            <pre id="error-message" class="text-xs text-red-700 whitespace-pre-wrap break-words max-h-32 overflow-y-auto">{{ $exception->getMessage() }}</pre>
            <button
                onclick="copyError()"
                id="copy-btn"
                class="absolute top-3 right-3 inline-flex items-center gap-1 rounded border border-red-200 bg-white px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                <span id="copy-label">Copy</span>
            </button>
        </div>
        @endif

        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-lg bg-rose px-5 py-2.5 text-sm font-medium text-white shadow hover:bg-rose/90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                Go home
            </a>
            <button onclick="location.reload()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Try again
            </button>
        </div>
    </div>
</section>

<script>
    function copyError() {
        const text = document.getElementById('error-message').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const label = document.getElementById('copy-label');
            label.textContent = 'Copied!';
            setTimeout(() => { label.textContent = 'Copy'; }, 2000);
        });
    }
</script>
@endsection
