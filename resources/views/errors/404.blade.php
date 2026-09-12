@extends('layouts.app')

@section('title', '404 — Page Not Found')

@section('content')
<section class="flex items-center justify-center min-h-[70vh] px-6">
    <div class="text-center max-w-md">
        <p class="text-7xl font-bold text-rose">404</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">Page not found</h1>
        <p class="mt-3 text-gray-600 leading-relaxed">
            Sorry, we couldn't find the page you're looking for. It may have been moved or no longer exists.
        </p>
        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-lg bg-rose px-5 py-2.5 text-sm font-medium text-white shadow hover:bg-rose/90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                Go home
            </a>
            <button onclick="history.back()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Go back
            </button>
        </div>
    </div>
</section>
@endsection
