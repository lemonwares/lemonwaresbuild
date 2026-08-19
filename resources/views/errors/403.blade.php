@extends('layouts.app')

@section('title', '403 — Forbidden')

@section('content')
<section class="flex items-center justify-center min-h-[70vh] px-6">
    <div class="text-center max-w-md">
        <p class="text-7xl font-bold text-rose">403</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">Access denied</h1>
        <p class="mt-3 text-gray-600 leading-relaxed">
            You don't have permission to access this page.
        </p>
        <div class="mt-8 flex items-center justify-center gap-4">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-lg bg-rose px-5 py-2.5 text-sm font-medium text-white shadow hover:bg-rose/90 transition">
                Go home
            </a>
            <button onclick="history.back()" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Go back
            </button>
        </div>
    </div>
</section>
@endsection
