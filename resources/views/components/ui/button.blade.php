@props([
    'href' => '#',
    'variant' => 'primary',
])

@php
    $classes = match ($variant) {
        'ghost' => 'btn btn-ghost',
        default => 'btn btn-primary',
    };
@endphp

<a href="{{ $href }}" {{ $attributes->class($classes) }}>
    {{ $slot }}
</a>
