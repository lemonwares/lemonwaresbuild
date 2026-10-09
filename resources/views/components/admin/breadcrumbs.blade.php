@props([
    'items' => [],
    'back' => null,
])

@php
    $crumbs = collect($items)->filter(fn ($item) => filled($item['label'] ?? null))->values();
    $previous = url()->previous();
    $parent = $crumbs->slice(0, -1)->reverse()->first(fn ($item) => filled($item['href'] ?? null));
    $backUrl = $back
        ?? (str_starts_with($previous, url('/admin')) && $previous !== url()->current()
            ? $previous
            : ($parent['href'] ?? route('admin.dashboard')));
@endphp

<nav {{ $attributes->class('acc-crumbs admin-crumbs') }} aria-label="Breadcrumb">
    @unless (request()->routeIs('admin.dashboard'))
        <a href="{{ $backUrl }}" class="acc-crumbs-back" title="Go back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5" />
                <path d="m12 19-7-7 7-7" />
            </svg>
            <span>Back</span>
        </a>
        <span class="acc-crumbs-divider" aria-hidden="true"></span>
    @endunless
    @if ($crumbs->isEmpty())
        <span class="acc-crumbs-current" aria-current="page">Admin</span>
    @else
        <a href="{{ route('admin.dashboard') }}">Admin</a>
    @endif
    @foreach ($crumbs as $item)
        <span class="acc-crumbs-sep" aria-hidden="true">/</span>
        @if (! empty($item['href']) && ! $loop->last)
            <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
        @else
            <span class="acc-crumbs-current" aria-current="page">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
