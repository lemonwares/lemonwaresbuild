@props([
    'prefix' => '',
    'slides' => [],
])

@php
    $slideItems = collect($slides)->filter()->values();
    if ($slideItems->isEmpty()) {
        $slideItems = collect([__('site.home.hosting_eyebrow_slide')]);
    }
@endphp

<span {{ $attributes->class('hosting-eyebrow') }}>
    <span class="hosting-eyebrow-dot" aria-hidden="true"></span>
    <span class="hosting-eyebrow-prefix">{{ $prefix !== '' ? $prefix : __('site.home.hosting_eyebrow_prefix') }}</span>
    <span class="hosting-eyebrow-sep" aria-hidden="true">·</span>
    <span class="hosting-eyebrow-slide" aria-live="polite">
        <span class="hosting-eyebrow-slide-track" style="--slide-count: {{ max($slideItems->count(), 1) }}">
            @foreach ($slideItems as $slide)
                <span class="hosting-eyebrow-slide-item">{{ $slide }}</span>
            @endforeach
            @if ($slideItems->count() > 1)
                <span class="hosting-eyebrow-slide-item" aria-hidden="true">{{ $slideItems->first() }}</span>
            @endif
        </span>
    </span>
</span>
