@props([
    'variant' => 'trusted',
])

@php
    $variant = in_array($variant, ['trusted', 'price', 'support'], true) ? $variant : 'trusted';
@endphp

<svg
    {{ $attributes->class(['domain-svg-benefit']) }}
    viewBox="0 0 120 100"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
>
    <ellipse cx="60" cy="72" rx="38" ry="14" fill="color-mix(in srgb, var(--color-rose) 12%, transparent)" />
    <circle class="domain-svg-float" cx="60" cy="48" r="28" stroke="currentColor" stroke-width="2" fill="color-mix(in srgb, currentColor 6%, #fff)" />
    <path d="M42 48a18 18 0 0 1 36 0" stroke="var(--color-rose)" stroke-width="2" fill="none" />
    <path d="M60 30v36M42 48h36" stroke="currentColor" stroke-width="1.5" opacity="0.35" />

    @if ($variant === 'trusted')
        <g class="domain-svg-float-delay">
            <circle cx="40" cy="70" r="10" fill="#fff" stroke="var(--color-rose)" stroke-width="2" />
            <circle cx="60" cy="66" r="12" fill="var(--color-rose)" />
            <circle cx="80" cy="70" r="10" fill="#fff" stroke="var(--color-rose)" stroke-width="2" />
            <path d="M60 62v8M56 66h8" stroke="#fff" stroke-width="2" stroke-linecap="round" />
        </g>
    @elseif ($variant === 'price')
        <g class="domain-svg-float-delay">
            <rect x="34" y="58" width="16" height="22" rx="3" fill="var(--color-rose)" />
            <rect x="52" y="50" width="16" height="30" rx="3" fill="color-mix(in srgb, var(--color-rose) 75%, #000)" />
            <rect x="70" y="54" width="16" height="26" rx="3" fill="var(--color-rose)" />
        </g>
    @else
        <g class="domain-svg-float-delay">
            <path d="M60 40l18 10v16c0 12-12 20-18 22-6-2-18-10-18-22V50z" fill="var(--color-rose)" />
            <path d="M52 58l6 6 12-12" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </g>
    @endif
</svg>
