@props([
    'items' => [],
])

@php
    $crumbs = collect($items)->filter(fn ($item) => filled($item['label'] ?? null))->values();
@endphp

@if ($crumbs->isNotEmpty())
    <nav {{ $attributes->class('admin-breadcrumbs') }} aria-label="Breadcrumb">
        <ol class="admin-breadcrumbs-list">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="admin-breadcrumbs-link">Admin</a>
            </li>
            @foreach ($crumbs as $index => $item)
                <li class="admin-breadcrumbs-sep" aria-hidden="true">/</li>
                <li>
                    @if (! empty($item['href']) && ! $loop->last)
                        <a href="{{ $item['href'] }}" class="admin-breadcrumbs-link">{{ $item['label'] }}</a>
                    @else
                        <span class="admin-breadcrumbs-current" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
