@props([
    'alt' => null,
])

@php
    $alt ??= config('site.name');
@endphp

<a href="{{ url('/') }}" {{ $attributes->class('site-logo') }}>
    <img
        src="{{ asset('lemonwareslogo.webp') }}"
        alt="{{ $alt }}"
        width="220"
        height="56"
    >
</a>
