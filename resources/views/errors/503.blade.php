@extends('layouts.app')

@section('title', '503 — Service Unavailable')

@section('content')
<section class="flex items-center justify-center min-h-[70vh] px-6">
    <div class="text-center max-w-md">
        <p class="text-7xl font-bold text-rose">503</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">Under maintenance</h1>
        <p class="mt-3 text-gray-600 leading-relaxed">
            We're performing scheduled maintenance. We'll be back shortly — thanks for your patience.
        </p>
        <div class="mt-8">
            <button onclick="location.reload()" class="inline-flex items-center gap-2 rounded-lg bg-rose px-5 py-2.5 text-sm font-medium text-white shadow hover:bg-rose/90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Try again
            </button>
        </div>
    </div>
</section>
@endsection
